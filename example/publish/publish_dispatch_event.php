<?php

declare(strict_types=1);

use Hyperf\Event\EventDispatcher;
use Hyperf\Context\ApplicationContext;
use Nashgao\MQTT\Event\PublishEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

require_once __DIR__ . '/boostrap.php';

\Swoole\Coroutine\run(
    function () {
        /** @var EventDispatcher $dispatcher */
        $dispatcher = ApplicationContext::getContainer()->get(EventDispatcherInterface::class);
        $dispatcher->dispatch(new PublishEvent('topic/test', 'hi mqtt', 2));
    }
);
