<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Event;

class OnReceiveEvent
{
    public int $type;

    public ?int $dup;

    public ?int $qos;

    public ?int $retain;

    public ?string $topic;

    public ?int $message_id;

    public ?array $properties;

    public string|array|null $message;

    public function __construct(int $type, ?int $dup, ?int $qos, ?int $retain, ?string $topic, ?int $message_id, ?array $properties, string|array|null $message)
    {
        $this->type = $type;
        $this->dup = $dup;
        $this->qos = $qos;
        $this->retain = $retain;
        $this->topic = $topic;
        $this->message_id = $message_id;
        $this->properties = $properties;
        $this->message = $message;
    }
}
