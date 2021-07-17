<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Event;

use Nashgao\MQTT\Client;
use Nashgao\MQTT\Config\TopicConfig;

class OnSubscribeEvent
{
    public Client $client;

    /**
     * @var TopicConfig[]
     */
    public array $topicConfigs;

    public function setClient(Client $client): OnSubscribeEvent
    {
        $this->client = $client;
        return $this;
    }

    public function setTopicConfigs(array $topicConfigs): static
    {
        $this->topicConfigs = $topicConfigs;
        return $this;
    }
}
