<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Metrics\Traits;

/**
 * Trait for tracking topic-based statistics.
 */
trait TopicTracking
{
    protected array $topicStats = [];

    /**
     * Get statistics for a specific topic.
     */
    public function getTopicStats(string $topic): ?array
    {
        return $this->topicStats[$topic] ?? null;
    }

    /**
     * Get all topic statistics.
     */
    public function getAllTopicStats(): array
    {
        return $this->topicStats;
    }

    /**
     * Get the most active topics.
     */
    public function getMostActiveTopics(int $limit = 10): array
    {
        return $this->getMostActive($this->topicStats, 'count', $limit);
    }

    /**
     * Get count of unique topics.
     */
    public function getUniqueTopicsCount(): int
    {
        return count($this->topicStats);
    }

    /**
     * Initialize topic statistics.
     */
    protected function initializeTopicStats(string $topic): void
    {
        if (! isset($this->topicStats[$topic])) {
            $this->topicStats[$topic] = [
                'count' => 0,
                'last_activity' => null,
                'qos_levels' => [0 => 0, 1 => 0, 2 => 0],
            ];
        }
    }

    /**
     * Record activity for a topic.
     */
    protected function recordTopicActivity(string $topic, int $qos = 0): void
    {
        $this->initializeTopicStats($topic);

        ++$this->topicStats[$topic]['count'];
        $this->topicStats[$topic]['last_activity'] = date('Y-m-d H:i:s');

        if (array_key_exists($qos, $this->topicStats[$topic]['qos_levels'])) {
            ++$this->topicStats[$topic]['qos_levels'][$qos];
        }
    }

    /**
     * Reset topic statistics.
     */
    protected function resetTopicStats(): void
    {
        $this->topicStats = [];
    }

    /**
     * Get topic statistics as array.
     */
    protected function getTopicStatsArray(): array
    {
        return [
            'topic_stats' => $this->topicStats,
            'unique_topics_count' => $this->getUniqueTopicsCount(),
        ];
    }
}
