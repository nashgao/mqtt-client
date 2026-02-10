<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Listener;

use Hyperf\Event\Contract\ListenerInterface;
use Nashgao\MQTT\Metrics\PerformanceMetrics;
use Nashgao\MQTT\Metrics\ServerMetrics;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Enhanced ServerIdListener with comprehensive server lifecycle metrics.
 */
class ServerIdListener implements ListenerInterface
{
    public static string $serverId;

    private static ?ServerMetrics $serverMetrics = null;

    private static ?PerformanceMetrics $performanceMetrics = null;

    private static ?LoggerInterface $logger = null;

    public function __construct(
        ?ServerMetrics $serverMetrics = null,
        ?PerformanceMetrics $performanceMetrics = null,
        ?LoggerInterface $logger = null
    ) {
        self::$serverMetrics = $serverMetrics ?? new ServerMetrics();
        self::$performanceMetrics = $performanceMetrics ?? new PerformanceMetrics();
        self::$logger = $logger ?? new NullLogger();
    }

    public function listen(): array
    {
        return [
            'Hyperf\Framework\Event\BeforeMainServerStart',
            'Hyperf\Framework\Event\AfterMainServerStart',
            'Hyperf\Framework\Event\BeforeMainServerStop',
            'Hyperf\Process\Event\BeforeProcessHandle',
        ];
    }

    public function process(object $event): void
    {
        try {
            switch (get_class($event)) {
                case 'Hyperf\Framework\Event\BeforeMainServerStart':
                    $this->handleBeforeServerStart($event);
                    break;
                case 'Hyperf\Framework\Event\AfterMainServerStart':
                    $this->handleAfterServerStart($event);
                    break;
                case 'Hyperf\Framework\Event\BeforeMainServerStop':
                    $this->handleBeforeServerStop($event);
                    break;
                case 'Hyperf\Process\Event\BeforeProcessHandle':
                    $this->handleBeforeProcessHandle($event);
                    break;
                default:
                    $this->handleGenericEvent($event);
                    break;
            }
        } catch (\Exception $e) {
            self::$logger->error('Failed to process server event', [
                'event_class' => get_class($event),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Get server metrics instance.
     */
    public static function getServerMetrics(): ?ServerMetrics
    {
        return self::$serverMetrics;
    }

    /**
     * Get performance metrics instance.
     */
    public static function getPerformanceMetrics(): ?PerformanceMetrics
    {
        return self::$performanceMetrics;
    }

    /**
     * Get server health status.
     */
    public static function getServerHealth(): array
    {
        if (self::$serverMetrics === null) {
            return ['status' => 'not_initialized'];
        }

        return self::$serverMetrics->getHealthStatus();
    }

    /**
     * Get comprehensive server information.
     */
    public static function getServerInfo(): array
    {
        $info = [
            'server_id' => static::$serverId ?? 'not_set',
            'pid' => getmypid(),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        if (self::$serverMetrics !== null) {
            $info = array_merge($info, self::$serverMetrics->getSummary());
        }

        return $info;
    }

    /**
     * Record custom server event.
     */
    public static function recordServerEvent(string $eventType, array $data = []): void
    {
        if (self::$serverMetrics !== null) {
            self::$serverMetrics->recordServerEvent($eventType, $data);
        }

        if (self::$logger !== null) {
            self::$logger->info("Custom server event: {$eventType}", array_merge($data, [
                'server_id' => static::$serverId ?? 'unknown',
                'timestamp' => date('Y-m-d H:i:s'),
            ]));
        }
    }

    /**
     * Create enhanced listener with dependencies.
     */
    public static function create(
        ?ServerMetrics $serverMetrics = null,
        ?PerformanceMetrics $performanceMetrics = null,
        ?LoggerInterface $logger = null
    ): self {
        return new self($serverMetrics, $performanceMetrics, $logger);
    }

    /**
     * Handle server start preparation.
     */
    private function handleBeforeServerStart(object $event): void
    {
        static::$serverId = uniqid('mqtt_server_', true);

        self::$serverMetrics->recordServerEvent('before_server_start', [
            'pid' => getmypid(),
            'memory_usage' => memory_get_usage(true),
        ]);

        self::$logger->info('MQTT Server preparing to start', [
            'server_id' => static::$serverId,
            'pid' => getmypid(),
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Handle server start completion.
     * @param object $event
     */
    private function handleAfterServerStart(object $event): void
    {
        self::$serverMetrics->recordServerStart(static::$serverId);

        // Record initial performance metrics
        self::$performanceMetrics->recordMemoryUsage();

        self::$logger->info('MQTT Server started successfully', [
            'server_id' => static::$serverId,
            'pid' => getmypid(),
            'uptime' => self::$serverMetrics->getUptime(),
            'memory_usage' => memory_get_usage(true),
            'timestamp' => date('Y-m-d H:i:s'),
        ]);

        // Start heartbeat monitoring
        $this->startHeartbeatMonitoring();
    }

    /**
     * Handle server stop preparation.
     * @param object $event
     */
    private function handleBeforeServerStop(object $event): void
    {
        $uptime = self::$serverMetrics->getUptime();
        $reason = 'normal_shutdown';

        self::$serverMetrics->recordServerStop($reason);

        self::$logger->info('MQTT Server stopping', [
            'server_id' => static::$serverId,
            'uptime' => $uptime,
            'uptime_formatted' => self::$serverMetrics->getUptimeFormatted(),
            'reason' => $reason,
            'final_memory_usage' => memory_get_usage(true),
            'peak_memory_usage' => memory_get_peak_usage(true),
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Handle process events.
     * @param object $event
     */
    private function handleBeforeProcessHandle(object $event): void
    {
        self::$serverMetrics->recordServerEvent('process_handle', [
            'pid' => getmypid(),
            'memory_usage' => memory_get_usage(true),
        ]);

        // Record heartbeat
        self::$serverMetrics->recordHeartbeat();

        // Record performance metrics
        self::$performanceMetrics->recordMemoryUsage();
    }

    /**
     * Handle generic server events.
     * @param object $event
     */
    private function handleGenericEvent($event): void
    {
        $eventClass = get_class($event);

        self::$serverMetrics->recordServerEvent('generic_event', [
            'event_class' => $eventClass,
            'pid' => getmypid(),
            'memory_usage' => memory_get_usage(true),
        ]);

        self::$logger->debug('Generic server event processed', [
            'event_class' => $eventClass,
            'server_id' => static::$serverId,
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Start heartbeat monitoring.
     */
    private function startHeartbeatMonitoring(): void
    {
        // This could be enhanced with actual coroutine-based monitoring
        // For now, we just record the initial heartbeat
        self::$serverMetrics->recordHeartbeat();

        self::$logger->debug('Heartbeat monitoring started', [
            'server_id' => static::$serverId,
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
    }
}
