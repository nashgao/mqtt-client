<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Listener;


use Hyperf\Event\Contract\ListenerInterface;
use Nashgao\MQTT\Client;
use Nashgao\MQTT\Event\PublishEvent;

class OnPublishListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            PublishEvent::class
        ];
    }

    /**
     * @param PublishEvent|object $event
     */
    public function process(object $event)
    {
        /** @var Client $client */
        $client = make(Client::class);
        $client->setPoolName($event->poolName ?? 'default');
        $client->publish($event->topic, $event->message, $event->qos, $event->dup, $event->retain, $event->properties);
    }
}