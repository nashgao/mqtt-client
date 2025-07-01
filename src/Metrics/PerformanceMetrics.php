<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Metrics;

class PerformanceMetrics
{
    private array $operationTimes = [];
    private array $messageThroughput = [];
    private int $totalMessages = 0;
    private int $totalOperations = 0;
    private float $peakMemoryUsage = 0.0;
    private float $currentMemoryUsage = 0.0;
    private array $latencyMeasurements = [];

    public function recordOperationTime(string $operation, float $duration): void
    {
        if (!isset($this->operationTimes[$operation])) {
            $this->operationTimes[$operation] = [];
        }
        
        $this->operationTimes[$operation][] = $duration;
        $this->totalOperations++;
        
        if (count($this->operationTimes[$operation]) > 100) {
            array_shift($this->operationTimes[$operation]);
        }
    }

    public function recordMessageThroughput(int $messageCount): void
    {
        $timestamp = time();
        $this->messageThroughput[$timestamp] = ($this->messageThroughput[$timestamp] ?? 0) + $messageCount;
        $this->totalMessages += $messageCount;
        
        $cutoff = $timestamp - 300;
        foreach ($this->messageThroughput as $ts => $count) {
            if ($ts < $cutoff) {
                unset($this->messageThroughput[$ts]);
            }
        }
    }

    public function recordMemoryUsage(): void
    {
        $current = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);
        
        $this->currentMemoryUsage = $current;
        if ($peak > $this->peakMemoryUsage) {
            $this->peakMemoryUsage = $peak;
        }
    }

    public function recordLatency(float $latency): void
    {
        $this->latencyMeasurements[] = $latency;
        
        if (count($this->latencyMeasurements) > 1000) {
            array_shift($this->latencyMeasurements);
        }
    }

    public function getAverageOperationTime(string $operation): float
    {
        if (!isset($this->operationTimes[$operation]) || empty($this->operationTimes[$operation])) {
            return 0.0;
        }
        
        return array_sum($this->operationTimes[$operation]) / count($this->operationTimes[$operation]);
    }

    public function getOperationPercentile(string $operation, float $percentile): float
    {
        if (!isset($this->operationTimes[$operation]) || empty($this->operationTimes[$operation])) {
            return 0.0;
        }
        
        $times = $this->operationTimes[$operation];
        sort($times);
        $index = (int) ceil($percentile / 100 * count($times)) - 1;
        return $times[max(0, $index)];
    }

    public function getMessagesPerSecond(): float
    {
        if (empty($this->messageThroughput)) {
            return 0.0;
        }
        
        $recentMessages = array_sum($this->messageThroughput);
        $timeWindow = count($this->messageThroughput);
        
        return $timeWindow > 0 ? $recentMessages / $timeWindow : 0.0;
    }

    public function getAverageLatency(): float
    {
        if (empty($this->latencyMeasurements)) {
            return 0.0;
        }
        
        return array_sum($this->latencyMeasurements) / count($this->latencyMeasurements);
    }

    public function getLatencyPercentile(float $percentile): float
    {
        if (empty($this->latencyMeasurements)) {
            return 0.0;
        }
        
        $measurements = $this->latencyMeasurements;
        sort($measurements);
        $index = (int) ceil($percentile / 100 * count($measurements)) - 1;
        return $measurements[max(0, $index)];
    }

    public function getCurrentMemoryUsage(): float
    {
        return $this->currentMemoryUsage;
    }

    public function getPeakMemoryUsage(): float
    {
        return $this->peakMemoryUsage;
    }

    public function getTotalMessages(): int
    {
        return $this->totalMessages;
    }

    public function getTotalOperations(): int
    {
        return $this->totalOperations;
    }

    public function toArray(): array
    {
        $operations = [];
        foreach ($this->operationTimes as $operation => $times) {
            $operations[$operation] = [
                'count' => count($times),
                'average_time' => $this->getAverageOperationTime($operation),
                'p95_time' => $this->getOperationPercentile($operation, 95),
                'p99_time' => $this->getOperationPercentile($operation, 99),
            ];
        }

        return [
            'operations' => $operations,
            'total_operations' => $this->totalOperations,
            'total_messages' => $this->totalMessages,
            'messages_per_second' => $this->getMessagesPerSecond(),
            'memory' => [
                'current' => $this->currentMemoryUsage,
                'peak' => $this->peakMemoryUsage,
            ],
            'latency' => [
                'average' => $this->getAverageLatency(),
                'p50' => $this->getLatencyPercentile(50),
                'p95' => $this->getLatencyPercentile(95),
                'p99' => $this->getLatencyPercentile(99),
            ],
        ];
    }

    public function reset(): void
    {
        $this->operationTimes = [];
        $this->messageThroughput = [];
        $this->totalMessages = 0;
        $this->totalOperations = 0;
        $this->peakMemoryUsage = 0.0;
        $this->currentMemoryUsage = 0.0;
        $this->latencyMeasurements = [];
    }
}