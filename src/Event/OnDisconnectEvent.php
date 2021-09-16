<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Event;

use Nashgao\MQTT\Config\ClientConfig;

class OnDisconnectEvent
{
    public int $type;

    public int $code;

    public ?int $qos;

    public string $poolName;

    public ClientConfig $clientConfig;

    public function __construct(int $type, int $code, string $poolName, ClientConfig $clientConfig, int $qos = null)
    {
        $this->type = $type;
        $this->code = $code;
        $this->poolName = $poolName;
        $this->clientConfig = $clientConfig;
        $this->qos = $qos;
    }
}
