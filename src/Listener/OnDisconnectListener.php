<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Listener;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Nashgao\MQTT\Event\OnDisconnectEvent;
use Nashgao\MQTT\Metrics\ConnectionMetrics;
use Nashgao\MQTT\Metrics\ErrorMetrics;
use Nashgao\MQTT\Metrics\ValidationMetrics;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Simps\MQTT\Hex\ReasonCode;

/**
 * Enhanced OnDisconnectListener with comprehensive metrics tracking.
 */
class OnDisconnectListener implements ListenerInterface
{
    protected LoggerInterface $logger;

    private ConnectionMetrics $connectionMetrics;

    private ErrorMetrics $errorMetrics;

    private ValidationMetrics $validationMetrics;

    private array $disconnectionReasons = [];

    private array $poolDisconnections = [];

    public function __construct(
        ?StdoutLoggerInterface $stdoutLogger = null,
        ?LoggerInterface $logger = null,
        ?ConnectionMetrics $connectionMetrics = null,
        ?ErrorMetrics $errorMetrics = null,
        ?ValidationMetrics $validationMetrics = null
    ) {
        $this->logger = $logger ?? $stdoutLogger ?? new NullLogger();
        $this->connectionMetrics = $connectionMetrics ?? new ConnectionMetrics();
        $this->errorMetrics = $errorMetrics ?? new ErrorMetrics();
        $this->validationMetrics = $validationMetrics ?? new ValidationMetrics();
    }

    public function listen(): array
    {
        return [
            OnDisconnectEvent::class,
        ];
    }

