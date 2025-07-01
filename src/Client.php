<?php

declare(strict_types=1);

namespace Nashgao\MQTT;

use Hyperf\Context\Context;
use Hyperf\Coroutine\Coroutine;
use Nashgao\MQTT\Constants\MQTTConstants;
use Nashgao\MQTT\Exception\InvalidMethodException;
use Nashgao\MQTT\Exception\InvalidMQTTConnectionException;
use Nashgao\MQTT\Pool\PoolFactory;
use Nashgao\MQTT\Utils\ErrorHandler;
use Nashgao\MQTT\Utils\HealthChecker;

/**
 * @method subscribe(array $topics, array $properties = [])
 * @todo:verify the unsub function
 * @method unSubscribe(array $topics, array $properties = [])
 * @method publish(string $topic,string $message,int $qos = 0,int $dup = 0,int $retain = 0,array $properties = [])
 * @method multiSub(array $topics, array $properties, int $num = 2)
 * @method connect(bool $clean, array $will = [])
 */
class Client
{
    protected PoolFactory $factory;

    protected string $poolName = 'default';

    protected \Closure $getConnection;

    protected ?ErrorHandler $errorHandler = null;

    protected ?HealthChecker $healthChecker = null;

    public function __construct(PoolFactory $factory, ?ErrorHandler $errorHandler = null, ?HealthChecker $healthChecker = null)
    {
        $this->factory = $factory;
        $this->errorHandler = $errorHandler ?? new ErrorHandler();
        $this->healthChecker = $healthChecker ?? new HealthChecker();
        $this->getConnection = function ($hasContextConnection, $name, $arguments): void {
            // check the available connection num
            $pool = $this->factory->getPool($this->poolName);
            if (($name === MQTTConstants::SUBSCRIBE || $name === MQTTConstants::MULTISUB) && $pool->getAvailableConnectionNum() < 2) {
                throw new \RuntimeException('Connection pool exhausted. Cannot establish new connection before wait_timeout.');
            }
            $connection = $this->getConnection($hasContextConnection);

            // Record the operation attempt for health monitoring
            $this->healthChecker->recordConnectionAttempt();

            try {
                // Wrap the operation with error handling
                $this->errorHandler->wrapOperation(function () use ($connection, $name, $arguments) {
                    return Coroutine::create(
                        static function () use ($connection, $name, $arguments) {
                            /* @var MQTTConnection $connection */
                            $connection->{$name}(...$arguments);
                        }
                    );
                }, "mqtt_{$name}");
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
        if (! in_array($name, $this->methods())) {
            throw new InvalidMethodException(sprintf('method %s does not exist', $name));
        }

        $hasContextConnection = Context::has($this->getContextKey());
        if ($name = $name === MQTTConstants::MULTISUB ? MQTTConstants::SUBSCRIBE : $name) {
            $num = count($arguments) !== 3 ? 1 : end($arguments); // set multi sub default as 2
        }

        for ($count = 0; $count < ($num ?? 1); ++$count) {
            ($this->getConnection)($hasContextConnection, $name, $arguments);
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
