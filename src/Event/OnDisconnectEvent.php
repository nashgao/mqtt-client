<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Event;

class OnDisconnectEvent
{
    public int $type;

    public int $code;

    public ?int $qos;

    public function __construct(int $type, int $code, int $qos = null)
    {
        $this->type = $type;
        $this->code = $code;
        $this->qos = $qos;
    }
}
