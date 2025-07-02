<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Utils;

use Nashgao\MQTT\Metrics\ConnectionMetrics;
use Nashgao\MQTT\Metrics\PerformanceMetrics;
use Nashgao\MQTT\Metrics\PublishMetrics;
use Nashgao\MQTT\Metrics\SubscriptionMetrics;
use Nashgao\MQTT\Metrics\ValidationMetrics;

/**
 * Metrics visualization utility for MQTT operations.
 * Provides various output formats including ASCII charts, JSON, and CSV.
 */
class MetricsVisualizer
{
    private array $metrics = [];

    public function __construct()
    {
        $this->metrics = [
            'connection' => null,
            'performance' => null,
            'publish' => null,
            'subscription' => null,
            'validation' => null,
        ];
    }

    /**
     * Set metrics instances for visualization.
     */
    public function setConnectionMetrics(ConnectionMetrics $metrics): self
    {
        $this->metrics['connection'] = $metrics;
        return $this;
    }

    public function setPerformanceMetrics(PerformanceMetrics $metrics): self
    {
        $this->metrics['performance'] = $metrics;
        return $this;
    }

    public function setPublishMetrics(PublishMetrics $metrics): self
    {
        $this->metrics['publish'] = $metrics;
        return $this;
    }

    public function setSubscriptionMetrics(SubscriptionMetrics $metrics): self
    {
        $this->metrics['subscription'] = $metrics;
        return $this;
    }

    public function setValidationMetrics(ValidationMetrics $metrics): self
    {
        $this->metrics['validation'] = $metrics;
        return $this;
    }

    /**
     * Generate ASCII dashboard view of all metrics.
     */
    public function generateDashboard(): string
    {
        $output = [];
        $output[] = $this->generateHeader();
        $output[] = $this->generateConnectionSection();
        $output[] = $this->generatePerformanceSection();
        $output[] = $this->generatePublishSection();
        $output[] = $this->generateSubscriptionSection();
        $output[] = $this->generateValidationSection();
        $output[] = $this->generateFooter();

        return implode("\n", $output);
    }

    /**
     * Generate real-time metrics display (similar to htop).
     */
    public function generateRealTimeDisplay(): string
    {
        $output = [];

        // Header with timestamp
        $output[] = "\033[2J\033[H"; // Clear screen and move cursor to top
        $output[] = sprintf(
            "\033[1;37mMQTT Metrics Monitor\033[0m - %s",
            date('Y-m-d H:i:s')
        );
        $output[] = str_repeat('=', 80);

        // System overview
        $output[] = $this->generateSystemOverview();
        $output[] = '';

        // Real-time metrics in columns
        $output[] = $this->generateRealTimeColumns();

        // Performance graphs
        $output[] = '';
        $output[] = $this->generatePerformanceGraphs();

        // Status bar
        $output[] = '';
        $output[] = str_repeat('-', 80);
        $output[] = "\033[1;32mPress Ctrl+C to exit\033[0m";

        return implode("\n", $output);
    }

    /**
     * Generate metrics as JSON.
     */
    public function generateJson(): string
    {
        $data = [
            'timestamp' => date('c'),
            'connection' => $this->getConnectionData(),
            'performance' => $this->getPerformanceData(),
            'publish' => $this->getPublishData(),
            'subscription' => $this->getSubscriptionData(),
            'validation' => $this->getValidationData(),
        ];

        return json_encode($data, JSON_PRETTY_PRINT);
    }

    /**
     * Generate metrics as CSV.
     */
    public function generateCsv(): string
    {
        $headers = [
            'timestamp', 'metric_type', 'metric_name', 'value', 'unit',
        ];

        $rows = [];
        $rows[] = implode(',', $headers);

        $timestamp = date('c');

        // Add all metrics data
        foreach ($this->getAllMetricsData() as $type => $metrics) {
            foreach ($metrics as $name => $value) {
                // Skip arrays and objects - only include scalar values
                if (is_scalar($value)) {
                    $unit = $this->getMetricUnit($type, $name);
                    $rows[] = sprintf(
                        '%s,%s,%s,%s,%s',
                        $timestamp,
                        $type,
                        $name,
                        $value,
                        $unit
                    );
                }
            }
        }

        return implode("\n", $rows);
    }

