<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\History;

/**
 * Interface for topic pattern matching strategies.
 *
 * Enables different pattern matching implementations (e.g., MQTT wildcards,
 * regex patterns, exact matches) to be plugged into MessageHistory.
 */
interface TopicMatcherInterface
{
    /**
     * Check if a topic matches a pattern.
     *
     * @param string $pattern The pattern to match against (e.g., "sensor/+/temperature")
     * @param string $topic The topic to check (e.g., "sensor/room1/temperature")
     * @return bool True if the topic matches the pattern
     */
    public function matches(string $pattern, string $topic): bool;
}
