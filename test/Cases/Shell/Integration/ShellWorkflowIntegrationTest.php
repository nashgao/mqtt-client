<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Test\Cases\Shell\Integration;

use Nashgao\MQTT\Shell\Config\ShellConfig;
use Nashgao\MQTT\Shell\Formatter\MqttMessageFormatter;
use Nashgao\MQTT\Shell\Stats\StatsCollector;
use Nashgao\MQTT\Shell\Visualization\FlowTimeline;
use Nashgao\MQTT\Shell\Visualization\TopicTree;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Integration tests for complete shell workflows.
 *
 * SPECIFICATION TESTS: These tests verify user sessions:
 * Command → Processing → Output
 *
 * @internal
 */
#[CoversNothing]
class ShellWorkflowIntegrationTest extends AbstractIntegrationTestCase
{
    /**
     * SPECIFICATION: Stats should reflect messages processed through the shell.
     */
    public function testStatsReflectProcessedMessages(): void
    {
        // Given: A stats collector and messages
        $stats = new StatsCollector();

        // When: Messages are processed
        $messages = [
            $this->createIncomingMessage('sensors/temp', ['v' => 23], 1),
            $this->createIncomingMessage('sensors/temp', ['v' => 24], 1),
            $this->createOutgoingMessage('commands/hvac', ['action' => 'on'], 0),
        ];

        foreach ($messages as $msg) {
            $stats->record($msg);
        }

        // Then: Stats reflect the messages
        $this->assertEquals(3, $stats->getTotalMessages());
        $this->assertEquals(2, $stats->getIncomingMessages());
        $this->assertEquals(1, $stats->getOutgoingMessages());

        // And: Display output shows the correct data
        $display = $stats->formatDisplay();
        $this->assertStringContainsString('Messages (total)', $display);
        $this->assertStringContainsString('3', $display);
        $this->assertStringContainsString('Incoming:', $display);
        $this->assertStringContainsString('Outgoing:', $display);
    }

    /**
     * SPECIFICATION: Topic tree should visualize message hierarchy.
     */
    public function testTopicTreeVisualizesMessageHierarchy(): void
    {
        // Given: A topic tree
        $tree = new TopicTree();

        // When: Messages from different topics are added
        $messages = [
            $this->createDataMessage('home/kitchen/temp', 23),
            $this->createDataMessage('home/kitchen/humidity', 45),
            $this->createDataMessage('home/bedroom/temp', 21),
            $this->createDataMessage('home/garage/door', 'closed'),
        ];

        foreach ($messages as $msg) {
            $tree->addMessage($msg);
        }

        // Then: Tree renders hierarchical structure
        $output = $tree->render();

        $this->assertStringContainsString('Topic Tree', $output);
        $this->assertStringContainsString('4 messages', $output);
        $this->assertStringContainsString('home/', $output);
        $this->assertStringContainsString('kitchen/', $output);
        $this->assertStringContainsString('bedroom/', $output);
        $this->assertStringContainsString('garage/', $output);
        $this->assertStringContainsString('├──', $output);
        $this->assertStringContainsString('└──', $output);
    }

    /**
     * SPECIFICATION: Topic tree should show message counts per topic.
     */
    public function testTopicTreeShowsMessageCountsPerTopic(): void
    {
        // Given: A topic tree
        $tree = new TopicTree();

        // When: Multiple messages to same topic are added
        for ($i = 0; $i < 5; $i++) {
            $tree->addMessage($this->createDataMessage('sensors/temp', $i));
        }

        // Then: Output shows message count
        $output = $tree->render();
        $this->assertStringContainsString('5 msgs', $output);
    }

    /**
     * SPECIFICATION: Flow timeline should show message flow with direction.
     */
    public function testFlowTimelineShowsMessageFlowWithDirection(): void
    {
        // Given: A flow timeline
        $timeline = new FlowTimeline();

        // When: Messages flow in and out
        $timeline->addMessage($this->createDataMessage('sensors/temp', ['v' => 23]));
        $timeline->addMessage($this->createOutgoingMessage('commands/hvac', ['action' => 'cool']));

        // Then: Timeline shows direction indicators
        $output = $timeline->render();

        $this->assertStringContainsString('Message Flow Timeline', $output);
        $this->assertStringContainsString('──▶ IN', $output);
        $this->assertStringContainsString('◀── OUT', $output);
        $this->assertStringContainsString('sensors/temp', $output);
        $this->assertStringContainsString('commands/hvac', $output);
    }

    /**
     * SPECIFICATION: Flow timeline should highlight rule matches.
     */
    public function testFlowTimelineHighlightsRuleMatches(): void
    {
        // Given: A flow timeline with rule match
        $timeline = new FlowTimeline();

        // When: Message is added with matched rule
        $timeline->addMessage(
            $this->createDataMessage('sensors/temp', ['v' => 35]),
            'temp_alert'
        );

        // Then: Output shows rule match highlight
        $output = $timeline->render();
        $this->assertStringContainsString('⚡', $output);
        $this->assertStringContainsString('[RULE: temp_alert]', $output);
    }

