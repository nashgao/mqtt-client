<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Test\Cases;

use Nashgao\MQTT\Config\TopicConfig;
use Nashgao\MQTT\Config\TopicSubscription;
use Nashgao\MQTT\Test\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * @internal
 */
#[CoversNothing]
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
        $this->assertFalse($topicConfig->noLocal);
        $this->assertFalse($topicConfig->retainAsPublished);
        $this->assertEquals(1, $topicConfig->retainHandling);
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
