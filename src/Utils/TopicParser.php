<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Utils;

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

    public static function generateTopicArray(string $topic, int $qos = 0): array
    {
        return [$topic => $qos];
    }
}
