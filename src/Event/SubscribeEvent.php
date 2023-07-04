<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Event;

use Nashgao\MQTT\Config\TopicConfig;

/**
 * dispatch subscribe event, create client and subscribe topics.
 */
readonly class SubscribeEvent
{
    /**
     * @param TopicConfig[] $topicConfigs
     */
    public function __construct(
        public string $poolName = 'default',
        public ?array $topicConfigs = null,
    ) {
    }
}
