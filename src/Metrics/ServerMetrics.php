<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Metrics;

use Nashgao\MQTT\Metrics\Abstracts\BaseMetrics;
use Nashgao\MQTT\Metrics\Traits\StatisticsCalculation;

class ServerMetrics extends BaseMetrics
{
    use StatisticsCalculation;

    private string $serverId = '';

    private float $serverStartTime = 0.0;

    private float $lastHeartbeat = 0.0;

    private int $restartCount = 0;

    private array $lifecycleEvents = [];

    private array $serverStats = [];

    private bool $isRunning = false;

    public function recordServerStart(string $serverId): void
    {
        $this->serverId = $serverId;
        $this->serverStartTime = $this->getCurrentTimestamp();
        $this->isRunning = true;
        ++$this->restartCount;
        $this->lastHeartbeat = $this->getCurrentTimestamp();

        $this->recordLifecycleEvent('server_start', [
            'server_id' => $serverId,
            'restart_count' => $this->restartCount,
            'timestamp' => $this->getCurrentDate(),
        ]);
    }

    public function recordServerStop(?string $reason = null): void
    {
        $this->isRunning = false;
        $uptime = $this->getUptime();

        $this->recordLifecycleEvent('server_stop', [
            'server_id' => $this->serverId,
            'uptime_seconds' => $uptime,
            'reason' => $reason ?? 'normal_shutdown',
            'timestamp' => $this->getCurrentDate(),
        ]);
    }

    public function recordHeartbeat(): void
    {
        $this->lastHeartbeat = $this->getCurrentTimestamp();
        $this->updateServerStats();
    }

    public function recordServerEvent(string $eventType, array $data = []): void
    {
        $this->recordLifecycleEvent($eventType, array_merge($data, [
            'server_id' => $this->serverId,
            'timestamp' => $this->getCurrentDate(),
        ]));
    }

    public function getServerId(): string
    {
        return $this->serverId;
    }

    public function getServerStartTime(): float
    {
        return $this->serverStartTime;
    }

    public function getUptime(): float
    {
        if ($this->serverStartTime === 0.0) {
            return 0.0;
        }

        return $this->getCurrentTimestamp() - $this->serverStartTime;
    }

    public function getUptimeFormatted(): string
    {
        return $this->formatDuration($this->getUptime());
    }

    public function isServerRunning(): bool
    {
        return $this->isRunning;
    }

    public function getRestartCount(): int
    {
        return $this->restartCount;
    }

    public function getLastHeartbeat(): float
    {
        return $this->lastHeartbeat;
    }

    public function getTimeSinceLastHeartbeat(): float
    {
        return $this->getCurrentTimestamp() - $this->lastHeartbeat;
    }

    public function getLifecycleEvents(): array
    {
        return $this->lifecycleEvents;
    }

    public function getRecentEvents(int $limit = 10): array
    {
        return array_slice($this->lifecycleEvents, -$limit);
    }

    public function getServerStats(): array
    {
        return $this->serverStats;
    }

    public function getEventsByType(string $eventType): array
    {
        return array_filter($this->lifecycleEvents, function ($event) use ($eventType) {
            return $event['event'] === $eventType;
        });
    }

    public function getEventCount(string $eventType): int
    {
        return count($this->getEventsByType($eventType));
    }

    public function getHealthStatus(): array
    {
        $timeSinceHeartbeat = $this->getTimeSinceLastHeartbeat();
        $isHealthy = $this->isRunning && $timeSinceHeartbeat < 300; // 5 minutes threshold

        return [
            'is_healthy' => $isHealthy,
            'is_running' => $this->isRunning,
            'uptime' => $this->getUptime(),
            'uptime_formatted' => $this->getUptimeFormatted(),
            'time_since_heartbeat' => $timeSinceHeartbeat,
            'restart_count' => $this->restartCount,
            'server_id' => $this->serverId,
            'memory_usage' => $this->serverStats['memory_usage'] ?? 0,
            'memory_peak' => $this->serverStats['memory_peak'] ?? 0,
        ];
    }

    public function getSummary(): array
    {
        return [
            'server_id' => $this->serverId,
            'is_running' => $this->isRunning,
            'uptime' => $this->getUptime(),
            'uptime_formatted' => $this->getUptimeFormatted(),
            'restart_count' => $this->restartCount,
            'total_events' => count($this->lifecycleEvents),
            'last_heartbeat' => $this->lastHeartbeat,
            'time_since_heartbeat' => $this->getTimeSinceLastHeartbeat(),
            'server_stats' => $this->serverStats,
        ];
    }

    public function toArray(): array
    {
        return [
            'server_id' => $this->serverId,
            'server_start_time' => $this->serverStartTime,
            'is_running' => $this->isRunning,
            'uptime' => $this->getUptime(),
            'restart_count' => $this->restartCount,
            'last_heartbeat' => $this->lastHeartbeat,
            'time_since_heartbeat' => $this->getTimeSinceLastHeartbeat(),
            'lifecycle_events' => $this->lifecycleEvents,
            'server_stats' => $this->serverStats,
            'health_status' => $this->getHealthStatus(),
        ];
    }

    public function reset(): void
    {
        $this->serverId = '';
        $this->serverStartTime = 0.0;
        $this->lastHeartbeat = 0.0;
        $this->restartCount = 0;
        $this->lifecycleEvents = [];
        $this->serverStats = [];
        $this->isRunning = false;
    }

    private function recordLifecycleEvent(string $event, array $data): void
    {
        $eventData = [
            'event' => $event,
            'data' => $data,
            'timestamp' => $this->getCurrentTimestamp(),
        ];

        $this->addToLimitedArray($this->lifecycleEvents, $eventData);
    }

    private function updateServerStats(): void
    {
        $this->serverStats = [
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'uptime' => $this->getUptime(),
            'last_heartbeat' => $this->lastHeartbeat,
            'time_since_heartbeat' => $this->getCurrentTimestamp() - $this->lastHeartbeat,
        ];
    }
}
