<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Test\Cases\Shell\Visualization;

use DateTimeImmutable;
use NashGao\InteractiveShell\Message\Message;
use Nashgao\MQTT\Shell\Visualization\TopicTree;
use Nashgao\MQTT\Test\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * SPECIFICATION TESTS for TopicTree.
 *
 * Tests topic tree visualization behavior through rendered output.
 * Focuses on what users SEE rather than internal data structures.
 *
 * @internal
 */
#[CoversNothing]
class TopicTreeTest extends AbstractTestCase
{
    private TopicTree $tree;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tree = new TopicTree();
    }

    /**
     * SPECIFICATION: Empty tree should display "No topics" message.
     */
    public function testEmptyTreeShowsNoTopicsMessage(): void
    {
        $output = $this->tree->render();

        $this->assertStringContainsString('No topics in tree', $output);
    }

    /**
     * SPECIFICATION: Tree should render hierarchical topic structure.
     */
    public function testTreeRendersHierarchicalTopicStructure(): void
    {
        $this->tree->addMessage($this->createMessage('home/kitchen/temp', ['value' => 23]));
        $this->tree->addMessage($this->createMessage('home/kitchen/humidity', ['value' => 45]));
        $this->tree->addMessage($this->createMessage('home/bedroom/temp', ['value' => 21]));

        $output = $this->tree->render();

        // Verify hierarchical structure in output
        $this->assertStringContainsString('Topic Tree', $output);
        $this->assertStringContainsString('home/', $output);
        $this->assertStringContainsString('kitchen/', $output);
        $this->assertStringContainsString('bedroom/', $output);
        $this->assertStringContainsString('temp', $output);
        $this->assertStringContainsString('humidity', $output);
    }

    /**
     * SPECIFICATION: Tree should show message counts per topic.
     */
    public function testTreeShowsMessageCountsPerTopic(): void
    {
        // Add 5 messages to same topic
        for ($i = 0; $i < 5; $i++) {
            $this->tree->addMessage($this->createMessage('sensors/temp', ['v' => $i]));
        }

        $output = $this->tree->render();

        // Verify message count shown
        $this->assertStringContainsString('5 msgs', $output);
    }

    /**
     * SPECIFICATION: Tree should display total message count in header.
     */
    public function testTreeDisplaysTotalMessageCountInHeader(): void
    {
        $this->tree->addMessage($this->createMessage('topic1', 'value1'));
        $this->tree->addMessage($this->createMessage('topic2', 'value2'));
        $this->tree->addMessage($this->createMessage('topic3', 'value3'));

        $output = $this->tree->render();

        $this->assertStringContainsString('3 messages', $output);
    }

    /**
     * SPECIFICATION: Tree should use visual tree characters for structure.
     */
    public function testTreeUsesVisualTreeCharacters(): void
    {
        $this->tree->addMessage($this->createMessage('root/branch1/leaf1', 'value1'));
        $this->tree->addMessage($this->createMessage('root/branch2/leaf2', 'value2'));

        $output = $this->tree->render();

        // Check for tree structure characters
        $this->assertStringContainsString('├──', $output);
        $this->assertStringContainsString('└──', $output);
    }

    /**
     * SPECIFICATION: Tree should show activity indicator for recent messages.
     */
    public function testTreeShowsActivityIndicatorForRecentMessages(): void
    {
        $this->tree->addMessage($this->createMessage('active/topic', 'current'));

        $output = $this->tree->render();

        // Should show diamond (◆) for recent activity
        $this->assertStringContainsString('◆', $output);
    }

    /**
     * SPECIFICATION: Tree should respect depth limit when rendering.
     */
    public function testTreeRespectsDepthLimit(): void
    {
        $this->tree->addMessage($this->createMessage('level1/level2/level3/level4', 'deep'));

        $output = $this->tree->render(2);

        // First 2 levels should be visible
        $this->assertStringContainsString('level1/', $output);
        $this->assertStringContainsString('level2/', $output);
        // Deeper levels should not appear
        $this->assertStringNotContainsString('level3/', $output);
    }

    /**
     * SPECIFICATION: Unlimited depth should show all levels.
     */
    public function testUnlimitedDepthShowsAllLevels(): void
    {
        $this->tree->addMessage($this->createMessage('a/b/c/d/e/f', 'deep'));

        $output = $this->tree->render(-1);

        // All levels should be visible with -1 depth
        $this->assertStringContainsString('a/', $output);
        $this->assertStringContainsString('b/', $output);
        $this->assertStringContainsString('c/', $output);
        $this->assertStringContainsString('d/', $output);
        $this->assertStringContainsString('e/', $output);
        $this->assertStringContainsString('f', $output);
    }

    /**
     * SPECIFICATION: Tree should display multiple root topics.
     */
    public function testTreeDisplaysMultipleRootTopics(): void
    {
        $this->tree->addMessage($this->createMessage('sensors/temp', '25'));
        $this->tree->addMessage($this->createMessage('devices/light', 'on'));
        $this->tree->addMessage($this->createMessage('commands/hvac', 'cool'));

        $output = $this->tree->render();

        $this->assertStringContainsString('sensors/', $output);
        $this->assertStringContainsString('devices/', $output);
        $this->assertStringContainsString('commands/', $output);
    }

    /**
     * SPECIFICATION: Tree should display legend explaining symbols.
     */
    public function testTreeDisplaysLegend(): void
    {
        $this->tree->addMessage($this->createMessage('test/topic', 'value'));

        $output = $this->tree->render();

        $this->assertStringContainsString('Legend:', $output);
    }

    /**
     * SPECIFICATION: Tree should display payload values.
     */
    public function testTreeDisplaysPayloadValues(): void
    {
        $this->tree->addMessage($this->createMessage('sensors/temperature', '25.5'));

        $output = $this->tree->render();

        $this->assertStringContainsString('25.5', $output);
    }

    /**
     * SPECIFICATION: Clear should reset tree to empty state.
     */
    public function testClearResetsTreeToEmptyState(): void
    {
        $this->tree->addMessage($this->createMessage('test/topic', 'value'));

        // Verify tree has content
        $output = $this->tree->render();
        $this->assertStringContainsString('test/', $output);

        // Clear the tree
        $this->tree->clear();

        // Tree should now be empty
        $output = $this->tree->render();
        $this->assertStringContainsString('No topics in tree', $output);
    }

    /**
     * SPECIFICATION: Tree should handle grouped topics under same parent.
     */
    public function testTreeGroupsTopicsUnderSameParent(): void
    {
        // Add messages to different leaf nodes under same parent
        $this->tree->addMessage($this->createMessage('sensors/room1/temperature', '22'));
        $this->tree->addMessage($this->createMessage('sensors/room1/humidity', '45'));
        $this->tree->addMessage($this->createMessage('sensors/room2/temperature', '24'));

        $output = $this->tree->render();

        // Should show parent with grouped children
        $this->assertStringContainsString('sensors/', $output);
        $this->assertStringContainsString('room1/', $output);
        $this->assertStringContainsString('room2/', $output);
        $this->assertStringContainsString('temperature', $output);
        $this->assertStringContainsString('humidity', $output);
        $this->assertStringContainsString('3 messages', $output);
    }

    /**
     * SPECIFICATION: Tree should handle JSON payloads correctly.
     */
    public function testTreeHandlesJsonPayloads(): void
    {
        $this->tree->addMessage($this->createMessage('test/json', ['key' => 'value', 'number' => 42]));

        $output = $this->tree->render();

        // Should contain the topic and show message count
        $this->assertStringContainsString('json', $output);
        $this->assertStringContainsString('1 msgs', $output);
    }

    /**
     * SPECIFICATION: Invalid messages should not appear in tree.
     */
    public function testInvalidMessagesDoNotAppearInTree(): void
    {
        // Message with non-array payload
        $invalidMsg = new Message(
            type: 'data',
            payload: 'not an array',
            source: 'mqtt',
            timestamp: new DateTimeImmutable(),
        );
        $this->tree->addMessage($invalidMsg);

        $output = $this->tree->render();

        // Should still show empty tree
        $this->assertStringContainsString('No topics in tree', $output);
    }

    /**
     * SPECIFICATION: Messages without topic should not appear in tree.
     */
    public function testMessagesWithoutTopicDoNotAppearInTree(): void
    {
        $noTopicMsg = new Message(
            type: 'data',
            payload: ['payload' => 'value'],
            source: 'mqtt',
            timestamp: new DateTimeImmutable(),
        );
        $this->tree->addMessage($noTopicMsg);

        $output = $this->tree->render();

        // Should still show empty tree
        $this->assertStringContainsString('No topics in tree', $output);
    }

    /**
     * Helper to create a test message.
     */
    private function createMessage(string $topic, mixed $payload): Message
    {
        return new Message(
            type: 'data',
            payload: [
                'topic' => $topic,
                'payload' => $payload,
            ],
            source: 'mqtt',
            timestamp: new DateTimeImmutable(),
        );
    }
}
