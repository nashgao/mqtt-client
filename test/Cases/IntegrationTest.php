<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Test\Cases;

use Nashgao\MQTT\Client;
use Nashgao\MQTT\Pool\PoolFactory;
use Nashgao\MQTT\Test\AbstractTestCase;
use Nashgao\MQTT\Utils\Qos;
use Nashgao\MQTT\Utils\TopicParser;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * @internal
 */
#[CoversNothing]
class IntegrationTest extends AbstractTestCase
{
    public function testClientInstantiationWithMultipleComponents()
    {
        $poolFactory = $this->createMock(PoolFactory::class);
        $client = new Client($poolFactory);

        $this->assertInstanceOf(Client::class, $client);

        // Test pool name chaining
        $result = $client->setPoolName('test-pool');
        $this->assertSame($client, $result);
    }

    public function testShareTopicIntegration()
    {
        $originalTopic = 'data/processing/queue';
        $group = 'worker-group';

        $shareTopic = TopicParser::generateShareTopic($originalTopic, $group);
        $this->assertEquals('$share/worker-group/data/processing/queue', $shareTopic);

        $parsedConfig = TopicParser::parseTopic($shareTopic, Qos::QOS_AT_LEAST_ONCE);

        $this->assertEquals($originalTopic, $parsedConfig->topic);
        $this->assertTrue($parsedConfig->enableShareTopic);
        $this->assertEquals(['group_name' => [$group]], $parsedConfig->shareTopic);
        $this->assertEquals(Qos::QOS_AT_LEAST_ONCE, $parsedConfig->qos);
    }

    public function testQueueTopicIntegration()
    {
        $originalTopic = 'commands/device/control';

        $queueTopic = TopicParser::generateQueueTopic($originalTopic);
        $this->assertEquals('$queue/commands/device/control', $queueTopic);

        $parsedConfig = TopicParser::parseTopic($queueTopic, Qos::QOS_EXACTLY_ONCE);

        $this->assertEquals($originalTopic, $parsedConfig->topic);
        $this->assertTrue($parsedConfig->enableQueueTopic);
        $this->assertFalse($parsedConfig->enableShareTopic);
        $this->assertEquals(Qos::QOS_EXACTLY_ONCE, $parsedConfig->qos);
    }

    public function testTopicArrayGenerationWithValidation()
    {
        $topic = 'sensors/data';
        $properties = ['qos' => Qos::QOS_AT_LEAST_ONCE];

        $topicArray = TopicParser::generateTopicArray($topic, $properties);

        $this->assertEquals([$topic => $properties], $topicArray);
    }

    public function testQosConstants()
    {
        // Test all QoS levels exist
        $this->assertEquals(0, Qos::QOS_AT_MOST_ONCE);
        $this->assertEquals(1, Qos::QOS_AT_LEAST_ONCE);
        $this->assertEquals(2, Qos::QOS_EXACTLY_ONCE);
    }
}