    /**
     * @param object|OnDisconnectEvent $event
     */
    public function process(object $event): void
    {
        try {
            // Validate the event
            $this->validateDisconnectEvent($event);

            // Record disconnection metrics
            $this->recordDisconnectionMetrics($event);

            // Log the disconnect event with comprehensive details
            $this->logDisconnectEvent($event);

            // Record successful processing
            $this->validationMetrics->recordValidation(
                'on_disconnect_processing',
                true,
                "Successfully processed OnDisconnectEvent for pool '{$event->poolName}'"
            );
        } catch (\Exception $e) {
            // Record error metrics
            $this->errorMetrics->recordError(
                'disconnect_processing',
                'on_disconnect_event',
                "Failed to process OnDisconnectEvent: {$e->getMessage()}",
                $e
            );

            $this->validationMetrics->recordValidation(
                'on_disconnect_processing',
                false,
                "Failed to process OnDisconnectEvent: {$e->getMessage()}"
            );

            $this->logger->error('Failed to process OnDisconnectEvent', [
                'pool_name' => $event->poolName ?? 'unknown',
                'code' => $event->code ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Don't re-throw, as this is a notification event
        }
    }

    /**
     * Get disconnection metrics summary.
     */
    public function getDisconnectionSummary(): array
    {
        $totalDisconnections = array_sum(array_column($this->disconnectionReasons, 'count'));
        $errorDisconnections = 0;

        foreach ($this->disconnectionReasons as $code => $data) {
            if ($this->isErrorDisconnectCode($code)) {
                $errorDisconnections += $data['count'];
            }
        }

        return [
            'total_disconnections' => $totalDisconnections,
            'error_disconnections' => $errorDisconnections,
            'normal_disconnections' => $totalDisconnections - $errorDisconnections,
            'error_rate' => $totalDisconnections > 0
                ? round($errorDisconnections / $totalDisconnections * 100, 2)
                : 0,
            'unique_pools' => count($this->poolDisconnections),
            'unique_reason_codes' => count($this->disconnectionReasons),
        ];
    }

    /**
     * Get disconnection reasons statistics.
     */
    public function getDisconnectionReasons(): array
    {
        return $this->disconnectionReasons;
    }

    /**
     * Get pool-specific disconnection statistics.
     */
    public function getPoolDisconnections(): array
    {
        return $this->poolDisconnections;
    }

    /**
     * Get the most frequent disconnection reason.
     */
    public function getMostFrequentDisconnectReason(): ?array
    {
        if (empty($this->disconnectionReasons)) {
            return null;
        }

        $maxCount = 0;
        $mostFrequent = null;

        foreach ($this->disconnectionReasons as $code => $data) {
            if ($data['count'] > $maxCount) {
                $maxCount = $data['count'];
                $mostFrequent = ['code' => $code] + $data;
            }
        }

        return $mostFrequent;
    }

    /**
     * Get the most problematic pool (highest disconnect rate).
     */
    public function getMostProblematicPool(): ?array
    {
        if (empty($this->poolDisconnections)) {
            return null;
        }

        $maxCount = 0;
        $mostProblematic = null;

        foreach ($this->poolDisconnections as $poolName => $data) {
            if ($data['count'] > $maxCount) {
                $maxCount = $data['count'];
                $mostProblematic = ['pool_name' => $poolName] + $data;
            }
        }

        return $mostProblematic;
    }

    /**
     * Get connection metrics.
     */
    public function getConnectionMetrics(): ConnectionMetrics
    {
        return $this->connectionMetrics;
    }

    /**
     * Get error metrics.
     */
    public function getErrorMetrics(): ErrorMetrics
    {
        return $this->errorMetrics;
    }

    /**
     * Get validation metrics.
     */
    public function getValidationMetrics(): ValidationMetrics
    {
        return $this->validationMetrics;
    }

    /**
     * Reset disconnection metrics.
     */
    public function resetMetrics(): void
    {
        $this->disconnectionReasons = [];
        $this->poolDisconnections = [];
    }

    /**
     * Create enhanced listener with dependencies.
     */
    public static function create(
        ?StdoutLoggerInterface $stdoutLogger = null,
        ?LoggerInterface $logger = null,
        ?ConnectionMetrics $connectionMetrics = null,
        ?ErrorMetrics $errorMetrics = null,
        ?ValidationMetrics $validationMetrics = null
    ): self {
        return new self($stdoutLogger, $logger, $connectionMetrics, $errorMetrics, $validationMetrics);
    }

    /**
     * Validate the OnDisconnectEvent.
     */
    private function validateDisconnectEvent(OnDisconnectEvent $event): void
    {
        if (empty($event->poolName)) {
            throw new \InvalidArgumentException('Pool name cannot be empty');
        }
    }

    /**
     * Record disconnection metrics.
     */
    private function recordDisconnectionMetrics(OnDisconnectEvent $event): void
    {
        // Record disconnection in connection metrics
        $this->connectionMetrics->recordDisconnection();

        // Track disconnection reasons
        $reasonPhrase = ReasonCode::getReasonPhrase($event->code);
        if (! isset($this->disconnectionReasons[$event->code])) {
            $this->disconnectionReasons[$event->code] = [
                'count' => 0,
                'reason' => $reasonPhrase,
                'last_occurrence' => null,
            ];
        }

        ++$this->disconnectionReasons[$event->code]['count'];
        $this->disconnectionReasons[$event->code]['last_occurrence'] = date('Y-m-d H:i:s');

        // Track pool-specific disconnections
        if (! isset($this->poolDisconnections[$event->poolName])) {
            $this->poolDisconnections[$event->poolName] = [
                'count' => 0,
                'last_disconnect' => null,
                'reason_distribution' => [],
            ];
        }

        ++$this->poolDisconnections[$event->poolName]['count'];
        $this->poolDisconnections[$event->poolName]['last_disconnect'] = date('Y-m-d H:i:s');

        if (! isset($this->poolDisconnections[$event->poolName]['reason_distribution'][$event->code])) {
            $this->poolDisconnections[$event->poolName]['reason_distribution'][$event->code] = 0;
        }
        ++$this->poolDisconnections[$event->poolName]['reason_distribution'][$event->code];

        // Record error if disconnect code indicates an error
        if ($this->isErrorDisconnectCode($event->code)) {
            $this->errorMetrics->recordError(
                'disconnect_error',
                'mqtt_connection',
                "MQTT disconnection with error code {$event->code}: {$reasonPhrase}"
            );
        }
    }

    /**
     * Log the disconnect event with comprehensive details.
     */
    private function logDisconnectEvent(OnDisconnectEvent $event): void
    {
        $reasonPhrase = ReasonCode::getReasonPhrase($event->code);
        $isError = $this->isErrorDisconnectCode($event->code);

        $context = [
            'pool_name' => $event->poolName,
            'disconnect_code' => $event->code,
            'reason_phrase' => $reasonPhrase,
            'disconnect_type' => $event->type,
            'is_error' => $isError,
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        if (isset($event->qos)) {
            $context['qos'] = $event->qos;
        }

        if (isset($event->clientConfig)) {
            $context['client_config'] = [
                'host' => $event->clientConfig->host ?? 'unknown',
                'port' => $event->clientConfig->port ?? 'unknown',
            ];
        }

        $logLevel = $isError ? 'warning' : 'info';
        $message = $isError
            ? "MQTT broker disconnected with error: {$reasonPhrase} [{$event->code}]"
            : "MQTT broker disconnected: {$reasonPhrase} [{$event->code}]";

        $this->logger->log($logLevel, $message, $context);

        // Log additional debug information
        $this->logger->debug('OnDisconnectEvent processed', [
            'event_data' => $context,
            'current_metrics' => $this->getDisconnectionSummary(),
        ]);
    }

    /**
     * Check if disconnect code indicates an error condition.
     */
    private function isErrorDisconnectCode(int $code): bool
    {
        // Based on MQTT v5.0 reason codes
        // 0x00 = Normal disconnection
        // 0x04 = Disconnect with Will Message
        // Other codes generally indicate error conditions
        return ! in_array($code, [0x00, 0x04], true);
    }
}
