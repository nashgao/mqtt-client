<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Config;

use Nashgao\MQTT\Utils\ConfigValidator;

class TopicSubscriptionConfig
{
    private array $topics = [];

    private array $globalOptions = [];

    public function __construct(array $config = [])
    {
        if (isset($config['topics'])) {
            foreach ($config['topics'] as $topicData) {
                $this->addTopic(new TopicSubscription($topicData));
            }
        }

        // Store global subscription options
        $this->globalOptions = array_diff_key($config, ['topics' => null]);
    }

    public function addTopic(TopicSubscription $topic): self
    {
        $this->topics[] = $topic;
        return $this;
    }

    public function getTopics(): array
    {
        return $this->topics;
    }

    public function getAutoSubscribeTopics(): array
    {
        return array_filter($this->topics, fn (TopicSubscription $topic) => $topic->isAutoSubscribe());
    }

    public function hasAutoSubscriptions(): bool
    {
        return count($this->getAutoSubscribeTopics()) > 0;
    }

    public function getFilteredTopics(): array
    {
        $filtered = [];
        foreach ($this->topics as $topic) {
            if ($topic->passesFilter()) {
                $filtered[] = $topic;
            }
        }
        return $filtered;
    }

    public function getTopicsByPattern(string $pattern): array
    {
        return array_filter($this->topics, function (TopicSubscription $topic) use ($pattern) {
            return fnmatch($pattern, $topic->getTopic());
        });
    }

    public function getGlobalOptions(): array
    {
        return $this->globalOptions;
    }

    public function setGlobalOption(string $key, $value): self
    {
        $this->globalOptions[$key] = $value;
        return $this;
    }

    public function getTopicConfigs(): array
    {
        $configs = [];
        foreach ($this->getAutoSubscribeTopics() as $topic) {
            $configs[] = $topic->toTopicConfig();
        }
        return $configs;
    }

    public function count(): int
    {
        return count($this->topics);
    }

    public function isEmpty(): bool
    {
        return empty($this->topics);
    }

    public function toArray(): array
    {
        return array_merge($this->globalOptions, [
            'topics' => array_map(fn (TopicSubscription $topic) => $topic->toArray(), $this->topics),
        ]);
    }

    public function validate(): bool
    {
        foreach ($this->topics as $topic) {
            if (! $topic->validate()) {
                return false;
            }
        }
        return true;
    }
}

class TopicSubscription
{
    public string $topic;

    public int $qos = 0;

    public bool $autoSubscribe = true;

    public bool $noLocal = true;

    public bool $retainAsPublished = true;

    public int $retainHandling = 2;

    public $filter;

    public array $properties = [];

    public ?string $handler = null;

    public array $metadata = [];

    public function __construct(array $config = [])
    {
        // Validate topic configuration
        $validatedConfig = ConfigValidator::validateTopicConfig($config);

        foreach ($validatedConfig as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }

        // Handle special cases and property mapping
        if (isset($config['auto_subscribe'])) {
            $this->autoSubscribe = (bool) $config['auto_subscribe'];
        }

        if (isset($config['no_local'])) {
            $this->noLocal = (bool) $config['no_local'];
        }

        if (isset($config['retain_as_published'])) {
            $this->retainAsPublished = (bool) $config['retain_as_published'];
        }

        if (isset($config['retain_handling'])) {
            $this->retainHandling = (int) $config['retain_handling'];
        }

        if (isset($config['filter']) && is_callable($config['filter'])) {
            $this->filter = $config['filter'];
        }

        if (isset($config['handler'])) {
            $this->handler = $config['handler'];
        }

        if (isset($config['metadata'])) {
            $this->metadata = $config['metadata'];
        }
    }

    public function getTopic(): string
    {
        return $this->topic;
    }

    public function getQos(): int
    {
        return $this->qos;
    }

    public function isAutoSubscribe(): bool
    {
        return $this->autoSubscribe;
    }

    public function passesFilter(): bool
    {
        if ($this->filter === null) {
            return true;
        }

        return ($this->filter)($this->toArray());
    }

    public function getHandler(): ?string
    {
        return $this->handler;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setTopic(string $topic): self
    {
        $this->topic = ConfigValidator::sanitizeTopicName($topic);
        return $this;
    }

    public function setQos(int $qos): self
    {
        if (! in_array($qos, [0, 1, 2], true)) {
            throw new \InvalidArgumentException("Invalid QoS level: {$qos}. Must be 0, 1, or 2");
        }
        $this->qos = $qos;
        return $this;
    }

    public function setAutoSubscribe(bool $autoSubscribe): self
    {
        $this->autoSubscribe = $autoSubscribe;
        return $this;
    }

    public function setFilter(?callable $filter): self
    {
        $this->filter = $filter;
        return $this;
    }

    public function setHandler(?string $handler): self
    {
        $this->handler = $handler;
        return $this;
    }

    public function addMetadata(string $key, $value): self
    {
        $this->metadata[$key] = $value;
        return $this;
    }

    public function toTopicConfig(): TopicConfig
    {
        return new TopicConfig([
            'topic' => $this->topic,
            'qos' => $this->qos,
            'no_local' => $this->noLocal,
            'retain_as_published' => $this->retainAsPublished,
            'retain_handling' => $this->retainHandling,
        ]);
    }

    public function toArray(): array
    {
        $array = [
            'topic' => $this->topic,
            'qos' => $this->qos,
            'auto_subscribe' => $this->autoSubscribe,
            'no_local' => $this->noLocal,
            'retain_as_published' => $this->retainAsPublished,
            'retain_handling' => $this->retainHandling,
            'properties' => $this->properties,
        ];

        if ($this->handler !== null) {
            $array['handler'] = $this->handler;
        }

        if (! empty($this->metadata)) {
            $array['metadata'] = $this->metadata;
        }

        return $array;
    }

    public function validate(): bool
    {
        try {
            ConfigValidator::validateTopicConfig($this->toArray());
            return ConfigValidator::validateTopicFilter($this->topic);
        } catch (\Exception $e) {
            return false;
        }
    }
}
