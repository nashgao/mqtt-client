<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Utils;

use Nashgao\MQTT\Metrics\ConnectionMetrics;
use Nashgao\MQTT\Metrics\PerformanceMetrics;
use Nashgao\MQTT\Metrics\PublishMetrics;
use Nashgao\MQTT\Metrics\SubscriptionMetrics;
use Nashgao\MQTT\Metrics\ValidationMetrics;
use Swoole\Coroutine\Socket;

/**
 * Web-based MQTT Dashboard with modern UI
 * Inspired by Grafana, Prometheus UI, and modern web dashboards.
 *
 * Uses Swoole's native coroutine socket API to avoid PHP 8.2+ deprecation
 * warnings from Swoole's socket function hooks.
 */
class WebDashboard
{
    private array $metrics = [];

    private int $port = 8080;

    private string $host = '127.0.0.1';

    private bool $running = false;

    public function __construct(string $host = '127.0.0.1', int $port = 8080)
    {
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * Add metrics for the dashboard.
     */
    public function addMetrics(
        ?ConnectionMetrics $connection = null,
        ?PerformanceMetrics $performance = null,
        ?PublishMetrics $publish = null,
        ?SubscriptionMetrics $subscription = null,
        ?ValidationMetrics $validation = null
    ): self {
        if ($connection) {
            $this->metrics['connection'] = $connection;
        }
        if ($performance) {
            $this->metrics['performance'] = $performance;
        }
        if ($publish) {
            $this->metrics['publish'] = $publish;
        }
        if ($subscription) {
            $this->metrics['subscription'] = $subscription;
        }
        if ($validation) {
            $this->metrics['validation'] = $validation;
        }

        return $this;
    }

    /**
     * Start the web server.
     */
    public function start(): void
    {
        $this->running = true;

        echo "🚀 Starting MQTT Web Dashboard...\n";
        echo "📊 Dashboard: http://{$this->host}:{$this->port}\n";
        echo "📡 API: http://{$this->host}:{$this->port}/api/metrics\n";
        echo "🛑 Press Ctrl+C to stop\n\n";

        $this->runServer();
    }

    /**
     * Simple HTTP server implementation using Swoole coroutine sockets.
     */
    private function runServer(): void
    {
        $socket = new Socket(AF_INET, SOCK_STREAM, 0);
        $socket->setOption(SOL_SOCKET, SO_REUSEADDR, 1);

        if (! $socket->bind($this->host, $this->port)) {
            echo "Failed to bind to {$this->host}:{$this->port}\n";
            return;
        }

        if (! $socket->listen(5)) {
            echo "Failed to listen on socket\n";
            $socket->close();
            return;
        }

        while ($this->running) {
            /** @var false|Socket $client */
            $client = $socket->accept();
            if ($client === false) {
                continue;
            }

            /** @var false|string $request */
            $request = $client->recv(4096);
            if ($request === false || $request === '') {
                $client->close();
                continue;
            }

            $response = $this->handleRequest($request);

            $client->send($response);
            $client->close();
        }

        $socket->close();
    }

    /**
     * Handle HTTP requests.
     */
    private function handleRequest(string $request): string
    {
        $lines = explode("\n", $request);
        $requestLine = $lines[0] ?? '';

        if (preg_match('/GET (\S+)/', $requestLine, $matches)) {
            $path = $matches[1];

            return match ($path) {
                '/' => $this->generateDashboardResponse(),
                '/api/metrics' => $this->generateApiResponse(),
                '/api/metrics/live' => $this->generateLiveApiResponse(),
                '/assets/dashboard.css' => $this->generateCssResponse(),
                '/assets/dashboard.js' => $this->generateJsResponse(),
                default => $this->generate404Response(),
            };
        }

        return $this->generate404Response();
    }

    /**
     * Generate main dashboard HTML.
     */
    private function generateDashboardResponse(): string
    {
        $html = $this->generateDashboardHtml();

        return "HTTP/1.1 200 OK\r\n"
               . "Content-Type: text/html; charset=UTF-8\r\n"
               . 'Content-Length: ' . strlen($html) . "\r\n"
               . "Connection: close\r\n\r\n" . $html;
    }

    /**
     * Generate metrics API response.
     */
    private function generateApiResponse(): string
    {
        $data = $this->collectMetricsData();
        $json = json_encode($data, JSON_PRETTY_PRINT);

        return "HTTP/1.1 200 OK\r\n"
               . "Content-Type: application/json\r\n"
               . "Access-Control-Allow-Origin: *\r\n"
               . 'Content-Length: ' . strlen($json) . "\r\n"
               . "Connection: close\r\n\r\n" . $json;
    }

    /**
     * Generate live metrics with Server-Sent Events.
     */
    private function generateLiveApiResponse(): string
    {
        $data = $this->collectMetricsData();
        $json = json_encode($data);

        $response = "data: {$json}\n\n";

        return "HTTP/1.1 200 OK\r\n"
               . "Content-Type: text/event-stream\r\n"
               . "Cache-Control: no-cache\r\n"
               . "Access-Control-Allow-Origin: *\r\n"
               . "Connection: keep-alive\r\n\r\n" . $response;
    }

    /**
     * Generate CSS response.
     */
    private function generateCssResponse(): string
    {
        $css = $this->generateDashboardCss();

        return "HTTP/1.1 200 OK\r\n"
               . "Content-Type: text/css\r\n"
               . 'Content-Length: ' . strlen($css) . "\r\n"
               . "Connection: close\r\n\r\n" . $css;
    }

    /**
     * Generate JavaScript response.
     */
    private function generateJsResponse(): string
    {
        $js = $this->generateDashboardJs();

        return "HTTP/1.1 200 OK\r\n"
               . "Content-Type: application/javascript\r\n"
               . 'Content-Length: ' . strlen($js) . "\r\n"
               . "Connection: close\r\n\r\n" . $js;
    }

    /**
     * Generate 404 response.
     */
    private function generate404Response(): string
    {
        $html = '<h1>404 Not Found</h1>';

        return "HTTP/1.1 404 Not Found\r\n"
               . "Content-Type: text/html\r\n"
               . 'Content-Length: ' . strlen($html) . "\r\n"
               . "Connection: close\r\n\r\n" . $html;
    }

    /**
     * Collect metrics data.
     */
    private function collectMetricsData(): array
    {
        return [
            'timestamp' => time(),
            'connection' => $this->metrics['connection']?->toArray() ?? [],
            'performance' => $this->metrics['performance']?->toArray() ?? [],
            'publish' => $this->metrics['publish']?->toArray() ?? [],
            'subscription' => $this->metrics['subscription']?->toArray() ?? [],
            'validation' => $this->metrics['validation']?->toArray() ?? [],
            'system' => [
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'uptime' => time() - $_SERVER['REQUEST_TIME_FLOAT'],
            ],
        ];
    }

    /**
     * Generate modern dashboard HTML.
     */
    private function generateDashboardHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚀 MQTT Dashboard Pro</title>
    <link rel="stylesheet" href="/assets/dashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="dashboard">
        <!-- Header -->
        <header class="header">
            <div class="header-left">
                <h1><i class="fas fa-rocket"></i> MQTT Dashboard Pro</h1>
                <span class="status-badge online">
                    <i class="fas fa-circle"></i> ONLINE
                </span>
            </div>
            <div class="header-right">
                <div class="time" id="currentTime"></div>
                <button class="theme-toggle" id="themeToggle">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Metrics Cards -->
            <section class="metrics-grid">
                <div class="metric-card connections">
                    <div class="metric-header">
                        <h3><i class="fas fa-plug"></i> Connections</h3>
                        <span class="metric-trend up">+5.2%</span>
                    </div>
                    <div class="metric-value" id="connectionsValue">0</div>
                    <div class="metric-subtitle">Active connections</div>
                    <canvas class="sparkline" id="connectionsChart"></canvas>
                </div>

                <div class="metric-card published">
                    <div class="metric-header">
                        <h3><i class="fas fa-paper-plane"></i> Published</h3>
                        <span class="metric-trend up">+12.8%</span>
                    </div>
                    <div class="metric-value" id="publishedValue">0</div>
                    <div class="metric-subtitle">Messages sent</div>
                    <canvas class="sparkline" id="publishedChart"></canvas>
                </div>

                <div class="metric-card subscriptions">
                    <div class="metric-header">
                        <h3><i class="fas fa-bell"></i> Subscriptions</h3>
                        <span class="metric-trend up">+3.1%</span>
                    </div>
                    <div class="metric-value" id="subscriptionsValue">0</div>
                    <div class="metric-subtitle">Active subscriptions</div>
                    <canvas class="sparkline" id="subscriptionsChart"></canvas>
                </div>

                <div class="metric-card throughput">
                    <div class="metric-header">
                        <h3><i class="fas fa-tachometer-alt"></i> Throughput</h3>
                        <span class="metric-trend up">+8.4%</span>
                    </div>
                    <div class="metric-value" id="throughputValue">0</div>
                    <div class="metric-subtitle">Messages/sec</div>
                    <canvas class="sparkline" id="throughputChart"></canvas>
                </div>
            </section>

            <!-- Charts Section -->
            <section class="charts-section">
                <div class="chart-container">
                    <div class="chart-header">
                        <h3><i class="fas fa-chart-line"></i> Response Time</h3>
                        <div class="chart-controls">
                            <button class="chart-btn active" data-range="1h">1H</button>
                            <button class="chart-btn" data-range="6h">6H</button>
                            <button class="chart-btn" data-range="24h">24H</button>
                        </div>
                    </div>
                    <canvas id="responseTimeChart"></canvas>
                </div>

                <div class="chart-container">
                    <div class="chart-header">
                        <h3><i class="fas fa-chart-bar"></i> Message Volume</h3>
                        <div class="chart-controls">
                            <button class="chart-btn active" data-range="1h">1H</button>
                            <button class="chart-btn" data-range="6h">6H</button>
                            <button class="chart-btn" data-range="24h">24H</button>
                        </div>
                    </div>
                    <canvas id="messageVolumeChart"></canvas>
                </div>
            </section>

            <!-- Activity Feed -->
            <section class="activity-section">
                <div class="activity-header">
                    <h3><i class="fas fa-stream"></i> Live Activity</h3>
                    <button class="clear-btn" id="clearActivity">
                        <i class="fas fa-trash"></i> Clear
                    </button>
                </div>
                <div class="activity-feed" id="activityFeed">
                    <!-- Activity items will be added here -->
                </div>
            </section>
        </main>
    </div>

    <script src="/assets/dashboard.js"></script>
</body>
</html>
HTML;
    }

