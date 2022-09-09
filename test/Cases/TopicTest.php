<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Test\Cases;

use Hyperf\Utils\ApplicationContext;
use Nashgao\MQTT\Event\SubscribeEvent;
use Nashgao\MQTT\Test\AbstractTestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 * @coversNothing
 */
class TopicTest extends AbstractTestCase
{
    public function testTopic()
    {
        $dispatcher = ApplicationContext::getContainer()->get(EventDispatcherInterface::class);
        $dispatcher->dispatch(new SubscribeEvent());
    }
}
