<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Utils;

use Nashgao\MQTT\Config\TopicConfig;
use Nashgao\MQTT\Exception\InvalidConfigException;

class TopicParser
{
    public const SHARE = '$share';

    public const QUEUE = '$queue';

    public const SEPARATOR = '/';

    public static function generateShareTopic(string $topic, string $group = 'default'): string
    {
        return join(static::SEPARATOR, [static::SHARE, $group, $topic]);
    }

    public static function generateQueueTopic(string $topic): string
    {
        return join(static::SEPARATOR, [static::QUEUE, $topic]);
    }

    /**
     * @throws InvalidConfigException
     */
    public static function generateTopicArray(string $topic, array $properties = []): array
    {
        if (! array_key_exists('qos', $properties)) {
            throw new InvalidConfigException('invalid config, must have qos');
        }
        return [$topic => $properties];
    }

    public static function parseTopic(string $topic, int $qos = 0, array $properties = []): TopicConfig
    {
        $topicTemplate = new TopicConfig($properties);
        $topicTemplate->setQos($qos);
        // queue topic has higher priority
        if (($pos = strpos($topic, static::QUEUE)) !== false) {
            return $topicTemplate->setEnableQueueTopic(true)->setTopic(substr($topic, $pos + strlen(static::QUEUE) + 1));
        }

        if (($pos = strpos($topic, static::SHARE)) !== false) {
            $groupTopic = substr($topic, $pos + strlen(static::SHARE) + 1);
            $topicArray = explode(static::SEPARATOR, $groupTopic);
            $group = current($topicArray);
            $group = ltrim((string) $group, '$');
            array_shift($topicArray);
            return $topicTemplate->setEnableShareTopic(true)
                ->setShareTopic(['group_name' => [$group]])
                ->setTopic(join(self::SEPARATOR, $topicArray));
        }

        return $topicTemplate->setTopic($topic);
    }
}
