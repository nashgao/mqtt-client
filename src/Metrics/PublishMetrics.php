<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Metrics;

use Nashgao\MQTT\Metrics\Abstracts\BaseMetrics;
use Nashgao\MQTT\Metrics\Traits\QosTracking;
use Nashgao\MQTT\Metrics\Traits\StatisticsCalculation;
use Nashgao\MQTT\Metrics\Traits\SuccessFailureTracking;
use Nashgao\MQTT\Metrics\Traits\TopicTracking;

class PublishMetrics extends BaseMetrics
{
    use SuccessFailureTracking;
    use QosTracking;
    use StatisticsCalculation;
    use TopicTracking;

    private array $messageSizes = [];

    private array $publishTimes = [];

    public function recordPublishAttempt(): self
    {
        $this->recordAttempt();
        return $this;
    }

    public function recordSuccessfulPublish(string $topic, int $qos = 0, ?int $messageSize = null): self
    {
        $this->recordSuccess();
        $this->recordTopicActivity($topic, $qos);
        $this->recordQosUsage($qos);

        if ($messageSize !== null) {
            $this->addToLimitedArray($this->messageSizes, $messageSize);
        }

        $this->addToTimestampArray($this->publishTimes, $this->getCurrentTimestamp());
        return $this;
    }

    public function recordFailedPublish(): self
    {
        $this->recordFailure();
        return $this;
    }

    public function getPublishRate(): float
    {
        return $this->calculateRateFromTimestamps($this->publishTimes);
    }

    public function getMessageSizeStats(): array
    {
        return $this->calculateStats($this->messageSizes);
    }

    public function getSummary(): array
    {
        return array_merge(
            $this->getSuccessFailureArray(),
            $this->getQosDistributionArray(),
            [
                'unique_topics' => $this->getUniqueTopicsCount(),
                'publish_rate' => $this->getPublishRate(),
                'message_size_stats' => $this->getMessageSizeStats(),
            ]
        );
    }

    public function toArray(): array
    {
        return array_merge(
            $this->getSuccessFailureArray(),
            $this->getQosDistributionArray(),
            $this->getTopicStatsArray(),
            [
                'message_sizes' => $this->messageSizes,
                'publish_times' => $this->publishTimes,
                'publish_rate' => $this->getPublishRate(),
                'message_size_stats' => $this->getMessageSizeStats(),
            ]
        );
    }

    public function reset(): void
    {
        $this->resetSuccessFailureCounters();
        $this->resetQosDistribution();
        $this->resetTopicStats();
        $this->messageSizes = [];
        $this->publishTimes = [];
    }

    // Backward compatibility methods
    public function getTotalPublishes(): int
    {
        return $this->getTotalAttempts();
    }

    public function getSuccessfulPublishes(): int
    {
        return $this->getSuccessful();
    }

    public function getFailedPublishes(): int
    {
        return $this->getFailed();
    }
}
