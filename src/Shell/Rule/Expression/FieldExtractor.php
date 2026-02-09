<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Rule\Expression;

use NashGao\InteractiveShell\Message\Message;

/**
 * Extract values from message context using dot notation paths
 */
final class FieldExtractor
{
    /**
     * Extract value from context using dot notation path
     *
     * @param array<string, mixed> $context Message context
     * @param string $path Dot notation path (e.g., 'payload.temperature')
     * @return mixed Extracted value or null if path doesn't exist
     */
    public static function extract(array $context, string $path): mixed
    {
        $parts = explode('.', $path);
        $current = $context;

        foreach ($parts as $part) {
            if (!is_array($current) || !array_key_exists($part, $current)) {
                return null;
            }
            $current = $current[$part];
        }

        return $current;
    }

    /**
     * Build context array from MQTT message
     *
     * @param Message $message MQTT message to extract context from
     * @return array<string, mixed> Context with all available fields
     */
    public static function buildContext(Message $message): array
    {
        // Extract MQTT-specific fields from payload
        $payload = is_array($message->payload) ? $message->payload : [];
        $topic = isset($payload['topic']) && is_string($payload['topic']) ? $payload['topic'] : '';
        $rawMessage = $payload['message'] ?? $payload;
        $qos = isset($payload['qos']) && is_numeric($payload['qos']) ? (int) $payload['qos'] : 0;

        // Parse message if it's JSON string
        if (is_string($rawMessage)) {
            $decoded = json_decode($rawMessage, true);
            $messageData = json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : ['raw' => $rawMessage];
        } else {
            $messageData = is_array($rawMessage) ? $rawMessage : ['value' => $rawMessage];
        }

        // String versions for grep (pattern matching against text)
        $payloadJson = json_encode($messageData, JSON_UNESCAPED_UNICODE) ?: '';
        // If raw message is a string, use it; otherwise fall back to JSON encoding
        $messageRaw = is_string($rawMessage) ? $rawMessage : $payloadJson;

        return [
            'type' => $message->type,
            'topic' => $topic,
            'payload' => $messageData,           // Array for payload.field access
            'message_raw' => $messageRaw,        // Original string for grep
            'payload_json' => $payloadJson,      // JSON string for grep
            'qos' => $qos,
            'pool' => isset($payload['pool']) && is_string($payload['pool']) ? $payload['pool'] : 'default',
            'retain' => isset($message->metadata['retain']) && is_bool($message->metadata['retain']) ? $message->metadata['retain'] : false,
            'dup' => isset($message->metadata['dup']) && is_bool($message->metadata['dup']) ? $message->metadata['dup'] : false,
            'direction' => isset($message->metadata['direction']) && is_string($message->metadata['direction']) ? $message->metadata['direction'] : 'unknown',
            'timestamp' => $message->timestamp->format(\DateTimeInterface::ATOM),
        ];
    }
}
