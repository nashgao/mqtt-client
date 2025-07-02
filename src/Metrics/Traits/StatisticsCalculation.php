<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Metrics\Traits;

/**
 * Trait for common statistical calculations.
 */
trait StatisticsCalculation
{
    /**
     * Calculate percentile from an array of values.
     */
    protected function calculatePercentile(array $values, float $percentile): float
    {
        if (empty($values)) {
            return 0.0;
        }

        $sorted = $values;
        sort($sorted);
        $index = (int) ceil($percentile / 100 * count($sorted)) - 1;
        return $sorted[max(0, $index)];
    }

    /**
     * Calculate comprehensive statistics for an array of numeric values.
     */
    protected function calculateStats(array $values): array
    {
        if (empty($values)) {
            return [];
        }

        $sorted = $values;
        sort($sorted);

        return [
            'min' => min($values),
            'max' => max($values),
            'avg' => round(array_sum($values) / count($values), 2),
            'count' => count($values),
            'median' => $sorted[intval(count($sorted) / 2)],
            'p95' => $this->calculatePercentile($values, 95),
            'p99' => $this->calculatePercentile($values, 99),
        ];
    }

    /**
     * Get the most active items from an associative array based on a count field.
     */
    protected function getMostActive(array $data, string $countField = 'count', int $limit = 10): array
    {
        if (empty($data)) {
            return [];
        }

        // Sort by count descending
        uasort($data, function ($a, $b) use ($countField) {
            $aValue = is_array($a) ? ($a[$countField] ?? 0) : $a;
            $bValue = is_array($b) ? ($b[$countField] ?? 0) : $b;
            return $bValue <=> $aValue;
        });

        return array_slice($data, 0, $limit, true);
    }

    /**
     * Calculate average from an array of values.
     */
    protected function calculateAverage(array $values): float
    {
        if (empty($values)) {
            return 0.0;
        }

        return array_sum($values) / count($values);
    }

    /**
     * Calculate rate per second from timestamps.
     */
    protected function calculateRatePerSecond(array $timestamps): float
    {
        if (count($timestamps) < 2) {
            return 0.0;
        }

        $timeSpan = end($timestamps) - reset($timestamps);

        if ($timeSpan <= 0) {
            return 0.0;
        }

        return count($timestamps) / $timeSpan;
    }
}
