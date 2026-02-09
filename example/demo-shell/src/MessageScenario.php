<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Examples\DemoShell;

/**
 * Defines a pattern for generating mock MQTT messages.
 */
final readonly class MessageScenario
{
    /**
     * @param string $name Unique name for this scenario
     * @param string $topicPattern Topic pattern with placeholders e.g., "sensors/{room}/temperature"
     * @param string $payloadType Payload type: 'json', 'string', 'binary'
     * @param array<string, mixed> $payloadTemplate Template for payload generation
     * @param int $qos QoS level (0, 1, 2)
     * @param string $direction Message direction: 'incoming' or 'outgoing'
     * @param float $frequency Messages per second
     * @param array<string, array<mixed>> $variableRanges Ranges for dynamic values
     */
    public function __construct(
        public string $name,
        public string $topicPattern,
        public string $payloadType = 'json',
        public array $payloadTemplate = [],
        public int $qos = 0,
        public string $direction = 'incoming',
        public float $frequency = 1.0,
        public array $variableRanges = [],
    ) {}
}
