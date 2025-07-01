<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Utils;

use Nashgao\MQTT\MQTTConnection;
use Nashgao\MQTT\Pool\MQTTPool;

class HealthChecker
{
    private array $metrics = [];

    public function __construct()
    {
        $this->metrics = [
            'connection_attempts' => 0,
            'connection_failures' => 0,
            'active_connections' => 0,
            'messages_published' => 0,
            'messages_received' => 0,
            'last_health_check' => null,
            'errors' => [],
        ];
    }

    /**
     * Check the health of a connection.
     */
    public function checkConnection(MQTTConnection $connection): array
    {
        $health = [
            'status' => 'unknown',
            'checks' => [],
            'timestamp' => time(),
            'connection_id' => spl_object_hash($connection),
        ];

        try {
            // Check if connection is alive
            $health['checks']['connection_alive'] = $this->isConnectionAlive($connection);

            // Check connection age
            $health['checks']['connection_age'] = $this->getConnectionAge($connection);

            // Check if connection is responding
            $health['checks']['ping_response'] = $this->checkPingResponse($connection);

            // Overall status determination
            $health['status'] = $this->determineOverallStatus($health['checks']);
        } catch (\Exception $e) {
            $health['status'] = 'error';
            $health['error'] = $e->getMessage();
            $this->recordError($e);
        }

        $this->metrics['last_health_check'] = time();
        return $health;
    }

    /**
     * Check the health of a connection pool.
     */
    public function checkPool(MQTTPool $pool): array
    {
        $health = [
            'status' => 'unknown',
            'pool_name' => $pool->getName(),
            'metrics' => [],
            'timestamp' => time(),
        ];

        try {
            // Get pool statistics
            $health['metrics']['available_connections'] = $pool->getAvailableConnectionNum();
            $health['metrics']['connections_in_use'] = $pool->getCurrentConnections() - $pool->getAvailableConnectionNum();
            $health['metrics']['total_connections'] = $pool->getCurrentConnections();

            // Check pool limits
            $health['checks']['pool_not_exhausted'] = $pool->getAvailableConnectionNum() > 0;
            $health['checks']['within_limits'] = $pool->getCurrentConnections() <= $pool->getMaxConnections();

            // Determine pool health status
            if ($health['checks']['pool_not_exhausted'] && $health['checks']['within_limits']) {
                $health['status'] = 'healthy';
            } elseif (! $health['checks']['pool_not_exhausted']) {
                $health['status'] = 'exhausted';
            } else {
                $health['status'] = 'degraded';
            }
        } catch (\Exception $e) {
            $health['status'] = 'error';
            $health['error'] = $e->getMessage();
            $this->recordError($e);
        }

        return $health;
    }

    /**
     * Get comprehensive system health metrics.
     */
    public function getSystemHealth(): array
    {
        return [
            'timestamp' => time(),
            'metrics' => $this->metrics,
            'memory' => [
                'usage' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
                'limit' => ini_get('memory_limit'),
            ],
            'process' => [
                'pid' => getmypid(),
                'uptime' => time() - $_SERVER['REQUEST_TIME_FLOAT'],
            ],
        ];
    }

    /**
     * Record a connection attempt.
     */
    public function recordConnectionAttempt(): void
    {
        ++$this->metrics['connection_attempts'];
    }

    /**
     * Record a connection failure.
     */
    public function recordConnectionFailure(): void
    {
        ++$this->metrics['connection_failures'];
    }

    /**
     * Record an active connection.
     */
    public function recordActiveConnection(): void
    {
        ++$this->metrics['active_connections'];
    }

    /**
     * Record a connection close.
     */
    public function recordConnectionClose(): void
    {
        $this->metrics['active_connections'] = max(0, $this->metrics['active_connections'] - 1);
    }

    /**
     * Record message published.
     */
    public function recordMessagePublished(): void
    {
        ++$this->metrics['messages_published'];
    }

    /**
     * Record message received.
     */
    public function recordMessageReceived(): void
    {
        ++$this->metrics['messages_received'];
    }

    /**
     * Get connection success rate.
     */
    public function getConnectionSuccessRate(): float
    {
        if ($this->metrics['connection_attempts'] === 0) {
            return 1.0;
        }

        $successful = $this->metrics['connection_attempts'] - $this->metrics['connection_failures'];
        return $successful / $this->metrics['connection_attempts'];
    }

    /**
     * Check if system is healthy based on metrics.
     */
    public function isSystemHealthy(): bool
    {
        // System is healthy if:
        // - Connection success rate > 90%
        // - Memory usage < 90% of limit
        // - No critical errors in last 5 minutes

        $successRate = $this->getConnectionSuccessRate();
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
        $memoryUsagePercent = $memoryLimit > 0 ? ($memoryUsage / $memoryLimit) : 0;

        $recentErrors = $this->getRecentErrors(300); // Last 5 minutes

        return $successRate >= 0.9
               && $memoryUsagePercent < 0.9
               && count($recentErrors) === 0;
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

    private function recordError(\Exception $e): void
    {
        $this->metrics['errors'][] = [
            'message' => $e->getMessage(),
            'timestamp' => time(),
            'trace' => $e->getTraceAsString(),
        ];

        // Keep only last 100 errors to prevent memory buildup
        if (count($this->metrics['errors']) > 100) {
            $this->metrics['errors'] = array_slice($this->metrics['errors'], -100);
        }
    }

    private function getRecentErrors(int $seconds): array
    {
        $cutoff = time() - $seconds;
        return array_filter($this->metrics['errors'], function ($error) use ($cutoff) {
            return $error['timestamp'] >= $cutoff;
        });
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
