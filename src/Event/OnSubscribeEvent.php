<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Event;

class OnSubscribeEvent
{
    public function __construct(
        public string $poolName,
        public string $clientId,
        public array $topics,
        public mixed $result
    ) {

    }
}
