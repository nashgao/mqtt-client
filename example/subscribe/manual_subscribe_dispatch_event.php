<?php

declare(strict_types=1);

use Hyperf\Utils\ApplicationContext;
use Nashgao\MQTT\Config\TopicConfig;
use Nashgao\MQTT\Event\SubscribeEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

require_once dirname(__DIR__) . '/boostrap.php';

\Swoole\Coroutine\run(
    function () {
        $event = new SubscribeEvent(topicConfigs: [
            new TopicConfig([
                'topic' => 'topic/test',
                'qos' => 2
            ])
        ]);
        $dispatcher = ApplicationContext::getContainer()->get(EventDispatcherInterface::class);
        $dispatcher->dispatch($event);
    }
);
