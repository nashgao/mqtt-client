<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Config;

use Nashgao\MQTT\Utils\ConfigValidator;

class TopicConfig
{
    public string $topic;

    public bool $enableMultisub = false;

    public int $multisubNum = 1;

    public bool $enableShareTopic = false;

    /**
     * @var array<string, array<string>>
     */
    public array $shareTopic = [];

    public bool $enableQueueTopic = false;

    public int $qos = 0;

    public bool $noLocal = true;

    public bool $retainAsPublished = true;

    public int $retainHandling = 2;

    public function __construct(array $params = [])
    {
        // Validate configuration before setting properties
        $validatedParams = ConfigValidator::validateTopicConfig($params);

        // Map snake_case config keys to camelCase properties
        $keyMapping = [
            'enable_multisub' => 'enableMultisub',
            'multisub_num' => 'multisubNum',
            'enable_share_topic' => 'enableShareTopic',
            'share_topic' => 'shareTopic',
            'enable_queue_topic' => 'enableQueueTopic',
            'no_local' => 'noLocal',
            'retain_as_published' => 'retainAsPublished',
            'retain_handling' => 'retainHandling',
        ];

        foreach ($validatedParams as $key => $value) {
            // Convert snake_case key to camelCase property name if mapping exists
            $propertyName = $keyMapping[$key] ?? $key;

            if (property_exists($this, $propertyName)) {
                $this->{$propertyName} = $value;
            }
        }
    }

    public function setTopic(string $topic): TopicConfig
    {
        $this->topic = $topic;
        return $this;
    }

    public function setEnableMultisub(bool $enableMultisub): TopicConfig
    {
        $this->enableMultisub = $enableMultisub;
        return $this;
    }

    public function setMultisubNum(int $multisubNum): TopicConfig
    {
        $this->multisubNum = $multisubNum;
        return $this;
    }

    public function setEnableShareTopic(bool $enableShareTopic): TopicConfig
    {
        $this->enableShareTopic = $enableShareTopic;
        return $this;
    }

    public function setShareTopic(array $shareTopic): TopicConfig
    {
        $this->shareTopic = $shareTopic;
        return $this;
    }

    public function setEnableQueueTopic(bool $enableQueueTopic): TopicConfig
    {
        $this->enableQueueTopic = $enableQueueTopic;
        return $this;
    }

    public function setQos(int $qos): TopicConfig
    {
        $this->qos = $qos;
        return $this;
    }

    public function setNoLocal(bool $noLocal): TopicConfig
    {
        $this->noLocal = $noLocal;
        return $this;
    }

    public function setRetainAsPublished(bool $retainAsPublished): TopicConfig
    {
        $this->retainAsPublished = $retainAsPublished;
        return $this;
    }

    public function setRetainHandling(int $retainHandling): TopicConfig
    {
        $this->retainHandling = $retainHandling;
        return $this;
    }

    public function getTopicProperties(): array
    {
        return [
            'qos' => $this->qos,
            'no_local' => $this->noLocal,
            'retain_as_published' => $this->retainAsPublished,
            'retain_handling' => $this->retainHandling,
        ];
    }
}
