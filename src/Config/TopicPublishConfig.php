<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Config;

class TopicPublishConfig
{
    private array $topics = [];

    private array $globalOptions = [];

    public function __construct(array $config = [])
    {
        if (isset($config['topics'])) {
            foreach ($config['topics'] as $topicData) {
                $this->addTopic(new TopicPublish($topicData));
            }
        }

        // Store global publish options
        $this->globalOptions = array_diff_key($config, ['topics' => null]);
    }

    public function addTopic(TopicPublish $topic): self
    {
        $this->topics[] = $topic;
        return $this;
    }

    public function getTopics(): array
    {
        return $this->topics;
    }

    public function getTopicByName(string $name): ?TopicPublish
    {
        foreach ($this->topics as $topic) {
            if ($topic->getTopic() === $name) {
                return $topic;
            }
        }
        return null;
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
            'topics' => array_map(fn (TopicPublish $topic) => $topic->toArray(), $this->topics),
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
