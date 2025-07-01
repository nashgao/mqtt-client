<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Listener;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\AfterWorkerStart;
use Nashgao\MQTT\Config\PoolConfig;
use Nashgao\MQTT\Config\TopicSubscription;
use Nashgao\MQTT\Event\SubscribeEvent;
use Nashgao\MQTT\Utils\ConfigValidator;
use Nashgao\MQTT\Metrics\ValidationMetrics;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Enhanced AfterWorkerStartListener that uses class-based configurations
 * instead of plain arrays for better type safety and validation.
 */
class AfterWorkerStartListener implements ListenerInterface
{
    private ContainerInterface $container;
    private LoggerInterface $logger;
    private ValidationMetrics $validationMetrics;
    private array $poolConfigs = [];

    public function __construct(
        ContainerInterface $container,
        ?LoggerInterface $logger = null,
        ?ValidationMetrics $validationMetrics = null
    ) {
        $this->container = $container;
        $this->logger = $logger ?? new NullLogger();
        $this->validationMetrics = $validationMetrics ?? new ValidationMetrics();
        
        // Set validation metrics for configuration validation
        ConfigValidator::setMetrics($this->validationMetrics);
    }

    public function listen(): array
    {
        return [
            AfterWorkerStart::class,
        ];
    }

    public function process(object $event): void
    {
        $this->logger->info('Enhanced MQTT worker starting, processing pool configurations...');
        
        try {
            $this->loadPoolConfigurations();
            $this->processSubscriptions();
            
            $this->logger->info('Enhanced MQTT worker started successfully', [
                'pools_loaded' => count($this->poolConfigs),
                'validation_stats' => $this->validationMetrics->toArray(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to start enhanced MQTT worker', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Load and validate all pool configurations.
     */
    private function loadPoolConfigurations(): void
    {
        $config = $this->container->get(ConfigInterface::class);
        $mqttConfigs = $config->get('mqtt', []);
        
        foreach ($mqttConfigs as $poolName => $poolConfigData) {
            try {
                $poolConfig = new PoolConfig($poolName, $poolConfigData);
                
                if (!$poolConfig->isValid()) {
                    $this->logger->warning("Invalid pool configuration for '{$poolName}', skipping...");
                    continue;
                }
                
                $this->poolConfigs[$poolName] = $poolConfig;
                
                $this->logger->debug("Loaded pool configuration", [
                    'pool_name' => $poolName,
                    'host' => $poolConfig->host,
                    'port' => $poolConfig->port,
                    'subscriptions' => $poolConfig->getSubscriptionConfig()->count(),
                    'publish_topics' => $poolConfig->getPublishConfig()->count(),
                ]);
                
            } catch (\Exception $e) {
                $this->logger->error("Failed to load pool configuration for '{$poolName}'", [
                    'error' => $e->getMessage(),
                    'config_data' => $poolConfigData,
                ]);
                
                // Record validation failure
                $this->validationMetrics->recordValidation(
                    'pool_config_load',
                    false,
                    "Failed to load pool '{$poolName}': {$e->getMessage()}"
                );
            }
        }
    }

    /**
     * Process subscriptions for all loaded pools.
     */
    private function processSubscriptions(): void
    {
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $this->container->get(EventDispatcherInterface::class);
        
        foreach ($this->poolConfigs as $poolName => $poolConfig) {
            if (!$poolConfig->hasSubscriptions()) {
                $this->logger->debug("No auto-subscriptions configured for pool '{$poolName}'");
                continue;
            }
            
            $this->processPoolSubscriptions($dispatcher, $poolName, $poolConfig);
        }
    }

    /**
     * Process subscriptions for a specific pool.
     */
    private function processPoolSubscriptions(
        EventDispatcherInterface $dispatcher,
        string $poolName,
        PoolConfig $poolConfig
    ): void {
        $subscriptionConfig = $poolConfig->getSubscriptionConfig();
        $autoSubscribeTopics = $subscriptionConfig->getAutoSubscribeTopics();
        
        if (empty($autoSubscribeTopics)) {
            return;
        }
        
        $validTopics = [];
        $skippedTopics = [];
        
        foreach ($autoSubscribeTopics as $topicSubscription) {
            try {
                // Validate the topic subscription
                if (!$topicSubscription->validate()) {
                    $skippedTopics[] = $topicSubscription->getTopic();
                    $this->logger->warning("Invalid topic subscription, skipping", [
                        'pool' => $poolName,
                        'topic' => $topicSubscription->getTopic(),
                    ]);
                    continue;
                }
                
                // Apply filter if configured
                if (!$topicSubscription->passesFilter()) {
                    $skippedTopics[] = $topicSubscription->getTopic();
                    $this->logger->debug("Topic filtered out", [
                        'pool' => $poolName,
                        'topic' => $topicSubscription->getTopic(),
                    ]);
                    continue;
                }
                
                // Convert to TopicConfig for event
                $validTopics[] = $topicSubscription->toTopicConfig();
                
                $this->logger->debug("Added topic for subscription", [
                    'pool' => $poolName,
                    'topic' => $topicSubscription->getTopic(),
                    'qos' => $topicSubscription->getQos(),
                    'handler' => $topicSubscription->getHandler(),
                ]);
                
            } catch (\Exception $e) {
                $skippedTopics[] = $topicSubscription->getTopic();
                $this->logger->error("Failed to process topic subscription", [
                    'pool' => $poolName,
                    'topic' => $topicSubscription->getTopic(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        // Dispatch subscription event if we have valid topics
        if (!empty($validTopics)) {
            $dispatcher->dispatch(new SubscribeEvent($poolName, $validTopics));
            
            $this->logger->info("Dispatched subscription event", [
                'pool' => $poolName,
                'topics_count' => count($validTopics),
                'skipped_count' => count($skippedTopics),
                'topics' => array_map(fn($topic) => $topic->topic, $validTopics),
            ]);
        } else {
            $this->logger->warning("No valid topics to subscribe for pool '{$poolName}'", [
                'skipped_topics' => $skippedTopics,
            ]);
        }
    }

    /**
     * Get loaded pool configurations.
     */
    public function getPoolConfigs(): array
    {
        return $this->poolConfigs;
    }

    /**
     * Get a specific pool configuration.
     */
    public function getPoolConfig(string $poolName): ?PoolConfig
    {
        return $this->poolConfigs[$poolName] ?? null;
    }

    /**
     * Get validation metrics.
     */
    public function getValidationMetrics(): ValidationMetrics
    {
        return $this->validationMetrics;
    }

    /**
     * Get statistics about the loaded configurations.
     */
    public function getConfigurationStats(): array
    {
        $stats = [
            'total_pools' => count($this->poolConfigs),
            'pools_with_subscriptions' => 0,
            'total_subscriptions' => 0,
            'total_publish_topics' => 0,
            'pools' => [],
        ];
        
        foreach ($this->poolConfigs as $poolName => $poolConfig) {
            $subscriptionCount = $poolConfig->getSubscriptionConfig()->count();
            $publishCount = $poolConfig->getPublishConfig()->count();
            
            if ($subscriptionCount > 0) {
                $stats['pools_with_subscriptions']++;
            }
            
            $stats['total_subscriptions'] += $subscriptionCount;
            $stats['total_publish_topics'] += $publishCount;
            
            $stats['pools'][$poolName] = [
                'host' => $poolConfig->host,
                'port' => $poolConfig->port,
                'subscriptions' => $subscriptionCount,
                'publish_topics' => $publishCount,
                'auto_subscriptions' => count($poolConfig->getAutoSubscribeTopics()),
                'is_valid' => $poolConfig->isValid(),
            ];
        }
        
        return array_merge($stats, [
            'validation_metrics' => $this->validationMetrics->toArray(),
        ]);
    }

    /**
     * Create enhanced listener with optional validation metrics.
     */
    public static function create(
        ContainerInterface $container,
        ?LoggerInterface $logger = null,
        ?ValidationMetrics $validationMetrics = null
    ): self {
        return new self($container, $logger, $validationMetrics);
    }

    /**
     * Validate all loaded configurations.
     */
    public function validateAllConfigurations(): array
    {
        $results = [];
        
        foreach ($this->poolConfigs as $poolName => $poolConfig) {
            $results[$poolName] = [
                'valid' => $poolConfig->validate(),
                'pool_config' => $poolConfig->toArray(),
                'subscription_valid' => $poolConfig->getSubscriptionConfig()->validate(),
                'publish_valid' => $poolConfig->getPublishConfig()->validate(),
            ];
        }
        
        return $results;
    }

    /**
     * Get topics that would be auto-subscribed (for debugging).
     */
    public function getAutoSubscribeTopicsPreview(): array
    {
        $preview = [];
        
        foreach ($this->poolConfigs as $poolName => $poolConfig) {
            $topics = [];
            foreach ($poolConfig->getAutoSubscribeTopics() as $subscription) {
                if ($subscription->passesFilter()) {
                    $topics[] = [
                        'topic' => $subscription->getTopic(),
                        'qos' => $subscription->getQos(),
                        'handler' => $subscription->getHandler(),
                        'metadata' => $subscription->getMetadata(),
                    ];
                }
            }
            
            if (!empty($topics)) {
                $preview[$poolName] = $topics;
            }
        }
        
        return $preview;
    }
}