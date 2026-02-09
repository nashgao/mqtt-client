<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Test\Cases\Shell\Visualization;

use DateTimeImmutable;
use NashGao\InteractiveShell\Message\Message;
use Nashgao\MQTT\Shell\Config\ShellConfig;
use Nashgao\MQTT\Shell\Visualization\FlowTimeline;
use Nashgao\MQTT\Test\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * SPECIFICATION TESTS for FlowTimeline.
 *
 * Tests timeline visualization behavior through rendered output.
 * Focuses on what users SEE rather than internal entry structures.
 *
 * @internal
 */
#[CoversNothing]
class FlowTimelineTest extends AbstractTestCase
{
    private FlowTimeline $timeline;

    protected function setUp(): void
    {
        parent::setUp();
        $this->timeline = new FlowTimeline(ShellConfig::fromArray(['flowTimelineLimit' => 100]));
    }

    /**
     * SPECIFICATION: Empty timeline should display "No messages" message.
     */
    public function testEmptyTimelineShowsNoMessagesMessage(): void
    {
        $output = $this->timeline->render();

        $this->assertStringContainsString('No messages in timeline', $output);
    }

    /**
     * SPECIFICATION: Timeline should display header with title.
     */
    public function testTimelineDisplaysHeader(): void
    {
        $this->timeline->addMessage($this->createDataMessage('test/topic', 'value'));
        $output = $this->timeline->render();

        $this->assertStringContainsString('Message Flow Timeline', $output);
    }

    /**
     * SPECIFICATION: Timeline should show topic and payload for each message.
     */
    public function testTimelineShowsTopicAndPayload(): void
    {
        $this->timeline->addMessage($this->createDataMessage('sensors/temperature', '25.5'));

        $output = $this->timeline->render();

        $this->assertStringContainsString('sensors/temperature', $output);
        $this->assertStringContainsString('25.5', $output);
    }

    /**
     * SPECIFICATION: Timeline should show incoming direction indicator.
     */
    public function testTimelineShowsIncomingDirectionIndicator(): void
    {
        $message = new Message(
            type: 'data',
            payload: [
                'topic' => 'test/in',
                'payload' => 'incoming',
            ],
            source: 'mqtt',
            timestamp: new DateTimeImmutable(),
        );

        $this->timeline->addMessage($message);
        $output = $this->timeline->render();

        $this->assertStringContainsString('──▶ IN', $output);
    }

    /**
     * SPECIFICATION: Timeline should show outgoing direction indicator.
     */
    public function testTimelineShowsOutgoingDirectionIndicator(): void
    {
        $message = new Message(
            type: 'publish',
            payload: [
                'topic' => 'test/out',
                'payload' => 'outgoing',
            ],
            source: 'mqtt',
            timestamp: new DateTimeImmutable(),
        );

        $this->timeline->addMessage($message);
        $output = $this->timeline->render();

        $this->assertStringContainsString('◀── OUT', $output);
    }

    /**
     * SPECIFICATION: Timeline should show timestamp for each message.
     */
    public function testTimelineShowsTimestamp(): void
    {
        $timestamp = new DateTimeImmutable('2024-01-15 14:30:45.123456');
        $message = new Message(
            type: 'data',
            payload: [
                'topic' => 'test/time',
                'payload' => 'value',
            ],
            source: 'mqtt',
            timestamp: $timestamp,
        );

        $this->timeline->addMessage($message);
        $output = $this->timeline->render();

        // Should show time in H:i:s format
        $this->assertStringContainsString('14:30:45', $output);
    }

    /**
     * SPECIFICATION: Timeline should highlight rule matches.
     */
    public function testTimelineHighlightsRuleMatches(): void
    {
        $this->timeline->addMessage(
            $this->createDataMessage('alerts/critical', 'alert message'),
            'critical-alert-rule'
        );

        $output = $this->timeline->render();

        $this->assertStringContainsString('[RULE: critical-alert-rule]', $output);
        $this->assertStringContainsString('⚡', $output);
    }

