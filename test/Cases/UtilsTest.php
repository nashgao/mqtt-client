<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Test\Cases;

use Nashgao\MQTT\Test\AbstractTestCase;
use Nashgao\MQTT\Utils\Qos;
use Nashgao\MQTT\Utils\TopicParser;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * @internal
 */
#[CoversNothing]
class UtilsTest extends AbstractTestCase
{
    public function testQosConstants()
    {
        $this->assertEquals(0, Qos::QOS_AT_MOST_ONCE);
        $this->assertEquals(1, Qos::QOS_AT_LEAST_ONCE);
        $this->assertEquals(2, Qos::QOS_EXACTLY_ONCE);
    }

    public function testTopicParserConstants()
    {
        $this->assertEquals('$share', TopicParser::SHARE);
        $this->assertEquals('$queue', TopicParser::QUEUE);
        $this->assertEquals('/', TopicParser::SEPARATOR);
    }

    public function testTopicParserGenerateShareTopicWithComplexTopic()
    {
        $topic = 'sensors/temperature/room1/data';
        $group = 'processing-group';

        $result = TopicParser::generateShareTopic($topic, $group);

        $this->assertEquals('$share/processing-group/sensors/temperature/room1/data', $result);
    }

    public function testTopicParserGenerateQueueTopicWithComplexTopic()
    {
        $topic = 'commands/device/control/power';

        $result = TopicParser::generateQueueTopic($topic);

        $this->assertEquals('$queue/commands/device/control/power', $result);
    }

    public function testTopicParserGenerateTopicArrayWithComplexProperties()
    {
        $topic = 'alerts/critical/system';
        $properties = [
            'qos' => 2,
            'retain' => true,
            'no_local' => false,
            'retain_as_published' => true,
            'retain_handling' => 1,
        ];

        $result = TopicParser::generateTopicArray($topic, $properties);

        $expected = [
            'alerts/critical/system' => [
                'qos' => 2,
                'retain' => true,
                'no_local' => false,
                'retain_as_published' => true,
                'retain_handling' => 1,
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function testTopicParserParseTopicWithProperties()
    {
        $topic = 'test/topic/with/properties';
        $qos = 1;
        $properties = [
            'no_local' => false,
            'retain_as_published' => false,
            'retain_handling' => 0,
        ];

        $result = TopicParser::parseTopic($topic, $qos, $properties);

        $this->assertEquals('test/topic/with/properties', $result->topic);
        $this->assertEquals(1, $result->qos);
        $this->assertFalse($result->no_local);
        $this->assertFalse($result->retain_as_published);
        $this->assertEquals(0, $result->retain_handling);
    }

    public function testTopicParserParseShareTopicWithComplexGroup()
    {
        $topic = '$share/processing-workers/data/stream/analytics';
        $qos = 2;

        $result = TopicParser::parseTopic($topic, $qos);

        $this->assertEquals('data/stream/analytics', $result->topic);
        $this->assertEquals(2, $result->qos);
        $this->assertTrue($result->enable_share_topic);
        $this->assertEquals(['group_name' => ['processing-workers']], $result->share_topic);
    }

    public function testTopicParserParseQueueTopicWithComplexPath()
    {
        $topic = '$queue/commands/device/control/temperature/set';
        $qos = 1;

        $result = TopicParser::parseTopic($topic, $qos);

        $this->assertEquals('commands/device/control/temperature/set', $result->topic);
        $this->assertEquals(1, $result->qos);
        $this->assertTrue($result->enable_queue_topic);
        $this->assertFalse($result->enable_share_topic);
    }

    public function testTopicParserParseTopicWithWildcards()
    {
        $topic = 'sensors/+/temperature/#';
        $qos = 0;

        $result = TopicParser::parseTopic($topic, $qos);

        $this->assertEquals('sensors/+/temperature/#', $result->topic);
        $this->assertEquals(0, $result->qos);
        $this->assertFalse($result->enable_share_topic);
        $this->assertFalse($result->enable_queue_topic);
    }

    public function testTopicParserParseEmptyTopic()
    {
        $topic = '';
        $qos = 1;

        $result = TopicParser::parseTopic($topic, $qos);

        $this->assertEquals('', $result->topic);
        $this->assertEquals(1, $result->qos);
    }

    public function testTopicParserQueueTopicHasPriorityOverShareTopic()
    {
        // This tests the comment "queue topic has higher priority"
        $topic = '$queue/$share/group/topic';
        $qos = 1;

        $result = TopicParser::parseTopic($topic, $qos);

        $this->assertTrue($result->enable_queue_topic);
        $this->assertFalse($result->enable_share_topic);
        $this->assertEquals('$share/group/topic', $result->topic);
    }
}
