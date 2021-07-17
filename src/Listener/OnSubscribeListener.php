<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Listener;

use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Nashgao\MQTT\Config\TopicConfig;
use Nashgao\MQTT\Event\OnSubscribeEvent;
use Nashgao\MQTT\Utils\TopicParser;

/**
 * @Listener
 */
class OnSubscribeListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            OnSubscribeEvent::class,
        ];
    }

    /**
     * @param object|OnSubscribeEvent $event
     */
    public function process(object $event)
    {
        if (! empty($event->topicConfigs)) {
            $subscribeConfigs = [];
            $multiSubscribeConfigs = [];
            /** @var TopicConfig $topicConfig */
            foreach ($event->topicConfigs as $topicConfig) {
                (function () use ($topicConfig, &$multiSubscribeConfigs, &$subscribeConfigs): void {
                    /* handle queue topic first, has higher priority */
                    if ($topicConfig->enable_queue_topic) {
                        $topic = TopicParser::generateQueueTopic($topicConfig->topic);
                        if ($topicConfig->enable_multisub) {
                            $multiSubscribeConfigs[$topic] = $topicConfig->multisub_num;
                        }
                        $subscribeConfigs[] = TopicParser::generateTopicArray($topic, $topicConfig->getTopicProperties());
                        return;
                    }

                    if ($topicConfig->enable_share_topic) {
                        $shareTopics = [];
                        foreach ($topicConfig->share_topic as $groupNames) {
                            foreach ($groupNames as $groupName) {
                                $topic = TopicParser::generateShareTopic($topicConfig->topic, $groupName);
                                if ($topicConfig->enable_multisub) {
                                    $multiSubscribeConfigs[$topic] = $topicConfig->multisub_num;
                                }
                                $shareTopics[] = TopicParser::generateTopicArray($topic, $topicConfig->getTopicProperties());
                            }
                        }

                        $subscribeConfigs = array_merge($subscribeConfigs, $shareTopics);
                        return;
                    }

                    if ($topicConfig->enable_multisub) {
                        $multiSubscribeConfigs[$topicConfig->topic] = $topicConfig->multisub_num;
                    }

                    $subscribeConfigs[] = TopicParser::generateTopicArray($topicConfig->topic, $topicConfig->getTopicProperties());
                })();
            }
            if (! empty($subscribeConfigs)) {
                $properties = $value['properties'] ?? [];
                foreach ($subscribeConfigs as $subscribeConfig) {
                    if (array_key_exists(key($subscribeConfig), $multiSubscribeConfigs)) {
                        $event->client->multiSub($subscribeConfig, $properties, $multiSubscribeConfigs[key($subscribeConfig)]);
                        continue;
                    }

                    $event->client->subscribe($subscribeConfig, $properties);
                }
            }
        }
    }
}
