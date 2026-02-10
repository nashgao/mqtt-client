<?php

declare(strict_types=1);

namespace Nashgao\MQTT;

use Hyperf\Context\Context;
use Hyperf\Coroutine\Coroutine;
use Nashgao\MQTT\Constants\MQTTConstants;
use Nashgao\MQTT\Exception\InvalidMethodException;
use Nashgao\MQTT\Exception\InvalidMQTTConnectionException;
use Nashgao\MQTT\Metrics\ConnectionMetrics;
use Nashgao\MQTT\Metrics\PerformanceMetrics;
use Nashgao\MQTT\Metrics\PublishMetrics;
use Nashgao\MQTT\Metrics\SubscriptionMetrics;
use Nashgao\MQTT\Metrics\ValidationMetrics;
use Nashgao\MQTT\Pool\PoolFactory;
use Nashgao\MQTT\Utils\ErrorHandler;
use Nashgao\MQTT\Utils\HealthChecker;

/**
 * Enhanced MQTT Client with comprehensive metrics tracking.
 *
 * @method void subscribe(array $topics, array $properties = [])
 * @method void unSubscribe(array $topics, array $properties = [])
 * @method void publish(string $topic, string $message, int $qos = 0, int $dup = 0, int $retain = 0, array $properties = [])
 * @method void multiSub(array $topics, array $properties = [], int $num = 2)
 * @method void connect(bool $clean, array $will = [])
 */
class Client
{
    protected PoolFactory $factory;

    protected string $poolName = 'default';

    protected \Closure $getConnection;

    protected ?ErrorHandler $errorHandler = null;

    protected ?HealthChecker $healthChecker = null;

    protected PerformanceMetrics $performanceMetrics;

    protected ConnectionMetrics $connectionMetrics;

    protected PublishMetrics $publishMetrics;

    protected SubscriptionMetrics $subscriptionMetrics;

    protected ValidationMetrics $validationMetrics;

    protected array $operationCounts = [];

    public function __construct(
        PoolFactory $factory,
        ?ErrorHandler $errorHandler = null,
        ?HealthChecker $healthChecker = null,
        ?PerformanceMetrics $performanceMetrics = null,
        ?ConnectionMetrics $connectionMetrics = null,
        ?PublishMetrics $publishMetrics = null,
        ?SubscriptionMetrics $subscriptionMetrics = null,
        ?ValidationMetrics $validationMetrics = null
    ) {
        $this->factory = $factory;
        $this->errorHandler = $errorHandler ?? new ErrorHandler();
        $this->healthChecker = $healthChecker ?? new HealthChecker();
        $this->performanceMetrics = $performanceMetrics ?? new PerformanceMetrics();
        $this->connectionMetrics = $connectionMetrics ?? new ConnectionMetrics();
        $this->publishMetrics = $publishMetrics ?? new PublishMetrics();
        $this->subscriptionMetrics = $subscriptionMetrics ?? new SubscriptionMetrics();
        $this->validationMetrics = $validationMetrics ?? new ValidationMetrics();

        $this->getConnection = function ($hasContextConnection, $name, $arguments): void {
            // check the available connection num
            $pool = $this->factory->getPool($this->poolName);
            if (($name === MQTTConstants::SUBSCRIBE || $name === MQTTConstants::MULTISUB) && $pool->getAvailableConnectionNum() < 2) {
                throw new \RuntimeException('Connection pool exhausted. Cannot establish new connection before wait_timeout.');
            }
            $connection = $this->getConnection($hasContextConnection);

            // Record the operation attempt for health monitoring
            $this->healthChecker->recordConnectionAttempt();

            // Record operation start time for performance metrics
            $operationStartTime = microtime(true);

            // Track operation attempt
            $this->recordOperationAttempt($name);

            try {
                // Wrap the operation with error handling
                $this->errorHandler->wrapOperation(function () use ($connection, $name, $arguments, $operationStartTime) {
                    return Coroutine::create(
                        function () use ($connection, $name, $arguments, $operationStartTime) {
                            /* @var MQTTConnection $connection */

                            // Execute the operation
                            $result = $connection->{$name}(...$arguments);

                            // Record successful operation metrics
                            $this->recordSuccessfulOperation($name, $arguments, microtime(true) - $operationStartTime);

                            return $result;
                        }
                    );
                }, "mqtt_{$name}");
            } catch (\Exception $e) {
                // Record failed operation metrics
                $this->recordFailedOperation($name, $arguments, $e, microtime(true) - $operationStartTime);
                throw $e;
            } finally {
                if ($name === MQTTConstants::SUBSCRIBE) {
                    Coroutine::create(
                        static function () use ($connection) {
                            while (true) {
                                if (! $connection->receive()) {
                                    break;
                                }
                            }

                            $connection->close();
                            $connection->release();
                        }
                    );
                }
            }
        };
    }

