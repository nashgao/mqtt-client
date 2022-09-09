<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Event;

use Nashgao\MQTT\Config\ClientConfig;

class OnDisconnectEvent
{
    public function __construct(
        public int $type,
        public int $code,
        public string $poolName,
        public ClientConfig $clientConfig,
        public ?int $qos = null
    ) {
    }
}
