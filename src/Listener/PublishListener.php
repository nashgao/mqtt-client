<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Listener;

use Hyperf\Event\Contract\ListenerInterface;
use Nashgao\MQTT\Client;
use Nashgao\MQTT\Event\PublishEvent;
use Nashgao\MQTT\Metrics\ValidationMetrics;
use Nashgao\MQTT\Utils\ConfigValidator;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use function Hyperf\Support\make;

/**
 * Enhanced PublishListener with validation and error handling.
 */
class PublishListener implements ListenerInterface
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
            PublishEvent::class,
        ];
    }

    /**
     * @param object|PublishEvent $event
     */
    public function process(object $event): void
    {
        try {
            // Validate the publish event
            $this->validatePublishEvent($event);

            // Create and configure client
            /** @var Client $client */
            $client = make(Client::class);
            $poolName = $this->getValidatedPoolName($event);
            $client->setPoolName($poolName);

            // Log the publish attempt
            $this->logger->debug('Publishing MQTT message', [
                'pool' => $poolName,
                'topic' => $event->topic,
                'qos' => $event->qos,
                'message_length' => strlen($event->message),
                'retain' => $event->retain,
                'dup' => $event->dup,
            ]);

            // Perform the publish operation
            $client->publish(
                $event->topic,
                $event->message,
                $event->qos,
                $event->dup,
                $event->retain,
                $event->properties
            );

            // Record successful publish
            $this->validationMetrics->recordValidation(
                'publish_operation',
                true,
                "Published to topic '{$event->topic}'"
            );

            $this->logger->info('MQTT message published successfully', [
                'pool' => $poolName,
                'topic' => $event->topic,
                'qos' => $event->qos,
            ]);
        } catch (\Exception $e) {
            // Record failed publish
            $this->validationMetrics->recordValidation(
                'publish_operation',
                false,
                "Failed to publish to topic '{$event->topic}': {$e->getMessage()}"
            );

            $this->logger->error('Failed to publish MQTT message', [
                'topic' => $event->topic ?? 'unknown',
                'pool' => $event->poolName ?? 'default',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw the exception to maintain error behavior
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
     * Get publish operation statistics.
     */
    public function getPublishStats(): array
    {
        return $this->validationMetrics->getOperationStats('publish_operation');
    }

    /**
     * Create enhanced publish listener with dependencies.
     */
    public static function create(
        ?LoggerInterface $logger = null,
        ?ValidationMetrics $validationMetrics = null
    ): self {
        return new self($logger, $validationMetrics);
    }

    /**
     * Validate the publish event data.
     */
    private function validatePublishEvent(PublishEvent $event): void
    {
        if (empty($event->topic)) {
            throw new \InvalidArgumentException('Topic cannot be empty');
        }

        if (! is_string($event->topic)) {
            throw new \InvalidArgumentException('Topic must be a string');
        }

        if (! is_string($event->message)) {
            throw new \InvalidArgumentException('Message must be a string');
        }

        if (! ConfigValidator::isValidQoS($event->qos)) {
            throw new \InvalidArgumentException("Invalid QoS level: {$event->qos}");
        }

        if (! is_bool($event->retain)) {
            throw new \InvalidArgumentException('Retain flag must be boolean');
        }

        if (! is_bool($event->dup)) {
            throw new \InvalidArgumentException('Dup flag must be boolean');
        }

        if (! is_array($event->properties)) {
            throw new \InvalidArgumentException('Properties must be an array');
        }

        // Validate topic format
        if (! ConfigValidator::isValidTopicName($event->topic)) {
            throw new \InvalidArgumentException("Invalid topic format: {$event->topic}");
        }

        // Check message size limits
        if (strlen($event->message) > 268435455) { // MQTT maximum message size
            throw new \InvalidArgumentException('Message size exceeds MQTT maximum limit');
        }
    }

    /**
     * Get and validate the pool name.
     */
    private function getValidatedPoolName(PublishEvent $event): string
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
