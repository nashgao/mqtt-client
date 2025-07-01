<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Event;

/**
 * dispatch publish event, create client and publish message.
 */
readonly class PublishEvent
{
    public function __construct(
        public string $topic,
        public string $message,
        public int $qos = 0,
        public int $dup = 0,
        public int $retain = 0,
        public array $properties = [],
        public ?string $poolName = null,
    ) {}
}
