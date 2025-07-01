<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Metrics;

class HealthMetrics
{
    private string $status = 'healthy';
    private array $healthChecks = [];
    private float $lastHealthCheck = 0.0;
    private array $healthHistory = [];
    private int $consecutiveFailures = 0;
    private int $consecutiveSuccesses = 0;
    private array $resourceUsage = [];

    public function recordHealthCheck(string $component, bool $isHealthy, string $message = '', array $details = []): void
    {
        $timestamp = microtime(true);
        $this->lastHealthCheck = $timestamp;
        
        $this->healthChecks[$component] = [
            'healthy' => $isHealthy,
            'message' => $message,
            'details' => $details,
            'timestamp' => $timestamp,
        ];

        $this->updateHealthHistory($component, $isHealthy, $timestamp);
        $this->updateOverallStatus();
    }

    public function recordResourceUsage(string $resource, float $value, float $limit = null): void
    {
        $this->resourceUsage[$resource] = [
            'value' => $value,
            'limit' => $limit,
            'usage_percentage' => $limit ? ($value / $limit) * 100 : 0,
            'timestamp' => microtime(true),
        ];
    }

    private function updateHealthHistory(string $component, bool $isHealthy, float $timestamp): void
    {
        if (!isset($this->healthHistory[$component])) {
            $this->healthHistory[$component] = [];
        }

        $this->healthHistory[$component][] = [
            'healthy' => $isHealthy,
            'timestamp' => $timestamp,
        ];

        if (count($this->healthHistory[$component]) > 100) {
            array_shift($this->healthHistory[$component]);
        }
    }

    private function updateOverallStatus(): void
    {
        $allHealthy = true;
        $hasComponents = false;

        foreach ($this->healthChecks as $component => $check) {
            $hasComponents = true;
            if (!$check['healthy']) {
                $allHealthy = false;
                break;
            }
        }

        $previousStatus = $this->status;
        
        if (!$hasComponents) {
            $this->status = 'unknown';
        } elseif ($allHealthy) {
            $this->status = 'healthy';
            if ($previousStatus !== 'healthy') {
                $this->consecutiveFailures = 0;
                $this->consecutiveSuccesses++;
            }
        } else {
            $this->status = 'unhealthy';
            if ($previousStatus !== 'unhealthy') {
                $this->consecutiveSuccesses = 0;
                $this->consecutiveFailures++;
            }
        }
    }

    public function getOverallStatus(): string
    {
        return $this->status;
    }

    public function isHealthy(): bool
    {
        return $this->status === 'healthy';
    }

    public function getComponentHealth(string $component): ?array
    {
        return $this->healthChecks[$component] ?? null;
    }

    public function getAllComponentsHealth(): array
    {
        return $this->healthChecks;
    }

    public function getUnhealthyComponents(): array
    {
        return array_filter($this->healthChecks, fn($check) => !$check['healthy']);
    }

    public function getHealthUptime(string $component): float
    {
        if (!isset($this->healthHistory[$component])) {
            return 0.0;
        }

        $history = $this->healthHistory[$component];
        if (empty($history)) {
            return 0.0;
        }

        $healthyCount = count(array_filter($history, fn($entry) => $entry['healthy']));
        return $healthyCount / count($history);
    }

    public function getResourceUsage(string $resource = null): array
    {
        if ($resource !== null) {
            return $this->resourceUsage[$resource] ?? [];
        }
        return $this->resourceUsage;
    }

    public function getCriticalResources(): array
    {
        return array_filter($this->resourceUsage, fn($resource) => 
            isset($resource['usage_percentage']) && $resource['usage_percentage'] > 80
        );
    }

    public function getLastHealthCheckTime(): float
    {
        return $this->lastHealthCheck;
    }

    public function getConsecutiveFailures(): int
    {
        return $this->consecutiveFailures;
    }

    public function getConsecutiveSuccesses(): int
    {
        return $this->consecutiveSuccesses;
    }

    public function getHealthScore(): float
    {
        if (empty($this->healthChecks)) {
            return 0.0;
        }

        $totalComponents = count($this->healthChecks);
        $healthyComponents = count(array_filter($this->healthChecks, fn($check) => $check['healthy']));
        
        $baseScore = $healthyComponents / $totalComponents;
        
        $resourcePenalty = 0.0;
        foreach ($this->resourceUsage as $resource) {
            if (isset($resource['usage_percentage']) && $resource['usage_percentage'] > 90) {
                $resourcePenalty += 0.1;
            } elseif (isset($resource['usage_percentage']) && $resource['usage_percentage'] > 80) {
                $resourcePenalty += 0.05;
            }
        }
        
        return max(0.0, $baseScore - $resourcePenalty);
    }

    public function toArray(): array
    {
        return [
            'overall_status' => $this->status,
            'is_healthy' => $this->isHealthy(),
            'health_score' => $this->getHealthScore(),
            'last_health_check' => $this->lastHealthCheck,
            'consecutive_failures' => $this->consecutiveFailures,
            'consecutive_successes' => $this->consecutiveSuccesses,
            'components' => $this->healthChecks,
            'unhealthy_components' => array_keys($this->getUnhealthyComponents()),
            'resource_usage' => $this->resourceUsage,
            'critical_resources' => array_keys($this->getCriticalResources()),
            'component_uptimes' => array_map(
                fn($component) => $this->getHealthUptime($component),
                array_keys($this->healthChecks)
            ),
        ];
    }

    public function reset(): void
    {
        $this->status = 'healthy';
        $this->healthChecks = [];
        $this->lastHealthCheck = 0.0;
        $this->healthHistory = [];
        $this->consecutiveFailures = 0;
        $this->consecutiveSuccesses = 0;
        $this->resourceUsage = [];
    }
}