<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Config;

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
