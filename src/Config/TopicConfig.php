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
    
    public function setTopic(string $topic): TopicConfig
    {
        $this->topic = $topic;
        return $this;
    }
    
    public function setAutoSubscribe(bool $auto_subscribe): TopicConfig
    {
        $this->auto_subscribe = $auto_subscribe;
        return $this;
    }
    
    public function setEnableMultisub(bool $enable_multisub): TopicConfig
    {
        $this->enable_multisub = $enable_multisub;
        return $this;
    }
    
    public function setMultisubNum(int $multisub_num): TopicConfig
    {
        $this->multisub_num = $multisub_num;
        return $this;
    }
    
    public function setEnableShareTopic(bool $enable_share_topic): TopicConfig
    {
        $this->enable_share_topic = $enable_share_topic;
        return $this;
    }
    
    public function setShareTopic(array $share_topic): TopicConfig
    {
        $this->share_topic = $share_topic;
        return $this;
    }
    
    public function setEnableQueueTopic(bool $enable_queue_topic): TopicConfig
    {
        $this->enable_queue_topic = $enable_queue_topic;
        return $this;
    }
    
    public function setQos(int $qos): TopicConfig
    {
        $this->qos = $qos;
        return $this;
    }
    
    public function setNoLocal(bool $no_local): TopicConfig
    {
        $this->no_local = $no_local;
        return $this;
    }
    
    public function setRetainAsPublished(bool $retain_as_published): TopicConfig
    {
        $this->retain_as_published = $retain_as_published;
        return $this;
    }
    
    public function setRetainHandling(int $retain_handling): TopicConfig
    {
        $this->retain_handling = $retain_handling;
        return $this;
    }

   
}
