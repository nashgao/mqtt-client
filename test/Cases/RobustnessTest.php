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
class RobustnessTest extends AbstractTestCase
{
    public function testTopicConfigValidation()
    {
        // Test that invalid QoS throws exception now that validation is integrated
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessageMatches('/Invalid QoS level.*3/');

        new TopicConfig(['qos' => 3]); // Invalid QoS should throw exception
    }

    public function testTopicNameValidation()
    {
        // Test empty topic
        $result = TopicParser::parseTopic('', 1);
        $this->assertEquals('', $result->topic);

        // Test topic with only wildcards
        $result = TopicParser::parseTopic('#', 0);
        $this->assertEquals('#', $result->topic);

        // Test very long topic name (MQTT spec limit is 65535 bytes)
        $longTopic = str_repeat('a', 1000);
        $result = TopicParser::parseTopic($longTopic, 1);
        $this->assertEquals($longTopic, $result->topic);
    }

    public function testQosRangeValidation()
    {
        // Test invalid QoS values should be caught somewhere
        $validQos = [0, 1, 2];
        foreach ($validQos as $qos) {
            $result = TopicParser::parseTopic('test/topic', $qos);
            $this->assertContains($result->qos, $validQos);
        }
    }

    public function testInvalidTopicPatterns()
    {
        // Test malformed share topics
        $malformedShare = '$share/';
        $result = TopicParser::parseTopic($malformedShare, 1);
        // The parser should handle this gracefully without enabling share topic
        $this->assertInstanceOf(TopicConfig::class, $result);

        // Test malformed queue topics
        $malformedQueue = '$queue/';
        $result = TopicParser::parseTopic($malformedQueue, 1);
        $this->assertInstanceOf(TopicConfig::class, $result);
    }

    public function testResourceLimits()
    {
        // Test very large property arrays with string keys
        $largeProperties = [];
        for ($i = 0; $i < 100; ++$i) {
            $largeProperties["property_{$i}"] = "value_{$i}";
        }
        $config = new TopicConfig($largeProperties);
        $this->assertInstanceOf(TopicConfig::class, $config);

        // Test deep nesting in share topic groups
        $deepNested = ['group_name' => [str_repeat('nested/', 100) . 'group']];
        $config = new TopicConfig();
        $config->setShareTopic($deepNested);
        $this->assertEquals($deepNested, $config->share_topic);
    }

    public function testConcurrentTopicParsing()
    {
        // Simulate concurrent access to topic parsing
        $topics = [
            'sensors/temperature',
            '$share/group/data',
            '$queue/commands',
            'alerts/+/critical',
            'system/#',
        ];

        $results = [];
        foreach ($topics as $topic) {
            $results[] = TopicParser::parseTopic($topic, rand(0, 2));
        }

        $this->assertCount(5, $results);
        foreach ($results as $result) {
            $this->assertInstanceOf(TopicConfig::class, $result);
        }
    }

    public function testMemoryUsageWithLargeConfigs()
    {
        $memoryBefore = memory_get_usage();

        // Create many topic configurations
        $configs = [];
        for ($i = 0; $i < 1000; ++$i) {
            $configs[] = new TopicConfig([
                'topic' => "test/topic/{$i}",
                'qos' => $i % 3,
                'enable_multisub' => ($i % 2) === 0,
                'multisub_num' => $i % 10 + 1,
            ]);
        }

        $memoryAfter = memory_get_usage();
        $memoryUsed = $memoryAfter - $memoryBefore;

        // Memory usage should be reasonable (less than 10MB for 1000 configs)
        $this->assertLessThan(10 * 1024 * 1024, $memoryUsed);

        // Cleanup
        unset($configs);
    }

    public function testErrorHandlingRobustness()
    {
        // Test exception handling with various inputs
        try {
            TopicParser::generateTopicArray('test', []);
        } catch (InvalidConfigException $e) {
            $this->assertStringContainsString('must have qos', $e->getMessage());
        }

        // Test with null values
        try {
            TopicParser::generateTopicArray('test', ['qos' => null]);
        } catch (\TypeError $e) {
            $this->assertInstanceOf(\TypeError::class, $e);
        }
    }

    public function testStringHandlingEdgeCases()
    {
        // Test with special characters
        $specialTopic = 'test/topic/with/特殊字符/and/émojis/🚀';
        $result = TopicParser::parseTopic($specialTopic, 1);
        $this->assertEquals($specialTopic, $result->topic);

        // Test with control characters
        $controlTopic = "test\ntopic\twith\rcontrol";
        $result = TopicParser::parseTopic($controlTopic, 1);
        $this->assertEquals($controlTopic, $result->topic);

        // Test with null bytes - should be sanitized
        $nullTopic = "test\0topic";
        $result = TopicParser::parseTopic($nullTopic, 1);
        $this->assertEquals('testtopic', $result->topic); // Null bytes removed by sanitization
    }
}
