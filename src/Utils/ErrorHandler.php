<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Utils;

use Nashgao\MQTT\Exception\InvalidConfigException;
use Nashgao\MQTT\Exception\InvalidMQTTConnectionException;
use Nashgao\MQTT\Metrics\ErrorMetrics;
use Nashgao\MQTT\Metrics\PerformanceMetrics;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ErrorHandler
{
    private LoggerInterface $logger;

    private ErrorMetrics $errorMetrics;

    private PerformanceMetrics $performanceMetrics;

    private array $circuitBreakers = [];

    private array $retryPolicies = [];

    public function __construct(
        ?LoggerInterface $logger = null,
        ?ErrorMetrics $errorMetrics = null,
        ?PerformanceMetrics $performanceMetrics = null
    ) {
        $this->logger = $logger ?? new NullLogger();
        $this->errorMetrics = $errorMetrics ?? new ErrorMetrics();
        $this->performanceMetrics = $performanceMetrics ?? new PerformanceMetrics();
    }

    /**
     * Handle connection errors with retry logic.
     */
    public function handleConnectionError(\Exception $e, string $operation, int $attempt = 1): bool
    {
        // Record error metrics
        $this->errorMetrics->recordError('connection', $operation, $e->getMessage(), $e);

        $this->logger->error("MQTT connection error in operation '{$operation}'", [
            'exception' => $e->getMessage(),
            'attempt' => $attempt,
            'trace' => $e->getTraceAsString(),
        ]);

        // Check circuit breaker
        if ($this->isCircuitBreakerOpen($operation)) {
            $this->errorMetrics->recordCircuitBreakerOpen($operation);
            $this->logger->warning("Circuit breaker is open for operation '{$operation}'");
            return false;
        }

        // Apply retry policy
        $maxRetries = $this->getMaxRetries($operation);
        if ($attempt > $maxRetries) {
            $this->errorMetrics->recordRetryExhausted($operation, $attempt);
            $this->openCircuitBreaker($operation);
            return false;
        }

        // Calculate backoff delay
        $delay = $this->calculateBackoffDelay($attempt);
        $this->logger->info("Retrying operation '{$operation}' in {$delay}ms", ['attempt' => $attempt]);

        usleep($delay * 1000); // Convert to microseconds
        return true;
    }

    /**
     * Handle configuration errors.
     */
    public function handleConfigError(InvalidConfigException $e, array $config = []): void
    {
        // Record error metrics
        $this->errorMetrics->recordError('configuration', 'config_validation', $e->getMessage(), $e);

        $this->logger->error('MQTT configuration error', [
            'exception' => $e->getMessage(),
            'config_keys' => array_keys($config),
            'trace' => $e->getTraceAsString(),
        ]);

        // Attempt to provide helpful suggestions
        $suggestions = $this->generateConfigSuggestions($e->getMessage(), $config);
        if (! empty($suggestions)) {
            $this->logger->info('Configuration suggestions', ['suggestions' => $suggestions]);
        }
    }

    /**
     * Handle MQTT protocol errors.
     */
    public function handleProtocolError(\Exception $e, string $operation, array $context = []): void
    {
        // Record error metrics
        $this->errorMetrics->recordError('protocol', $operation, $e->getMessage(), $e);

        $this->logger->error("MQTT protocol error in operation '{$operation}'", [
            'exception' => $e->getMessage(),
            'context' => $context,
            'trace' => $e->getTraceAsString(),
        ]);

        // Record error for circuit breaker
        $this->recordError($operation);
    }

    /**
     * Handle resource exhaustion errors.
     */
    public function handleResourceError(\Exception $e, string $resource): void
    {
        // Record error metrics
        $this->errorMetrics->recordError('resource', $resource, $e->getMessage(), $e);

        // Record memory metrics
        $this->performanceMetrics->recordMemoryUsage();

        $this->logger->critical("Resource exhaustion: {$resource}", [
            'exception' => $e->getMessage(),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'trace' => $e->getTraceAsString(),
        ]);

        // Trigger cleanup if memory usage is high
        if ($this->isMemoryUsageHigh()) {
            $this->triggerMemoryCleanup();
        }
    }

    /**
     * Wrap operation with error handling.
     */
    public function wrapOperation(callable $operation, string $operationName, array $context = [])
    {
        $startTime = microtime(true);
        $attempt = 1;
        $maxAttempts = $this->getMaxRetries($operationName);

        while ($attempt <= $maxAttempts) {
            try {
                $result = $operation();

                // Record successful operation metrics
                $duration = microtime(true) - $startTime;
                $this->performanceMetrics->recordOperationTime($operationName, $duration);

                // Reset circuit breaker on success
                $this->closeCircuitBreaker($operationName);

                return $result;
            } catch (InvalidConfigException $e) {
                // Record failed operation time
                $duration = microtime(true) - $startTime;
                $this->performanceMetrics->recordOperationTime($operationName . '_failed', $duration);

                $this->handleConfigError($e, $context);
                throw $e; // Config errors are not retryable
            } catch (InvalidMQTTConnectionException $e) {
                if (! $this->handleConnectionError($e, $operationName, $attempt)) {
                    // Record failed operation time before throwing
                    $duration = microtime(true) - $startTime;
                    $this->performanceMetrics->recordOperationTime($operationName . '_failed', $duration);
                    throw $e;
                }
            } catch (\Exception $e) {
                $this->handleProtocolError($e, $operationName, $context);

                if ($attempt >= $maxAttempts) {
                    // Record failed operation time before throwing
                    $duration = microtime(true) - $startTime;
                    $this->performanceMetrics->recordOperationTime($operationName . '_failed', $duration);
                    throw $e;
                }

                if (! $this->handleConnectionError($e, $operationName, $attempt)) {
                    // Record failed operation time before throwing
                    $duration = microtime(true) - $startTime;
                    $this->performanceMetrics->recordOperationTime($operationName . '_failed', $duration);
                    throw $e;
                }
            }

            ++$attempt;
        }
    }

    /**
     * Set retry policy for an operation.
     */
    public function setRetryPolicy(string $operation, int $maxRetries, int $baseDelay = 1000): void
    {
        $this->retryPolicies[$operation] = [
            'max_retries' => $maxRetries,
            'base_delay' => $baseDelay,
        ];
    }

    /**
     * Get circuit breaker status.
     */
    public function getCircuitBreakerStatus(string $operation): array
    {
        return $this->circuitBreakers[$operation] ?? [
            'state' => 'closed',
            'failure_count' => 0,
            'last_failure' => null,
            'next_attempt' => null,
        ];
    }

    /**
     * Get error metrics.
     */
    public function getErrorMetrics(): ErrorMetrics
    {
        return $this->errorMetrics;
    }

    /**
     * Get performance metrics.
     */
    public function getPerformanceMetrics(): PerformanceMetrics
    {
        return $this->performanceMetrics;
    }

    private function isCircuitBreakerOpen(string $operation): bool
    {
        $breaker = $this->circuitBreakers[$operation] ?? null;

        if (! $breaker || $breaker['state'] === 'closed') {
            return false;
        }

        if ($breaker['state'] === 'open') {
            // Check if we should try again (half-open state)
            if (time() >= $breaker['next_attempt']) {
                $this->circuitBreakers[$operation]['state'] = 'half-open';
                return false;
            }
            return true;
        }

        // Half-open state - allow one attempt
        return false;
    }

    private function openCircuitBreaker(string $operation): void
    {
        $this->circuitBreakers[$operation] = [
            'state' => 'open',
            'failure_count' => ($this->circuitBreakers[$operation]['failure_count'] ?? 0) + 1,
            'last_failure' => time(),
            'next_attempt' => time() + 60, // Try again in 60 seconds
        ];

        // Record circuit breaker opening in metrics
        $this->errorMetrics->recordCircuitBreakerOpen($operation);

        $this->logger->warning("Circuit breaker opened for operation '{$operation}'");
    }

    private function closeCircuitBreaker(string $operation): void
    {
        if (isset($this->circuitBreakers[$operation])) {
            $this->circuitBreakers[$operation]['state'] = 'closed';
            $this->circuitBreakers[$operation]['failure_count'] = 0;

            $this->logger->info("Circuit breaker closed for operation '{$operation}'");
        }
    }

    private function recordError(string $operation): void
    {
        if (! isset($this->circuitBreakers[$operation])) {
            $this->circuitBreakers[$operation] = [
                'state' => 'closed',
                'failure_count' => 0,
                'last_failure' => null,
                'next_attempt' => null,
            ];
        }

        ++$this->circuitBreakers[$operation]['failure_count'];
        $this->circuitBreakers[$operation]['last_failure'] = time();

        // Open circuit breaker after 5 failures
        if ($this->circuitBreakers[$operation]['failure_count'] >= 5) {
            $this->openCircuitBreaker($operation);
        }
    }

    private function getMaxRetries(string $operation): int
    {
        return $this->retryPolicies[$operation]['max_retries'] ?? 3;
    }

    private function calculateBackoffDelay(int $attempt): int
    {
        // Exponential backoff with jitter
        $baseDelay = 1000; // 1 second
        $delay = $baseDelay * (2 ** ($attempt - 1));

        // Add jitter (±25%)
        $jitter = $delay * 0.25 * (mt_rand() / mt_getrandmax() - 0.5);

        return (int) ($delay + $jitter);
    }

    private function generateConfigSuggestions(string $errorMessage, array $config): array
    {
        $suggestions = [];

        if (strpos($errorMessage, 'host') !== false) {
            $suggestions[] = 'Check that the host is accessible and properly formatted';
            $suggestions[] = 'Verify DNS resolution for hostname';
        }

        if (strpos($errorMessage, 'port') !== false) {
            $suggestions[] = 'Ensure port is between 1 and 65535';
            $suggestions[] = 'Check if the port is blocked by firewall';
        }

        if (strpos($errorMessage, 'qos') !== false) {
            $suggestions[] = 'QoS must be 0 (at most once), 1 (at least once), or 2 (exactly once)';
        }

        return $suggestions;
    }

    private function isMemoryUsageHigh(): bool
    {
        $usage = memory_get_usage(true);
        $limit = ini_get('memory_limit');

        if ($limit === '-1') {
            return false; // No limit set
        }

        $limitBytes = $this->parseMemoryLimit($limit);
        return $limitBytes > 0 && ($usage / $limitBytes) > 0.8; // 80% threshold
    }

    private function triggerMemoryCleanup(): void
    {
        $this->logger->info('Triggering memory cleanup due to high usage');

        // Force garbage collection
        gc_collect_cycles();

        // Clear internal caches if possible
        $this->circuitBreakers = array_slice($this->circuitBreakers, -10, null, true);

        $this->logger->info('Memory cleanup completed', [
            'memory_usage_after' => memory_get_usage(true),
        ]);
    }

    private function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $limit = (int) $limit;

        $limit *= match ($last) {
            'g' => 1024 * 1024 * 1024,
            'm' => 1024 * 1024,
            'k' => 1024,
            default => 1
        };

        return $limit;
    }
}
