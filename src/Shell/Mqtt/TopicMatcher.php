<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Mqtt;

/**
 * MQTT topic pattern matching utility.
 *
 * Provides centralized MQTT topic pattern matching and validation.
 * Supports MQTT wildcards:
 * - `+` matches exactly one topic level
 * - `#` matches any number of levels (must be at end)
 */
final class TopicMatcher
{
    /**
     * Match topic against MQTT wildcard pattern.
     *
     * @param string $pattern The MQTT pattern (e.g., "sensors/+/temperature" or "sensors/#")
     * @param string $topic The actual topic (e.g., "sensors/living-room/temperature")
     * @return bool True if topic matches pattern
     *
     * @example
     * TopicMatcher::matches('sensors/+/temperature', 'sensors/living-room/temperature') // true
     * TopicMatcher::matches('sensors/#', 'sensors/a/b/c') // true
     * TopicMatcher::matches('sensors/+', 'sensors/a/b') // false (+ is single level)
     */
    public static function matches(string $pattern, string $topic): bool
    {
        // Handle exact match (no wildcards)
        if (! str_contains($pattern, '+') && ! str_contains($pattern, '#')) {
            return $topic === $pattern;
        }

        // Handle global multi-level wildcard
        if ($pattern === '#') {
            return true;
        }

        // Handle multi-level wildcard at end
        if (str_ends_with($pattern, '/#')) {
            $prefix = substr($pattern, 0, -2);
            return $topic === $prefix || str_starts_with($topic, $prefix . '/');
        }

        $topicParts = explode('/', $topic);
        $patternParts = explode('/', $pattern);

        $topicLen = count($topicParts);
        $patternLen = count($patternParts);

        for ($i = 0; $i < $patternLen; ++$i) {
            $patternPart = $patternParts[$i];

            // Multi-level wildcard - matches rest of topic
            if ($patternPart === '#') {
                return true;
            }

            // No more topic parts but pattern continues
            if ($i >= $topicLen) {
                return false;
            }

            // Single-level wildcard - matches any single level
            if ($patternPart === '+') {
                continue;
            }

            // Exact match required
            if ($patternPart !== $topicParts[$i]) {
                return false;
            }
        }

        // Pattern exhausted - topic must also be exhausted
        return $topicLen === $patternLen;
    }

    /**
     * Validate MQTT topic pattern.
     *
     * Rules:
     * - `#` must be at the end and alone in its level
     * - `+` must be alone in its level
     * - No empty levels (except empty pattern which is valid)
     * - Topic levels cannot contain both wildcards and other characters
     *
     * @param string $pattern The MQTT pattern to validate
     * @return ValidationResult Validation result with error message if invalid
     */
    public static function validate(string $pattern): ValidationResult
    {
        // Empty pattern is valid (matches nothing)
        if ($pattern === '') {
            return ValidationResult::valid();
        }

        $parts = explode('/', $pattern);
        $lastIndex = count($parts) - 1;

        foreach ($parts as $i => $part) {
            // Empty level check (e.g., "sensors//temperature")
            if ($part === '' && $pattern !== '') {
                // Allow trailing slash and leading slash for special cases
                if ($i !== 0 && $i !== $lastIndex) {
                    return ValidationResult::invalid("Empty topic level at position {$i}");
                }
            }

            // `#` must be last and alone in its level
            if ($part === '#') {
                if ($i !== $lastIndex) {
                    return ValidationResult::invalid('Multi-level wildcard # must be at the end of the pattern');
                }
                continue;
            }

            // `#` mixed with other characters
            if (str_contains($part, '#')) {
                return ValidationResult::invalid('Multi-level wildcard # must be alone in its level');
            }

            // `+` must be alone in its level
            if ($part !== '+' && str_contains($part, '+')) {
                return ValidationResult::invalid('Single-level wildcard + must be alone in its level');
            }
        }

        return ValidationResult::valid();
    }

    /**
     * Check if a pattern contains any MQTT wildcards.
     */
    public static function hasWildcards(string $pattern): bool
    {
        return str_contains($pattern, '+') || str_contains($pattern, '#');
    }
}
