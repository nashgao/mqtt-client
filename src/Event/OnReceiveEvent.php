<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Event;

readonly class OnReceiveEvent
{
    public function __construct(
        public string $poolName,
        public int $type,
        public ?int $dup,
        public ?int $qos,
        public ?int $retain,
        public ?string $topic,
        public ?int $message_id,
        public ?array $properties,
        public string|array|null $message
    ) {
    }
}
