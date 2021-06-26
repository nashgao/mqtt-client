<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Config;

class TopicConfig
{
    public string $topic;

    public bool $auto_subscribe;

    public bool $enable_multisub;

    public int $multisub_num;

    public bool $enable_share_topic;

    public array $share_topic;

    public bool $enable_queue_topic;

    public int $qos;

    public bool $no_local;

    public bool $retain_as_published;

    public int $retain_handling;

    public function __construct($params)
    {
        foreach ($params ?? [] as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    public function setTopic(string $topic): void
    {
        $this->topic = $topic;
    }

    public function setAutoSubscribe(bool $auto_subscribe): void
    {
        $this->auto_subscribe = $auto_subscribe;
    }

    public function setEnableMultisub(bool $enable_multisub): void
    {
        $this->enable_multisub = $enable_multisub;
    }

    public function setMultisubNum(int $multisub_num): void
    {
        $this->multisub_num = $multisub_num;
    }

    public function setEnableShareTopic(bool $enable_share_topic): void
    {
        $this->enable_share_topic = $enable_share_topic;
    }

    public function setShareTopic(array $share_topic): void
    {
        $this->share_topic = $share_topic;
    }

    public function setEnableQueueTopic(bool $enable_queue_topic): void
    {
        $this->enable_queue_topic = $enable_queue_topic;
    }

    public function setQos(int $qos): void
    {
        $this->qos = $qos;
    }

    public function setNoLocal(bool $no_local): void
    {
        $this->no_local = $no_local;
    }

    public function setRetainAsPublished(bool $retain_as_published): void
    {
        $this->retain_as_published = $retain_as_published;
    }

    public function setRetainHandling(int $retain_handling): void
    {
        $this->retain_handling = $retain_handling;
    }
}
