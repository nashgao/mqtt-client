<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Test\Cases\Shell\Formatter;

use DateTimeImmutable;
use NashGao\InteractiveShell\Message\Message;
use Nashgao\MQTT\Shell\Formatter\MqttMessageFormatter;
use Nashgao\MQTT\Test\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * SPECIFICATION TESTS for MqttMessageFormatter.
 *
 * Tests message formatting behavior through the public format() API.
 * Focuses on format output rather than internal helper methods.
 *
 * @internal
 */
#[CoversNothing]
class MqttMessageFormatterTest extends AbstractTestCase
{
    private MqttMessageFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new MqttMessageFormatter();
    }

    /**
     * SPECIFICATION: Default format should be compact.
     */
    public function testDefaultFormatIsCompact(): void
    {
        $this->assertEquals('compact', $this->formatter->getFormat());
    }

    /**
     * SPECIFICATION: Format should accept 'compact' and 'c' aliases.
     */
    public function testCompactFormatAliases(): void
    {
        $this->formatter->setFormat('compact');
        $this->assertEquals('compact', $this->formatter->getFormat());

        $this->formatter->setFormat('c');
        $this->assertEquals('compact', $this->formatter->getFormat());
    }

    /**
     * SPECIFICATION: Format should accept 'table' and 't' aliases.
     */
    public function testTableFormatAliases(): void
    {
        $this->formatter->setFormat('table');
        $this->assertEquals('table', $this->formatter->getFormat());

        $this->formatter->setFormat('t');
        $this->assertEquals('table', $this->formatter->getFormat());
    }

    /**
     * SPECIFICATION: Format should accept 'vertical' and 'v' aliases.
     */
    public function testVerticalFormatAliases(): void
    {
        $this->formatter->setFormat('vertical');
        $this->assertEquals('vertical', $this->formatter->getFormat());

        $this->formatter->setFormat('v');
        $this->assertEquals('vertical', $this->formatter->getFormat());
    }

    /**
     * SPECIFICATION: Format should accept 'json' and 'j' aliases.
     */
    public function testJsonFormatAliases(): void
    {
        $this->formatter->setFormat('json');
        $this->assertEquals('json', $this->formatter->getFormat());

        $this->formatter->setFormat('j');
        $this->assertEquals('json', $this->formatter->getFormat());
    }

    /**
     * SPECIFICATION: Format should accept 'hex' and 'h' aliases.
     */
    public function testHexFormatAliases(): void
    {
        $this->formatter->setFormat('hex');
        $this->assertEquals('hex', $this->formatter->getFormat());

        $this->formatter->setFormat('h');
        $this->assertEquals('hex', $this->formatter->getFormat());
    }

    /**
     * SPECIFICATION: Invalid format should fallback to compact.
     */
    public function testInvalidFormatFallsBackToCompact(): void
    {
        $this->formatter->setFormat('invalid');
        $this->assertEquals('compact', $this->formatter->getFormat());
    }

    /**
     * SPECIFICATION: Compact format should show topic and direction.
     */
    public function testCompactFormatShowsTopicAndDirection(): void
    {
        $message = $this->createPublishMessage(
            'sensors/temperature',
            ['value' => 23.5, 'unit' => 'celsius'],
            'incoming'
        );

        $this->formatter->setFormat('compact');
        $this->formatter->setColorEnabled(false);
        $output = $this->formatter->format($message);

        $this->assertStringContainsString('sensors/temperature', $output);
        $this->assertStringContainsString('IN', $output);
    }

    /**
     * SPECIFICATION: Compact format should display string payloads.
     */
    public function testCompactFormatDisplaysStringPayloads(): void
    {
        $message = new Message(
            type: 'publish',
            payload: [
                'topic' => 'test/topic',
                'message' => 'Hello World',
            ],
            source: 'test',
            timestamp: new DateTimeImmutable(),
            metadata: ['direction' => 'incoming']
        );

        $this->formatter->setFormat('compact');
        $this->formatter->setColorEnabled(false);
        $output = $this->formatter->format($message);

        $this->assertStringContainsString('test/topic', $output);
        $this->assertStringContainsString('Hello World', $output);
    }

    /**
     * SPECIFICATION: Compact format should handle binary payloads.
     */
    public function testCompactFormatHandlesBinaryPayloads(): void
    {
        $binaryData = "\x00\x01\x02\x03\xFF\xFE";
        $message = new Message(
            type: 'publish',
            payload: [
                'topic' => 'binary/data',
                'message' => $binaryData,
            ],
            source: 'test',
            timestamp: new DateTimeImmutable(),
            metadata: ['direction' => 'incoming']
        );

        $this->formatter->setFormat('compact');
        $this->formatter->setColorEnabled(false);
        $output = $this->formatter->format($message);

        $this->assertStringContainsString('binary/data', $output);
    }

    /**
     * SPECIFICATION: Table format should show direction and QoS.
     */
    public function testTableFormatShowsDirectionAndQos(): void
    {
        $message = $this->createPublishMessage(
            'sensors/humidity',
            ['value' => 65.2],
            'outgoing',
            1
        );

        $this->formatter->setFormat('table');
        $this->formatter->setColorEnabled(false);
        $output = $this->formatter->format($message);

        $this->assertStringContainsString('OUT', $output);
        $this->assertStringContainsString('sensors/humidity', $output);
        $this->assertStringContainsString('1', $output);
    }

    /**
     * SPECIFICATION: Vertical format should show all message fields.
     */
    public function testVerticalFormatShowsAllMessageFields(): void
    {
        $message = $this->createPublishMessage(
            'test/topic',
            ['key' => 'value', 'number' => 42],
            'incoming',
            2
        );

        $this->formatter->setFormat('vertical');
        $this->formatter->setColorEnabled(false);
        $output = $this->formatter->format($message);

        $this->assertStringContainsString('Message', $output);
        $this->assertStringContainsString('type:', $output);
        $this->assertStringContainsString('direction:', $output);
        $this->assertStringContainsString('topic:', $output);
        $this->assertStringContainsString('qos:', $output);
        $this->assertStringContainsString('payload:', $output);
        $this->assertStringContainsString('test/topic', $output);
        $this->assertStringContainsString('incoming', $output);
    }

    /**
     * SPECIFICATION: JSON format should output valid parseable JSON.
     */
    public function testJsonFormatOutputsValidJson(): void
    {
        $message = $this->createPublishMessage(
            'sensors/pressure',
            ['value' => 1013.25],
            'outgoing',
            1
        );

        $this->formatter->setFormat('json');
        $output = $this->formatter->format($message);

        $decoded = json_decode($output, true);
        $this->assertIsArray($decoded);
        $this->assertEquals('publish', $decoded['type']);
        $this->assertEquals('outgoing', $decoded['direction']);
        $this->assertEquals('sensors/pressure', $decoded['topic']);
        $this->assertEquals(1, $decoded['qos']);
    }

    /**
     * SPECIFICATION: JSON format should work with string payloads.
     */
    public function testJsonFormatWorksWithStringPayloads(): void
    {
        $message = new Message(
            type: 'publish',
            payload: [
                'topic' => 'text/message',
                'message' => 'Plain text message',
            ],
            source: 'test',
            timestamp: new DateTimeImmutable(),
            metadata: ['direction' => 'incoming']
        );

        $this->formatter->setFormat('json');
        $output = $this->formatter->format($message);

        $decoded = json_decode($output, true);
        $this->assertIsArray($decoded);
        $this->assertEquals('text/message', $decoded['topic']);
    }

    /**
     * SPECIFICATION: Hex format should handle binary payloads.
     */
    public function testHexFormatHandlesBinaryPayloads(): void
    {
        $binaryData = "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F";
        $message = new Message(
            type: 'publish',
            payload: [
                'topic' => 'binary/test',
                'message' => $binaryData,
            ],
            source: 'test',
            timestamp: new DateTimeImmutable(),
            metadata: ['direction' => 'outgoing']
        );

        $this->formatter->setFormat('hex');
        $this->formatter->setColorEnabled(false);
        $output = $this->formatter->format($message);

        $this->assertStringContainsString('binary/test', $output);
    }

    /**
     * SPECIFICATION: System messages should show SYS indicator.
     */
    public function testSystemMessagesShowSysIndicator(): void
    {
        $message = new Message(
            type: 'system',
            payload: 'Connected to broker',
            source: 'test',
            timestamp: new DateTimeImmutable(),
            metadata: []
        );

        $this->formatter->setFormat('compact');
        $this->formatter->setColorEnabled(false);
        $output = $this->formatter->format($message);

        $this->assertStringContainsString('SYS', $output);
        $this->assertStringContainsString('Connected to broker', $output);
    }

    /**
     * SPECIFICATION: Subscribe messages should show SUB indicator and topics.
     */
    public function testSubscribeMessagesShowSubIndicatorAndTopics(): void
    {
        $message = new Message(
            type: 'subscribe',
            payload: ['topics' => ['test/topic1', 'test/topic2']],
            source: 'test',
            timestamp: new DateTimeImmutable(),
            metadata: []
        );

        $this->formatter->setFormat('compact');
        $this->formatter->setColorEnabled(false);
        $output = $this->formatter->format($message);

        $this->assertStringContainsString('SUB', $output);
        $this->assertStringContainsString('test/topic1', $output);
        $this->assertStringContainsString('test/topic2', $output);
    }

    /**
     * SPECIFICATION: Disconnect messages should show DIS indicator and code.
     */
    public function testDisconnectMessagesShowDisIndicatorAndCode(): void
    {
        $message = new Message(
            type: 'disconnect',
            payload: ['code' => 0],
            source: 'test',
            timestamp: new DateTimeImmutable(),
            metadata: []
        );

        $this->formatter->setFormat('compact');
        $this->formatter->setColorEnabled(false);
        $output = $this->formatter->format($message);

        $this->assertStringContainsString('DIS', $output);
        $this->assertStringContainsString('code=0', $output);
    }

    /**
     * SPECIFICATION: Color should be enabled by default.
     */
    public function testColorIsEnabledByDefault(): void
    {
        $message = $this->createPublishMessage('test/topic', 'payload', 'incoming');

        $this->formatter->setFormat('compact');
        $output = $this->formatter->format($message);

        $this->assertStringContainsString("\033[", $output);
    }

    /**
     * SPECIFICATION: Color can be disabled.
     */
    public function testColorCanBeDisabled(): void
    {
        $message = $this->createPublishMessage('test/topic', 'payload', 'incoming');

        $this->formatter->setFormat('compact');
        $this->formatter->setColorEnabled(false);
        $output = $this->formatter->format($message);

        $this->assertStringNotContainsString("\033[", $output);
    }

    /**
     * SPECIFICATION: Vertical format should display message metadata.
     */
    public function testVerticalFormatDisplaysMessageMetadata(): void
    {
        $message = new Message(
            type: 'publish',
            payload: [
                'topic' => 'test/topic',
                'message' => 'payload',
                'qos' => 1,
            ],
            source: 'test',
            timestamp: new DateTimeImmutable(),
            metadata: [
                'direction' => 'incoming',
                'retain' => true,
                'dup' => false,
                'message_id' => 12345,
                'qos' => 1,
            ]
        );

        $this->formatter->setFormat('vertical');
        $this->formatter->setColorEnabled(false);
        $output = $this->formatter->format($message);

        $this->assertStringContainsString('retain:', $output);
        $this->assertStringContainsString('true', $output);
        $this->assertStringContainsString('dup:', $output);
        $this->assertStringContainsString('false', $output);
        $this->assertStringContainsString('message_id:', $output);
        $this->assertStringContainsString('12345', $output);
    }

    /**
     * SPECIFICATION: Long payloads should be truncated with ellipsis.
     */
    public function testLongPayloadsAreTruncatedWithEllipsis(): void
    {
        $longPayload = str_repeat('A', 200);
        $message = new Message(
            type: 'publish',
            payload: [
                'topic' => 'test/topic',
                'message' => $longPayload,
            ],
            source: 'test',
            timestamp: new DateTimeImmutable(),
            metadata: ['direction' => 'incoming']
        );

        $this->formatter->setFormat('compact');
        $this->formatter->setColorEnabled(false);
        $output = $this->formatter->format($message);

        $this->assertStringContainsString('...', $output);
        $this->assertLessThan(200, strlen($output));
    }

    /**
     * SPECIFICATION: Nested JSON payloads should be properly formatted.
     */
    public function testNestedJsonPayloadsAreProperlyFormatted(): void
    {
        $nestedPayload = [
            'sensor' => [
                'id' => 'sensor-001',
                'readings' => [
                    ['temperature' => 23.5],
                    ['humidity' => 65.2],
                ],
            ],
        ];

        $message = $this->createPublishMessage('sensors/data', $nestedPayload, 'incoming');

        $this->formatter->setFormat('vertical');
        $this->formatter->setColorEnabled(false);
        $output = $this->formatter->format($message);

        $this->assertStringContainsString('sensor', $output);
        $this->assertStringContainsString('sensor-001', $output);
    }

    /**
     * SPECIFICATION: Default depth limit should be 0 (unlimited).
     */
    public function testDefaultDepthLimitIsZero(): void
    {
        $this->assertEquals(0, $this->formatter->getDepthLimit());
    }

    /**
     * SPECIFICATION: Depth limit can be set and retrieved.
     */
    public function testDepthLimitGetterSetter(): void
    {
        $this->formatter->setDepthLimit(5);
        $this->assertEquals(5, $this->formatter->getDepthLimit());

        $this->formatter->setDepthLimit(0);
        $this->assertEquals(0, $this->formatter->getDepthLimit());
    }

    /**
     * SPECIFICATION: Negative depth limit should be clamped to zero.
     */
    public function testDepthLimitClampsNegativeToZero(): void
    {
        $this->formatter->setDepthLimit(-5);
        $this->assertEquals(0, $this->formatter->getDepthLimit());
    }

    /**
     * SPECIFICATION: Schema mode should be disabled by default.
     */
    public function testSchemaModeIsDisabledByDefault(): void
    {
        $this->assertFalse($this->formatter->isSchemaMode());
    }

    /**
     * SPECIFICATION: Schema mode can be enabled and disabled.
     */
    public function testSchemaModeGetterSetter(): void
    {
        $this->formatter->setSchemaMode(true);
        $this->assertTrue($this->formatter->isSchemaMode());

        $this->formatter->setSchemaMode(false);
        $this->assertFalse($this->formatter->isSchemaMode());
    }

    /**
     * Helper to create a publish message.
     */
    private function createPublishMessage(
        string $topic,
        mixed $payload,
        string $direction = 'incoming',
        int $qos = 0
    ): Message {
        return new Message(
            type: 'publish',
            payload: [
                'topic' => $topic,
                'message' => $payload,
                'qos' => $qos,
            ],
            source: 'test',
            timestamp: new DateTimeImmutable(),
            metadata: [
                'direction' => $direction,
                'qos' => $qos,
            ]
        );
    }
}
