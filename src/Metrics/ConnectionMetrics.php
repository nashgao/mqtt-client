<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Metrics;

class ConnectionMetrics
{
    private int $totalAttempts = 0;

    private int $successfulConnections = 0;

    private int $failedConnections = 0;

    private int $timeouts = 0;

    private int $reconnections = 0;

    private float $lastConnectionTime = 0.0;

    private array $connectionDurations = [];

    private int $activeConnections = 0;

    public function recordConnectionAttempt(): self
    {
        ++$this->totalAttempts;
        $this->lastConnectionTime = microtime(true);
        return $this;
    }

    public function recordSuccessfulConnection(?float $connectionTime = null): self
    {
        ++$this->successfulConnections;
        ++$this->activeConnections;

        if ($connectionTime !== null) {
            $this->connectionDurations[] = $connectionTime;
            if (count($this->connectionDurations) > 100) {
                array_shift($this->connectionDurations);
            }
        }
        return $this;
    }

    public function recordFailedConnection(): self
    {
        ++$this->failedConnections;
        return $this;
    }

    public function recordTimeout(): self
    {
        ++$this->timeouts;
        return $this;
    }

    public function recordReconnection(): self
    {
        ++$this->reconnections;
        return $this;
    }

    public function recordDisconnection(): self
    {
        if ($this->activeConnections > 0) {
            --$this->activeConnections;
        }
        return $this;
    }

    public function getSuccessRate(): float
    {
        if ($this->totalAttempts === 0) {
            return 1.0;
        }
        return $this->successfulConnections / $this->totalAttempts;
    }

    public function getAverageConnectionTime(): float
    {
        if (empty($this->connectionDurations)) {
            return 0.0;
        }
        return array_sum($this->connectionDurations) / count($this->connectionDurations);
    }

    public function getTotalAttempts(): int
    {
        return $this->totalAttempts;
    }

    public function getSuccessfulConnections(): int
    {
        return $this->successfulConnections;
    }

    public function getFailedConnections(): int
    {
        return $this->failedConnections;
    }

    public function getTimeouts(): int
    {
        return $this->timeouts;
    }

    public function getReconnections(): int
    {
        return $this->reconnections;
    }

    public function getActiveConnections(): int
    {
        return $this->activeConnections;
    }

    public function getLastConnectionTime(): float
    {
        return $this->lastConnectionTime;
    }

    public function toArray(): array
    {
        return [
            'total_attempts' => $this->totalAttempts,
            'successful_connections' => $this->successfulConnections,
            'failed_connections' => $this->failedConnections,
            'timeouts' => $this->timeouts,
            'reconnections' => $this->reconnections,
            'active_connections' => $this->activeConnections,
            'success_rate' => $this->getSuccessRate(),
            'average_connection_time' => $this->getAverageConnectionTime(),
            'last_connection_time' => $this->lastConnectionTime,
        ];
    }

    public function reset(): self
    {
        $this->totalAttempts = 0;
        $this->successfulConnections = 0;
        $this->failedConnections = 0;
        $this->timeouts = 0;
        $this->reconnections = 0;
        $this->lastConnectionTime = 0.0;
        $this->connectionDurations = [];
        $this->activeConnections = 0;
        return $this;
    }
}
