<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Listener;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Nashgao\MQTT\Event\OnDisconnectEvent;
use Simps\MQTT\Hex\ReasonCode;

class OnDisconnectListener implements ListenerInterface
{
    protected StdoutLoggerInterface $logger;

    public function __construct(StdoutLoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function listen(): array
    {
        return [
            OnDisconnectEvent::class,
        ];
    }

    /**
     * @param object|OnDisconnectEvent $event
     */
    public function process(object $event)
    {
        $this->logger->debug(sprintf("broker is disconnected, the reason is %s [%d]\n", ReasonCode::getReasonPhrase($event->code), $event->code));
    }
}