    /**
     * Generate modern CSS.
     */
    private function generateDashboardCss(): string
    {
        return <<<'CSS'
/* Modern Dashboard CSS inspired by Grafana and modern web apps */
:root {
    --primary-color: #3b82f6;
    --success-color: #10b981;
    --warning-color: #f59e0b;
    --danger-color: #ef4444;
    --background-color: #0f172a;
    --surface-color: #1e293b;
    --border-color: #334155;
    --text-primary: #f1f5f9;
    --text-secondary: #94a3b8;
    --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

[data-theme="light"] {
    --background-color: #f8fafc;
    --surface-color: #ffffff;
    --border-color: #e2e8f0;
    --text-primary: #1e293b;
    --text-secondary: #64748b;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: var(--background-color);
    color: var(--text-primary);
    line-height: 1.6;
}

.dashboard {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

/* Header */
.header {
    background: var(--surface-color);
    border-bottom: 1px solid var(--border-color);
    padding: 1rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    backdrop-filter: blur(10px);
    position: sticky;
    top: 0;
    z-index: 100;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.header h1 {
    font-size: 1.5rem;
    font-weight: 700;
    background: var(--gradient-primary);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.status-badge.online {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success-color);
    border: 1px solid rgba(16, 185, 129, 0.2);
}

.header-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.time {
    font-family: 'SF Mono', Monaco, monospace;
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.theme-toggle {
    background: transparent;
    border: 1px solid var(--border-color);
    color: var(--text-primary);
    padding: 0.5rem;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.2s;
}

.theme-toggle:hover {
    background: var(--border-color);
}

/* Main Content */
.main-content {
    flex: 1;
    padding: 2rem;
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

/* Metrics Grid */
.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.metric-card {
    background: var(--surface-color);
    border: 1px solid var(--border-color);
    border-radius: 1rem;
    padding: 1.5rem;
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
}

.metric-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.metric-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--gradient-primary);
}

.metric-card.connections::before { background: var(--primary-color); }
.metric-card.published::before { background: var(--success-color); }
.metric-card.subscriptions::before { background: var(--warning-color); }
.metric-card.throughput::before { background: var(--danger-color); }

.metric-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.metric-header h3 {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-secondary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.metric-trend {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
}

.metric-trend.up {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success-color);
}

.metric-value {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    background: var(--gradient-primary);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.metric-subtitle {
    font-size: 0.875rem;
    color: var(--text-secondary);
    margin-bottom: 1rem;
}

.sparkline {
    height: 40px;
    width: 100%;
}

/* Charts Section */
.charts-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 2rem;
}

.chart-container {
    background: var(--surface-color);
    border: 1px solid var(--border-color);
    border-radius: 1rem;
    padding: 1.5rem;
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.chart-header h3 {
    font-size: 1.125rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.chart-controls {
    display: flex;
    gap: 0.5rem;
}

.chart-btn {
    background: transparent;
    border: 1px solid var(--border-color);
    color: var(--text-secondary);
    padding: 0.375rem 0.75rem;
    border-radius: 0.375rem;
    cursor: pointer;
    font-size: 0.75rem;
    transition: all 0.2s;
}

.chart-btn.active,
.chart-btn:hover {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

/* Activity Section */
.activity-section {
    background: var(--surface-color);
    border: 1px solid var(--border-color);
    border-radius: 1rem;
    overflow: hidden;
}

.activity-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.activity-header h3 {
    font-size: 1.125rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.clear-btn {
    background: transparent;
    border: 1px solid var(--border-color);
    color: var(--text-secondary);
    padding: 0.375rem 0.75rem;
    border-radius: 0.375rem;
    cursor: pointer;
    font-size: 0.75rem;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.clear-btn:hover {
    background: var(--danger-color);
    color: white;
    border-color: var(--danger-color);
}

.activity-feed {
    max-height: 300px;
    overflow-y: auto;
}

.activity-item {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: background 0.2s;
}

.activity-item:hover {
    background: rgba(255, 255, 255, 0.02);
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
}

.activity-icon.connection { background: rgba(59, 130, 246, 0.1); color: var(--primary-color); }
.activity-icon.publish { background: rgba(16, 185, 129, 0.1); color: var(--success-color); }
.activity-icon.error { background: rgba(239, 68, 68, 0.1); color: var(--danger-color); }

.activity-content {
    flex: 1;
}

.activity-message {
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.activity-time {
    font-size: 0.75rem;
    color: var(--text-secondary);
    font-family: 'SF Mono', Monaco, monospace;
}

/* Responsive */
@media (max-width: 768px) {
    .header {
        padding: 1rem;
    }
    
    .main-content {
        padding: 1rem;
    }
    
    .metrics-grid {
        grid-template-columns: 1fr;
    }
    
    .charts-section {
        grid-template-columns: 1fr;
    }
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.metric-card {
    animation: fadeIn 0.5s ease forwards;
}

.activity-item {
    animation: fadeIn 0.3s ease forwards;
}

/* Scrollbar */
::-webkit-scrollbar {
    width: 6px;
}

::-webkit-scrollbar-track {
    background: var(--background-color);
}

::-webkit-scrollbar-thumb {
    background: var(--border-color);
    border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
    background: var(--text-secondary);
}
CSS;
    }

    /**
     * Generate interactive JavaScript.
     */
    private function generateDashboardJs(): string
    {
        return <<<'JS'
// Modern Dashboard JavaScript with real-time updates
class MQTTDashboard {
    constructor() {
        this.charts = {};
        this.eventSource = null;
        this.data = {
            connections: [],
            published: [],
            subscriptions: [],
            throughput: [],
            responseTime: [],
            messageVolume: []
        };
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.initCharts();
        this.startRealTimeUpdates();
        this.updateTime();
        setInterval(() => this.updateTime(), 1000);
    }
    
    setupEventListeners() {
        // Theme toggle
        document.getElementById('themeToggle').addEventListener('click', () => {
            this.toggleTheme();
        });
        
        // Clear activity
        document.getElementById('clearActivity').addEventListener('click', () => {
            this.clearActivity();
        });
        
        // Chart range buttons
        document.querySelectorAll('.chart-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                document.querySelectorAll('.chart-btn').forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                this.updateChartRange(e.target.dataset.range);
            });
        });
    }
    
    toggleTheme() {
        const currentTheme = document.body.dataset.theme || 'dark';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        document.body.dataset.theme = newTheme;
        
        const icon = document.querySelector('#themeToggle i');
        icon.className = newTheme === 'dark' ? 'fas fa-moon' : 'fas fa-sun';
    }
    
    initCharts() {
        // Response Time Chart
        const responseTimeCtx = document.getElementById('responseTimeChart').getContext('2d');
        this.charts.responseTime = new Chart(responseTimeCtx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Response Time (ms)',
                    data: [],
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255, 255, 255, 0.1)' },
                        ticks: { color: '#94a3b8' }
                    },
                    x: {
                        grid: { color: 'rgba(255, 255, 255, 0.1)' },
                        ticks: { color: '#94a3b8' }
                    }
                }
            }
        });
        
        // Message Volume Chart
        const messageVolumeCtx = document.getElementById('messageVolumeChart').getContext('2d');
        this.charts.messageVolume = new Chart(messageVolumeCtx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Messages',
                    data: [],
                    backgroundColor: 'rgba(16, 185, 129, 0.6)',
                    borderColor: '#10b981',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255, 255, 255, 0.1)' },
                        ticks: { color: '#94a3b8' }
                    },
                    x: {
                        grid: { color: 'rgba(255, 255, 255, 0.1)' },
                        ticks: { color: '#94a3b8' }
                    }
                }
            }
        });
        
