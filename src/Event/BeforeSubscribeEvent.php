<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Event;

use Nashgao\MQTT\Config\TopicConfig;

class BeforeSubscribeEvent
{
    /**
     * @param TopicConfig[] $topicConfigs
     */
    public function __construct(
        public ?string $poolName = null,
        public ?array $topicConfigs = null
    ) {
    }

    public function setPoolName(string $poolName): BeforeSubscribeEvent
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
