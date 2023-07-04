<?php

declare(strict_types=1);

use Nashgao\MQTT\Client;

use function Hyperf\Support\make;

require_once dirname(__DIR__) . '/boostrap.php';

\Swoole\Coroutine\run(
    function () {
        /** @var Client $client */
        $client = make(Client::class);
        $client->subscribe([
            'topic/test' => [
                'qos' => 2,
            ],
        ]);
    }
);
