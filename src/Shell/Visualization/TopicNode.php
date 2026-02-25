<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Visualization;

/**
 * Represents a node in the MQTT topic tree.
 *
 * Tracks message statistics and child topics for hierarchical visualization.
 */
final class TopicNode
{
    /** @var array<string, TopicNode> */
    public array $children = [];

    public function __construct(
        public string $name,
        public int $messageCount = 0,
        public ?string $lastValue = null,
        public float $lastUpdate = 0.0,
    ) {}

    /**
     * Check if this node has recent activity within the threshold.
     */
    public function isRecentlyActive(float $threshold = 60.0): bool
    {
        if ($this->lastUpdate === 0.0) {
            return false;
        }

        return (microtime(true) - $this->lastUpdate) <= $threshold;
    }

    /**
     * Add a message to this node.
     */
    public function addMessage(mixed $payload): void
    {
        ++$this->messageCount;
        $this->lastUpdate = microtime(true);

        // Store the last value (truncate if too long)
        if (is_string($payload)) {
            $this->lastValue = mb_strlen($payload) > 100
                ? mb_substr($payload, 0, 97) . '...'
                : $payload;
        } elseif (is_array($payload)) {
            $json = json_encode($payload);
            $this->lastValue = $json !== false
                ? (mb_strlen($json) > 100 ? mb_substr($json, 0, 97) . '...' : $json)
                : '[array]';
        } elseif (is_scalar($payload)) {
            $this->lastValue = (string) $payload;
        } else {
            $this->lastValue = '[unknown]';
        }
    }

    /**
     * Get or create a child node.
     */
    public function getChild(string $name): TopicNode
    {
        if (!isset($this->children[$name])) {
            $this->children[$name] = new TopicNode($name);
        }

        return $this->children[$name];
    }

    /**
     * Check if this node has children.
     */
    public function hasChildren(): bool
    {
        return count($this->children) > 0;
    }

    /**
     * Get the rate of messages per second over the last 60 seconds.
     */
    public function getMessageRate(): float
    {
        if ($this->messageCount === 0 || $this->lastUpdate === 0.0) {
            return 0.0;
        }

        $elapsed = microtime(true) - $this->lastUpdate;

        // If very recent, estimate based on message count
        if ($elapsed < 1.0) {
            return (float) $this->messageCount;
        }

        // Calculate rate (messages per second)
        return $this->messageCount / min($elapsed, 60.0);
    }
}
