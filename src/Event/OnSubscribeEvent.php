<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Event;

use Nashgao\MQTT\Config\TopicConfig;

class OnSubscribeEvent
{
    public string $poolName;

    /**
     * @var TopicConfig[]
     */
    public array $topicConfigs;

    public function __construct(array $configs)
    {
        foreach ($configs as $name => $value) {
            if (isset($value) and property_exists($this, $name)) {
                $this->{$name} = $value;
            }
        }
    }

    public function setPoolName(string $poolName): OnSubscribeEvent
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
