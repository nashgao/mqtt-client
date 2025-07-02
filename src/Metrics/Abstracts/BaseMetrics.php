<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Metrics\Abstracts;

/**
 * Base abstract class for all metrics implementations.
 */
abstract class BaseMetrics
{
    protected const DEFAULT_ARRAY_LIMIT = 100;

    protected const TIME_WINDOW_SECONDS = 300; // 5 minutes

    abstract public function toArray(): array;

    abstract public function reset(): void;

    /**
     * Get a summary of the most important metrics.
     */
    abstract public function getSummary(): array;

    /**
     * Add timestamp-based data with automatic array size management.
     */
    protected function addToTimestampArray(array &$array, float $timestamp, int $limit = self::DEFAULT_ARRAY_LIMIT): void
    {
        $array[] = $timestamp;

        if (count($array) > $limit) {
            array_shift($array);
        }
    }

    /**
     * Add data to an array with size limit management.
     * @param mixed $data
     */
    protected function addToLimitedArray(array &$array, $data, int $limit = self::DEFAULT_ARRAY_LIMIT): void
    {
        $array[] = $data;

        if (count($array) > $limit) {
            array_shift($array);
        }
    }

    /**
     * Clean old entries from a time-based array.
     */
    protected function cleanOldTimeEntries(array &$array, int $maxAge = self::TIME_WINDOW_SECONDS): void
    {
        $cutoff = time() - $maxAge;

        foreach ($array as $timestamp => $value) {
            if ($timestamp < $cutoff) {
                unset($array[$timestamp]);
            }
        }
    }

    /**
     * Get current timestamp.
     */
    protected function getCurrentTimestamp(): float
    {
        return microtime(true);
    }

    /**
     * Get current formatted date.
     */
    protected function getCurrentDate(): string
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * Calculate rate based on timestamps.
     */
    protected function calculateRateFromTimestamps(array $timestamps): float
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

    /**
     * Format duration in human-readable format.
     */
    protected function formatDuration(float $seconds): string
    {
        // Handle very small durations to avoid float precision issues
        if ($seconds < 1.0) {
            return '0d 00h 00m 00s';
        }

        $totalSeconds = intval($seconds);

        $days = intval($totalSeconds / 86400);
        $remainingAfterDays = $totalSeconds % 86400;

        $hours = intval($remainingAfterDays / 3600);
        $remainingAfterHours = $remainingAfterDays % 3600;

        $minutes = intval($remainingAfterHours / 60);
        $finalSeconds = $remainingAfterHours % 60;

        return sprintf('%dd %02dh %02dm %02ds', $days, $hours, $minutes, $finalSeconds);
    }
}
