<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Test\Cases;

use Nashgao\MQTT\Config\TopicConfig;
use Nashgao\MQTT\Config\TopicSubscription;
use Nashgao\MQTT\Config\TopicSubscriptionConfig;
use Nashgao\MQTT\Metrics\ValidationMetrics;
use Nashgao\MQTT\Test\AbstractTestCase;
use Nashgao\MQTT\Utils\ConfigValidator;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * @internal
 */
#[CoversNothing]
class TopicSubscriptionConfigTest extends AbstractTestCase
{
    private ValidationMetrics $validationMetrics;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validationMetrics = new ValidationMetrics();
        ConfigValidator::setMetrics($this->validationMetrics);
    }

    public function testTopicSubscriptionConfigCreation()
    {
        $config = [
            'global_option' => 'test_value',
            'topics' => [
                [
                    'topic' => 'sensors/temperature',
                    'qos' => 1,
                    'auto_subscribe' => true,
                ],
                [
                    'topic' => 'sensors/humidity',
                    'qos' => 2,
                    'auto_subscribe' => false,
                ],
            ],
        ];

        $subscriptionConfig = new TopicSubscriptionConfig($config);

        $this->assertEquals(2, $subscriptionConfig->count());
        $this->assertFalse($subscriptionConfig->isEmpty());
        $this->assertEquals(['global_option' => 'test_value'], $subscriptionConfig->getGlobalOptions());
    }

    public function testTopicSubscriptionAutoSubscribe()
    {
        $config = [
            'topics' => [
                [
                    'topic' => 'auto/topic1',
                    'qos' => 1,
                    'auto_subscribe' => true,
                ],
                [
                    'topic' => 'manual/topic1',
                    'qos' => 1,
                    'auto_subscribe' => false,
                ],
                [
                    'topic' => 'auto/topic2',
                    'qos' => 2,
                    'auto_subscribe' => true,
                ],
            ],
        ];

        $subscriptionConfig = new TopicSubscriptionConfig($config);

        $this->assertTrue($subscriptionConfig->hasAutoSubscriptions());

        $autoTopics = $subscriptionConfig->getAutoSubscribeTopics();
        $this->assertCount(2, $autoTopics);

        foreach ($autoTopics as $topic) {
            $this->assertTrue($topic->isAutoSubscribe());
            $this->assertStringStartsWith('auto/', $topic->getTopic());
        }
    }

    public function testTopicSubscriptionWithFilter()
    {
        $config = [
            'topics' => [
                [
                    'topic' => 'sensors/temp1',
                    'qos' => 1,
                    'auto_subscribe' => true,
                    'filter' => function ($topicData) {
                        return $topicData['qos'] >= 1;
                    },
                ],
                [
                    'topic' => 'sensors/temp2',
                    'qos' => 0,
                    'auto_subscribe' => true,
                    'filter' => function ($topicData) {
                        return $topicData['qos'] >= 1;
                    },
                ],
            ],
        ];

        $subscriptionConfig = new TopicSubscriptionConfig($config);
        $filteredTopics = $subscriptionConfig->getFilteredTopics();

        // Only the first topic should pass the filter (qos >= 1)
        $this->assertCount(1, $filteredTopics);
        $this->assertEquals('sensors/temp1', $filteredTopics[0]->getTopic());
    }

    public function testTopicSubscriptionPatternMatching()
    {
        $config = [
            'topics' => [
                [
                    'topic' => 'sensors/temperature/room1',
                    'qos' => 1,
                    'auto_subscribe' => true,
                ],
                [
                    'topic' => 'sensors/humidity/room1',
                    'qos' => 1,
                    'auto_subscribe' => true,
                ],
                [
                    'topic' => 'alerts/fire/building1',
                    'qos' => 2,
                    'auto_subscribe' => true,
                ],
            ],
        ];

        $subscriptionConfig = new TopicSubscriptionConfig($config);

        $sensorTopics = $subscriptionConfig->getTopicsByPattern('sensors/*');
        $this->assertCount(2, $sensorTopics);

        $alertTopics = $subscriptionConfig->getTopicsByPattern('alerts/*');
        $this->assertCount(1, $alertTopics);
    }

    public function testTopicSubscriptionAddTopic()
    {
        $subscriptionConfig = new TopicSubscriptionConfig();

        $topic = new TopicSubscription([
            'topic' => 'test/topic',
            'qos' => 1,
            'auto_subscribe' => true,
        ]);

        $subscriptionConfig->addTopic($topic);

        $this->assertEquals(1, $subscriptionConfig->count());
        $this->assertFalse($subscriptionConfig->isEmpty());
        $this->assertTrue($subscriptionConfig->hasAutoSubscriptions());
    }

    public function testTopicSubscriptionGlobalOptions()
    {
        $subscriptionConfig = new TopicSubscriptionConfig(['option1' => 'value1']);

        $subscriptionConfig->setGlobalOption('option2', 'value2');

        $options = $subscriptionConfig->getGlobalOptions();
        $this->assertEquals('value1', $options['option1']);
        $this->assertEquals('value2', $options['option2']);
    }

    public function testTopicSubscriptionGetTopicConfigs()
    {
        $config = [
            'topics' => [
                [
                    'topic' => 'sensors/temperature',
                    'qos' => 1,
                    'auto_subscribe' => true,
                    'no_local' => false,
                    'retain_as_published' => false,
                ],
            ],
        ];

        $subscriptionConfig = new TopicSubscriptionConfig($config);
        $topicConfigs = $subscriptionConfig->getTopicConfigs();

        $this->assertCount(1, $topicConfigs);
        $this->assertInstanceOf(TopicConfig::class, $topicConfigs[0]);
        $this->assertEquals('sensors/temperature', $topicConfigs[0]->topic);
        $this->assertEquals(1, $topicConfigs[0]->qos);
        // Check that the no_local property is correctly set from the config
        // The TopicSubscription properly converts to TopicConfig with the configured value
        $this->assertFalse($topicConfigs[0]->no_local);
    }

    public function testTopicSubscriptionValidation()
    {
        $validConfig = [
            'topics' => [
                [
                    'topic' => 'valid/topic',
                    'qos' => 1,
                    'auto_subscribe' => true,
                ],
            ],
        ];

        $validSubscriptionConfig = new TopicSubscriptionConfig($validConfig);
        $this->assertTrue($validSubscriptionConfig->validate());

        // Test that validation catches invalid topics by bypassing constructor validation
        $invalidSubscriptionConfig = new TopicSubscriptionConfig();
        $invalidTopic = new TopicSubscription(['topic' => 'test', 'qos' => 0]);
        // Manually set invalid QoS to bypass constructor validation
        $invalidTopic->qos = 5;
        $invalidSubscriptionConfig->addTopic($invalidTopic);

        $this->assertFalse($invalidSubscriptionConfig->validate());
    }

    public function testTopicSubscriptionToArray()
    {
        $config = [
            'global_option' => 'test',
            'topics' => [
                [
                    'topic' => 'test/topic',
                    'qos' => 1,
                    'auto_subscribe' => true,
                    'handler' => 'TestHandler',
                ],
            ],
        ];

        $subscriptionConfig = new TopicSubscriptionConfig($config);
        $array = $subscriptionConfig->toArray();

        $this->assertEquals('test', $array['global_option']);
        $this->assertArrayHasKey('topics', $array);
        $this->assertCount(1, $array['topics']);
        $this->assertEquals('test/topic', $array['topics'][0]['topic']);
        $this->assertEquals('TestHandler', $array['topics'][0]['handler']);
    }
}