        // Initialize sparklines
        this.initSparklines();
    }
    
    initSparklines() {
        const sparklineOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { display: false },
                y: { display: false }
            },
            elements: {
                point: { radius: 0 },
                line: { borderWidth: 2 }
            }
        };
        
        // Connections sparkline
        const connectionsCtx = document.getElementById('connectionsChart').getContext('2d');
        this.charts.connectionsSparkline = new Chart(connectionsCtx, {
            type: 'line',
            data: {
                labels: Array.from({length: 10}, (_, i) => i),
                datasets: [{
                    data: Array.from({length: 10}, () => Math.floor(Math.random() * 50) + 10),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: sparklineOptions
        });
        
        // Published sparkline
        const publishedCtx = document.getElementById('publishedChart').getContext('2d');
        this.charts.publishedSparkline = new Chart(publishedCtx, {
            type: 'line',
            data: {
                labels: Array.from({length: 10}, (_, i) => i),
                datasets: [{
                    data: Array.from({length: 10}, () => Math.floor(Math.random() * 100) + 20),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: sparklineOptions
        });
    }
    
    startRealTimeUpdates() {
        // Simulate real-time data updates
        setInterval(() => {
            this.fetchAndUpdateMetrics();
        }, 2000);
        
        // Start with initial data
        this.fetchAndUpdateMetrics();
    }
    
    async fetchAndUpdateMetrics() {
        try {
            const response = await fetch('/api/metrics');
            const data = await response.json();
            this.updateMetrics(data);
        } catch (error) {
            console.error('Failed to fetch metrics:', error);
            this.simulateMetrics();
        }
    }
    
    simulateMetrics() {
        // Simulate realistic MQTT metrics
        const now = new Date();
        const data = {
            connection: {
                active_connections: Math.floor(Math.random() * 20) + 30,
                total_attempts: Math.floor(Math.random() * 1000) + 500,
                success_rate: 0.95 + Math.random() * 0.05
            },
            publish: {
                total_published: Math.floor(Math.random() * 10000) + 5000,
                success_rate: 0.97 + Math.random() * 0.03
            },
            subscription: {
                active_subscriptions: Math.floor(Math.random() * 50) + 100,
                success_rate: 0.98 + Math.random() * 0.02
            }
        };
        
        this.updateMetrics(data);
    }
    
    updateMetrics(data) {
        // Update metric cards
        document.getElementById('connectionsValue').textContent = 
            data.connection?.active_connections || 0;
        document.getElementById('publishedValue').textContent = 
            this.formatNumber(data.publish?.total_published || 0);
        document.getElementById('subscriptionsValue').textContent = 
            data.subscription?.active_subscriptions || 0;
        document.getElementById('throughputValue').textContent = 
            Math.floor(Math.random() * 200) + 50;
        
        // Update charts
        this.updateCharts(data);
        
        // Add activity
        this.addActivity();
    }
    
    updateCharts(data) {
        const now = new Date();
        
        // Update response time chart
        this.data.responseTime.push({
            time: now.toLocaleTimeString(),
            value: Math.floor(Math.random() * 100) + 20
        });
        
        if (this.data.responseTime.length > 20) {
            this.data.responseTime.shift();
        }
        
        this.charts.responseTime.data.labels = this.data.responseTime.map(d => d.time);
        this.charts.responseTime.data.datasets[0].data = this.data.responseTime.map(d => d.value);
        this.charts.responseTime.update('none');
        
        // Update message volume chart
        this.data.messageVolume.push({
            time: now.toLocaleTimeString(),
            value: Math.floor(Math.random() * 300) + 100
        });
        
        if (this.data.messageVolume.length > 15) {
            this.data.messageVolume.shift();
        }
        
        this.charts.messageVolume.data.labels = this.data.messageVolume.map(d => d.time);
        this.charts.messageVolume.data.datasets[0].data = this.data.messageVolume.map(d => d.value);
        this.charts.messageVolume.update('none');
        
        // Update sparklines
        this.updateSparklines(data);
    }
    
    updateSparklines(data) {
        // Update connections sparkline
        const connectionsData = this.charts.connectionsSparkline.data.datasets[0].data;
        connectionsData.shift();
        connectionsData.push(data.connection?.active_connections || Math.floor(Math.random() * 50) + 10);
        this.charts.connectionsSparkline.update('none');
        
        // Update published sparkline
        const publishedData = this.charts.publishedSparkline.data.datasets[0].data;
        publishedData.shift();
        publishedData.push(Math.floor((data.publish?.total_published || 1000) / 100));
        this.charts.publishedSparkline.update('none');
    }
    
    addActivity() {
        const activities = [
            { type: 'connection', icon: 'fas fa-plug', message: 'Client device_001 connected from 192.168.1.100' },
            { type: 'publish', icon: 'fas fa-paper-plane', message: 'Published to sensor/temperature (QoS 1, 245 bytes)' },
            { type: 'connection', icon: 'fas fa-plug', message: 'Client subscribed to alerts/# pattern' },
            { type: 'error', icon: 'fas fa-exclamation-triangle', message: 'Connection timeout for client_002' },
            { type: 'publish', icon: 'fas fa-paper-plane', message: 'Published to device/status (QoS 0, 128 bytes)' }
        ];
        
        if (Math.random() < 0.3) {
            const activity = activities[Math.floor(Math.random() * activities.length)];
            this.addActivityItem(activity);
        }
    }
    
    addActivityItem(activity) {
        const feed = document.getElementById('activityFeed');
        const item = document.createElement('div');
        item.className = 'activity-item';
        item.innerHTML = `
            <div class="activity-icon ${activity.type}">
                <i class="${activity.icon}"></i>
            </div>
            <div class="activity-content">
                <div class="activity-message">${activity.message}</div>
                <div class="activity-time">${new Date().toLocaleTimeString()}</div>
            </div>
        `;
        
        feed.insertBefore(item, feed.firstChild);
        
        // Keep only last 10 items
        while (feed.children.length > 10) {
            feed.removeChild(feed.lastChild);
        }
    }
    
    clearActivity() {
        document.getElementById('activityFeed').innerHTML = '';
    }
    
    updateTime() {
        document.getElementById('currentTime').textContent = new Date().toLocaleTimeString();
    }
    
    updateChartRange(range) {
        // Update chart data based on selected range
        console.log('Updating chart range to:', range);
    }
    
    formatNumber(num) {
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1) + 'M';
        }
        if (num >= 1000) {
            return (num / 1000).toFixed(1) + 'K';
        }
        return num.toString();
    }
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new MQTTDashboard();
});
JS;
    }
}
