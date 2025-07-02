<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Utils;

use Nashgao\MQTT\Metrics\ConnectionMetrics;
use Nashgao\MQTT\Metrics\HealthMetrics;
use Nashgao\MQTT\Metrics\PerformanceMetrics;
use Nashgao\MQTT\MQTTConnection;
use Nashgao\MQTT\Pool\MQTTPool;

class HealthChecker
{
    private ConnectionMetrics $connectionMetrics;

    private HealthMetrics $healthMetrics;

    private PerformanceMetrics $performanceMetrics;

    public function __construct(
        ?ConnectionMetrics $connectionMetrics = null,
        ?HealthMetrics $healthMetrics = null,
        ?PerformanceMetrics $performanceMetrics = null
    ) {
        $this->connectionMetrics = $connectionMetrics ?? new ConnectionMetrics();
        $this->healthMetrics = $healthMetrics ?? new HealthMetrics();
        $this->performanceMetrics = $performanceMetrics ?? new PerformanceMetrics();
    }

    /**
     * Check the health of a connection.
     */
    public function checkConnection(MQTTConnection $connection): array
    {
        $connectionId = spl_object_hash($connection);

        try {
            $isAlive = $this->isConnectionAlive($connection);
            $age = $this->getConnectionAge($connection);
            $pingResponse = $this->checkPingResponse($connection);

            $this->healthMetrics->recordHealthCheck(
                "connection_{$connectionId}",
                $isAlive && $pingResponse,
                $isAlive ? 'Connection healthy' : 'Connection unhealthy',
                [
                    'connection_alive' => $isAlive,
                    'connection_age' => $age,
                    'ping_response' => $pingResponse,
                ]
            );
        } catch (\Exception $e) {
            $this->healthMetrics->recordHealthCheck(
                "connection_{$connectionId}",
                false,
                "Connection check failed: {$e->getMessage()}"
            );
        }

        return $this->healthMetrics->getComponentHealth("connection_{$connectionId}") ?? [];
    }

    /**
     * Check the health of a connection pool.
     */
    public function checkPool(MQTTPool $pool): array
    {
        $poolName = $pool->getName();

        try {
            $available = $pool->getAvailableConnectionNum();
            $total = $pool->getCurrentConnections();
            $max = $pool->getMaxConnections();
            $inUse = $total - $available;

            $isHealthy = $available > 0 && $total <= $max;
            $message = $isHealthy ? 'Pool healthy' : 'Pool issues detected';

            // Record resource usage
            $this->healthMetrics->recordResourceUsage('pool_connections', $total, $max);
            $this->healthMetrics->recordResourceUsage('pool_usage_percentage', ($total / $max) * 100, 100);

            $this->healthMetrics->recordHealthCheck(
                "pool_{$poolName}",
                $isHealthy,
                $message,
                [
                    'available_connections' => $available,
                    'connections_in_use' => $inUse,
                    'total_connections' => $total,
                    'max_connections' => $max,
                    'pool_not_exhausted' => $available > 0,
                    'within_limits' => $total <= $max,
                ]
            );
        } catch (\Exception $e) {
            $this->healthMetrics->recordHealthCheck(
                "pool_{$poolName}",
                false,
                "Pool check failed: {$e->getMessage()}"
            );
        }

        return $this->healthMetrics->getComponentHealth("pool_{$poolName}") ?? [];
    }

    /**
     * Get comprehensive system health metrics.
     */
    public function getSystemHealth(): array
    {
        // Update performance metrics
        $this->performanceMetrics->recordMemoryUsage();

        // Record system resource usage in health metrics
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
        $this->healthMetrics->recordResourceUsage('memory', $memoryUsage, $memoryLimit);

        return [
            'timestamp' => time(),
            'health' => $this->healthMetrics->toArray(),
            'connections' => $this->connectionMetrics->toArray(),
            'performance' => $this->performanceMetrics->toArray(),
            'process' => [
                'pid' => getmypid(),
                'uptime' => time() - ($_SERVER['REQUEST_TIME_FLOAT'] ?? time()),
            ],
        ];
    }

    /**
     * Record a connection attempt.
     */
    public function recordConnectionAttempt(): void
    {
        $this->connectionMetrics->recordConnectionAttempt();
    }

    /**
     * Record a connection failure.
     */
    public function recordConnectionFailure(): void
    {
        $this->connectionMetrics->recordFailedConnection();
    }

    /**
     * Record an active connection.
     */
    public function recordActiveConnection(): void
    {
        $this->connectionMetrics->recordSuccessfulConnection();
    }

    /**
     * Record a connection close.
     */
    public function recordConnectionClose(): void
    {
        $this->connectionMetrics->recordDisconnection();
    }

    /**
     * Record message published.
     */
    public function recordMessagePublished(): void
    {
        $this->performanceMetrics->recordMessageThroughput(1);
    }

    /**
     * Record message received.
     */
    public function recordMessageReceived(): void
    {
        $this->performanceMetrics->recordMessageThroughput(1);
    }

    /**
     * Get connection success rate.
     */
    public function getConnectionSuccessRate(): float
    {
        return $this->connectionMetrics->getSuccessRate();
    }

    /**
     * Check if system is healthy based on metrics.
     */
    public function isSystemHealthy(): bool
    {
        // Update system health checks
        $successRate = $this->getConnectionSuccessRate();
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
        $memoryUsagePercent = $memoryLimit > 0 ? ($memoryUsage / $memoryLimit) : 0;

        $this->healthMetrics->recordHealthCheck(
            'system',
            $successRate >= 0.9 && $memoryUsagePercent < 0.9,
            'System health check',
            [
                'connection_success_rate' => $successRate,
                'memory_usage_percent' => $memoryUsagePercent,
            ]
        );

        return $this->healthMetrics->isHealthy();
    }

    private function isConnectionAlive(MQTTConnection $connection): bool
    {
        try {
            // This is a simplified check - in real implementation,
            // you might ping the connection or check its internal state
            return $connection instanceof MQTTConnection;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getConnectionAge(MQTTConnection $connection): int
    {
        // Simplified - would need to track connection creation time
        return 0;
    }

    private function checkPingResponse(MQTTConnection $connection): bool
    {
        try {
            // Simplified ping check - in real implementation,
            // you would send a PING packet and wait for PONG
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function determineOverallStatus(array $checks): string
    {
        $allPassed = true;
        foreach ($checks as $check => $result) {
            if (! $result) {
                $allPassed = false;
                break;
            }
        }

        return $allPassed ? 'healthy' : 'unhealthy';
    }

    private function parseMemoryLimit(string $limit): int
    {
        if ($limit === '-1') {
            return 0; // No limit
        }

        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $limit = (int) $limit;

        $limit *= match ($last) {
            'g' => 1024 * 1024 * 1024,
            'm' => 1024 * 1024,
            'k' => 1024,
            default => 1
        };

        return $limit;
    }
}
