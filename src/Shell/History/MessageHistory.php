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
     * @return array<int, Message> Messages matching the topic pattern
     */
    public function search(string $topic): array
    {
        if ($this->topicMatcher === null) {
            return [];
        }

        $matches = [];
        foreach ($this->messages as $message) {
            // Extract topic from message payload
            $messageTopic = '';
            if (is_array($message->payload) && isset($message->payload['topic'])) {
                $messageTopic = is_string($message->payload['topic']) ? $message->payload['topic'] : '';
            }

            if ($messageTopic !== '' && $this->topicMatcher->matches($topic, $messageTopic)) {
                $matches[] = $message;
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
}
