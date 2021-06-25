<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Listener;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\AfterWorkerStart;
use Nashgao\MQTT\Client;
use Nashgao\MQTT\Config\TopicConfig;
use Nashgao\MQTT\Constants\MQTTConstants;
use Nashgao\MQTT\Utils\TopicParser;
use Psr\Container\ContainerInterface;

class AfterWorkerStartListener implements ListenerInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function listen(): array
    {
        return [
            AfterWorkerStart::class,
        ];
    }

    public function process(object $event)
    {
        //todo: consider how to add other attributes when client is subscribing
        $config = $this->container->get(ConfigInterface::class);
        foreach ($config->get('mqtt') ?? [] as $key => $value) {
            if ($key === MQTTConstants::SUBSCRIBE) {
                $subConfig = [];
                $multiSubConfig = [];
                foreach ($value as $topic) {
                    $topicConfig = new TopicConfig($topic);
                    if ($topicConfig->auto_subscribe) {
                        $subConfig[] = (function () use ($topicConfig, &$multiSubConfig) {
                            if ($topicConfig->enable_queue_topic) {
                                $topic = TopicParser::generateQueueTopic($topicConfig->topic);
                                if ($topicConfig->enable_multisub) {
                                    $multiSubConfig[$topic] = $topicConfig->multisub_num;
                                }
                                return TopicParser::generateTopicArray($topic, $topicConfig->qos);
                            }

                            if ($topicConfig->enable_share_topic) {
                                $shareTopics = [];
                                foreach ($topicConfig->share_topic as $groupName) {
                                    $topic = TopicParser::generateShareTopic($topicConfig->topic, $groupName);
                                    if ($topicConfig->enable_multisub) {
                                        $multiSubConfig[$topic] = $topicConfig->multisub_num;
                                    }
                                    $shareTopics[] = TopicParser::generateTopicArray($topic, $topicConfig->qos);
                                }
                                return $shareTopics;
                            }

                            if ($topicConfig->enable_multisub) {
                                $multiSubConfig[$topicConfig->topic] = $topicConfig->multisub_num;
                            }

                            return TopicParser::generateTopicArray($topicConfig->topic, $topicConfig->qos);
                        })();
                    }
                }

                if (! empty($subConfig)) {
                    $client = make(Client::class);
                    $properties = $value['properties'] ?? [];
                    foreach ($subConfig as $config) {
                        $key = key($config);
                        if (array_key_exists($key, $multiSubConfig)) {
                            $client->multiSub($config, $multiSubConfig[$key]);
                            continue;
                        }
                        $client->subscribe($config, $properties);
                    }
                }
            }
        }
    }
}
