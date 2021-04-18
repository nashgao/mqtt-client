<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Event;

use Simps\MQTT\Client;

class OnDisconnectEvent
{
    public int $type;

    public int $code;

    public int $qos;

    public Client $client;

    public function __construct(int $type, int $code, int $qos, Client $client)
    {
        $this->type = $type;
        $this->code = $code;
        $this->qos = $qos;
        $this->client = $client;
    }
}
