<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Config;

use Nashgao\MQTT\Utils\ConfigValidator;

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

class TopicPublish
{
    public string $topic;

    public int $qos = 0;

    public bool $retain = false;

    public bool $dup = false;

    public array $properties = [];

    public ?string $messageClass = null;

    public array $defaultPayload = [];

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

        // Handle publish-specific options
        if (isset($config['retain'])) {
            $this->retain = (bool) $config['retain'];
        }

        if (isset($config['dup'])) {
            $this->dup = (bool) $config['dup'];
        }

        if (isset($config['message_class'])) {
            $this->messageClass = $config['message_class'];
        }

        if (isset($config['default_payload'])) {
            $this->defaultPayload = $config['default_payload'];
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

    public function isRetain(): bool
    {
        return $this->retain;
    }

    public function isDup(): bool
    {
        return $this->dup;
    }

    public function getMessageClass(): ?string
    {
        return $this->messageClass;
    }

    public function getDefaultPayload(): array
    {
        return $this->defaultPayload;
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

    public function setRetain(bool $retain): self
    {
        $this->retain = $retain;
        return $this;
    }

    public function setDup(bool $dup): self
    {
        $this->dup = $dup;
        return $this;
    }

    public function setMessageClass(?string $messageClass): self
    {
        $this->messageClass = $messageClass;
        return $this;
    }

    public function setDefaultPayload(array $payload): self
    {
        $this->defaultPayload = $payload;
        return $this;
    }

    public function addMetadata(string $key, $value): self
    {
        $this->metadata[$key] = $value;
        return $this;
    }

    public function createPublishParams(string $message = '', array $overrides = []): array
    {
        return array_merge([
            'topic' => $this->topic,
            'message' => $message ?: json_encode($this->defaultPayload),
            'qos' => $this->qos,
            'dup' => $this->dup ? 1 : 0,
            'retain' => $this->retain ? 1 : 0,
            'properties' => $this->properties,
        ], $overrides);
    }

    public function toArray(): array
    {
        $array = [
            'topic' => $this->topic,
            'qos' => $this->qos,
            'retain' => $this->retain,
            'dup' => $this->dup,
            'properties' => $this->properties,
        ];

        if ($this->messageClass !== null) {
            $array['message_class'] = $this->messageClass;
        }

        if (! empty($this->defaultPayload)) {
            $array['default_payload'] = $this->defaultPayload;
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
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
