<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Listener;

use Hyperf\Event\Contract\ListenerInterface;
use Nashgao\MQTT\Client;
use Nashgao\MQTT\Event\PublishEvent;

class PublishListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            PublishEvent::class,
        ];
    }

    /**
     * @param object|PublishEvent $event
     */
    public function process(object $event): void
    {
        /** @var Client $client */
        $client = make(Client::class);
        $client->setPoolName($event->poolName ?? 'default');
        $client->publish($event->topic, $event->message, $event->qos, $event->dup, $event->retain, $event->properties);
    }
}
