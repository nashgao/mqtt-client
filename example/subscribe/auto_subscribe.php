<?php

declare(strict_types=1);

use Hyperf\Utils\ApplicationContext;
use Nashgao\MQTT\Listener\AfterWorkerStartListener;

require_once dirname(__DIR__) . '/boostrap.php';

\Swoole\Coroutine\run(
    function () {
        $listener = new AfterWorkerStartListener(ApplicationContext::getContainer());
        $listener->process(new stdClass());
    }
);