/**
 * @internal
 * @coversNothing
 */
class TopicSubscriptionTest extends AbstractTestCase
{
    public function testTopicSubscriptionCreation()
    {
        $config = [
            'topic' => 'sensors/temperature',
            'qos' => 1,
            'auto_subscribe' => true,
            'handler' => 'TemperatureHandler',
            'metadata' => ['unit' => 'celsius'],
        ];

        $subscription = new TopicSubscription($config);

        $this->assertEquals('sensors/temperature', $subscription->getTopic());
        $this->assertEquals(1, $subscription->getQos());
        $this->assertTrue($subscription->isAutoSubscribe());
        $this->assertEquals('TemperatureHandler', $subscription->getHandler());
        $this->assertEquals(['unit' => 'celsius'], $subscription->getMetadata());
    }

    public function testTopicSubscriptionFluentInterface()
    {
        $subscription = new TopicSubscription(['topic' => 'test', 'qos' => 0]);

        $result = $subscription
            ->setTopic('sensors/humidity')
            ->setQos(2)
            ->setAutoSubscribe(false)
            ->setHandler('HumidityHandler')
            ->addMetadata('unit', 'percent');

        $this->assertSame($subscription, $result);
        $this->assertEquals('sensors/humidity', $subscription->getTopic());
        $this->assertEquals(2, $subscription->getQos());
        $this->assertFalse($subscription->isAutoSubscribe());
        $this->assertEquals('HumidityHandler', $subscription->getHandler());
        $this->assertEquals(['unit' => 'percent'], $subscription->getMetadata());
    }

