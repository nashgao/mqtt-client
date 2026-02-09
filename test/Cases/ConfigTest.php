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
        $this->assertFalse($config->enableMultisub);
        $this->assertFalse($config->enableShareTopic);
        $this->assertFalse($config->enableQueueTopic);
        $this->assertTrue($config->noLocal);
        $this->assertTrue($config->retainAsPublished);
        $this->assertEquals(2, $config->retainHandling);
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
        $this->assertTrue($config->enableMultisub);
        $this->assertEquals(3, $config->multisubNum);
        $this->assertFalse($config->noLocal);
        $this->assertEquals(1, $config->retainHandling);
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
        $this->assertTrue($config->enableMultisub);
        $this->assertEquals(5, $config->multisubNum);
        $this->assertTrue($config->enableShareTopic);
        $this->assertEquals(['group1', 'group2'], $config->shareTopic);
        $this->assertTrue($config->enableQueueTopic);
        $this->assertFalse($config->noLocal);
        $this->assertFalse($config->retainAsPublished);
        $this->assertEquals(0, $config->retainHandling);
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
