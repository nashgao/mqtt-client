<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Event;

use Nashgao\MQTT\ClientProxy;

class OnDisconnectEvent
{
    public int $type;

    public int $code;

    public ?int $qos;

    public ?ClientProxy $client;

    public function __construct(int $type, int $code, int $qos = null, ClientProxy $client = null)
    {
        $this->type = $type;
        $this->code = $code;
        $this->qos = $qos;
        $this->client = $client;
    }
}
