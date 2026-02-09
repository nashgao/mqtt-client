<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Debug;

use Hyperf\Event\Contract\ListenerInterface;
use Nashgao\MQTT\Event\OnDisconnectEvent;
use Nashgao\MQTT\Event\OnPublishEvent;
use Nashgao\MQTT\Event\OnReceiveEvent;
use Nashgao\MQTT\Event\OnSubscribeEvent;

/**
 * Listener that forwards MQTT events to the debug tap server for streaming to debug clients.
 *
 * This listener hooks into:
 * - OnReceiveEvent: Incoming MQTT messages
 * - OnPublishEvent: Outgoing publish confirmations
 * - OnSubscribeEvent: Subscription confirmations
 * - OnDisconnectEvent: Disconnection events
 */
final class DebugTapListener implements ListenerInterface
{
    public function __construct(
        private readonly DebugTapServer $server,
    ) {}

    /**
     * @return array<class-string>
     */
    public function listen(): array
    {
        return [
            OnReceiveEvent::class,
            OnPublishEvent::class,
            OnSubscribeEvent::class,
            OnDisconnectEvent::class,
        ];
    }

    public function process(object $event): void
    {
        if (! $this->server->isEnabled() || ! $this->server->isRunning()) {
            return;
        }

        // Process pending connections and commands
        $this->server->tick();

        // Forward event to debug clients
        match (true) {
            $event instanceof OnReceiveEvent => $this->handleReceive($event),
            $event instanceof OnPublishEvent => $this->handlePublish($event),
            $event instanceof OnSubscribeEvent => $this->handleSubscribe($event),
            $event instanceof OnDisconnectEvent => $this->handleDisconnect($event),
            default => null,
        };
    }

    private function handleReceive(OnReceiveEvent $event): void
    {
        if ($event->topic === null) {
            return;
        }

        $this->server->broadcastPublish(
            topic: $event->topic,
            message: $event->message,
            qos: $event->qos ?? 0,
            poolName: $event->poolName,
            metadata: [
                'direction' => 'incoming',
                'type' => $event->type,
                'dup' => $event->dup,
                'retain' => $event->retain,
                'message_id' => $event->message_id,
                'properties' => $event->properties ?? [],
            ],
        );
    }

    private function handlePublish(OnPublishEvent $event): void
    {
        $this->server->broadcastPublish(
            topic: $event->topic,
            message: $event->message,
            qos: $event->qos,
            poolName: $event->poolName,
            metadata: [
                'direction' => 'outgoing',
                'result' => $event->result,
            ],
        );
    }

    private function handleSubscribe(OnSubscribeEvent $event): void
    {
        $this->server->broadcastSubscribe(
            topics: $event->topics,
            clientId: $event->clientId,
            poolName: $event->poolName,
            metadata: [
                'result' => $event->result,
            ],
        );
    }

    private function handleDisconnect(OnDisconnectEvent $event): void
    {
        $this->server->broadcastDisconnect(
            type: $event->type,
            code: $event->code,
            poolName: $event->poolName,
            metadata: [
                'qos' => $event->qos,
                'host' => $event->clientConfig->host,
                'port' => $event->clientConfig->port,
            ],
        );
    }
}
