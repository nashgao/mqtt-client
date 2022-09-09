<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Listener;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Nashgao\MQTT\Event\OnSubscribeEvent;
use Simps\MQTT\Protocol\Types;

class OnSubscribeListener implements ListenerInterface
{
    protected StdoutLoggerInterface $logger;

    public function __construct(StdoutLoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function listen(): array
    {
        return [
            OnSubscribeEvent::class,
        ];
    }

    /**
     * @param OnSubscribeEvent $event
     */
    public function process(object $event): void
    {
        foreach ($event->topics as $topic => $value) {
            $this->logger->debug(
                \sprintf(
                    'Mqtt client: %s from %s pool subscribe to %s successful, result type as %s',
                    $event->clientId,
                    $event->poolName,
                    $topic,
                    Types::getType($event->result['type'])
                )
            );
        }
    }
}
