<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\History;

use NashGao\InteractiveShell\Message\Message;

/**
 * MQTT-specific message history with automatic topic matcher configuration.
 *
 * Provides circular buffer storage for messages with topic-based search
 * capabilities using MQTT wildcard pattern matching (+ and #).
 */
final class MessageHistory
{
    /**
     * @var array<int, Message> Circular buffer of messages
     */
    private array $messages = [];

    /**
     * @var int Maximum number of messages to store
     */
    private int $maxMessages;

    /**
     * @var TopicMatcherInterface|null Topic pattern matcher
     */
    private ?TopicMatcherInterface $topicMatcher = null;

    public function __construct(int $maxMessages = 500)
    {
        $this->maxMessages = $maxMessages;
        $this->setTopicMatcher(new MqttTopicMatcherAdapter());
    }

    /**
     * Add a message to the history buffer.
     *
     * @param Message $message The message to add
     */
    public function add(Message $message): void
    {
        $this->messages[] = $message;

        // Maintain circular buffer by removing oldest messages
        if (count($this->messages) > $this->maxMessages) {
            array_shift($this->messages);
        }
    }

    /**
     * Get the last N messages from the history.
     *
     * @param int $count Number of messages to retrieve
     * @return array<int, Message> Array of messages (most recent last)
     */
    public function getLast(int $count): array
    {
        return array_slice($this->messages, -$count);
    }

    /**
     * Get the most recent message.
     */
    public function getLatest(): ?Message
    {
        if (empty($this->messages)) {
            return null;
        }
        return $this->messages[array_key_last($this->messages)];
    }

    /**
     * Get the ID (index) of the most recent message.
     */
    public function getLatestId(): ?int
    {
        if (empty($this->messages)) {
            return null;
        }
        return array_key_last($this->messages);
    }

    /**
     * Get a message by its ID (index).
     */
    public function get(int $id): ?Message
    {
        return $this->messages[$id] ?? null;
    }

    /**
     * Get the total count of messages in history.
     *
     * @return int Number of messages stored
     */
    public function count(): int
    {
        return count($this->messages);
    }

    /**
     * Search messages by topic pattern.
     *
     * @param string $topic Topic pattern to search for (supports MQTT wildcards)
     * @param int|null $limit Maximum number of results (null for all)
     * @return array<int, Message> Messages matching the topic pattern
     */
    public function search(string $topic, ?int $limit = null): array
    {
        if ($this->topicMatcher === null) {
            return [];
        }

        $matches = [];
        foreach ($this->messages as $id => $message) {
            // Extract topic from message payload
            $messageTopic = '';
            if (is_array($message->payload) && isset($message->payload['topic'])) {
                $messageTopic = is_string($message->payload['topic']) ? $message->payload['topic'] : '';
            }

            if ($messageTopic !== '' && $this->topicMatcher->matches($topic, $messageTopic)) {
                $matches[$id] = $message;
                if ($limit !== null && count($matches) >= $limit) {
                    break;
                }
            }
        }

        return $matches;
    }

    /**
     * Clear all messages from history.
     */
    public function clear(): void
    {
        $this->messages = [];
    }

    /**
     * Set the topic matcher strategy.
     *
     * @param TopicMatcherInterface $matcher The topic matcher to use
     */
    public function setTopicMatcher(TopicMatcherInterface $matcher): void
    {
        $this->topicMatcher = $matcher;
    }

    /**
     * Get messages by topic pattern (alias for search with preserved IDs).
     *
     * @param string $topicPattern Topic pattern (supports MQTT wildcards)
     * @param int|null $limit Maximum number of results
     * @return array<int, Message> Messages keyed by their history ID
     */
    public function getByTopic(string $topicPattern, ?int $limit = null): array
    {
        return $this->search($topicPattern, $limit);
    }

    /**
     * Export messages as array.
     *
     * @param int|null $limit Maximum number of messages to export
     * @return array<int, array<string, mixed>> Exported messages
     */
    public function export(?int $limit = null): array
    {
        $messages = $limit !== null ? array_slice($this->messages, -$limit, null, true) : $this->messages;
        $exported = [];

        foreach ($messages as $id => $message) {
            $exported[$id] = [
                'id' => $id,
                'timestamp' => $message->timestamp,
                'payload' => $message->payload,
                'metadata' => $message->metadata,
            ];
        }

        return $exported;
    }
}
