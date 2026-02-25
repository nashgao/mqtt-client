<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Debug;

use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\AfterWorkerStart;

/**
 * Starts the DebugTapServer when a Hyperf worker starts.
 *
 * This listener is responsible for initializing the debug tap Unix socket server
 * so that debug clients can connect and receive MQTT message streams.
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
        ];
    }

    public function process(object $event): void
    {
        // Start the debug tap server (will check if enabled internally)
        $this->server->start();
    }
}
