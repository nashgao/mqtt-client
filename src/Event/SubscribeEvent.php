<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Event;

use Nashgao\MQTT\Config\TopicConfig;

/**
 * dispatch subscribe event, create client and subscribe topics.
 */
class SubscribeEvent
{
    /**
     * @param TopicConfig[] $topicConfigs
     */
    public function __construct(
        public ?string $poolName = null,
        public ?array $topicConfigs = null,
    ) {
    }

    public function setPoolName(string $poolName): static
    {
        $this->poolName = $poolName;
        return $this;
    }

    public function setTopicConfigs(array $topicConfigs): static
    {
        $this->topicConfigs = $topicConfigs;
        return $this;
    }
}
