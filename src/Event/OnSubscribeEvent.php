<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Event;

/**
 * after sending subscribe event, dispatch this event to return results of if subscription is successful or not.
 */
readonly class OnSubscribeEvent
{
    public function __construct(
        public string $poolName,
        public string $clientId,
        public array $topics,
        public mixed $result
    ) {}
}
