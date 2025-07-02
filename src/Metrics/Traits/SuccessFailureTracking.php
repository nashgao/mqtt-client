<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Metrics\Traits;

/**
 * Trait for tracking success and failure rates.
 */
trait SuccessFailureTracking
{
    protected int $totalAttempts = 0;

    protected int $successful = 0;

    protected int $failed = 0;

    public function recordAttempt(): void
    {
        ++$this->totalAttempts;
    }

    public function recordSuccess(): void
    {
        ++$this->successful;
    }

    public function recordFailure(): void
    {
        ++$this->failed;
    }

    public function getTotalAttempts(): int
    {
        return $this->totalAttempts;
    }

    public function getSuccessful(): int
    {
        return $this->successful;
    }

    public function getFailed(): int
    {
        return $this->failed;
    }

    public function getSuccessRate(): float
    {
        if ($this->totalAttempts === 0) {
            return 0.0;
        }

        return round($this->successful / $this->totalAttempts * 100, 2);
    }

    public function getFailureRate(): float
    {
        if ($this->totalAttempts === 0) {
            return 0.0;
        }

        return round($this->failed / $this->totalAttempts * 100, 2);
    }

    protected function resetSuccessFailureCounters(): void
    {
        $this->totalAttempts = 0;
        $this->successful = 0;
        $this->failed = 0;
    }

    protected function getSuccessFailureArray(): array
    {
        return [
            'total_attempts' => $this->totalAttempts,
            'successful' => $this->successful,
            'failed' => $this->failed,
            'success_rate' => $this->getSuccessRate(),
            'failure_rate' => $this->getFailureRate(),
        ];
    }
}
