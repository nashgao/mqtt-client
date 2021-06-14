<?php

declare(strict_types=1);

namespace Nashgao\MQTT;

use Nashgao\MQTT\Config\ClientConfig;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;

class ClientFactory
{
    public Channel $channel;

    public function __construct()
    {
        $this->channel = new Channel();
    }

    public function create(ClientConfig $config)
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

        for (;;) {
            $this->channel->push($client->recv());
        }
    }
}
