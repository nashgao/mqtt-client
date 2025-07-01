<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Test\Cases;

use Nashgao\MQTT\Config\TopicConfig;
use Nashgao\MQTT\Exception\InvalidConfigException;
use Nashgao\MQTT\Test\AbstractTestCase;
use Nashgao\MQTT\Utils\TopicParser;
use Nashgao\MQTT\Utils\ConfigValidator;
use Nashgao\MQTT\Metrics\ValidationMetrics;
use Nashgao\MQTT\Metrics\PerformanceMetrics;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * @internal
 */
#[CoversNothing]
class RobustnessTest extends AbstractTestCase
{
    private ValidationMetrics $validationMetrics;
    private PerformanceMetrics $performanceMetrics;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->validationMetrics = new ValidationMetrics();
        $this->performanceMetrics = new PerformanceMetrics();
        ConfigValidator::setMetrics($this->validationMetrics);
    }
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
        $startTime = microtime(true);
        
        // Test very large property arrays with string keys
        $largeProperties = [];
        for ($i = 0; $i < 100; ++$i) {
            $largeProperties["property_{$i}"] = "value_{$i}";
        }
        
        $configStart = microtime(true);
        $config = new TopicConfig($largeProperties);
        $configTime = microtime(true) - $configStart;
        
        $this->performanceMetrics->recordOperationTime('large_property_config_creation', $configTime);
        $this->assertInstanceOf(TopicConfig::class, $config);

        // Test deep nesting in share topic groups
        $deepNested = ['group_name' => [str_repeat('nested/', 100) . 'group']];
        $config = new TopicConfig();
        
        $nestedStart = microtime(true);
        $config->setShareTopic($deepNested);
        $nestedTime = microtime(true) - $nestedStart;
        
        $this->performanceMetrics->recordOperationTime('deep_nested_topic_setting', $nestedTime);
        $this->assertEquals($deepNested, $config->share_topic);
        
        $totalTime = microtime(true) - $startTime;
        $this->performanceMetrics->recordOperationTime('resource_limits_test', $totalTime);
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
        $startTime = microtime(true);
        
        foreach ($topics as $topic) {
            $parseStart = microtime(true);
            $results[] = TopicParser::parseTopic($topic, rand(0, 2));
            $parseTime = microtime(true) - $parseStart;
            
            // Record parsing time for each topic type
            $topicType = 'regular';
            if (strpos($topic, '$share') === 0) {
                $topicType = 'share';
            } elseif (strpos($topic, '$queue') === 0) {
                $topicType = 'queue';
            } elseif (strpos($topic, '#') !== false || strpos($topic, '+') !== false) {
                $topicType = 'wildcard';
            }
            
            $this->performanceMetrics->recordOperationTime("topic_parsing_{$topicType}", $parseTime);
        }
        
        $totalTime = microtime(true) - $startTime;
        $this->performanceMetrics->recordOperationTime('concurrent_topic_parsing', $totalTime);

        $this->assertCount(5, $results);
        foreach ($results as $result) {
            $this->assertInstanceOf(TopicConfig::class, $result);
        }
        
        // Verify performance metrics
        $this->assertGreaterThan(0, $this->performanceMetrics->getTotalOperations());
    }

    public function testMemoryUsageWithLargeConfigs()
    {
        $memoryBefore = memory_get_usage();
        
        // Record initial memory usage in performance metrics
        $this->performanceMetrics->recordMemoryUsage();

        // Create many topic configurations
        $configs = [];
        $startTime = microtime(true);
        
        for ($i = 0; $i < 1000; ++$i) {
            $iterationStart = microtime(true);
            
            $configs[] = new TopicConfig([
                'topic' => "test/topic/{$i}",
                'qos' => $i % 3,
                'enable_multisub' => ($i % 2) === 0,
                'multisub_num' => $i % 10 + 1,
            ]);
            
            // Record operation time every 100 iterations
            if ($i % 100 === 0) {
                $this->performanceMetrics->recordOperationTime('topic_config_creation', microtime(true) - $iterationStart);
                $this->performanceMetrics->recordMemoryUsage();
            }
        }
        
        $totalTime = microtime(true) - $startTime;
        $this->performanceMetrics->recordOperationTime('bulk_config_creation', $totalTime);

        $memoryAfter = memory_get_usage();
        $memoryUsed = $memoryAfter - $memoryBefore;
        
        // Record final memory usage
        $this->performanceMetrics->recordMemoryUsage();

        // Memory usage should be reasonable (less than 10MB for 1000 configs)
        $this->assertLessThan(10 * 1024 * 1024, $memoryUsed);
        
        // Verify performance metrics were recorded
        $this->assertGreaterThan(0, $this->performanceMetrics->getTotalOperations());
        $avgCreationTime = $this->performanceMetrics->getAverageOperationTime('topic_config_creation');
        $this->assertGreaterThan(0, $avgCreationTime);

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
        $testCases = [
            'special' => 'test/topic/with/特殊字符/and/émojis/🚀',
            'control' => "test\ntopic\twith\rcontrol",
            'null_bytes' => "test\0topic",
        ];
        
        foreach ($testCases as $type => $topic) {
            $parseStart = microtime(true);
            $result = TopicParser::parseTopic($topic, 1);
            $parseTime = microtime(true) - $parseStart;
            
            $this->performanceMetrics->recordOperationTime("string_handling_{$type}", $parseTime);
            
            if ($type === 'null_bytes') {
                $this->assertEquals('testtopic', $result->topic); // Null bytes removed by sanitization
            } else {
                $this->assertEquals($topic, $result->topic);
            }
        }
        
        // Verify performance metrics were recorded for all string handling cases
        $this->assertGreaterThan(0, $this->performanceMetrics->getAverageOperationTime('string_handling_special'));
        $this->assertGreaterThan(0, $this->performanceMetrics->getAverageOperationTime('string_handling_control'));
        $this->assertGreaterThan(0, $this->performanceMetrics->getAverageOperationTime('string_handling_null_bytes'));
    }
    
    public function testRobustnessMetricsIntegration()
    {
        // Test that robustness operations are properly tracked in metrics
        $this->validationMetrics->reset();
        $this->performanceMetrics->reset();
        
        // Perform various operations that should be tracked
        for ($i = 0; $i < 10; ++$i) {
            try {
                new TopicConfig(['qos' => $i % 4]); // Some will fail validation
            } catch (InvalidConfigException $e) {
                // Expected for invalid QoS values
            }
        }
        
        // Verify validation metrics were recorded
        $count = $this->validationMetrics->getValidationCount('topic_config');
        $this->assertEquals(10, $count['total']);
        $this->assertGreaterThan(0, $count['failed']); // Some should have failed
        $this->assertGreaterThan(0, $count['successful']); // Some should have succeeded
        
        $successRate = $this->validationMetrics->getValidationSuccessRate('topic_config');
        $this->assertGreaterThan(0.0, $successRate);
        $this->assertLessThan(1.0, $successRate);
        
        // Test metrics array output
        $metricsArray = $this->validationMetrics->toArray();
        $this->assertIsArray($metricsArray);
        $this->assertArrayHasKey('validation_counts', $metricsArray);
        $this->assertArrayHasKey('topic_config', $metricsArray['validation_counts']);
    }
}
