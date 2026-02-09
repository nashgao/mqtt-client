<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Listener;

use Hyperf\Event\Contract\ListenerInterface;
use Nashgao\MQTT\Client;
use Nashgao\MQTT\Config\TopicConfig;
use Nashgao\MQTT\Event\SubscribeEvent;
use Nashgao\MQTT\Metrics\ValidationMetrics;
use Nashgao\MQTT\Utils\ConfigValidator;
use Nashgao\MQTT\Utils\TopicParser;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use function Hyperf\Support\make;

/**
 * Enhanced SubscribeListener with validation and error handling.
 */
class SubscribeListener implements ListenerInterface
{
    private LoggerInterface $logger;

    private ValidationMetrics $validationMetrics;

    public function __construct(
        ?LoggerInterface $logger = null,
        ?ValidationMetrics $validationMetrics = null
    ) {
        $this->logger = $logger ?? new NullLogger();
        $this->validationMetrics = $validationMetrics ?? new ValidationMetrics();
    }

    public function listen(): array
    {
        return [
            SubscribeEvent::class,
        ];
    }

    /**
     * @param object|SubscribeEvent $event
     */
    public function process(object $event): void
    {
        try {
            // Validate the subscribe event
            $this->validateSubscribeEvent($event);

            if (empty($event->topicConfigs)) {
                $this->logger->info('No topic configurations provided for subscription');
                return;
            }

            $poolName = $this->getValidatedPoolName($event);
            $subscribeConfigs = [];
            $multiSubscribeConfigs = [];
            $processedTopics = [];
            $skippedTopics = [];

            // Process each topic configuration
            /** @var TopicConfig $topicConfig */
            foreach ($event->topicConfigs as $topicConfig) {
                try {
                    $this->validateTopicConfig($topicConfig);

                    $this->processTopicConfig(
                        $topicConfig,
                        $subscribeConfigs,
                        $multiSubscribeConfigs,
                        $processedTopics
                    );
                } catch (\Exception $e) {
                    $skippedTopics[] = $topicConfig->topic ?? 'unknown';
                    $this->logger->warning('Skipping invalid topic configuration', [
                        'topic' => $topicConfig->topic ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);

                    $this->validationMetrics->recordValidation(
                        'topic_config_validation',
                        false,
                        "Invalid topic config: {$e->getMessage()}"
                    );
                }
            }

            // Execute subscriptions if we have valid configurations
            if (! empty($subscribeConfigs)) {
                $this->executeSubscriptions(
                    $poolName,
                    $subscribeConfigs,
                    $multiSubscribeConfigs
                );

                $this->logger->info('Subscription event processed successfully', [
                    'pool' => $poolName,
                    'processed_topics' => count($processedTopics),
                    'skipped_topics' => count($skippedTopics),
                    'topics' => $processedTopics,
                ]);
            } else {
                $this->logger->warning('No valid topic configurations to subscribe', [
                    'pool' => $poolName,
                    'skipped_topics' => $skippedTopics,
                ]);
            }
        } catch (\Exception $e) {
            $this->validationMetrics->recordValidation(
                'subscribe_operation',
                false,
                "Failed to process subscription event: {$e->getMessage()}"
            );

            $this->logger->error('Failed to process subscription event', [
                'pool' => $event->poolName ?? 'default',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get validation metrics.
     */
    public function getValidationMetrics(): ValidationMetrics
    {
        return $this->validationMetrics;
    }

    /**
     * Get subscription operation statistics.
     */
    public function getSubscriptionStats(): array
    {
        return [
            'subscribe_operations' => $this->validationMetrics->getOperationStats('subscribe_operation'),
            'topic_config_validations' => $this->validationMetrics->getOperationStats('topic_config_validation'),
        ];
    }

    /**
     * Create enhanced subscribe listener with dependencies.
     */
    public static function create(
        ?LoggerInterface $logger = null,
        ?ValidationMetrics $validationMetrics = null
    ): self {
        return new self($logger, $validationMetrics);
    }

    /**
     * Validate the subscribe event.
     */
    private function validateSubscribeEvent(SubscribeEvent $event): void
    {
        if (! is_array($event->topicConfigs)) {
            throw new \InvalidArgumentException('Topic configurations must be an array');
        }
    }

    /**
     * Validate a topic configuration.
     */
    private function validateTopicConfig(TopicConfig $topicConfig): void
    {
        if (empty($topicConfig->topic)) {
            throw new \InvalidArgumentException('Topic cannot be empty');
        }

        if (! is_string($topicConfig->topic)) {
            throw new \InvalidArgumentException('Topic must be a string');
        }

        if (! ConfigValidator::isValidTopicName($topicConfig->topic)) {
            throw new \InvalidArgumentException("Invalid topic format: {$topicConfig->topic}");
        }

        if (! ConfigValidator::isValidQoS($topicConfig->qos)) {
            throw new \InvalidArgumentException("Invalid QoS level: {$topicConfig->qos}");
        }

        // Validate multi-subscription configuration
        if ($topicConfig->enableMultisub && $topicConfig->multisubNum < 1) {
            throw new \InvalidArgumentException('Multi-subscription number must be at least 1');
        }

        // Validate shared topic configuration
        if ($topicConfig->enableShareTopic) {
            if (empty($topicConfig->shareTopic['group_name']) || ! is_array($topicConfig->shareTopic['group_name'])) {
                throw new \InvalidArgumentException('Shared topic requires valid group names');
            }
        }
    }

    /**
     * Process a single topic configuration.
     */
    private function processTopicConfig(
        TopicConfig $topicConfig,
        array &$subscribeConfigs,
        array &$multiSubscribeConfigs,
        array &$processedTopics
    ): void {
        // Handle queue topic first (has higher priority)
        if ($topicConfig->enableQueueTopic) {
            $topic = TopicParser::generateQueueTopic($topicConfig->topic);

            if ($topicConfig->enableMultisub) {
                $multiSubscribeConfigs[$topic] = $topicConfig->multisubNum;
            }

            $subscribeConfigs[] = TopicParser::generateTopicArray($topic, $topicConfig->getTopicProperties());
            $processedTopics[] = $topic;

            $this->logger->debug('Processed queue topic', [
                'original_topic' => $topicConfig->topic,
                'queue_topic' => $topic,
                'multisub' => $topicConfig->enableMultisub,
            ]);

            return;
        }

        // Handle shared topics
        if ($topicConfig->enableShareTopic) {
            $shareTopics = [];

            foreach ($topicConfig->shareTopic['group_name'] as $groupName) {
                $topic = TopicParser::generateShareTopic($topicConfig->topic, $groupName);

                if ($topicConfig->enableMultisub) {
                    $multiSubscribeConfigs[$topic] = $topicConfig->multisubNum;
                }

                $shareTopics[] = TopicParser::generateTopicArray($topic, $topicConfig->getTopicProperties());
                $processedTopics[] = $topic;

                $this->logger->debug('Processed shared topic', [
                    'original_topic' => $topicConfig->topic,
                    'shared_topic' => $topic,
                    'group_name' => $groupName,
                    'multisub' => $topicConfig->enableMultisub,
                ]);
            }

            $subscribeConfigs = array_merge($subscribeConfigs, $shareTopics);
            return;
        }

        // Handle regular topic
        if ($topicConfig->enableMultisub) {
            $multiSubscribeConfigs[$topicConfig->topic] = $topicConfig->multisubNum;
        }

        $subscribeConfigs[] = TopicParser::generateTopicArray($topicConfig->topic, $topicConfig->getTopicProperties());
        $processedTopics[] = $topicConfig->topic;

        $this->logger->debug('Processed regular topic', [
            'topic' => $topicConfig->topic,
            'multisub' => $topicConfig->enableMultisub,
        ]);
    }

    /**
     * Execute the subscription operations.
     */
    private function executeSubscriptions(
        string $poolName,
        array $subscribeConfigs,
        array $multiSubscribeConfigs
    ): void {
        /** @var Client $client */
        $client = make(Client::class);
        $client->setPoolName($poolName);

        foreach ($subscribeConfigs as $subscribeConfig) {
            try {
                $topicKey = key($subscribeConfig);

                if (array_key_exists($topicKey, $multiSubscribeConfigs)) {
                    $client->multiSub(
                        $subscribeConfig,
                        $subscribeConfig['properties'] ?? [],
                        $multiSubscribeConfigs[$topicKey]
                    );

                    $this->logger->debug('Multi-subscription executed', [
                        'pool' => $poolName,
                        'topic' => $topicKey,
                        'count' => $multiSubscribeConfigs[$topicKey],
                    ]);
                } else {
                    $client->subscribe($subscribeConfig, $subscribeConfig['properties'] ?? []);

                    $this->logger->debug('Single subscription executed', [
                        'pool' => $poolName,
                        'topic' => $topicKey,
                    ]);
                }

                $this->validationMetrics->recordValidation(
                    'subscribe_operation',
                    true,
                    "Subscribed to topic '{$topicKey}'"
                );
            } catch (\Exception $e) {
                $this->validationMetrics->recordValidation(
                    'subscribe_operation',
                    false,
                    "Failed to subscribe to topic: {$e->getMessage()}"
                );

                $this->logger->error('Failed to execute subscription', [
                    'pool' => $poolName,
                    'config' => $subscribeConfig,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        }
    }

    /**
     * Get and validate the pool name.
     */
    private function getValidatedPoolName(SubscribeEvent $event): string
    {
        $poolName = $event->poolName ?? 'default';

        if (! is_string($poolName) || empty($poolName)) {
            $this->logger->warning('Invalid pool name, using default', [
                'provided_pool' => $poolName,
            ]);
            return 'default';
        }

        return $poolName;
    }
}
