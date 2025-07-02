<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Metrics;

class ErrorMetrics
{
    private array $errorCounts = [];

    private array $errorsByType = [];

    private array $errorsByOperation = [];

    private array $recentErrors = [];

    private int $totalErrors = 0;

    private float $lastErrorTime = 0.0;

    private array $errorRates = [];

    public function recordError(string $type, string $operation, string $message, ?\Throwable $exception = null): void
    {
        $timestamp = microtime(true);
        ++$this->totalErrors;
        $this->lastErrorTime = $timestamp;

        if (! isset($this->errorsByType[$type])) {
            $this->errorsByType[$type] = 0;
        }
        ++$this->errorsByType[$type];

        if (! isset($this->errorsByOperation[$operation])) {
            $this->errorsByOperation[$operation] = 0;
        }
        ++$this->errorsByOperation[$operation];

        $errorData = [
            'type' => $type,
            'operation' => $operation,
            'message' => $message,
            'timestamp' => $timestamp,
            'exception_class' => $exception ? get_class($exception) : null,
            'stack_trace' => $exception ? $exception->getTraceAsString() : null,
        ];

        $this->recentErrors[] = $errorData;
        if (count($this->recentErrors) > 100) {
            array_shift($this->recentErrors);
        }

        $this->updateErrorRate($timestamp);
    }

    public function recordCircuitBreakerOpen(string $operation): void
    {
        $this->recordError('circuit_breaker', $operation, "Circuit breaker opened for operation: {$operation}");
    }

    public function recordTimeout(string $operation, float $duration): void
    {
        $this->recordError('timeout', $operation, "Operation timed out after {$duration}s");
    }

    public function recordRetryExhausted(string $operation, int $attempts): void
    {
        $this->recordError('retry_exhausted', $operation, "Max retry attempts ({$attempts}) exhausted for operation: {$operation}");
    }

    public function getErrorRate(): float
    {
        if (empty($this->errorRates)) {
            return 0.0;
        }

        $totalMinutes = count($this->errorRates);
        $totalErrors = array_sum($this->errorRates);

        return $totalMinutes > 0 ? $totalErrors / $totalMinutes : 0.0;
    }

    public function getErrorCountByType(string $type): int
    {
        return $this->errorsByType[$type] ?? 0;
    }

    public function getErrorCountByOperation(string $operation): int
    {
        return $this->errorsByOperation[$operation] ?? 0;
    }

    public function getRecentErrors(int $limit = 10): array
    {
        return array_slice($this->recentErrors, -$limit);
    }

    public function getMostFrequentErrorType(): ?string
    {
        if (empty($this->errorsByType)) {
            return null;
        }

        return array_key_first(
            array_slice(
                arsort($this->errorsByType) ? $this->errorsByType : [],
                0,
                1,
                true
            )
        );
    }

    public function getMostProblematicOperation(): ?string
    {
        if (empty($this->errorsByOperation)) {
            return null;
        }

        return array_key_first(
            array_slice(
                arsort($this->errorsByOperation) ? $this->errorsByOperation : [],
                0,
                1,
                true
            )
        );
    }

    public function getTotalErrors(): int
    {
        return $this->totalErrors;
    }

    public function getLastErrorTime(): float
    {
        return $this->lastErrorTime;
    }

    public function getTimeSinceLastError(): float
    {
        if ($this->lastErrorTime === 0.0) {
            return 0.0;
        }
        return microtime(true) - $this->lastErrorTime;
    }

    public function toArray(): array
    {
        return [
            'total_errors' => $this->totalErrors,
            'error_rate_per_minute' => $this->getErrorRate(),
            'last_error_time' => $this->lastErrorTime,
            'time_since_last_error' => $this->getTimeSinceLastError(),
            'errors_by_type' => $this->errorsByType,
            'errors_by_operation' => $this->errorsByOperation,
            'most_frequent_error_type' => $this->getMostFrequentErrorType(),
            'most_problematic_operation' => $this->getMostProblematicOperation(),
            'recent_errors' => $this->getRecentErrors(5),
        ];
    }

    public function reset(): void
    {
        $this->errorCounts = [];
        $this->errorsByType = [];
        $this->errorsByOperation = [];
        $this->recentErrors = [];
        $this->totalErrors = 0;
        $this->lastErrorTime = 0.0;
        $this->errorRates = [];
    }

    private function updateErrorRate(float $timestamp): void
    {
        $minute = (int) ($timestamp / 60);
        if (! isset($this->errorRates[$minute])) {
            $this->errorRates[$minute] = 0;
        }
        ++$this->errorRates[$minute];

        $cutoff = $minute - 60;
        foreach ($this->errorRates as $min => $count) {
            if ($min < $cutoff) {
                unset($this->errorRates[$min]);
            }
        }
    }
}
