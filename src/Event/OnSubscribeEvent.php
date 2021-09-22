<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Event;

class OnSubscribeEvent
{
    public function __construct(
        public string $client_id,
        public array $topics,
        public mixed $result
    ) {

    }
}
