<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Stats;

use NashGao\InteractiveShell\Message\Message;

/**
 * MQTT-specific statistics collector.
 *
 * Standalone implementation with base stats tracking:
 * - Message counts, rates, latency tracking
 * - QoS distribution (0, 1, 2)
 * - Direction counts (incoming/outgoing)
 * - Topic counts (top N topics)
 * - Subscribe/disconnect/error tracking
 */
final class StatsCollector
{
    // Base stats properties
    private int $totalMessages = 0;

    private int $latencyCount = 0;

    private float $latencySum = 0.0;

    private float $minLatency = PHP_FLOAT_MAX;

    private float $maxLatency = 0.0;

    /** @var array<float> Latency window for percentile calculations */
    private array $latencyWindow = [];

    private float $startTime;

    /** @var array<array{timestamp: float, count: int}> Rate calculation window */
    private array $rateWindow = [];

    private readonly int $rateWindowSeconds;

    private readonly int $latencyWindowSize;

    // MQTT-specific properties
    private int $incomingMessages = 0;

    private int $outgoingMessages = 0;

    /** @var array<int, int> QoS level counts */
    private array $qosCounts = [0 => 0, 1 => 0, 2 => 0];

    /** @var array<string, int> Message count by topic */
    private array $topicCounts = [];

    private int $errorCount = 0;

    private int $subscribeCount = 0;

    private int $disconnectCount = 0;

    private readonly int $topTopicsLimit;

    private readonly int $topicTruncationThreshold;

    public function __construct(
        int $rateWindowSeconds = 300,
        int $latencyWindowSize = 1000,
        int $topTopicsLimit = 5,
        int $topicTruncationThreshold = 28,
    ) {
        $this->rateWindowSeconds = $rateWindowSeconds;
        $this->latencyWindowSize = $latencyWindowSize;
        $this->topTopicsLimit = $topTopicsLimit;
        $this->topicTruncationThreshold = $topicTruncationThreshold;
        $this->startTime = microtime(true);
    }

    /**
     * Record a message and update all statistics.
     */
    public function record(Message $message): void
    {
        // Update base statistics
        ++$this->totalMessages;

        // Update rate window
        $now = microtime(true);
        $this->rateWindow[] = ['timestamp' => $now, 'count' => 1];

        // Clean old rate window entries
        $cutoff = $now - $this->rateWindowSeconds;
        $this->rateWindow = array_filter(
            $this->rateWindow,
            fn(array $entry): bool => $entry['timestamp'] > $cutoff
        );

        // Track latency if available
        if (isset($message->metadata['latency']) && is_numeric($message->metadata['latency'])) {
            $latency = (float) $message->metadata['latency'];
            $this->recordLatency($latency);
        }

        // Call MQTT-specific tracking
        $this->onRecord($message);
    }

    /**
     * Calculate current message rate (messages per second).
     */
    public function getRate(): float
    {
        if (empty($this->rateWindow)) {
            return 0.0;
        }

        $now = microtime(true);
        $cutoff = $now - $this->rateWindowSeconds;

        $count = 0;
        $oldestTimestamp = $now;

        foreach ($this->rateWindow as $entry) {
            if ($entry['timestamp'] > $cutoff) {
                $count += $entry['count'];
                $oldestTimestamp = min($oldestTimestamp, $entry['timestamp']);
            }
        }

        $duration = $now - $oldestTimestamp;
        return $duration > 0 ? $count / $duration : 0.0;
    }

    /**
     * Get uptime in seconds since start.
     */
    public function getUptime(): float
    {
        return microtime(true) - $this->startTime;
    }

    /**
     * Get average latency in milliseconds.
     */
    public function getAverageLatency(): float
    {
        return $this->latencyCount > 0 ? $this->latencySum / $this->latencyCount : 0.0;
    }

    /**
     * Get minimum latency in milliseconds.
     */
    public function getMinLatency(): float
    {
        return $this->minLatency === PHP_FLOAT_MAX ? 0.0 : $this->minLatency;
    }

    /**
     * Get maximum latency in milliseconds.
     */
    public function getMaxLatency(): float
    {
        return $this->maxLatency;
    }

    /**
     * Get latency percentile (e.g., 50 for median, 95, 99).
     */
    public function getLatencyPercentile(int $percentile): float
    {
        if (empty($this->latencyWindow)) {
            return 0.0;
        }

        $sorted = $this->latencyWindow;
        sort($sorted);

        $count = count($sorted);
        $index = (int) ceil($count * $percentile / 100) - 1;
        $index = max(0, min($index, $count - 1));

        return $sorted[$index];
    }

    /**
     * Get total messages recorded.
     */
    public function getTotalMessages(): int
    {
        return $this->totalMessages;
    }

