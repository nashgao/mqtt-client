<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Utils;

use Nashgao\MQTT\Metrics\ConnectionMetrics;
use Nashgao\MQTT\Metrics\PerformanceMetrics;
use Nashgao\MQTT\Metrics\PublishMetrics;
use Nashgao\MQTT\Metrics\SubscriptionMetrics;
use Nashgao\MQTT\Metrics\ValidationMetrics;

/**
 * Real-time MQTT monitoring utility (htop-style).
 * Provides live updates of MQTT metrics with keyboard controls.
 */
class MqttMonitor
{
    private MetricsVisualizer $visualizer;

    private bool $running = false;

    private int $refreshInterval = 1; // seconds

    private string $sortBy = 'name';

    private bool $showHelp = false;

    private array $metrics = [];

    private array $history = [];

    private int $maxHistorySize = 60; // Keep 60 data points for graphs

    public function __construct()
    {
        $this->visualizer = new MetricsVisualizer();
        $this->initializeMetrics();
    }

    /**
     * Start the real-time monitoring.
     */
    public function start(): void
    {
        $this->running = true;
        $this->setupTerminal();

        // Clear screen and hide cursor
        echo "\033[2J\033[H\033[?25l";

        // Set up signal handlers
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGINT, [$this, 'stop']);
            pcntl_signal(SIGTERM, [$this, 'stop']);
        }

        try {
            $this->mainLoop();
        } finally {
            $this->cleanup();
        }
    }

    /**
     * Stop the monitoring.
     */
    public function stop(): void
    {
        $this->running = false;
    }

    /**
     * Set refresh interval in seconds.
     */
    public function setRefreshInterval(int $seconds): self
    {
        $this->refreshInterval = max(1, $seconds);
        return $this;
    }

    /**
     * Add metrics instances for monitoring.
     */
    public function addMetrics(
        ?ConnectionMetrics $connection = null,
        ?PerformanceMetrics $performance = null,
        ?PublishMetrics $publish = null,
        ?SubscriptionMetrics $subscription = null,
        ?ValidationMetrics $validation = null
    ): self {
        if ($connection) {
            $this->visualizer->setConnectionMetrics($connection);
            $this->metrics['connection'] = $connection;
        }

        if ($performance) {
            $this->visualizer->setPerformanceMetrics($performance);
            $this->metrics['performance'] = $performance;
        }

        if ($publish) {
            $this->visualizer->setPublishMetrics($publish);
            $this->metrics['publish'] = $publish;
        }

        if ($subscription) {
            $this->visualizer->setSubscriptionMetrics($subscription);
            $this->metrics['subscription'] = $subscription;
        }

        if ($validation) {
            $this->visualizer->setValidationMetrics($validation);
            $this->metrics['validation'] = $validation;
        }

        return $this;
    }

    /**
     * Export current metrics to file.
     */
    public function exportMetrics(string $format = 'json', ?string $filename = null): string
    {
        $timestamp = date('Y-m-d_H-i-s');
        $defaultFilename = "mqtt_metrics_{$timestamp}.{$format}";
        $filename = $filename ?? $defaultFilename;

        $data = match ($format) {
            'json' => $this->visualizer->generateJson(),
            'csv' => $this->visualizer->generateCsv(),
            'dashboard' => $this->visualizer->generateDashboard(),
            default => throw new \InvalidArgumentException("Unsupported format: {$format}"),
        };

        file_put_contents($filename, $data);
        return $filename;
    }

    /**
     * Generate a one-time snapshot of metrics.
     */
    public function snapshot(string $format = 'dashboard'): string
    {
        $this->updateMetrics();

        return match ($format) {
            'json' => $this->visualizer->generateJson(),
            'csv' => $this->visualizer->generateCsv(),
            'dashboard' => $this->visualizer->generateDashboard(),
            'realtime' => $this->visualizer->generateRealTimeDisplay(),
            default => throw new \InvalidArgumentException("Unsupported format: {$format}"),
        };
    }

    private function mainLoop(): void
    {
        while ($this->running) {
            $this->updateMetrics();
            $this->updateHistory();
            $this->renderScreen();
            $this->handleInput();

            // Use usleep for more responsive input handling
            for ($i = 0; $i < $this->refreshInterval * 10; ++$i) {
                usleep(100000); // 0.1 seconds
                if (! $this->running) {
                    break;
                }

                // Check for input more frequently
                if ($i % 5 == 0) {
                    $this->handleInput();
                }

                // Process signals if available
                if (function_exists('pcntl_signal_dispatch')) {
                    pcntl_signal_dispatch();
                }
            }
        }
    }

    private function setupTerminal(): void
    {
        // Set terminal to raw mode for immediate key detection
        if (function_exists('system')) {
            system('stty -icanon -echo');
        }

        // Set stdin to non-blocking mode
        if (function_exists('stream_set_blocking')) {
            stream_set_blocking(STDIN, false);
        }
    }

    private function cleanup(): void
    {
        echo "\033[?25h"; // Show cursor
        echo "\033[2J\033[H"; // Clear screen
        echo "\033[0m"; // Reset colors

        // Restore terminal settings
        if (function_exists('system')) {
            system('stty icanon echo');
        }

        // Restore blocking mode for stdin
        if (function_exists('stream_set_blocking')) {
            stream_set_blocking(STDIN, true);
        }

        echo "MQTT Monitor stopped.\n";
    }

    private function renderScreen(): void
    {
        // Move cursor to top-left and clear screen from cursor down
        echo "\033[H\033[J";

        if ($this->showHelp) {
            echo $this->generateHelpScreen();
        } else {
            echo $this->generateMainScreen();
        }

        // Flush output immediately
        flush();
    }

    private function generateMainScreen(): string
    {
        $output = [];

        // Header
        $output[] = $this->generateHeader();

        // System overview
        $output[] = $this->generateSystemOverview();
        $output[] = '';

        // Metrics grid
        $output[] = $this->generateMetricsGrid();
        $output[] = '';

        // Graphs
        $output[] = $this->generateGraphsSection();
        $output[] = '';

        // Top topics/operations
        $output[] = $this->generateTopOperations();
        $output[] = '';

        // Status bar
        $output[] = $this->generateStatusBar();

        return implode("\n", $output);
    }

    private function generateHelpScreen(): string
    {
        return <<<EOF
\033[1;37mMQTT Monitor - Help\033[0m

\033[1;36mKeyboard Commands:\033[0m
  \033[1;32mq, Q, Ctrl+C\033[0m  - Quit the monitor
  \033[1;32mh, H, ?\033[0m       - Show/hide this help screen
  \033[1;32mr, R\033[0m          - Reset metrics counters
  \033[1;32me, E\033[0m          - Export metrics to file
  \033[1;32m+\033[0m             - Increase refresh rate (faster updates)
  \033[1;32m-\033[0m             - Decrease refresh rate (slower updates)
  \033[1;32ms, S\033[0m          - Toggle sort order
  \033[1;32mp, P\033[0m          - Pause/resume monitoring
  \033[1;32mSpace\033[0m         - Force refresh

\033[1;36mMetrics Explanation:\033[0m
  \033[1;33mConnections\033[0m   - MQTT client connection statistics
  \033[1;33mPerformance\033[0m   - Response times and throughput metrics
  \033[1;33mPublish\033[0m       - Message publishing statistics
  \033[1;33mSubscription\033[0m  - Topic subscription and message receiving stats
  \033[1;33mValidation\033[0m    - Configuration and data validation metrics

\033[1;36mGraph Symbols:\033[0m
  \033[1;32m●\033[0m             - Data point
  \033[1;32m│\033[0m             - Vertical line
  \033[1;32m█\033[0m             - Bar chart fill
  \033[1;32m░\033[0m             - Bar chart empty

\033[1;36mColor Coding:\033[0m
  \033[1;32mGreen\033[0m         - Good performance/high success rates
  \033[1;33mYellow\033[0m        - Warning/moderate performance
  \033[1;31mRed\033[0m           - Error/poor performance
  \033[1;36mCyan\033[0m          - Information/neutral

Current refresh interval: {$this->refreshInterval} second(s)

Press any key to return to the main screen...
EOF;
    }

    private function generateHeader(): string
    {
        $timestamp = date('H:i:s');
        $uptime = $this->getUptime();

        return sprintf(
            "\033[1;37m┌─ MQTT Monitor ─ %s ─ Uptime: %s ─ Refresh: %ds ─┐\033[0m",
            $timestamp,
            $uptime,
            $this->refreshInterval
        );
    }

    private function generateSystemOverview(): string
    {
        $cpu = $this->getCpuUsage();
        $memory = $this->getMemoryUsage();
        $load = $this->getSystemLoad();

        return sprintf(
            'System: CPU %s%% │ Memory %sMB │ Load %s │ PHP %s',
            $this->colorizePercentage($cpu),
            number_format($memory, 1),
            $load,
            PHP_VERSION
        );
    }

    private function generateMetricsGrid(): string
    {
        $output = [];

        // Create 2x3 grid of metrics
        $metrics = [
            'Connections' => $this->formatConnectionMetrics(),
            'Performance' => $this->formatPerformanceMetrics(),
            'Publishing' => $this->formatPublishMetrics(),
            'Subscriptions' => $this->formatSubscriptionMetrics(),
            'Validation' => $this->formatValidationMetrics(),
            'System Health' => $this->formatSystemHealth(),
        ];

        $keys = array_keys($metrics);
        for ($i = 0; $i < 6; $i += 3) {
            $row = [];
            for ($j = 0; $j < 3 && ($i + $j) < 6; ++$j) {
                $key = $keys[$i + $j] ?? '';
                $data = $metrics[$key] ?? '';
                $row[] = sprintf('%-25s', $data);
            }
            $output[] = implode(' │ ', $row);
        }

        return implode("\n", $output);
    }

    private function generateGraphsSection(): string
    {
        $responseTimeHistory = $this->history['response_time'] ?? array_fill(0, 30, 0);
        $throughputHistory = $this->history['throughput'] ?? array_fill(0, 30, 0);

        $graph1 = $this->generateMiniGraph('Response Time', $responseTimeHistory, 35);
        $graph2 = $this->generateMiniGraph('Throughput', $throughputHistory, 35);

        return $graph1 . ' │ ' . $graph2;
    }

    private function generateTopOperations(): string
    {
        // Show top topics by activity
        $operations = [
            'sensor/temperature' => 1250,
            'device/status' => 890,
            'alert/critical' => 45,
            'logs/application' => 230,
            'metrics/system' => 670,
        ];

        arsort($operations);
        $operations = array_slice($operations, 0, 5, true);

        $output = ["\033[1;37mTop Topics by Activity:\033[0m"];
        foreach ($operations as $topic => $count) {
            $bar = $this->generateMiniBar($count, max($operations), 20);
            $output[] = sprintf('  %-20s %s %4d', substr($topic, 0, 20), $bar, $count);
        }

        return implode("\n", $output);
    }

    private function generateStatusBar(): string
    {
        $controls = [
            "\033[1;32mQ\033[0m=Quit",
            "\033[1;32mH\033[0m=Help",
            "\033[1;32mE\033[0m=Export",
            "\033[1;32mR\033[0m=Reset",
            "\033[1;32m+/-\033[0m=Speed",
        ];

        return '└─ ' . implode(' │ ', $controls) . ' ─┘';
    }

    private function handleInput(): void
    {
        // Read all available input without blocking
        while (($key = fread(STDIN, 1)) !== false && $key !== '') {
            switch (strtolower($key)) {
                case 'q':
                case "\x03": // Ctrl+C
                    $this->stop();
                    return;
                case 'h':
                case '?':
                    $this->showHelp = ! $this->showHelp;
                    break;
                case 'r':
                    $this->resetMetrics();
                    break;
                case 'e':
                    $filename = $this->exportMetrics('json');
                    // Could show a temporary notification here
                    break;
                case '+':
                    $this->refreshInterval = max(1, $this->refreshInterval - 1);
                    break;
                case '-':
                    $this->refreshInterval = min(10, $this->refreshInterval + 1);
                    break;
                case ' ':
                    // Force refresh - just continue to next iteration
                    break;
            }
        }
    }

    private function initializeMetrics(): void
    {
        $this->metrics = [
            'connection' => new ConnectionMetrics(),
            'performance' => new PerformanceMetrics(),
            'publish' => new PublishMetrics(),
            'subscription' => new SubscriptionMetrics(),
            'validation' => new ValidationMetrics(),
        ];

        // Set up the visualizer with default metrics
        $this->addMetrics(
            $this->metrics['connection'],
            $this->metrics['performance'],
            $this->metrics['publish'],
            $this->metrics['subscription'],
            $this->metrics['validation']
        );
    }

    private function updateMetrics(): void
    {
        // In a real implementation, this would fetch live data
        // For now, we'll simulate some activity
        $this->simulateMetricUpdates();
    }

    private function updateHistory(): void
    {
        // Store current metrics for graphing
        $performanceData = $this->metrics['performance']->toArray();

        $this->history['response_time'][] = $performanceData['avg_response_time'] ?? rand(10, 100);
        $this->history['throughput'][] = $performanceData['throughput'] ?? rand(50, 200);

        // Keep only recent history
        foreach ($this->history as $key => $values) {
            if (count($values) > $this->maxHistorySize) {
                $this->history[$key] = array_slice($values, -$this->maxHistorySize);
            }
        }
    }

    private function simulateMetricUpdates(): void
    {
        // Simulate some metrics activity for demonstration using method chaining
        if (rand(1, 10) > 7) {
            if (rand(1, 10) > 3) {
                $this->metrics['connection']
                    ->recordConnectionAttempt()
                    ->recordSuccessfulConnection(rand(50, 200) / 1000);
            } else {
                $this->metrics['connection']
                    ->recordConnectionAttempt()
                    ->recordFailedConnection();
            }
        }

        if (rand(1, 10) > 6) {
            if (rand(1, 10) > 2) {
                $this->metrics['publish']
                    ->recordPublishAttempt()
                    ->recordSuccessfulPublish('test/topic', rand(0, 2), strlen('test message'));
            } else {
                $this->metrics['publish']
                    ->recordPublishAttempt()
                    ->recordFailedPublish();
            }
        }

        if (rand(1, 10) > 5) {
            if (rand(1, 10) > 2) {
                $this->metrics['subscription']
                    ->recordSubscriptionAttempt()
                    ->recordSuccessfulSubscription(
                        'test_pool',
                        'test_client',
                        ['test/topic' => rand(0, 2)]
                    );
            } else {
                $this->metrics['subscription']
                    ->recordSubscriptionAttempt()
                    ->recordFailedSubscription(
                        'test_pool',
                        'test_client',
                        ['test/topic' => rand(0, 2)]
                    );
            }
        }

        if (rand(1, 10) > 8) {
            $this->metrics['validation']->recordValidation('test_validation', true, '');
        }
    }

    private function resetMetrics(): void
    {
        foreach ($this->metrics as $metric) {
            if (method_exists($metric, 'reset')) {
                $metric->reset();
            }
        }
        $this->history = [];
    }

    private function formatConnectionMetrics(): string
    {
        $data = $this->metrics['connection']->toArray();
        return sprintf(
            "\033[1;36mConnections\033[0m\nActive: %d\nTotal: %d\nRate: %s%%",
            $data['active_connections'] ?? 0,
            $data['total_connections'] ?? 0,
            number_format($data['success_rate'] ?? 0, 1)
        );
    }

    private function formatPerformanceMetrics(): string
    {
        $data = $this->metrics['performance']->toArray();
        return sprintf(
            "\033[1;33mPerformance\033[0m\nAvg RT: %sms\nThroughput: %s/s\nPeak: %sms",
            number_format($data['avg_response_time'] ?? 0, 1),
            number_format($data['throughput'] ?? 0, 0),
            number_format($data['max_response_time'] ?? 0, 1)
        );
    }

    private function formatPublishMetrics(): string
    {
        $data = $this->metrics['publish']->toArray();
        return sprintf(
            "\033[1;32mPublishing\033[0m\nTotal: %d\nFailed: %d\nRate: %s%%",
            $data['total_published'] ?? 0,
            $data['failed_publishes'] ?? 0,
            number_format($data['success_rate'] ?? 0, 1)
        );
    }

    private function formatSubscriptionMetrics(): string
    {
        $data = $this->metrics['subscription']->toArray();
        return sprintf(
            "\033[1;35mSubscriptions\033[0m\nActive: %d\nReceived: %d\nRate: %s%%",
            $data['active_subscriptions'] ?? 0,
            $data['messages_received'] ?? 0,
            number_format($data['success_rate'] ?? 0, 1)
        );
    }

    private function formatValidationMetrics(): string
    {
        $data = $this->metrics['validation']->toArray();
        return sprintf(
            "\033[1;34mValidation\033[0m\nTotal: %d\nFailed: %d\nRate: %s%%",
            $data['total_validations'] ?? 0,
            $data['failed_validations'] ?? 0,
            number_format($data['success_rate'] ?? 0, 1)
        );
    }

    private function formatSystemHealth(): string
    {
        $cpu = $this->getCpuUsage();
        $memory = $this->getMemoryUsage();
        return sprintf(
            "\033[1;37mSystem Health\033[0m\nCPU: %s%%\nMemory: %sMB\nStatus: %s",
            number_format($cpu, 1),
            number_format($memory, 1),
            $this->getHealthStatus()
        );
    }

    private function generateMiniGraph(string $title, array $data, int $width): string
    {
        if (empty($data)) {
            return sprintf("%-{$width}s", "{$title}: No data");
        }

        $max = max($data);
        $min = min($data);
        $range = $max - $min ?: 1;

        $graph = '';
        $displayWidth = $width - strlen($title) - 3;

        for ($i = 0; $i < min(count($data), $displayWidth); ++$i) {
            $value = $data[$i];
            $normalized = ($value - $min) / $range;

            if ($normalized > 0.8) {
                $graph .= '█';
            } elseif ($normalized > 0.6) {
                $graph .= '▇';
            } elseif ($normalized > 0.4) {
                $graph .= '▅';
            } elseif ($normalized > 0.2) {
                $graph .= '▃';
            } else {
                $graph .= '▁';
            }
        }

        return sprintf('%s: %s', $title, $graph);
    }

    private function generateMiniBar(int $value, int $maxValue, int $width): string
    {
        $filled = (int) (($value / $maxValue) * $width);
        return str_repeat('█', $filled) . str_repeat('░', $width - $filled);
    }

    private function colorizePercentage(float $percentage): string
    {
        if ($percentage > 80) {
            return "\033[1;31m" . number_format($percentage, 1) . "\033[0m"; // Red
        }
        if ($percentage > 60) {
            return "\033[1;33m" . number_format($percentage, 1) . "\033[0m"; // Yellow
        }
        return "\033[1;32m" . number_format($percentage, 1) . "\033[0m"; // Green
    }

    private function getCpuUsage(): float
    {
        // Simulate CPU usage - in production, use system calls
        return rand(5, 85) / 10.0;
    }

    private function getMemoryUsage(): float
    {
        return memory_get_usage(true) / 1024 / 1024;
    }

    private function getSystemLoad(): string
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return sprintf('%.2f', $load[0]);
        }
        return 'N/A';
    }

    private function getUptime(): string
    {
        // Simple uptime simulation
        static $startTime = null;
        if ($startTime === null) {
            $startTime = time();
        }

        $uptime = time() - $startTime;
        $hours = floor($uptime / 3600);
        $minutes = floor(($uptime % 3600) / 60);
        $seconds = $uptime % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    private function getHealthStatus(): string
    {
        $cpu = $this->getCpuUsage();
        $memory = $this->getMemoryUsage();

        if ($cpu > 80 || $memory > 1000) {
            return "\033[1;31mCritical\033[0m";
        }
        if ($cpu > 60 || $memory > 500) {
            return "\033[1;33mWarning\033[0m";
        }
        return "\033[1;32mHealthy\033[0m";
    }
}