    /**
     * SPECIFICATION: Flow timeline should filter by topic pattern.
     */
    public function testFlowTimelineFiltersMessagesByTopicPattern(): void
    {
        // Given: A timeline with mixed messages
        $timeline = new FlowTimeline();
        $timeline->addMessage($this->createDataMessage('sensors/room1/temp', ['v' => 22]));
        $timeline->addMessage($this->createDataMessage('sensors/room2/temp', ['v' => 24]));
        $timeline->addMessage($this->createDataMessage('alerts/critical', ['msg' => 'error']));

        // When: Filtering by sensors topic pattern
        $output = $timeline->render(10, 'sensors/#');

        // Then: Only sensor messages appear
        $this->assertStringContainsString('room1/temp', $output);
        $this->assertStringContainsString('room2/temp', $output);
        $this->assertStringNotContainsString('alerts/critical', $output);
    }

    /**
     * SPECIFICATION: Message formatter should format messages in different styles.
     */
    public function testFormatterFormatsMessagesInDifferentStyles(): void
    {
        // Given: A formatter and message
        $formatter = new MqttMessageFormatter();
        $formatter->setColorEnabled(false);
        $message = $this->createIncomingMessage('sensors/temp', ['value' => 25.5], 1);

        // When: Formatting as compact
        $formatter->setFormat('compact');
        $compact = $formatter->format($message);

        // Then: Compact format shows essential info
        $this->assertStringContainsString('sensors/temp', $compact);
        $this->assertStringContainsString('IN', $compact);

        // When: Formatting as vertical
        $formatter->setFormat('vertical');
        $vertical = $formatter->format($message);

        // Then: Vertical format shows detailed fields
        $this->assertStringContainsString('topic:', $vertical);
        $this->assertStringContainsString('direction:', $vertical);
        $this->assertStringContainsString('qos:', $vertical);
        $this->assertStringContainsString('payload:', $vertical);

        // When: Formatting as JSON
        $formatter->setFormat('json');
        $json = $formatter->format($message);

        // Then: JSON format is valid JSON
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertEquals('sensors/temp', $decoded['topic']);
        $this->assertEquals(1, $decoded['qos']);
    }

    /**
     * SPECIFICATION: Stats should track QoS distribution.
     */
    public function testStatsTrackQosDistribution(): void
    {
        // Given: A stats collector
        $stats = new StatsCollector();

        // When: Messages with different QoS levels arrive
        $stats->record($this->createIncomingMessage('test', 'msg0', 0));
        $stats->record($this->createIncomingMessage('test', 'msg1', 1));
        $stats->record($this->createIncomingMessage('test', 'msg1b', 1));
        $stats->record($this->createIncomingMessage('test', 'msg2', 2));
        $stats->record($this->createIncomingMessage('test', 'msg2b', 2));
        $stats->record($this->createIncomingMessage('test', 'msg2c', 2));

        // Then: QoS distribution is tracked
        $distribution = $stats->getQosDistribution();
        $this->assertEquals(1, $distribution[0]);
        $this->assertEquals(2, $distribution[1]);
        $this->assertEquals(3, $distribution[2]);

        // And: Display shows QoS distribution
        $display = $stats->formatDisplay();
        $this->assertStringContainsString('QoS Distribution:', $display);
        $this->assertStringContainsString('QoS 0:', $display);
        $this->assertStringContainsString('QoS 1:', $display);
        $this->assertStringContainsString('QoS 2:', $display);
    }

    /**
     * SPECIFICATION: Stats should track top topics by message count.
     */
    public function testStatsTrackTopTopicsByMessageCount(): void
    {
        // Given: A stats collector
        $stats = new StatsCollector();

        // When: Messages to different topics arrive
        for ($i = 0; $i < 10; $i++) {
            $stats->record($this->createIncomingMessage('sensors/temperature', "msg{$i}"));
        }
        for ($i = 0; $i < 5; $i++) {
            $stats->record($this->createIncomingMessage('sensors/humidity', "msg{$i}"));
        }
        for ($i = 0; $i < 3; $i++) {
            $stats->record($this->createIncomingMessage('sensors/pressure', "msg{$i}"));
        }

        // Then: Top topics are ranked correctly
        $topTopics = $stats->getTopTopics(3);
        $topics = array_keys($topTopics);

        $this->assertEquals('sensors/temperature', $topics[0]);
        $this->assertEquals('sensors/humidity', $topics[1]);
        $this->assertEquals('sensors/pressure', $topics[2]);

        $this->assertEquals(10, $topTopics['sensors/temperature']);
        $this->assertEquals(5, $topTopics['sensors/humidity']);
        $this->assertEquals(3, $topTopics['sensors/pressure']);
    }

