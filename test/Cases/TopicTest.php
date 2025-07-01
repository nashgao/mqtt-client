<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Test\Cases;

use Nashgao\MQTT\Config\TopicConfig;
use Nashgao\MQTT\Exception\InvalidConfigException;
use Nashgao\MQTT\Test\AbstractTestCase;
use Nashgao\MQTT\Utils\TopicParser;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * @internal
 */
#[CoversNothing]
class TopicTest extends AbstractTestCase
{
    public function testTopicParserGenerateShareTopic()
    {
        $topic = 'test/topic';
        $group = 'mygroup';

        $result = TopicParser::generateShareTopic($topic, $group);

        $this->assertEquals('$share/mygroup/test/topic', $result);
    }

    public function testTopicParserGenerateShareTopicWithDefaultGroup()
    {
        $topic = 'test/topic';

        $result = TopicParser::generateShareTopic($topic);

        $this->assertEquals('$share/default/test/topic', $result);
    }

    public function testTopicParserGenerateQueueTopic()
    {
        $topic = 'test/topic';

        $result = TopicParser::generateQueueTopic($topic);

        $this->assertEquals('$queue/test/topic', $result);
    }

    public function testTopicParserGenerateTopicArray()
    {
        $topic = 'test/topic';
        $properties = ['qos' => 1];

        $result = TopicParser::generateTopicArray($topic, $properties);

        $this->assertEquals(['test/topic' => ['qos' => 1]], $result);
    }

    public function testTopicParserGenerateTopicArrayThrowsExceptionWithoutQos()
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('invalid config, must have qos');

        TopicParser::generateTopicArray('test/topic', []);
    }

    public function testTopicParserParseRegularTopic()
    {
        $topic = 'test/topic';
        $qos = 1;

        $result = TopicParser::parseTopic($topic, $qos);

        $this->assertInstanceOf(TopicConfig::class, $result);
        $this->assertEquals('test/topic', $result->topic);
        $this->assertEquals(1, $result->qos);
        $this->assertFalse($result->enable_share_topic);
        $this->assertFalse($result->enable_queue_topic);
    }

    public function testTopicParserParseQueueTopic()
    {
        $topic = '$queue/test/topic';
        $qos = 2;

        $result = TopicParser::parseTopic($topic, $qos);

        $this->assertInstanceOf(TopicConfig::class, $result);
        $this->assertEquals('test/topic', $result->topic);
        $this->assertEquals(2, $result->qos);
        $this->assertTrue($result->enable_queue_topic);
        $this->assertFalse($result->enable_share_topic);
    }

    public function testTopicParserParseShareTopic()
    {
        $topic = '$share/mygroup/test/topic';
        $qos = 0;

        $result = TopicParser::parseTopic($topic, $qos);

        $this->assertInstanceOf(TopicConfig::class, $result);
        $this->assertEquals('test/topic', $result->topic);
        $this->assertEquals(0, $result->qos);
        $this->assertTrue($result->enable_share_topic);
        $this->assertFalse($result->enable_queue_topic);
        $this->assertEquals(['group_name' => ['mygroup']], $result->share_topic);
    }
}
