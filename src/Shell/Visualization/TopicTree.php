<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Visualization;

use NashGao\InteractiveShell\Message\Message;

/**
 * ASCII topic tree visualization for MQTT messages.
 *
 * Renders a hierarchical tree view of MQTT topics with statistics and activity indicators.
 */
final class TopicTree
{
    /** @var array<string, TopicNode> */
    private array $roots = [];

    private int $totalMessages = 0;

    /**
     * Add a message to the topic tree.
     */
    public function addMessage(Message $message): void
    {
        // Extract topic from message payload
        if (!is_array($message->payload)) {
            return;
        }

        $topic = $message->payload['topic'] ?? null;
        if (!is_string($topic)) {
            return;
        }

        ++$this->totalMessages;

        // Split topic into parts and build tree
        $parts = explode('/', $topic);
        if ($parts[0] === '') {
            return;
        }

        // Get or create root node
        $rootName = $parts[0];
        if (!isset($this->roots[$rootName])) {
            $this->roots[$rootName] = new TopicNode($rootName);
        }

        $currentNode = $this->roots[$rootName];

        // Traverse/create path to leaf
        $partsCount = count($parts);
        for ($i = 1; $i < $partsCount; ++$i) {
            $currentNode = $currentNode->getChild($parts[$i]);
        }

        // Add message to the leaf node
        $payload = $message->payload['payload'] ?? $message->payload['message'] ?? null;
        $currentNode->addMessage($payload);
    }

    /**
     * Render the topic tree as ASCII art.
     */
    public function render(int $maxDepth = -1): string
    {
        if (empty($this->roots)) {
            return 'No topics in tree';
        }

        $topicCount = $this->countTopics();

        $output = [];
        $output[] = sprintf('Topic Tree (%d messages across %d topics)', $this->totalMessages, $topicCount);
        $output[] = str_repeat('═', 50);
        $output[] = '';

        // Render each root node
        $rootKeys = array_keys($this->roots);
        $rootCount = count($rootKeys);

        foreach ($rootKeys as $index => $rootName) {
            $isLast = ($index === $rootCount - 1);
            $node = $this->roots[$rootName];

            $output[] = $this->renderNode($node, '', $isLast, 0, $maxDepth);
        }

        $output[] = '';
        $output[] = 'Legend: ◆ = recent activity (last 60s)';

        return implode("\n", $output);
    }

    /**
     * Get statistics about the tree.
     *
     * @return array{total_messages: int, total_topics: int, roots: int}
     */
    public function getStats(): array
    {
        return [
            'total_messages' => $this->totalMessages,
            'total_topics' => $this->countTopics(),
            'roots' => count($this->roots),
        ];
    }

    /**
     * Clear the tree.
     */
    public function clear(): void
    {
        $this->roots = [];
        $this->totalMessages = 0;
    }

    /**
     * Render a node and its children recursively.
     */
    private function renderNode(
        TopicNode $node,
        string $prefix,
        bool $isLast,
        int $depth,
        int $maxDepth,
    ): string {
        if ($maxDepth !== -1 && $depth >= $maxDepth) {
            return '';
        }

        $output = [];

        // Determine the branch character
        $branch = $isLast ? '└── ' : '├── ';

        // Build the node display
        $nodeName = $node->name;
        $hasChildren = $node->hasChildren();

        // Add trailing slash for parent nodes
        if ($hasChildren) {
            $nodeName .= '/';
        }

        // Add message stats and activity indicator for leaf nodes
        $stats = '';
        if ($node->messageCount > 0) {
            $rate = $node->getMessageRate();
            $isActive = $node->isRecentlyActive();

            $parts = [];
            if ($node->lastValue !== null) {
                $parts[] = $node->lastValue;
            }

            $parts[] = sprintf('%d msgs', $node->messageCount);

            if ($rate >= 0.1) {
                $parts[] = sprintf('%.1f/s', $rate);
            }

            $stats = ' ◆ ' . implode(', ', $parts);

            if (!$isActive) {
                // Replace diamond with dash for inactive nodes
                $stats = ' — ' . implode(', ', $parts);
            }
        }

        $output[] = $prefix . $branch . $nodeName . $stats;

        // Render children
        if ($hasChildren) {
            $childPrefix = $prefix . ($isLast ? '    ' : '│   ');
            $childKeys = array_keys($node->children);
            $childCount = count($childKeys);

            foreach ($childKeys as $childIndex => $childName) {
                $isLastChild = ($childIndex === $childCount - 1);
                $childNode = $node->children[$childName];

                $childOutput = $this->renderNode(
                    $childNode,
                    $childPrefix,
                    $isLastChild,
                    $depth + 1,
                    $maxDepth,
                );

                if ($childOutput !== '') {
                    $output[] = $childOutput;
                }
            }
        }

        return implode("\n", $output);
    }

    /**
     * Count total number of topics (leaf nodes).
     */
    private function countTopics(): int
    {
        $count = 0;

        foreach ($this->roots as $root) {
            $count += $this->countNodeTopics($root);
        }

        return $count;
    }

    /**
     * Count topics in a node recursively.
     */
    private function countNodeTopics(TopicNode $node): int
    {
        if (!$node->hasChildren()) {
            // Leaf node
            return $node->messageCount > 0 ? 1 : 0;
        }

        $count = 0;
        foreach ($node->children as $child) {
            $count += $this->countNodeTopics($child);
        }

        return $count;
    }
}
