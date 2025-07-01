<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Listener;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Nashgao\MQTT\Event\OnPublishEvent;

class OnPublishListener implements ListenerInterface
{
    protected StdoutLoggerInterface $logger;

    public function __construct(StdoutLoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function listen(): array
    {
        return [
            OnPublishEvent::class,
        ];
    }

    /**
     * @param OnPublishEvent $event
     */
    public function process(object $event): void {}
}
