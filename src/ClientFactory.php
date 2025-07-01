<?php

declare(strict_types=1);

namespace Nashgao\MQTT;

use Hyperf\Coroutine\Coroutine;
use Nashgao\MQTT\Config\ClientConfig;

class ClientFactory
{
    public function create(ClientConfig $config, string $poolName): ClientProxy
    {
        $client = new ClientProxy($config, $poolName);
        Coroutine::create(
            function () use ($client) {
                $client->loop();
            }
        );

        Coroutine::create(
            function () use ($client, $config) {
                $client->connect($config->cleanSession, $config->will);
            }
        );

        return $client;
    }
}
