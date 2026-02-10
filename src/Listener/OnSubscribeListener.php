<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Listener;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Nashgao\MQTT\Event\OnSubscribeEvent;
use Nashgao\MQTT\Metrics\ErrorMetrics;
use Nashgao\MQTT\Metrics\PerformanceMetrics;
use Nashgao\MQTT\Metrics\SubscriptionMetrics;
use Nashgao\MQTT\Metrics\ValidationMetrics;
use Nashgao\MQTT\Utils\ConfigValidator;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Simps\MQTT\Protocol\Types;

/**
 * Enhanced OnSubscribeListener with comprehensive metrics tracking.
 */
class OnSubscribeListener implements ListenerInterface
{
    protected LoggerInterface $logger;

    private SubscriptionMetrics $subscriptionMetrics;

    private ValidationMetrics $validationMetrics;

    private PerformanceMetrics $performanceMetrics;

    private ErrorMetrics $errorMetrics;

    public function __construct(
        ?StdoutLoggerInterface $stdoutLogger = null,
        ?LoggerInterface $logger = null,
        ?SubscriptionMetrics $subscriptionMetrics = null,
        ?ValidationMetrics $validationMetrics = null,
        ?PerformanceMetrics $performanceMetrics = null,
        ?ErrorMetrics $errorMetrics = null
    ) {
        $this->logger = $logger ?? $stdoutLogger ?? new NullLogger();
        $this->subscriptionMetrics = $subscriptionMetrics ?? new SubscriptionMetrics();
        $this->validationMetrics = $validationMetrics ?? new ValidationMetrics();
        $this->performanceMetrics = $performanceMetrics ?? new PerformanceMetrics();
        $this->errorMetrics = $errorMetrics ?? new ErrorMetrics();
    }

    public function listen(): array
    {
        return [
            OnSubscribeEvent::class,
        ];
    }