    /**
     * Get latency statistics summary.
     *
     * @return array<string, float|int>
     */
    public function getLatencyStats(): array
    {
        return [
            'count' => $this->latencyCount,
            'avg' => $this->getAverageLatency(),
            'min' => $this->getMinLatency(),
            'max' => $this->getMaxLatency(),
            'p50' => $this->getLatencyPercentile(50),
            'p95' => $this->getLatencyPercentile(95),
            'p99' => $this->getLatencyPercentile(99),
        ];
    }

    /**
     * Get latency histogram for distribution analysis.
     *
     * @param int $bucketCount Number of buckets (ignored, uses fixed buckets)
     * @return array<string, int>
     */
    public function getLatencyHistogram(int $bucketCount = 10): array
    {
        $histogram = [];
        $buckets = [0, 1, 5, 10, 25, 50, 100, 250, 500, 1000];

        foreach ($buckets as $bucket) {
            $key = $bucket < 1000 ? "{$bucket}ms" : '1s+';
            $histogram[$key] = 0;
        }

        foreach ($this->latencyWindow as $latency) {
            $found = false;
            for ($i = count($buckets) - 1; $i >= 0; $i--) {
                if ($latency >= $buckets[$i]) {
                    $key = $buckets[$i] < 1000 ? "{$buckets[$i]}ms" : '1s+';
                    $histogram[$key]++;
                    $found = true;
                    break;
                }
            }
            if (! $found) {
                $histogram['0ms']++;
            }
        }

        return $histogram;
    }

    /**
     * Record latency measurement.
     */
    public function recordLatency(float $latency): void
    {
        ++$this->latencyCount;
        $this->latencySum += $latency;
        $this->minLatency = min($this->minLatency, $latency);
        $this->maxLatency = max($this->maxLatency, $latency);

        // Add to window for percentile calculations
        $this->latencyWindow[] = $latency;

        // Trim window if it exceeds size limit
        if (count($this->latencyWindow) > $this->latencyWindowSize) {
            array_shift($this->latencyWindow);
        }
    }

    /**
     * MQTT-specific message tracking.
     */
    private function onRecord(Message $message): void
    {
        // Track specific MQTT message types
        match ($message->type) {
            'publish' => $this->recordPublish($message),
            'subscribe' => ++$this->subscribeCount,
            'disconnect' => ++$this->disconnectCount,
            'error' => ++$this->errorCount,
            default => null,
        };
    }

    /**
     * Get incoming message count.
     */
    public function getIncomingMessages(): int
    {
        return $this->incomingMessages;
    }

    /**
     * Get outgoing message count.
     */
    public function getOutgoingMessages(): int
    {
        return $this->outgoingMessages;
    }

    /**
     * Get QoS distribution.
     *
     * @return array<int, int>
     */
    public function getQosDistribution(): array
    {
        return $this->qosCounts;
    }

    /**
     * Get top topics by message count.
     *
     * @return array<string, int>
     */
    public function getTopTopics(int $limit = 10): array
    {
        $sorted = $this->topicCounts;
        arsort($sorted);
        return array_slice($sorted, 0, $limit, true);
    }

    /**
     * Get error count.
     */
    public function getErrorCount(): int
    {
        return $this->errorCount;
    }

