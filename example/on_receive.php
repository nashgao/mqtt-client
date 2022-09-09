<?php

declare(strict_types=1);

use Hyperf\Event\Contract\ListenerInterface;
use Nashgao\MQTT\Event\OnReceiveEvent;

class OnReceiveListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            OnReceiveEvent::class,
        ];
    }

    /**
     * @param OnReceiveEvent $event
     */
    public function process(object $event): void
    {
        var_dump($event->message);
    }
}

return new OnReceiveListener();
