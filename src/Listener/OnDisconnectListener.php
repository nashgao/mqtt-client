<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Listener;

use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Nashgao\MQTT\Event\OnDisconnectEvent;
use Psr\Log\LoggerInterface;
use Simps\MQTT\Hex\ReasonCode;

/**
 * @Listener
 */
class OnDisconnectListener implements ListenerInterface
{
    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
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
        $this->logger->debug(sprintf("Broker is disconnected, the reason is %s [%d]\n", ReasonCode::getReasonPhrase($event->code), $event->code));
        $event->client->close($event->code);
    }
}