    /**
     * SPECIFICATION: Timeline should respect display limit parameter.
     */
    public function testTimelineRespectsDisplayLimit(): void
    {
        // Add 20 messages
        for ($i = 0; $i < 20; ++$i) {
            $this->timeline->addMessage($this->createDataMessage("test/message{$i}", "value{$i}"));
        }

        $output = $this->timeline->render(5);

        // Should only show last 5 entries (15-19)
        $this->assertStringContainsString('message19', $output);
        $this->assertStringContainsString('message15', $output);

        // Earlier messages should not appear
        $this->assertStringNotContainsString('message14', $output);
        $this->assertStringNotContainsString('message0', $output);
    }

    /**
     * SPECIFICATION: Timeline should filter by exact topic.
     */
    public function testTimelineFiltersByExactTopic(): void
    {
        $this->timeline->addMessage($this->createDataMessage('sensors/temperature', '25'));
        $this->timeline->addMessage($this->createDataMessage('sensors/humidity', '60'));
        $this->timeline->addMessage($this->createDataMessage('alerts/critical', 'alert'));

        $output = $this->timeline->render(10, 'sensors/temperature');

        $this->assertStringContainsString('sensors/temperature', $output);
        $this->assertStringNotContainsString('sensors/humidity', $output);
        $this->assertStringNotContainsString('alerts/critical', $output);
    }

    /**
     * SPECIFICATION: Timeline should filter with single-level wildcard.
     */
    public function testTimelineFiltersWithSingleLevelWildcard(): void
    {
        $this->timeline->addMessage($this->createDataMessage('sensors/room1/temperature', '22'));
        $this->timeline->addMessage($this->createDataMessage('sensors/room2/temperature', '24'));
        $this->timeline->addMessage($this->createDataMessage('alerts/critical', 'alert'));

        $output = $this->timeline->render(10, 'sensors/+/temperature');

        $this->assertStringContainsString('room1/temperature', $output);
        $this->assertStringContainsString('room2/temperature', $output);
        $this->assertStringNotContainsString('alerts/critical', $output);
    }

    /**
     * SPECIFICATION: Timeline should filter with multi-level wildcard.
     */
    public function testTimelineFiltersWithMultiLevelWildcard(): void
    {
        $this->timeline->addMessage($this->createDataMessage('sensors/room1/temperature', '22'));
        $this->timeline->addMessage($this->createDataMessage('sensors/room1/humidity', '45'));
        $this->timeline->addMessage($this->createDataMessage('alerts/critical', 'alert'));

        $output = $this->timeline->render(10, 'sensors/#');

        $this->assertStringContainsString('room1/temperature', $output);
        $this->assertStringContainsString('room1/humidity', $output);
        $this->assertStringNotContainsString('alerts/critical', $output);
    }

    /**
     * SPECIFICATION: Timeline should show message when filter has no matches.
     */
    public function testTimelineShowsNoMatchesMessageWhenFilterHasNoMatches(): void
    {
        $this->timeline->addMessage($this->createDataMessage('sensors/temperature', '25'));

        $output = $this->timeline->render(10, 'alerts/#');

        $this->assertStringContainsString('No messages matching topic filter', $output);
        $this->assertStringContainsString('alerts/#', $output);
    }

    /**
     * SPECIFICATION: Timeline should respect max entries limit.
     */
    public function testTimelineRespectsMaxEntriesLimit(): void
    {
        $limitedTimeline = new FlowTimeline(ShellConfig::fromArray(['flowTimelineLimit' => 5]));

        // Add 10 messages
        for ($i = 0; $i < 10; ++$i) {
            $limitedTimeline->addMessage($this->createDataMessage("test/msg{$i}", "value{$i}"));
        }

        $output = $limitedTimeline->render(10);

        // Should keep the last 5 entries
        $this->assertStringContainsString('msg9', $output);
        $this->assertStringContainsString('msg5', $output);
        $this->assertStringNotContainsString('msg4', $output);
        $this->assertStringNotContainsString('msg0', $output);
    }

