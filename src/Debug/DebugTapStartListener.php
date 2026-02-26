<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Debug;

use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\AfterWorkerStart;
use Hyperf\Framework\Event\OnPipeMessage;

/**
 * Manages DebugTapServer lifecycle across Swoole workers.
 *
 * - Worker 0: starts the Unix socket server and handles relayed events from other workers.
 * - Workers 1+: store Swoole server context so events can be relayed to worker 0 via PipeMessage.
 */
final class DebugTapStartListener implements ListenerInterface
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
            AfterWorkerStart::class,
            OnPipeMessage::class,
        ];
    }

    public function process(object $event): void
    {
        if ($event instanceof AfterWorkerStart) {
            $this->server->setWorkerContext($event->workerId, $event->server);

            if ($event->workerId === 0) {
                $this->server->start();
            }

            return;
        }

        if ($event instanceof OnPipeMessage) {
            $this->server->handlePipeMessage($event->data);
        }
    }
}
