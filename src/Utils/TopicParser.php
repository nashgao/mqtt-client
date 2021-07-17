<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Utils;

use Nashgao\MQTT\Exception\InvalidConfigException;

class TopicParser
{
    const SHARE = '$share';

    const QUEUE = '$queue';

    const SEPARATOR = '/';

    public static function generateShareTopic(string $topic, string $group = 'default'): string
    {
        return join(static::SEPARATOR, [static::SHARE, $group, $topic]);
    }

    public static function generateQueueTopic(string $topic): string
    {
        return join(static::SEPARATOR, [static::QUEUE, $topic]);
    }

    public static function generateTopicArray(string $topic, array $properties = []): array
    {
        if (! array_key_exists('qos', $properties)) {
            throw new InvalidConfigException(
                sprintf('invalid config, must have qos')
            );
        }
        return [$topic => $properties];
    }
}