    /**
     * SPECIFICATION: Clear should reset timeline to empty state.
     */
    public function testClearResetsTimelineToEmptyState(): void
    {
        $this->timeline->addMessage($this->createDataMessage('test/topic', 'value'));

        // Verify timeline has content
        $output = $this->timeline->render();
        $this->assertStringContainsString('test/topic', $output);

        // Clear the timeline
        $this->timeline->clear();

        // Timeline should now be empty
        $output = $this->timeline->render();
        $this->assertStringContainsString('No messages in timeline', $output);
    }

    /**
     * SPECIFICATION: Timeline should truncate long payloads.
     */
    public function testTimelineTruncatesLongPayloads(): void
    {
        $longPayload = str_repeat('a', 100);
        $this->timeline->addMessage($this->createDataMessage('test/long', $longPayload));

        $output = $this->timeline->render();

        // Should truncate and show ellipsis
        $this->assertStringContainsString('...', $output);
    }

    /**
     * SPECIFICATION: Timeline should handle non-array payloads.
     */
    public function testTimelineHandlesNonArrayPayloads(): void
    {
        $message = Message::data(
            payload: 'simple string payload',
            source: 'mqtt',
        );

        $this->timeline->addMessage($message);
        $output = $this->timeline->render();

        $this->assertStringContainsString('simple string payload', $output);
    }

    /**
     * SPECIFICATION: Timeline should display legend explaining symbols.
     */
    public function testTimelineDisplaysLegend(): void
    {
        $this->timeline->addMessage($this->createDataMessage('test/topic', 'value'));

        $output = $this->timeline->render();

        $this->assertStringContainsString('──▶ IN = Incoming', $output);
        $this->assertStringContainsString('◀── OUT = Outgoing', $output);
        $this->assertStringContainsString('⚡ = Rule triggered', $output);
    }

    /**
     * SPECIFICATION: Timeline should use default limit of 10 entries.
     */
    public function testTimelineUsesDefaultLimitOfTenEntries(): void
    {
        // Add 15 messages
        for ($i = 0; $i < 15; ++$i) {
            $this->timeline->addMessage($this->createDataMessage("test/msg{$i}", "value{$i}"));
        }

        $output = $this->timeline->render(); // Default limit is 10

        // Should show last 10 entries
        $this->assertStringContainsString('msg14', $output);
        $this->assertStringContainsString('msg5', $output);
        $this->assertStringNotContainsString('msg4', $output);
    }

    /**
     * SPECIFICATION: Timeline should show mixed incoming and outgoing messages.
     */
    public function testTimelineShowsMixedIncomingAndOutgoingMessages(): void
    {
        $incoming = new Message(
            type: 'data',
            payload: [
                'topic' => 'test/in',
                'payload' => 'received',
            ],
            source: 'mqtt',
            timestamp: new DateTimeImmutable(),
        );

        $outgoing = new Message(
            type: 'publish',
            payload: [
                'topic' => 'test/out',
                'payload' => 'sent',
            ],
            source: 'mqtt',
            timestamp: new DateTimeImmutable(),
        );

        $this->timeline->addMessage($incoming);
        $this->timeline->addMessage($outgoing);

        $output = $this->timeline->render();

        $this->assertStringContainsString('──▶ IN', $output);
        $this->assertStringContainsString('◀── OUT', $output);
        $this->assertStringContainsString('test/in', $output);
        $this->assertStringContainsString('test/out', $output);
    }

    /**
     * SPECIFICATION: Messages without rule match should not show rule indicator.
     */
    public function testMessagesWithoutRuleMatchDoNotShowRuleIndicator(): void
    {
        $this->timeline->addMessage($this->createDataMessage('test/topic', 'value'));

        $output = $this->timeline->render();

        $this->assertStringNotContainsString('[RULE:', $output);
    }

    /**
     * Helper to create a data message.
     */
    private function createDataMessage(string $topic, mixed $payload): Message
    {
        return Message::data(
            payload: [
                'topic' => $topic,
                'payload' => $payload,
            ],
            source: 'mqtt',
        );
    }
}
