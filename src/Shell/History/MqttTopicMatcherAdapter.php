<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\History;

use Nashgao\MQTT\Shell\History\TopicMatcherInterface;
use Nashgao\MQTT\Shell\Mqtt\TopicMatcher;

/**
 * Adapter for MQTT topic pattern matching.
 *
 * Bridges the base library's TopicMatcherInterface to the MQTT-specific
 * TopicMatcher implementation with support for MQTT wildcards (+ and #).
 */
final class MqttTopicMatcherAdapter implements TopicMatcherInterface
{
    public function matches(string $pattern, string $topic): bool
    {
        return TopicMatcher::matches($pattern, $topic);
    }
}
