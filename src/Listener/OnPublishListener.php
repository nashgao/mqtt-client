<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Listener;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Nashgao\MQTT\Event\OnPublishEvent;
use Nashgao\MQTT\Metrics\PerformanceMetrics;
use Nashgao\MQTT\Metrics\PublishMetrics;
use Nashgao\MQTT\Metrics\ValidationMetrics;
use Nashgao\MQTT\Utils\ConfigValidator;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Enhanced OnPublishListener with validation and comprehensive logging.
 */
class OnPublishListener implements ListenerInterface
{
    protected LoggerInterface $logger;

    private ValidationMetrics $validationMetrics;

    private PublishMetrics $publishMetrics;

    private PerformanceMetrics $performanceMetrics;

    public function __construct(
        ?StdoutLoggerInterface $stdoutLogger = null,
        ?LoggerInterface $logger = null,
        ?ValidationMetrics $validationMetrics = null,
        ?PublishMetrics $publishMetrics = null,
        ?PerformanceMetrics $performanceMetrics = null
    ) {
        $this->logger = $logger ?? $stdoutLogger ?? new NullLogger();
        $this->validationMetrics = $validationMetrics ?? new ValidationMetrics();
        $this->publishMetrics = $publishMetrics ?? new PublishMetrics();
        $this->performanceMetrics = $performanceMetrics ?? new PerformanceMetrics();
    }

    public function listen(): array
    {
        return [
            OnPublishEvent::class,
        ];
    }

    /**
     * @param OnPublishEvent $event
     */
    public function process(object $event): void
    {
        $startTime = microtime(true);

        try {
            // Record publish attempt
            $this->publishMetrics->recordPublishAttempt();

            // Validate the event
            $this->validateOnPublishEvent($event);

            // Record successful publish with details
            $messageSize = isset($event->message) ? strlen($event->message) : null;
            $qos = $event->qos ?? 0;
            $this->publishMetrics->recordSuccessfulPublish($event->topic, $qos, $messageSize);

            // Record performance metrics
            $this->performanceMetrics->recordOperationTime('publish_processing', microtime(true) - $startTime);
            $this->performanceMetrics->recordMessageThroughput(1);

            // Log the publish event
            $this->logPublishEvent($event);

            // Record successful processing
            $this->validationMetrics->recordValidation(
                'on_publish_processing',
                true,
                "Successfully processed OnPublishEvent for topic '{$event->topic}'"
            );
        } catch (\Exception $e) {
            // Record failed publish
            $this->publishMetrics->recordFailedPublish();

            // Record failed processing
            $this->validationMetrics->recordValidation(
                'on_publish_processing',
                false,
                "Failed to process OnPublishEvent: {$e->getMessage()}"
            );

            $this->logger->error('Failed to process OnPublishEvent', [
                'topic' => $event->topic ?? 'unknown',
                'client_id' => $event->clientId ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Don't re-throw, as this is a notification event
        }
    }

    /**
     * Get a summary of publish metrics.
     */
    public function getPublishSummary(): array
    {
        return $this->publishMetrics->getSummary();
    }

    /**
     * Get detailed metrics for a specific topic.
     */
    public function getTopicMetrics(string $topic): ?array
    {
        return $this->publishMetrics->getTopicStats($topic);
    }

    /**
     * Get all topic metrics.
     */
    public function getAllTopicMetrics(): array
    {
        return $this->publishMetrics->getAllTopicStats();
    }

    /**
     * Get validation metrics.
     */
    public function getValidationMetrics(): ValidationMetrics
    {
        return $this->validationMetrics;
    }

    /**
     * Get publish metrics.
     */
    public function getPublishMetrics(): PublishMetrics
    {
        return $this->publishMetrics;
    }

    /**
     * Get performance metrics.
     */
    public function getPerformanceMetrics(): PerformanceMetrics
    {
        return $this->performanceMetrics;
    }

    /**
     * Reset publish metrics.
     */
    public function resetMetrics(): void
    {
        $this->publishMetrics->reset();
        $this->performanceMetrics->reset();
    }

    /**
     * Get the most active topics.
     */
    public function getMostActiveTopics(int $limit = 10): array
    {
        return $this->publishMetrics->getMostActiveTopics($limit);
    }

    /**
     * Create enhanced listener with dependencies.
     */
    public static function create(
        ?StdoutLoggerInterface $stdoutLogger = null,
        ?LoggerInterface $logger = null,
        ?ValidationMetrics $validationMetrics = null,
        ?PublishMetrics $publishMetrics = null,
        ?PerformanceMetrics $performanceMetrics = null
    ): self {
        return new self($stdoutLogger, $logger, $validationMetrics, $publishMetrics, $performanceMetrics);
    }

    /**
     * Validate the OnPublishEvent.
     */
    private function validateOnPublishEvent(OnPublishEvent $event): void
    {
        if (empty($event->topic)) {
            throw new \InvalidArgumentException('Topic cannot be empty');
        }

        if (! ConfigValidator::isValidTopicName($event->topic)) {
            throw new \InvalidArgumentException("Invalid topic format: {$event->topic}");
        }

        if (isset($event->qos) && ! ConfigValidator::isValidQoS($event->qos)) {
            throw new \InvalidArgumentException("Invalid QoS level: {$event->qos}");
        }

        if (isset($event->messageId) && $event->messageId < 0) {
            throw new \InvalidArgumentException('Message ID must be a non-negative integer');
        }
    }

    /**
     * Log the publish event with appropriate level.
     */
    private function logPublishEvent(OnPublishEvent $event): void
    {
        $context = [
            'topic' => $event->topic,
            'client_id' => $event->clientId ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        // Add optional fields if available
        if (isset($event->qos)) {
            $context['qos'] = $event->qos;
        }

        if (isset($event->messageId)) {
            $context['message_id'] = $event->messageId;
        }

        if (isset($event->message)) {
            $context['message_length'] = strlen($event->message);
            // Don't log full message content for security/privacy
            $context['message_preview'] = substr($event->message, 0, 50) . (strlen($event->message) > 50 ? '...' : '');
        }

        if (isset($event->retain)) {
            $context['retain'] = $event->retain;
        }

        if (isset($event->properties) && ! empty($event->properties)) {
            $context['properties_count'] = count($event->properties);
        }

        $this->logger->info('MQTT message published', $context);

        // Log additional debug information
        $this->logger->debug('OnPublishEvent processed', [
            'event_data' => $context,
            'current_metrics' => $this->publishMetrics->getSummary(),
        ]);
    }
}
