<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Event;

/**
 * dispatch publish event, create client and publish message.
 */
class PublishEvent
{
    public function __construct(
        public string $topic,
        public string $message,
        public int $qos = 0,
        public int $dup = 0,
        public int $retain = 0,
        public array $properties = [],
        public ?string $poolName = null,
    ) {
    }

    public function setTopic(string $topic): static
    {
        $this->topic = $topic;
        return $this;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function setQos(int $qos): static
    {
        $this->qos = $qos;
        return $this;
    }

    public function setDup(int $dup): static
    {
        $this->dup = $dup;
        return $this;
    }

    public function setRetain(int $retain): static
    {
        $this->retain = $retain;
        return $this;
    }

    public function setProperties(array $properties): static
    {
        $this->properties = $properties;
        return $this;
    }

    public function setPoolName(?string $poolName): static
    {
        $this->poolName = $poolName;
        return $this;
    }
}
