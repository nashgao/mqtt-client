<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Metrics\Traits;

/**
 * Trait for tracking MQTT QoS distribution.
 */
trait QosTracking
{
    protected array $qosDistribution = [0 => 0, 1 => 0, 2 => 0];

    public function getQosDistribution(): array
    {
        return $this->qosDistribution;
    }

    protected function recordQosUsage(int $qos): void
    {
        if (array_key_exists($qos, $this->qosDistribution)) {
            ++$this->qosDistribution[$qos];
        }
    }

    protected function resetQosDistribution(): void
    {
        $this->qosDistribution = [0 => 0, 1 => 0, 2 => 0];
    }

    protected function getQosDistributionArray(): array
    {
        return [
            'qos_distribution' => $this->qosDistribution,
            'qos_0_count' => $this->qosDistribution[0],
            'qos_1_count' => $this->qosDistribution[1],
            'qos_2_count' => $this->qosDistribution[2],
        ];
    }
}
