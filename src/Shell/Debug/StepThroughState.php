<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Debug;

use NashGao\InteractiveShell\Message\Message;
use Nashgao\MQTT\Shell\Mqtt\TopicMatcher;

/**
 * Manages step-through debugging state for MQTT shell.
 *
 * Tracks whether step mode is enabled, current message being inspected,
 * and breakpoints for automatic pausing.
 */
final class StepThroughState
{
    private bool $enabled = false;
    private ?Message $currentMessage = null;
    private bool $waitingForInput = false;

    /**
     * @var array<string, string> Breakpoints by field:pattern
     */
    private array $breakpoints = [];

    /**
     * Check if step-through mode is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Enable or disable step-through mode.
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * Check if step mode is waiting for user input.
     */
    public function isWaitingForInput(): bool
    {
        return $this->waitingForInput;
    }

    /**
     * Set waiting for input state.
     */
    public function setWaitingForInput(bool $waiting): void
    {
        $this->waitingForInput = $waiting;
    }

    /**
     * Get the current message being inspected.
     */
    public function getCurrentMessage(): ?Message
    {
        return $this->currentMessage;
    }

    /**
     * Set the current message being inspected.
     */
    public function setCurrentMessage(?Message $message): void
    {
        $this->currentMessage = $message;
    }

    /**
     * Add a breakpoint for a specific field pattern.
     *
     * @param string $field Field name (e.g., 'topic', 'payload')
     * @param string $pattern Pattern to match (supports wildcards)
     */
    public function addBreakpoint(string $field, string $pattern): void
    {
        $this->breakpoints[$field] = $pattern;
    }

    /**
     * Remove a breakpoint for a specific field.
     */
    public function removeBreakpoint(string $field): void
    {
        unset($this->breakpoints[$field]);
    }

    /**
     * Get all configured breakpoints.
     *
     * @return array<string, string>
     */
    public function getBreakpoints(): array
    {
        return $this->breakpoints;
    }

    /**
     * Clear all breakpoints.
     */
    public function clearBreakpoints(): void
    {
        $this->breakpoints = [];
    }

    /**
     * Check if a message matches any configured breakpoint.
     *
     * Uses MQTT topic matching rules for pattern comparison.
     */
    public function shouldBreakOn(Message $message): bool
    {
        if (empty($this->breakpoints)) {
            return false;
        }

        foreach ($this->breakpoints as $field => $pattern) {
            $value = $this->getMessageField($message, $field);
            if ($value !== null && TopicMatcher::matches($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get a field value from the message.
     */
    private function getMessageField(Message $message, string $field): ?string
    {
        return match (strtolower($field)) {
            'topic' => $this->extractTopic($message),
            'payload' => $this->extractPayloadString($message),
            'qos' => (string) $this->extractQos($message),
            'retain' => $this->extractRetain($message) ? 'true' : 'false',
            default => null,
        };
    }

    /**
     * Extract topic from message payload or metadata.
     */
    private function extractTopic(Message $message): string
    {
        if (is_array($message->payload) && isset($message->payload['topic'])) {
            return is_string($message->payload['topic']) ? $message->payload['topic'] : '';
        }
        return '';
    }

    /**
     * Extract payload as string from message.
     */
    private function extractPayloadString(Message $message): string
    {
        if (is_string($message->payload)) {
            return $message->payload;
        }
        if (is_array($message->payload) && isset($message->payload['payload'])) {
            if (is_string($message->payload['payload'])) {
                return $message->payload['payload'];
            }
            $encoded = json_encode($message->payload['payload']);
            return $encoded !== false ? $encoded : '';
        }
        $encoded = json_encode($message->payload);
        return $encoded !== false ? $encoded : '';
    }

    /**
     * Extract QoS level from message.
     */
    private function extractQos(Message $message): int
    {
        if (is_array($message->payload) && isset($message->payload['qos'])) {
            $qos = $message->payload['qos'];
            return is_numeric($qos) ? (int) $qos : 0;
        }
        if (isset($message->metadata['qos'])) {
            $qos = $message->metadata['qos'];
            return is_numeric($qos) ? (int) $qos : 0;
        }
        return 0;
    }

    /**
     * Extract retain flag from message.
     */
    private function extractRetain(Message $message): bool
    {
        if (is_array($message->payload) && isset($message->payload['retain'])) {
            return (bool) $message->payload['retain'];
        }
        if (isset($message->metadata['retain'])) {
            return (bool) $message->metadata['retain'];
        }
        return false;
    }

}
