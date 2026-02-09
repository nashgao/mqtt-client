<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Examples\DemoShell;

use DateTimeImmutable;
use NashGao\InteractiveShell\Message\Message;

/**
 * Generates realistic MQTT messages from scenario definitions.
 */
final class MessageGenerator
{
    private int $messageCounter = 0;

    public function generate(MessageScenario $scenario): Message
    {
        $topic = $this->interpolateTopic($scenario->topicPattern, $scenario->variableRanges);
        $payload = $this->generatePayload($scenario);

        return new Message(
            type: 'publish',
            payload: [
                'topic' => $topic,
                'message' => $payload,
                'qos' => $scenario->qos,
            ],
            source: "demo:{$scenario->name}",
            timestamp: new DateTimeImmutable(),
            metadata: [
                'direction' => $scenario->direction,
                'qos' => $scenario->qos,
                'message_id' => ++$this->messageCounter,
                'demo_scenario' => $scenario->name,
            ],
        );
    }

    /**
     * Generate a message with specific topic and payload (for injection).
     */
    public function generateCustom(
        string $topic,
        mixed $payload,
        string $direction = 'incoming',
        int $qos = 0,
    ): Message {
        return new Message(
            type: 'publish',
            payload: [
                'topic' => $topic,
                'message' => $payload,
                'qos' => $qos,
            ],
            source: 'demo:inject',
            timestamp: new DateTimeImmutable(),
            metadata: [
                'direction' => $direction,
                'qos' => $qos,
                'message_id' => ++$this->messageCounter,
                'demo_scenario' => 'inject',
            ],
        );
    }

    public function getMessageCount(): int
    {
        return $this->messageCounter;
    }

    public function reset(): void
    {
        $this->messageCounter = 0;
    }

    private function interpolateTopic(string $pattern, array $ranges): string
    {
        return (string) preg_replace_callback('/\{(\w+)\}/', function (array $matches) use ($ranges): string {
            $key = $matches[1];
            if (isset($ranges[$key]) && is_array($ranges[$key])) {
                $options = $ranges[$key];
                return (string) $options[array_rand($options)];
            }
            return $matches[0];
        }, $pattern);
    }

    private function generatePayload(MessageScenario $scenario): mixed
    {
        if ($scenario->payloadType === 'binary') {
            $size = $scenario->payloadTemplate['size'] ?? 16;
            return random_bytes((int) $size);
        }

        if ($scenario->payloadType === 'string') {
            return $this->interpolateString(
                $scenario->payloadTemplate['value'] ?? '',
                $scenario->variableRanges
            );
        }

        return $this->interpolateArray($scenario->payloadTemplate, $scenario->variableRanges);
    }

    /**
     * @param array<string, mixed> $template
     * @param array<string, array<mixed>> $ranges
     * @return array<string, mixed>
     */
    private function interpolateArray(array $template, array $ranges): array
    {
        $result = [];
        foreach ($template as $key => $value) {
            if (is_string($value) && preg_match('/^\{(\w+)\}$/', $value, $matches)) {
                $varName = $matches[1];
                $result[$key] = $this->generateValue($varName, $ranges);
            } elseif (is_array($value)) {
                $result[$key] = $this->interpolateArray($value, $ranges);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * @param array<string, array<mixed>> $ranges
     */
    private function interpolateString(string $template, array $ranges): string
    {
        return (string) preg_replace_callback('/\{(\w+)\}/', function (array $matches) use ($ranges): string {
            $varName = $matches[1];
            $value = $this->generateValue($varName, $ranges);
            return is_scalar($value) ? (string) $value : json_encode($value, JSON_THROW_ON_ERROR);
        }, $template);
    }

    /**
     * @param array<string, array<mixed>> $ranges
     */
    private function generateValue(string $varName, array $ranges): mixed
    {
        // Special built-in variables
        return match ($varName) {
            'timestamp' => (new DateTimeImmutable())->format('c'),
            'bool' => (bool) random_int(0, 1),
            'uuid' => $this->generateUuid(),
            'onoff' => random_int(0, 1) ? 'on' : 'off',
            default => $this->generateFromRanges($varName, $ranges),
        };
    }

    /**
     * @param array<string, array<mixed>> $ranges
     */
    private function generateFromRanges(string $varName, array $ranges): mixed
    {
        if (!isset($ranges[$varName])) {
            return $varName;
        }

        $range = $ranges[$varName];
        if (!is_array($range)) {
            return $range;
        }

        // Check if it's a numeric range [min, max]
        if (count($range) === 2 && is_numeric($range[0]) && is_numeric($range[1])) {
            $min = $range[0];
            $max = $range[1];
            // Check if both are integers
            if (is_int($min) && is_int($max)) {
                return random_int($min, $max);
            }
            // Float range
            return $this->randomFloat((float) $min, (float) $max);
        }

        // Array of discrete options
        return $range[array_rand($range)];
    }

    private function randomFloat(float $min, float $max): float
    {
        return round($min + (mt_rand() / mt_getrandmax()) * ($max - $min), 1);
    }

    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