    public function __call(string $name, mixed $arguments): void
    {
        $callStartTime = microtime(true);

        try {
            // Validate method exists
            if (! in_array($name, $this->methods())) {
                $this->validationMetrics->recordValidation(
                    'method_validation',
                    false,
                    "Invalid method: {$name}"
                );
                throw new InvalidMethodException(sprintf('method %s does not exist', $name));
            }

            $this->validationMetrics->recordValidation(
                'method_validation',
                true,
                "Valid method call: {$name}"
            );

            $hasContextConnection = Context::has($this->getContextKey());

            // Handle multiSub: change method name to subscribe and get iteration count
            $isMultiSub = $name === MQTTConstants::MULTISUB;
            /** @var array<int, mixed> $argsArray */
            $argsArray = $arguments;
            if ($isMultiSub) {
                $name = MQTTConstants::SUBSCRIBE;
                // multiSub third argument is the subscription count, default to 1
                $num = count($argsArray) >= 3 ? (int) end($argsArray) : 1;
            } else {
                $num = 1;
            }

            for ($count = 0; $count < $num; ++$count) {
                ($this->getConnection)($hasContextConnection, $name, $arguments);
            }

            // Record overall method call performance
            $this->performanceMetrics->recordOperationTime(
                "client_method_{$name}",
                microtime(true) - $callStartTime
            );
        } catch (\Exception $e) {
            // Record method call failure
            $this->performanceMetrics->recordOperationTime(
                "client_method_{$name}_failed",
                microtime(true) - $callStartTime
            );
            throw $e;
        }
    }

    public function setPoolName(string $poolName): static
    {
        $this->poolName = $poolName;
        return $this;
    }

    /**
     * Get health status of the MQTT client.
     */
    public function getHealthStatus(): array
    {
        return $this->healthChecker->getSystemHealth();
    }

    /**
     * Check if the client is healthy.
     */
    public function isHealthy(): bool
    {
        return $this->healthChecker->isSystemHealthy();
    }

    /**
     * Get connection success rate.
     */
    public function getConnectionSuccessRate(): float
    {
        return $this->healthChecker->getConnectionSuccessRate();
    }

    /**
     * Set custom retry policy for operations.
     */
    public function setRetryPolicy(string $operation, int $maxRetries, int $baseDelay = 1000): void
    {
        $this->errorHandler->setRetryPolicy($operation, $maxRetries, $baseDelay);
    }

    /**
     * Get comprehensive client metrics.
     */
    public function getMetrics(): array
    {
        return [
            'operation_counts' => $this->operationCounts,
            'performance' => $this->performanceMetrics->toArray(),
            'connection' => $this->connectionMetrics->toArray(),
            'publish' => $this->publishMetrics->toArray(),
            'subscription' => $this->subscriptionMetrics->toArray(),
            'validation' => $this->validationMetrics->toArray(),
            'health' => $this->getHealthStatus(),
        ];
    }

    /**
     * Get operation success rates.
     */
    public function getOperationSuccessRates(): array
    {
        $rates = [];

        foreach ($this->operationCounts as $operation => $counts) {
            if ($counts['attempts'] > 0) {
                $rates[$operation] = round($counts['successes'] / $counts['attempts'] * 100, 2);
            } else {
                $rates[$operation] = 0.0;
            }
        }

        return $rates;
    }

    /**
     * Get performance metrics.
     */
    public function getPerformanceMetrics(): PerformanceMetrics
    {
        return $this->performanceMetrics;
    }

    /**
     * Get connection metrics.
     */
    public function getConnectionMetrics(): ConnectionMetrics
    {
        return $this->connectionMetrics;
    }

    /**
     * Get publish metrics.
     */
    public function getPublishMetrics(): PublishMetrics
    {
        return $this->publishMetrics;
    }

    /**
     * Get subscription metrics.
     */
    public function getSubscriptionMetrics(): SubscriptionMetrics
    {
        return $this->subscriptionMetrics;
    }

    /**
     * Get validation metrics.
     */
    public function getValidationMetrics(): ValidationMetrics
    {
        return $this->validationMetrics;
    }

    /**
     * Reset all metrics.
     */
    public function resetMetrics(): void
    {
        $this->operationCounts = [];
        $this->performanceMetrics->reset();
        $this->connectionMetrics->reset();
        $this->publishMetrics->reset();
        $this->subscriptionMetrics->reset();
        $this->validationMetrics->reset();
    }

