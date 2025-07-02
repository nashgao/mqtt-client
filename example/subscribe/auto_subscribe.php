<?php

declare(strict_types=1);

use Hyperf\Context\ApplicationContext;
use Nashgao\MQTT\Listener\AfterWorkerStartListener;
use Simps\MQTT\Example\BaseExample;

require_once dirname(__DIR__, 2) . '/example/BaseExample.php';

class AutoSubscribeExample extends BaseExample
{
    protected function main(): void
    {
        $listener = new AfterWorkerStartListener(ApplicationContext::getContainer());
        $listener->process(new stdClass());
    }
}

(new AutoSubscribeExample())->execute();
