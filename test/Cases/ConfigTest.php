<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Test\Cases;

use Nashgao\MQTT\Config\TopicConfig;
use Nashgao\MQTT\Test\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * @internal
 */
#[CoversNothing]
class ConfigTest extends AbstractTestCase
{
    public function testTopicConfigInstantiation()
    {
        $config = new TopicConfig();

        $this->assertInstanceOf(TopicConfig::class, $config);
        $this->assertFalse($config->enable_multisub);
        $this->assertFalse($config->enable_share_topic);
        $this->assertFalse($config->enable_queue_topic);
        $this->assertTrue($config->no_local);
        $this->assertTrue($config->retain_as_published);
        $this->assertEquals(2, $config->retain_handling);
    }

    public function testTopicConfigWithParams()
    {
        $params = [
            'topic' => 'test/topic',
            'qos' => 1,
            'enable_multisub' => true,
            'multisub_num' => 3,
            'no_local' => false,
            'retain_handling' => 1,
        ];

        $config = new TopicConfig($params);

        $this->assertEquals('test/topic', $config->topic);
        $this->assertEquals(1, $config->qos);
        $this->assertTrue($config->enable_multisub);
        $this->assertEquals(3, $config->multisub_num);
        $this->assertFalse($config->no_local);
        $this->assertEquals(1, $config->retain_handling);
    }

    public function testTopicConfigIgnoresInvalidParams()
    {
        $params = [
            'invalid_param' => 'value',
            'qos' => 2,
        ];

        $config = new TopicConfig($params);

        $this->assertEquals(2, $config->qos);
        $this->assertFalse(property_exists($config, 'invalid_param'));
    }

    public function testTopicConfigSetters()
    {
        $config = new TopicConfig();

        $result = $config->setTopic('my/topic')
            ->setQos(1)
            ->setEnableMultisub(true)
            ->setMultisubNum(5)
            ->setEnableShareTopic(true)
            ->setShareTopic(['group1', 'group2'])
            ->setEnableQueueTopic(true)
            ->setNoLocal(false)
            ->setRetainAsPublished(false)
            ->setRetainHandling(0);

        $this->assertInstanceOf(TopicConfig::class, $result);
        $this->assertEquals('my/topic', $config->topic);
        $this->assertEquals(1, $config->qos);
        $this->assertTrue($config->enable_multisub);
        $this->assertEquals(5, $config->multisub_num);
        $this->assertTrue($config->enable_share_topic);
        $this->assertEquals(['group1', 'group2'], $config->share_topic);
        $this->assertTrue($config->enable_queue_topic);
        $this->assertFalse($config->no_local);
        $this->assertFalse($config->retain_as_published);
        $this->assertEquals(0, $config->retain_handling);
    }

    public function testTopicConfigGetTopicProperties()
    {
        $config = new TopicConfig();
        $config->setQos(1)
            ->setNoLocal(false)
            ->setRetainAsPublished(false)
            ->setRetainHandling(1);

        $properties = $config->getTopicProperties();

        $this->assertEquals([
            'qos' => 1,
            'no_local' => false,
            'retain_as_published' => false,
            'retain_handling' => 1,
        ], $properties);
    }

    public function testTopicConfigFluentInterface()
    {
        $config = new TopicConfig();

        $result = $config->setTopic('test')
            ->setQos(2)
            ->setEnableMultisub(true);

        $this->assertSame($config, $result);
    }
}
