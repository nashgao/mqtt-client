<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Metrics;

use Nashgao\MQTT\Metrics\Abstracts\BaseMetrics;
use Nashgao\MQTT\Metrics\Traits\QosTracking;
use Nashgao\MQTT\Metrics\Traits\StatisticsCalculation;
use Nashgao\MQTT\Metrics\Traits\SuccessFailureTracking;
use Nashgao\MQTT\Metrics\Traits\TopicTracking;

class SubscriptionMetrics extends BaseMetrics
{
    use SuccessFailureTracking;
    use QosTracking;
    use StatisticsCalculation;
    use TopicTracking;

    private array $poolSubscriptions = [];

    private array $clientSubscriptions = [];

    private array $subscriptionTimes = [];

    public function recordSubscriptionAttempt(): self
    {
        $this->recordAttempt();
        return $this;
    }

    public function recordSuccessfulSubscription(string $poolName, string $clientId, array $topics, mixed $result = null): self
    {
        $this->recordSuccess();
        $this->addToTimestampArray($this->subscriptionTimes, $this->getCurrentTimestamp());

        foreach ($topics as $topic => $qos) {
            // Support both MQTT v3 (int) and v5 (array with 'qos' key) formats
            $qosValue = is_array($qos) ? ($qos['qos'] ?? 0) : $qos;
            $this->recordTopicActivity($topic, $qosValue);
            $this->recordQosUsage($qosValue);
        }

        $this->updatePoolStats($poolName, count($topics), true);
        $this->updateClientStats($clientId, count($topics), true);
        return $this;
    }

    public function recordFailedSubscription(string $poolName, string $clientId, array $topics, ?string $reason = null): self
    {
        $this->recordFailure();

        foreach ($topics as $topic => $qos) {
            // Support both MQTT v3 (int) and v5 (array with 'qos' key) formats
            $qosValue = is_array($qos) ? ($qos['qos'] ?? 0) : $qos;
            $this->recordTopicActivity($topic, $qosValue);
        }

        $this->updatePoolStats($poolName, 0, false, $reason);
        $this->updateClientStats($clientId, 0, false, $reason);
        return $this;
    }

    public function getSubscriptionRate(): float
    {
        return $this->calculateRateFromTimestamps($this->subscriptionTimes);
    }

    public function getPoolSubscriptionStats(string $poolName): ?array
    {
        return $this->poolSubscriptions[$poolName] ?? null;
    }

    public function getClientSubscriptionStats(string $clientId): ?array
    {
        return $this->clientSubscriptions[$clientId] ?? null;
    }

    public function getMostActiveClients(int $limit = 10): array
    {
        return $this->getMostActive($this->clientSubscriptions, 'topics_subscribed', $limit);
    }

    public function getMostActivePools(int $limit = 10): array
    {
        return $this->getMostActive($this->poolSubscriptions, 'topics_subscribed', $limit);
    }

    public function getSummary(): array
    {
        return array_merge(
            $this->getSuccessFailureArray(),
            $this->getQosDistributionArray(),
            [
                'unique_topics' => $this->getUniqueTopicsCount(),
                'unique_pools' => count($this->poolSubscriptions),
                'unique_clients' => count($this->clientSubscriptions),
                'subscription_rate' => $this->getSubscriptionRate(),
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
                'pool_subscriptions' => $this->poolSubscriptions,
                'client_subscriptions' => $this->clientSubscriptions,
                'subscription_times' => $this->subscriptionTimes,
                'subscription_rate' => $this->getSubscriptionRate(),
            ]
        );
    }

    public function reset(): void
    {
        $this->resetSuccessFailureCounters();
        $this->resetQosDistribution();
        $this->resetTopicStats();
        $this->poolSubscriptions = [];
        $this->clientSubscriptions = [];
        $this->subscriptionTimes = [];
    }

    // Backward compatibility methods
    public function getTotalSubscriptionAttempts(): int
    {
        return $this->getTotalAttempts();
    }

    public function getSuccessfulSubscriptions(): int
    {
        return $this->getSuccessful();
    }

    public function getFailedSubscriptions(): int
    {
        return $this->getFailed();
    }

    public function getPoolSubscriptions(): array
    {
        return $this->poolSubscriptions;
    }

    public function getClientSubscriptions(): array
    {
        return $this->clientSubscriptions;
    }

    public function getTopicSubscriptions(): array
    {
        return $this->getAllTopicStats();
    }

    public function getTopicSubscriptionStats(string $topic): ?array
    {
        return $this->getTopicStats($topic);
    }

    public function getMostSubscribedTopics(int $limit = 10): array
    {
        return $this->getMostActiveTopics($limit);
    }

    private function updatePoolStats(string $poolName, int $topicCount, bool $success, ?string $failureReason = null): void
    {
        if (! isset($this->poolSubscriptions[$poolName])) {
            $this->poolSubscriptions[$poolName] = [
                'total_attempts' => 0,
                'successful' => 0,
                'failed' => 0,
                'topics_subscribed' => 0,
                'last_subscription' => null,
                'failure_reasons' => [],
            ];
        }

        ++$this->poolSubscriptions[$poolName]['total_attempts'];

        if ($success) {
            ++$this->poolSubscriptions[$poolName]['successful'];
            $this->poolSubscriptions[$poolName]['topics_subscribed'] += $topicCount;
            $this->poolSubscriptions[$poolName]['last_subscription'] = $this->getCurrentDate();
        } else {
            ++$this->poolSubscriptions[$poolName]['failed'];
            if ($failureReason) {
                $this->poolSubscriptions[$poolName]['failure_reasons'][$failureReason]
                    = ($this->poolSubscriptions[$poolName]['failure_reasons'][$failureReason] ?? 0) + 1;
            }
        }
    }

    private function updateClientStats(string $clientId, int $topicCount, bool $success, ?string $failureReason = null): void
    {
        if (! isset($this->clientSubscriptions[$clientId])) {
            $this->clientSubscriptions[$clientId] = [
                'total_attempts' => 0,
                'successful' => 0,
                'failed' => 0,
                'topics_subscribed' => 0,
                'last_subscription' => null,
                'failure_reasons' => [],
            ];
        }

        ++$this->clientSubscriptions[$clientId]['total_attempts'];

        if ($success) {
            ++$this->clientSubscriptions[$clientId]['successful'];
            $this->clientSubscriptions[$clientId]['topics_subscribed'] += $topicCount;
            $this->clientSubscriptions[$clientId]['last_subscription'] = $this->getCurrentDate();
        } else {
            ++$this->clientSubscriptions[$clientId]['failed'];
            if ($failureReason) {
                $this->clientSubscriptions[$clientId]['failure_reasons'][$failureReason]
                    = ($this->clientSubscriptions[$clientId]['failure_reasons'][$failureReason] ?? 0) + 1;
            }
        }
    }
}
