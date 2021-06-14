<?php

declare(strict_types=1);

namespace Nashgao\MQTT;

use Nashgao\MQTT\Config\ClientConfig;
use Swoole\Coroutine;

class ClientFactory
{
    public function create(ClientConfig $config): ClientProxy
    {
        $client = new ClientProxy($config);
        Coroutine::create(
            function () use ($client) {
                $client->loop();
            }
        );

        Coroutine::create(
            function () use ($client) {
                $client->connect();
            }
        );

        return $client;
    }
}
