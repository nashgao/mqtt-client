<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Visualization;

use NashGao\InteractiveShell\Message\Message;

/**
 * Represents an entry in the message flow timeline.
 *
 * Tracks a message along with optional rule matching information.
 */
final readonly class FlowEntry
{
    public function __construct(
        public Message $message,
        public ?string $matchedRule = null,
        public float $timestamp = 0.0,
    ) {}

    /**
     * Get the topic from the message.
     */
    public function getTopic(): ?string
    {
        if (!is_array($this->message->payload)) {
            return null;
        }

        $topic = $this->message->payload['topic'] ?? null;
        return is_string($topic) ? $topic : null;
    }

    /**
     * Get the payload for display.
     */
    public function getDisplayPayload(): string
    {
        if (!is_array($this->message->payload)) {
            if (is_string($this->message->payload)) {
                return $this->message->payload;
            }

            if (is_scalar($this->message->payload)) {
                return (string) $this->message->payload;
            }

            return '[unknown]';
        }

        $payload = $this->message->payload['payload']
            ?? $this->message->payload['message']
            ?? $this->message->payload;

        if (is_string($payload)) {
            return mb_strlen($payload) > 80
                ? mb_substr($payload, 0, 77) . '...'
                : $payload;
        }

        if (is_array($payload)) {
            $json = json_encode($payload);
            if ($json === false) {
                return '[array]';
            }

            return mb_strlen($json) > 80
                ? mb_substr($json, 0, 77) . '...'
                : $json;
        }

        if (is_scalar($payload)) {
            return (string) $payload;
        }

        return '[unknown]';
    }

    /**
     * Check if this is an incoming or outgoing message.
     */
    public function isIncoming(): bool
    {
        return $this->message->type === 'data';
    }

    /**
     * Check if this entry has a matched rule.
     */
    public function hasMatchedRule(): bool
    {
        return $this->matchedRule !== null;
    }
}