    /**
     * Get complete statistics summary (base + MQTT-specific).
     *
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        $total = $this->totalMessages;
        $incoming = $this->incomingMessages;
        $outgoing = $this->outgoingMessages;

        return [
            // Base statistics
            'total_messages' => $total,
            'rate' => $this->getRate(),
            'uptime_seconds' => $this->getUptime(),
            'latency' => [
                'count' => $this->latencyCount,
                'average' => $this->getAverageLatency(),
                'min' => $this->getMinLatency(),
                'max' => $this->getMaxLatency(),
                'p50' => $this->getLatencyPercentile(50),
                'p95' => $this->getLatencyPercentile(95),
                'p99' => $this->getLatencyPercentile(99),
            ],
            // MQTT-specific statistics
            'incoming' => [
                'count' => $incoming,
                'percentage' => $total > 0 ? round($incoming / $total * 100, 1) : 0,
            ],
            'outgoing' => [
                'count' => $outgoing,
                'percentage' => $total > 0 ? round($outgoing / $total * 100, 1) : 0,
            ],
            'qos_distribution' => $this->qosCounts,
            'top_topics' => $this->getTopTopics($this->topTopicsLimit),
            'errors' => $this->errorCount,
            'subscribes' => $this->subscribeCount,
            'disconnects' => $this->disconnectCount,
        ];
    }

    /**
     * Format statistics for display.
     */
    public function formatDisplay(): string
    {
        // Use instance properties directly for type safety instead of getSummary()
        $lines = [];

        $lines[] = '+' . str_repeat('-', 43) . '+';
        $lines[] = '|' . str_pad('MQTT Statistics', 43, ' ', STR_PAD_BOTH) . '|';
        $lines[] = '+' . str_repeat('-', 43) . '+';

        $total = $this->totalMessages;
        $incoming = $this->incomingMessages;
        $outgoing = $this->outgoingMessages;
        $rate = round($this->getRate(), 2);
        $incomingPct = $total > 0 ? round($incoming / $total * 100, 1) : 0.0;
        $outgoingPct = $total > 0 ? round($outgoing / $total * 100, 1) : 0.0;

        $lines[] = sprintf('| Messages (total):     %-20d |', $total);
        $lines[] = sprintf('| Rate:                 %-17.2f msg/s |', $rate);
        $lines[] = sprintf(
            '| Incoming:             %-12d (%4.1f%%) |',
            $incoming,
            $incomingPct
        );
        $lines[] = sprintf(
            '| Outgoing:             %-12d (%4.1f%%) |',
            $outgoing,
            $outgoingPct
        );

        $lines[] = '+' . str_repeat('-', 43) . '+';
        $lines[] = '| QoS Distribution:                         |';
        foreach ($this->qosCounts as $qos => $count) {
            $lines[] = sprintf('|   QoS %d:              %-20d |', $qos, $count);
        }

        $topTopics = $this->getTopTopics($this->topTopicsLimit);
        if (! empty($topTopics)) {
            $lines[] = '+' . str_repeat('-', 43) . '+';
            $lines[] = '| Top Topics:                               |';
            $threshold = $this->topicTruncationThreshold;
            foreach ($topTopics as $topic => $count) {
                $truncatedTopic = mb_strlen($topic) > $threshold ? mb_substr($topic, 0, $threshold - 3) . '...' : $topic;
                $lines[] = sprintf('|   %-28s %8d |', $truncatedTopic, $count);
            }
        }

        $lines[] = '+' . str_repeat('-', 43) . '+';
        $lines[] = sprintf('| Errors:               %-20d |', $this->errorCount);
        $lines[] = sprintf('| Uptime:               %-17.1f sec |', round($this->getUptime(), 1));

        // Add latency statistics if measurements exist
        if ($this->latencyCount > 0) {
            $lines[] = '+' . str_repeat('-', 43) . '+';
            $lines[] = '| Latency Statistics:                       |';
            $lines[] = sprintf('| Count:                %-20d |', $this->latencyCount);
            $lines[] = sprintf('| Average:              %-17.2f ms |', $this->getAverageLatency());
            $lines[] = sprintf('| Min:                  %-17.2f ms |', $this->getMinLatency());
            $lines[] = sprintf('| Max:                  %-17.2f ms |', $this->getMaxLatency());
            $lines[] = sprintf('| P50 (Median):         %-17.2f ms |', $this->getLatencyPercentile(50));
            $lines[] = sprintf('| P95:                  %-17.2f ms |', $this->getLatencyPercentile(95));
            $lines[] = sprintf('| P99:                  %-17.2f ms |', $this->getLatencyPercentile(99));
        }

        $lines[] = '+' . str_repeat('-', 43) . '+';

        return implode("\n", $lines);
    }

    /**
     * Reset all statistics (base + MQTT-specific).
     */
    public function reset(): void
    {
        // Reset base statistics
        $this->totalMessages = 0;
        $this->latencyCount = 0;
        $this->latencySum = 0.0;
        $this->minLatency = PHP_FLOAT_MAX;
        $this->maxLatency = 0.0;
        $this->latencyWindow = [];
        $this->rateWindow = [];
        $this->startTime = microtime(true);

        // Reset MQTT-specific statistics
        $this->incomingMessages = 0;
        $this->outgoingMessages = 0;
        $this->qosCounts = [0 => 0, 1 => 0, 2 => 0];
        $this->topicCounts = [];
        $this->errorCount = 0;
        $this->subscribeCount = 0;
        $this->disconnectCount = 0;
    }

    /**
     * Record a publish message.
     */
    private function recordPublish(Message $message): void
    {
        // Direction
        $direction = $message->metadata['direction'] ?? 'unknown';
        if ($direction === 'incoming' || $direction === 'in') {
            ++$this->incomingMessages;
        } elseif ($direction === 'outgoing' || $direction === 'out') {
            ++$this->outgoingMessages;
        }

        // QoS
        $qos = $this->extractQos($message);
        if (isset($this->qosCounts[$qos])) {
            ++$this->qosCounts[$qos];
        }

        // Topic
        $topic = $this->extractTopic($message);
        if ($topic !== null) {
            $this->topicCounts[$topic] = ($this->topicCounts[$topic] ?? 0) + 1;
        }
    }

    private function extractQos(Message $message): int
    {
        if (is_array($message->payload) && isset($message->payload['qos'])) {
            $qos = $message->payload['qos'];
            return is_numeric($qos) ? (int) $qos : 0;
        }
        if (isset($message->metadata['qos'])) {
            $qos = $message->metadata['qos'];
            return is_numeric($qos) ? (int) $qos : 0;
        }
        return 0;
    }

    private function extractTopic(Message $message): ?string
    {
        if (is_array($message->payload) && isset($message->payload['topic'])) {
            $topic = $message->payload['topic'];
            return is_string($topic) ? $topic : null;
        }
        return null;
    }
}