    /**
     * Record operation attempt metrics.
     */
    private function recordOperationAttempt(string $operation): void
    {
        if (! isset($this->operationCounts[$operation])) {
            $this->operationCounts[$operation] = ['attempts' => 0, 'successes' => 0, 'failures' => 0];
        }

        ++$this->operationCounts[$operation]['attempts'];

        // Record in connection metrics if it's a connection-related operation
        if (in_array($operation, [MQTTConstants::CONNECT, MQTTConstants::SUBSCRIBE, MQTTConstants::MULTISUB])) {
            $this->connectionMetrics->recordConnectionAttempt();
        }

        // Record in subscription metrics for subscription operations
        if (in_array($operation, [MQTTConstants::SUBSCRIBE, MQTTConstants::MULTISUB])) {
            $this->subscriptionMetrics->recordSubscriptionAttempt();
        }

        // Record in publish metrics for publish operations
        if ($operation === MQTTConstants::PUBLISH) {
            $this->publishMetrics->recordPublishAttempt();
        }
    }

    /**
     * Record successful operation metrics.
     */
    private function recordSuccessfulOperation(string $operation, array $arguments, float $duration): void
    {
        ++$this->operationCounts[$operation]['successes'];

        // Record operation time
        $this->performanceMetrics->recordOperationTime($operation, $duration);

        // Record specific metrics based on operation type
        switch ($operation) {
            case MQTTConstants::PUBLISH:
                $this->recordPublishSuccess($arguments);
                break;
            case MQTTConstants::SUBSCRIBE:
            case MQTTConstants::MULTISUB:
                $this->recordSubscribeSuccess($arguments);
                break;
            case MQTTConstants::CONNECT:
                $this->connectionMetrics->recordSuccessfulConnection($duration);
                break;
        }
    }

    /**
     * Record failed operation metrics.
     */
    private function recordFailedOperation(string $operation, array $arguments, \Exception $exception, float $duration): void
    {
        ++$this->operationCounts[$operation]['failures'];

        // Record operation time even for failures
        $this->performanceMetrics->recordOperationTime("{$operation}_failed", $duration);

        // Record specific failure metrics based on operation type
        switch ($operation) {
            case MQTTConstants::PUBLISH:
                $this->publishMetrics->recordFailedPublish();
                break;
            case MQTTConstants::SUBSCRIBE:
            case MQTTConstants::MULTISUB:
                $this->recordSubscribeFailure($arguments, $exception->getMessage());
                break;
            case MQTTConstants::CONNECT:
                $this->connectionMetrics->recordFailedConnection();
                break;
        }
    }

    /**
     * Record publish success metrics.
     */
    private function recordPublishSuccess(array $arguments): void
    {
        $topic = $arguments[0] ?? 'unknown';
        $message = $arguments[1] ?? '';
        $qos = $arguments[2] ?? 0;

        $this->publishMetrics->recordSuccessfulPublish(
            $topic,
            $qos,
            strlen($message)
        );

        $this->performanceMetrics->recordMessageThroughput(1);
    }

    /**
     * Record subscribe success metrics.
     */
    private function recordSubscribeSuccess(array $arguments): void
    {
        $topics = $arguments[0] ?? [];

        if (is_array($topics)) {
            $this->subscriptionMetrics->recordSuccessfulSubscription(
                $this->poolName,
                'client_' . getmypid(), // Simple client ID generation
                $topics
            );
        }
    }

    /**
     * Record subscribe failure metrics.
     */
    private function recordSubscribeFailure(array $arguments, string $reason): void
    {
        $topics = $arguments[0] ?? [];

        if (is_array($topics)) {
            $this->subscriptionMetrics->recordFailedSubscription(
                $this->poolName,
                'client_' . getmypid(), // Simple client ID generation
                $topics,
                $reason
            );
        }
    }

    private function methods(): array
    {
        return [
            MQTTConstants::SUBSCRIBE,
            MQTTConstants::MULTISUB,
            MQTTConstants::UNSUBSCRIBE,
            MQTTConstants::PUBLISH,
            MQTTConstants::CONNECT,
        ];
    }

    private function getConnection($hasContextConnection): MQTTConnection
    {
        $connection = null;
        if ($hasContextConnection) {
            $connection = Context::get($this->getContextKey());
        }

        if (! $connection instanceof MQTTConnection) {
            $pool = $this->factory->getPool($this->poolName);
            $connection = $pool->get();
        }

        if (! $connection instanceof MQTTConnection) {
            throw new InvalidMQTTConnectionException('invalid mqtt connection');
        }

        return $connection;
    }

    private function getContextKey(): string
    {
        return sprintf('mqtt.connection.%s', $this->poolName);
    }
}