    /**
     * @param OnSubscribeEvent $event
     */
    public function process(object $event): void
    {
        $startTime = microtime(true);

        try {
            // Validate the event
            $this->validateSubscribeEvent($event);

            // Record subscription attempt
            $this->subscriptionMetrics->recordSubscriptionAttempt();

            // Determine if subscription was successful
            $isSuccessful = $this->isSubscriptionSuccessful($event->result);

            if ($isSuccessful) {
                // Record successful subscription
                $this->subscriptionMetrics->recordSuccessfulSubscription(
                    $event->poolName,
                    $event->clientId,
                    $event->topics,
                    $event->result
                );

                // Record performance metrics
                $this->performanceMetrics->recordOperationTime('subscription', microtime(true) - $startTime);
                $this->performanceMetrics->recordMessageThroughput(count($event->topics));

                // Log successful subscriptions
                $this->logSuccessfulSubscription($event);
            } else {
                // Record failed subscription
                $failureReason = $this->getFailureReason($event->result);
                $this->subscriptionMetrics->recordFailedSubscription(
                    $event->poolName,
                    $event->clientId,
                    $event->topics,
                    $failureReason
                );

                // Record error metrics
                $this->errorMetrics->recordError(
                    'subscription_failure',
                    'mqtt_subscription',
                    "Subscription failed for client {$event->clientId}: {$failureReason}"
                );

                // Log failed subscription
                $this->logFailedSubscription($event, $failureReason);
            }

            // Record successful processing
            $this->validationMetrics->recordValidation(
                'on_subscribe_processing',
                true,
                "Successfully processed OnSubscribeEvent for client '{$event->clientId}'"
            );
        } catch (\Exception $e) {
            // Record error metrics
            $this->errorMetrics->recordError(
                'subscribe_processing',
                'on_subscribe_event',
                "Failed to process OnSubscribeEvent: {$e->getMessage()}",
                $e
            );

            $this->validationMetrics->recordValidation(
                'on_subscribe_processing',
                false,
                "Failed to process OnSubscribeEvent: {$e->getMessage()}"
            );

            $this->logger->error('Failed to process OnSubscribeEvent', [
                'pool_name' => $event->poolName ?? 'unknown',
                'client_id' => $event->clientId ?? 'unknown',
                'topics' => $event->topics ?? [],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Don't re-throw, as this is a notification event
        }
    }

    /**
     * Get subscription metrics summary.
     */
    public function getSubscriptionSummary(): array
    {
        return $this->subscriptionMetrics->getSummary();
    }

    /**
     * Get subscription metrics.
     */
    public function getSubscriptionMetrics(): SubscriptionMetrics
    {
        return $this->subscriptionMetrics;
    }

    /**
     * Get validation metrics.
     */
    public function getValidationMetrics(): ValidationMetrics
    {
        return $this->validationMetrics;
    }

    /**
     * Get performance metrics.
     */
    public function getPerformanceMetrics(): PerformanceMetrics
    {
        return $this->performanceMetrics;
    }

    /**
     * Get error metrics.
     */
    public function getErrorMetrics(): ErrorMetrics
    {
        return $this->errorMetrics;
    }

    /**
     * Reset all metrics.
     */
    public function resetMetrics(): void
    {
        $this->subscriptionMetrics->reset();
        $this->performanceMetrics->reset();
    }

    /**
     * Create enhanced listener with dependencies.
     */
    public static function create(
        ?StdoutLoggerInterface $stdoutLogger = null,
        ?LoggerInterface $logger = null,
        ?SubscriptionMetrics $subscriptionMetrics = null,
        ?ValidationMetrics $validationMetrics = null,
        ?PerformanceMetrics $performanceMetrics = null,
        ?ErrorMetrics $errorMetrics = null
    ): self {
        return new self($stdoutLogger, $logger, $subscriptionMetrics, $validationMetrics, $performanceMetrics, $errorMetrics);
    }

    /**
     * Validate the OnSubscribeEvent.
     */
    private function validateSubscribeEvent(OnSubscribeEvent $event): void
    {
        if (empty($event->poolName)) {
            throw new \InvalidArgumentException('Pool name cannot be empty');
        }

        if (empty($event->clientId)) {
            throw new \InvalidArgumentException('Client ID cannot be empty');
        }

        if (empty($event->topics)) {
            throw new \InvalidArgumentException('Topics must be a non-empty array');
        }

        // Validate each topic
        foreach ($event->topics as $topic => $qos) {
            if (! is_string($topic) || empty($topic)) {
                throw new \InvalidArgumentException('Topic must be a non-empty string');
            }

            if (! ConfigValidator::isValidTopicName($topic)) {
                throw new \InvalidArgumentException("Invalid topic format: {$topic}");
            }

            if (! ConfigValidator::isValidQoS($qos)) {
                throw new \InvalidArgumentException("Invalid QoS level: {$qos}");
            }
        }
    }

    /**
     * Determine if the subscription was successful based on the result.
     */
    private function isSubscriptionSuccessful(mixed $result): bool
    {
        // Check if result indicates success
        if (is_array($result) && isset($result['type'])) {
            // Assuming successful subscription types (you may need to adjust based on actual values)
            $successTypes = ['SUBACK', 'SUCCESS'];
            $type = Types::getType($result['type']);
            return in_array($type, $successTypes, true);
        }

        // If result is not in expected format, assume success for backward compatibility
        return true;
    }

    /**
     * Get failure reason from result.
     */
    private function getFailureReason(mixed $result): string
    {
        if (is_array($result)) {
            if (isset($result['reason'])) {
                return $result['reason'];
            }

            if (isset($result['type'])) {
                return 'Subscription failed with type: ' . Types::getType($result['type']);
            }

            if (isset($result['error'])) {
                return $result['error'];
            }
        }

        return 'Unknown subscription failure';
    }

    /**
     * Log successful subscription.
     */
    private function logSuccessfulSubscription(OnSubscribeEvent $event): void
    {
        foreach ($event->topics as $topic => $qos) {
            $context = [
                'client_id' => $event->clientId,
                'pool_name' => $event->poolName,
                'topic' => $topic,
                'qos' => $qos,
                'result_type' => isset($event->result['type']) ? Types::getType($event->result['type']) : 'unknown',
                'timestamp' => date('Y-m-d H:i:s'),
            ];

            $this->logger->info(
                "MQTT subscription successful: Client {$event->clientId} from {$event->poolName} pool subscribed to {$topic} with QoS {$qos}",
                $context
            );
        }

        // Log summary debug information
        $this->logger->debug('OnSubscribeEvent processed successfully', [
            'event_data' => [
                'client_id' => $event->clientId,
                'pool_name' => $event->poolName,
                'topics_count' => count($event->topics),
                'topics' => array_keys($event->topics),
            ],
            'current_metrics' => $this->subscriptionMetrics->getSummary(),
        ]);
    }

    /**
     * Log failed subscription.
     */
    private function logFailedSubscription(OnSubscribeEvent $event, string $reason): void
    {
        $context = [
            'client_id' => $event->clientId,
            'pool_name' => $event->poolName,
            'topics' => $event->topics,
            'failure_reason' => $reason,
            'result' => $event->result,
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        $this->logger->warning(
            "MQTT subscription failed: Client {$event->clientId} from {$event->poolName} pool failed to subscribe. Reason: {$reason}",
            $context
        );
    }
}
