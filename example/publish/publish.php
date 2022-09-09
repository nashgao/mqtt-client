<?php

declare(strict_types=1);

use Nashgao\MQTT\Client;

require_once dirname(__DIR__) . '/boostrap.php';

\Swoole\Coroutine\run(
    function () {
        /** @var Client $client */
        $client = make(Client::class);
        $client->publish('topic/test', 'hi_mqtt', 2);
    }
);