    /**
     * Generate horizontal bar chart for a metric.
     */
    public function generateBarChart(string $title, array $data, int $width = 50): string
    {
        $output = [];
        $output[] = "\033[1;37m{$title}\033[0m";
        $output[] = str_repeat('-', $width + 20);

        if (empty($data)) {
            $output[] = 'No data available';
            return implode("\n", $output);
        }

        $maxValue = max($data);
        if ($maxValue == 0) {
            $maxValue = 1; // Prevent division by zero
        }

        foreach ($data as $label => $value) {
            $barLength = (int) (($value / $maxValue) * $width);
            $bar = str_repeat('█', $barLength) . str_repeat('░', $width - $barLength);
            $percentage = $maxValue > 0 ? ($value / $maxValue) * 100 : 0;

            $output[] = sprintf(
                '%-15s |%s| %6.1f%% (%s)',
                substr($label, 0, 15),
                $bar,
                $percentage,
                $this->formatNumber($value)
            );
        }

        return implode("\n", $output);
    }

    /**
     * Generate line graph for time-series data.
     */
    public function generateLineGraph(string $title, array $dataPoints, int $width = 60, int $height = 10): string
    {
        $output = [];
        $output[] = "\033[1;37m{$title}\033[0m";

        if (empty($dataPoints)) {
            $output[] = 'No data available';
            return implode("\n", $output);
        }

        $minValue = min($dataPoints);
        $maxValue = max($dataPoints);
        $range = $maxValue - $minValue;

        if ($range == 0) {
            $range = 1; // Prevent division by zero
        }

        // Create graph
        for ($y = $height - 1; $y >= 0; --$y) {
            $line = sprintf('%6.1f |', $minValue + ($range * $y / ($height - 1)));

            for ($x = 0; $x < min(count($dataPoints), $width); ++$x) {
                $value = $dataPoints[$x];
                $normalizedValue = ($value - $minValue) / $range;
                $pixelHeight = (int) ($normalizedValue * ($height - 1));

                if ($pixelHeight == $y) {
                    $line .= '●';
                } elseif ($pixelHeight > $y) {
                    $line .= '│';
                } else {
                    $line .= ' ';
                }
            }

            $output[] = $line;
        }

        // X-axis
        $output[] = str_repeat(' ', 8) . str_repeat('-', min(count($dataPoints), $width));

        return implode("\n", $output);
    }

    private function generateHeader(): string
    {
        $timestamp = date('Y-m-d H:i:s T');
        return <<<EOF
╭─────────────────────────────────────────────────────────────────────────────╮
│                           MQTT Metrics Dashboard                            │
│                              {$timestamp}                             │
╰─────────────────────────────────────────────────────────────────────────────╯
EOF;
    }

    private function generateConnectionSection(): string
    {
        $data = $this->getConnectionData();

        return <<<EOF

┌─ Connection Metrics ─────────────────────────────────────────────────────────┐
│ Active Connections: {$this->formatNumber($data['active_connections'] ?? 0, 8)}                                    │
│ Total Connections:  {$this->formatNumber($data['total_connections'] ?? 0, 8)}                                    │
│ Failed Connections: {$this->formatNumber($data['failed_connections'] ?? 0, 8)}                                    │
│ Success Rate:       {$this->formatPercentage($data['success_rate'] ?? 0, 8)}                                    │
└─────────────────────────────────────────────────────────────────────────────┘
EOF;
    }

