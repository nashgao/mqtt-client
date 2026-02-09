<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Visualization;

use NashGao\InteractiveShell\Message\Message;
use Nashgao\MQTT\Shell\Config\ShellConfig;
use Nashgao\MQTT\Shell\Mqtt\TopicMatcher;

/**
 * Message flow timeline visualization.
 *
 * Tracks and displays a chronological view of message flow with direction indicators.
 */
final class FlowTimeline
{
    /** @var array<FlowEntry> */
    private array $entries = [];

    private readonly int $maxEntries;

    private readonly int $defaultRenderCount;

    public function __construct(?ShellConfig $config = null)
    {
        $config ??= ShellConfig::default();
        $this->maxEntries = $config->flowTimelineLimit;
        $this->defaultRenderCount = $config->defaultFlowRenderCount;
    }

    /**
     * Add a message to the timeline.
     */
    public function addMessage(Message $message, ?string $matchedRule = null): void
    {
        $entry = new FlowEntry(
            message: $message,
            matchedRule: $matchedRule,
            timestamp: $message->timestamp->getTimestamp() + ($message->timestamp->format('u') / 1000000),
        );

        $this->entries[] = $entry;

        // Trim if over limit
        if (count($this->entries) > $this->maxEntries) {
            $this->entries = array_slice($this->entries, -$this->maxEntries);
        }
    }

    /**
     * Render the timeline.
     */
    public function render(?int $limit = null, ?string $topicFilter = null): string
    {
        if (empty($this->entries)) {
            return 'No messages in timeline';
        }

        // Use default render count if limit not specified
        $limit ??= $this->defaultRenderCount;

        // Filter entries if needed
        $entries = $this->filterEntries($topicFilter);

        // Get last N entries
        $entries = array_slice($entries, -$limit);

        if (empty($entries)) {
            return $topicFilter !== null
                ? sprintf('No messages matching topic filter: %s', $topicFilter)
                : 'No messages in timeline';
        }

        $output = [];
        $output[] = 'Message Flow Timeline';
        $output[] = str_repeat('═', 50);
        $output[] = '';

        foreach ($entries as $entry) {
            $output[] = $this->renderEntry($entry);

            // Add rule match info if present
            if ($entry->hasMatchedRule()) {
                $output[] = sprintf('    └─ [RULE: %s] ⚡ Rule triggered', $entry->matchedRule);
            }
        }

        $output[] = '';
        $output[] = '──▶ IN = Incoming   ◀── OUT = Outgoing   ⚡ = Rule triggered';

        return implode("\n", $output);
    }

    /**
     * Clear the timeline.
     */
    public function clear(): void
    {
        $this->entries = [];
    }

    /**
     * Get all entries.
     *
     * @return array<FlowEntry>
     */
    public function getEntries(): array
    {
        return $this->entries;
    }

    /**
     * Render a single entry.
     */
    private function renderEntry(FlowEntry $entry): string
    {
        $timestamp = $entry->message->timestamp->format('H:i:s.u');
        // Truncate microseconds to 3 digits
        $timestamp = preg_replace('/(\.\d{3})\d+/', '$1', $timestamp) ?? $timestamp;

        $direction = $entry->isIncoming() ? '──▶ IN ' : '◀── OUT';
        $topic = $entry->getTopic() ?? 'unknown';
        $payload = $entry->getDisplayPayload();

        // Pad topic to align payloads
        $paddedTopic = str_pad($topic, 25);

        return sprintf('%s %s %s %s', $timestamp, $direction, $paddedTopic, $payload);
    }

    /**
     * Filter entries by topic pattern.
     *
     * @return array<FlowEntry>
     */
    private function filterEntries(?string $topicFilter): array
    {
        if ($topicFilter === null) {
            return $this->entries;
        }

        $filtered = [];

        foreach ($this->entries as $entry) {
            $topic = $entry->getTopic();
            if ($topic !== null && TopicMatcher::matches($topicFilter, $topic)) {
                $filtered[] = $entry;
            }
        }

        return $filtered;
    }
}