    public function testTopicSubscriptionQosValidation()
    {
        $subscription = new TopicSubscription(['topic' => 'test', 'qos' => 0]);

        // Valid QoS values
        $subscription->setQos(0);
        $subscription->setQos(1);
        $subscription->setQos(2);

        // Invalid QoS should throw exception
        $this->expectException(\InvalidArgumentException::class);
        $subscription->setQos(3);
    }

    public function testTopicSubscriptionFilter()
    {
        $subscription = new TopicSubscription([
            'topic' => 'test/topic',
            'qos' => 1,
            'filter' => function ($data) {
                return $data['qos'] >= 1;
            },
        ]);

        $this->assertTrue($subscription->passesFilter());

        $subscription->setQos(0);
        $this->assertFalse($subscription->passesFilter());

        // No filter should always pass
        $subscription->setFilter(null);
        $this->assertTrue($subscription->passesFilter());
    }

    public function testTopicSubscriptionToTopicConfig()
    {
        $subscription = new TopicSubscription([
            'topic' => 'sensors/temperature',
            'qos' => 2,
            'no_local' => false,
            'retain_as_published' => false,
            'retain_handling' => 1,
        ]);

        $topicConfig = $subscription->toTopicConfig();

        $this->assertInstanceOf(TopicConfig::class, $topicConfig);
        $this->assertEquals('sensors/temperature', $topicConfig->topic);
        $this->assertEquals(2, $topicConfig->qos);
        $this->assertFalse($topicConfig->no_local);
        $this->assertFalse($topicConfig->retain_as_published);
        $this->assertEquals(1, $topicConfig->retain_handling);
    }

    public function testTopicSubscriptionTopicSanitization()
    {
        $subscription = new TopicSubscription(['topic' => 'test', 'qos' => 0]);

        // Test topic sanitization
        $subscription->setTopic("test\x00topic\x01with\x02control");
        $this->assertEquals('testtopicwithcontrol', $subscription->getTopic());
    }

    public function testTopicSubscriptionValidation()
    {
        $validSubscription = new TopicSubscription([
            'topic' => 'sensors/temperature',
            'qos' => 1,
        ]);

        $this->assertTrue($validSubscription->validate());

        $invalidSubscription = new TopicSubscription([
            'topic' => 'sensors/temp+/invalid',  // Invalid wildcard usage
            'qos' => 1,
        ]);

        $this->assertFalse($invalidSubscription->validate());
    }

    public function testTopicSubscriptionToArray()
    {
        $subscription = new TopicSubscription([
            'topic' => 'test/topic',
            'qos' => 1,
            'auto_subscribe' => false,
            'handler' => 'TestHandler',
            'metadata' => ['key' => 'value'],
        ]);

        $array = $subscription->toArray();

        $this->assertEquals('test/topic', $array['topic']);
        $this->assertEquals(1, $array['qos']);
        $this->assertFalse($array['auto_subscribe']);
        $this->assertEquals('TestHandler', $array['handler']);
        $this->assertEquals(['key' => 'value'], $array['metadata']);
    }
}