    private function generatePerformanceSection(): string
    {
        $data = $this->getPerformanceData();

        return <<<EOF

┌─ Performance Metrics ────────────────────────────────────────────────────────┐
│ Avg Response Time:  {$this->formatTime($data['avg_response_time'] ?? 0, 8)}                                    │
│ Max Response Time:  {$this->formatTime($data['max_response_time'] ?? 0, 8)}                                    │
│ Min Response Time:  {$this->formatTime($data['min_response_time'] ?? 0, 8)}                                    │
│ Throughput:         {$this->formatNumber($data['throughput'] ?? 0, 8)} ops/sec                              │
└─────────────────────────────────────────────────────────────────────────────┘
EOF;
    }

    private function generatePublishSection(): string
    {
        $data = $this->getPublishData();

        return <<<EOF

┌─ Publish Metrics ────────────────────────────────────────────────────────────┐
│ Total Published:    {$this->formatNumber($data['total_published'] ?? 0, 8)}                                    │
│ Failed Publishes:   {$this->formatNumber($data['failed_publishes'] ?? 0, 8)}                                    │
│ Success Rate:       {$this->formatPercentage($data['success_rate'] ?? 0, 8)}                                    │
│ Avg Message Size:   {$this->formatBytes($data['avg_message_size'] ?? 0, 8)}                                    │
└─────────────────────────────────────────────────────────────────────────────┘
EOF;
    }

    private function generateSubscriptionSection(): string
    {
        $data = $this->getSubscriptionData();

        return <<<EOF

┌─ Subscription Metrics ───────────────────────────────────────────────────────┐
│ Active Subscriptions: {$this->formatNumber($data['active_subscriptions'] ?? 0, 6)}                                  │
│ Total Subscriptions:  {$this->formatNumber($data['total_subscriptions'] ?? 0, 6)}                                  │
│ Messages Received:    {$this->formatNumber($data['messages_received'] ?? 0, 6)}                                  │
│ Subscription Rate:    {$this->formatPercentage($data['success_rate'] ?? 0, 6)}                                  │
└─────────────────────────────────────────────────────────────────────────────┘
EOF;
    }

    private function generateValidationSection(): string
    {
        $data = $this->getValidationData();

        return <<<EOF

┌─ Validation Metrics ─────────────────────────────────────────────────────────┐
│ Total Validations:  {$this->formatNumber($data['total_validations'] ?? 0, 8)}                                    │
│ Failed Validations: {$this->formatNumber($data['failed_validations'] ?? 0, 8)}                                    │
│ Success Rate:       {$this->formatPercentage($data['success_rate'] ?? 0, 8)}                                    │
│ Avg Validation Time: {$this->formatTime($data['avg_validation_time'] ?? 0, 6)}                                    │
└─────────────────────────────────────────────────────────────────────────────┘
EOF;
    }

    private function generateFooter(): string
    {
        return <<<'EOF'

╭─────────────────────────────────────────────────────────────────────────────╮
│ Legend: ops/sec = Operations per second, ms = Milliseconds, KB/MB = Kilobytes/Megabytes │
╰─────────────────────────────────────────────────────────────────────────────╯
EOF;
    }

    private function generateSystemOverview(): string
    {
        $connectionData = $this->getConnectionData();
        $performanceData = $this->getPerformanceData();

        $cpuUsage = $this->getCpuUsage();
        $memoryUsage = $this->getMemoryUsage();

        return sprintf(
            'System: CPU %s%% | Memory %s%% | Connections %d | Avg Response %sms',
            number_format($cpuUsage, 1),
            number_format($memoryUsage, 1),
            $connectionData['active_connections'] ?? 0,
            number_format($performanceData['avg_response_time'] ?? 0, 2)
        );
    }