    /**
     * SPECIFICATION: Stats should track latency metrics.
     */
    public function testStatsTrackLatencyMetrics(): void
    {
        // Given: A stats collector with latency measurements
        $stats = new StatsCollector();
        $latencies = [5.0, 10.0, 15.0, 20.0, 25.0, 100.0];

        foreach ($latencies as $latency) {
            $stats->recordLatency($latency);
        }

        // Then: Latency statistics are available
        $this->assertEquals(5.0, $stats->getMinLatency());
        $this->assertEquals(100.0, $stats->getMaxLatency());
        $this->assertEqualsWithDelta(29.17, $stats->getAverageLatency(), 0.1);

        // And: Display shows latency stats
        $display = $stats->formatDisplay();
        $this->assertStringContainsString('Latency Statistics:', $display);
        $this->assertStringContainsString('Min:', $display);
        $this->assertStringContainsString('Max:', $display);
        $this->assertStringContainsString('Average:', $display);
    }

    /**
     * SPECIFICATION: Stats should reset to initial state.
     */
    public function testStatsResetToInitialState(): void
    {
        // Given: Stats with data
        $stats = new StatsCollector();
        $stats->record($this->createIncomingMessage('test', 'msg'));
        $stats->recordLatency(25.5);

        $this->assertGreaterThan(0, $stats->getTotalMessages());

        // When: Reset is called
        $stats->reset();

        // Then: All stats are zero
        $this->assertEquals(0, $stats->getTotalMessages());
        $this->assertEquals(0, $stats->getIncomingMessages());
        $this->assertEquals(0, $stats->getOutgoingMessages());
        $this->assertEquals(0.0, $stats->getAverageLatency());
        $this->assertEmpty($stats->getTopTopics());
    }

    /**
     * SPECIFICATION: Topic tree should respect depth limit when rendering.
     */
    public function testTopicTreeRespectsDepthLimit(): void
    {
        // Given: A deep topic structure
        $tree = new TopicTree();
        $tree->addMessage($this->createDataMessage('a/b/c/d/e/f', 'deep'));

        // When: Rendering with depth limit
        $output = $tree->render(2);

        // Then: Only first 2 levels are shown
        $this->assertStringContainsString('a/', $output);
        $this->assertStringContainsString('b/', $output);
        $this->assertStringNotContainsString('c/', $output);
    }

    /**
     * SPECIFICATION: Stats should count error messages.
     */
    public function testStatsCountErrorMessages(): void
    {
        // Given: A stats collector
        $stats = new StatsCollector();

        // When: Error messages are recorded
        $stats->record($this->createErrorMessage('Connection failed'));
        $stats->record($this->createErrorMessage('Timeout'));

        // Then: Error count is tracked
        $this->assertEquals(2, $stats->getErrorCount());

        // And: Summary includes errors
        $summary = $stats->getSummary();
        $this->assertEquals(2, $summary['errors']);
    }

    /**
     * SPECIFICATION: Stats should count subscribe and disconnect events.
     */
    public function testStatsCountSubscribeAndDisconnectEvents(): void
    {
        // Given: A stats collector
        $stats = new StatsCollector();

        // When: Events are recorded
        $stats->record($this->createSubscribeMessage(['sensors/#', 'commands/#']));
        $stats->record($this->createSubscribeMessage(['alerts/#']));
        $stats->record($this->createDisconnectMessage(0));

        // Then: Event counts are tracked
        $summary = $stats->getSummary();
        $this->assertEquals(2, $summary['subscribes']);
        $this->assertEquals(1, $summary['disconnects']);
    }

    /**
     * SPECIFICATION: Flow timeline should limit entries to prevent memory issues.
     */
    public function testFlowTimelineLimitsEntries(): void
    {
        // Given: A timeline with small max entries
        $timeline = new FlowTimeline(ShellConfig::fromArray(['flowTimelineLimit' => 5]));

        // When: More messages than limit are added
        for ($i = 0; $i < 10; $i++) {
            $timeline->addMessage($this->createDataMessage("test/msg{$i}", "value{$i}"));
        }

        // Then: Only last N entries are kept
        $entries = $timeline->getEntries();
        $this->assertCount(5, $entries);

        // And: Output shows most recent messages
        $output = $timeline->render(10);
        $this->assertStringContainsString('msg9', $output);
        $this->assertStringContainsString('msg5', $output);
        $this->assertStringNotContainsString('msg4', $output);
    }

    /**
     * SPECIFICATION: Topic tree can be cleared.
     */
    public function testTopicTreeCanBeCleared(): void
    {
        // Given: A tree with messages
        $tree = new TopicTree();
        $tree->addMessage($this->createDataMessage('test/topic', 'value'));

        // Verify tree has content
        $output = $tree->render();
        $this->assertStringContainsString('test/', $output);

        // When: Tree is cleared
        $tree->clear();

        // Then: Tree is empty
        $output = $tree->render();
        $this->assertStringContainsString('No topics in tree', $output);
    }
}