    private function generateRealTimeColumns(): string
    {
        $output = [];

        // Create 3 columns
        $col1 = $this->generateConnectionMetricsColumn();
        $col2 = $this->generatePerformanceMetricsColumn();
        $col3 = $this->generateOperationsMetricsColumn();

        // Combine columns
        $col1Lines = explode("\n", $col1);
        $col2Lines = explode("\n", $col2);
        $col3Lines = explode("\n", $col3);

        $maxLines = max(count($col1Lines), count($col2Lines), count($col3Lines));

        for ($i = 0; $i < $maxLines; ++$i) {
            $line1 = $col1Lines[$i] ?? str_repeat(' ', 25);
            $line2 = $col2Lines[$i] ?? str_repeat(' ', 25);
            $line3 = $col3Lines[$i] ?? str_repeat(' ', 25);

            $output[] = sprintf('%-25s %-25s %-25s', $line1, $line2, $line3);
        }

        return implode("\n", $output);
    }

    private function generateConnectionMetricsColumn(): string
    {
        $data = $this->getConnectionData();
        $active = $data['active_connections'] ?? 0;
        $total = $data['total_connections'] ?? 0;
        $failed = $data['failed_connections'] ?? 0;
        $rate = $this->formatPercentage($data['success_rate'] ?? 0);

        return <<<EOF
\033[1;36mCONNECTIONS\033[0m
Active:  {$active}
Total:   {$total}
Failed:  {$failed}
Rate:    {$rate}
EOF;
    }

    private function generatePerformanceMetricsColumn(): string
    {
        $data = $this->getPerformanceData();
        $avgRt = $this->formatTime($data['avg_response_time'] ?? 0);
        $maxRt = $this->formatTime($data['max_response_time'] ?? 0);
        $throughput = $data['throughput'] ?? 0;
        $memory = $this->formatBytes($this->getMemoryUsage() * 1024 * 1024);

        return <<<EOF
\033[1;33mPERFORMANCE\033[0m
Avg RT:  {$avgRt}ms
Max RT:  {$maxRt}ms
Throughput: {$throughput}/s
Memory:  {$memory}
EOF;
    }

    private function generateOperationsMetricsColumn(): string
    {
        $pubData = $this->getPublishData();
        $subData = $this->getSubscriptionData();
        $published = $pubData['total_published'] ?? 0;
        $received = $subData['messages_received'] ?? 0;
        $pubRate = $this->formatPercentage($pubData['success_rate'] ?? 0);
        $subRate = $this->formatPercentage($subData['success_rate'] ?? 0);

        return <<<EOF
\033[1;32mOPERATIONS\033[0m
Published: {$published}
Received:  {$received}
Pub Rate:  {$pubRate}
Sub Rate:  {$subRate}
EOF;
    }

    private function generatePerformanceGraphs(): string
    {
        // Simulate some time-series data for demonstration
        $responseTimeData = $this->getResponseTimeHistory();
        $throughputData = $this->getThroughputHistory();

        $graph1 = $this->generateLineGraph('Response Time (ms)', $responseTimeData, 35, 6);
        $graph2 = $this->generateLineGraph('Throughput (ops/s)', $throughputData, 35, 6);

        // Combine graphs side by side
        $graph1Lines = explode("\n", $graph1);
        $graph2Lines = explode("\n", $graph2);

        $output = [];
        $maxLines = max(count($graph1Lines), count($graph2Lines));

        for ($i = 0; $i < $maxLines; ++$i) {
            $line1 = $graph1Lines[$i] ?? str_repeat(' ', 40);
            $line2 = $graph2Lines[$i] ?? str_repeat(' ', 40);
            $output[] = sprintf('%-40s %s', $line1, $line2);
        }

        return implode("\n", $output);
    }

    private function getConnectionData(): array
    {
        if (! $this->metrics['connection']) {
            return [
                'active_connections' => 0,
                'total_connections' => 0,
                'failed_connections' => 0,
                'success_rate' => 0.0,
            ];
        }

        return $this->metrics['connection']->toArray();
    }

    private function getPerformanceData(): array
    {
        if (! $this->metrics['performance']) {
            return [
                'avg_response_time' => 0.0,
                'max_response_time' => 0.0,
                'min_response_time' => 0.0,
                'throughput' => 0.0,
            ];
        }

        return $this->metrics['performance']->toArray();
    }

    private function getPublishData(): array
    {
        if (! $this->metrics['publish']) {
            return [
                'total_published' => 0,
                'failed_publishes' => 0,
                'success_rate' => 0.0,
                'avg_message_size' => 0,
            ];
        }

        return $this->metrics['publish']->toArray();
    }

    private function getSubscriptionData(): array
    {
        if (! $this->metrics['subscription']) {
            return [
                'active_subscriptions' => 0,
                'total_subscriptions' => 0,
                'messages_received' => 0,
                'success_rate' => 0.0,
            ];
        }

        return $this->metrics['subscription']->toArray();
    }

    private function getValidationData(): array
    {
        if (! $this->metrics['validation']) {
            return [
                'total_validations' => 0,
                'failed_validations' => 0,
                'success_rate' => 0.0,
                'avg_validation_time' => 0.0,
            ];
        }

        return $this->metrics['validation']->toArray();
    }

    private function getAllMetricsData(): array
    {
        return [
            'connection' => $this->getConnectionData(),
            'performance' => $this->getPerformanceData(),
            'publish' => $this->getPublishData(),
            'subscription' => $this->getSubscriptionData(),
            'validation' => $this->getValidationData(),
        ];
    }

    private function getMetricUnit(string $type, string $name): string
    {
        $units = [
            'connection' => [
                'success_rate' => 'percentage',
                'default' => 'count',
            ],
            'performance' => [
                'avg_response_time' => 'ms',
                'max_response_time' => 'ms',
                'min_response_time' => 'ms',
                'throughput' => 'ops/sec',
            ],
            'publish' => [
                'success_rate' => 'percentage',
                'avg_message_size' => 'bytes',
                'default' => 'count',
            ],
            'subscription' => [
                'success_rate' => 'percentage',
                'default' => 'count',
            ],
            'validation' => [
                'success_rate' => 'percentage',
                'avg_validation_time' => 'ms',
                'default' => 'count',
            ],
        ];

        return $units[$type][$name] ?? $units[$type]['default'] ?? 'unit';
    }

    private function formatNumber(float|int $number, int $width = 0): string
    {
        $formatted = number_format($number);
        return $width > 0 ? str_pad($formatted, $width, ' ', STR_PAD_LEFT) : $formatted;
    }

    private function formatPercentage(float $percentage, int $width = 0): string
    {
        $formatted = number_format($percentage, 1) . '%';
        return $width > 0 ? str_pad($formatted, $width, ' ', STR_PAD_LEFT) : $formatted;
    }

    private function formatTime(float $milliseconds, int $width = 0): string
    {
        $formatted = number_format($milliseconds, 2);
        return $width > 0 ? str_pad($formatted, $width, ' ', STR_PAD_LEFT) : $formatted;
    }

    private function formatBytes(float|int $bytes, int $width = 0): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        $size = $bytes;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            ++$unitIndex;
        }

        $formatted = number_format($size, 1) . $units[$unitIndex];
        return $width > 0 ? str_pad($formatted, $width, ' ', STR_PAD_LEFT) : $formatted;
    }

    private function getCpuUsage(): float
    {
        // Simulate CPU usage - in real implementation, you'd use system calls
        return rand(10, 80) / 10.0;
    }

    private function getMemoryUsage(): float
    {
        $memoryBytes = memory_get_usage(true);
        return $memoryBytes / 1024 / 1024;
    }

    private function getResponseTimeHistory(): array
    {
        // Simulate response time history - in real implementation, you'd store actual data
        $data = [];
        for ($i = 0; $i < 30; ++$i) {
            $data[] = rand(10, 100) + (sin($i / 5) * 20);
        }
        return $data;
    }

    private function getThroughputHistory(): array
    {
        // Simulate throughput history
        $data = [];
        for ($i = 0; $i < 30; ++$i) {
            $data[] = rand(50, 200) + (cos($i / 3) * 30);
        }
        return $data;
    }
}
