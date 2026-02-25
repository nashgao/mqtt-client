<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Utils;

use Nashgao\MQTT\Metrics\ConnectionMetrics;
use Nashgao\MQTT\Metrics\PerformanceMetrics;
use Nashgao\MQTT\Metrics\PublishMetrics;
use Nashgao\MQTT\Metrics\SubscriptionMetrics;
use Nashgao\MQTT\Metrics\ValidationMetrics;

/**
 * Modern MQTT Monitor with advanced terminal UI
 * Inspired by btop, lazydocker, k9s for beautiful monitoring experience.
 */
class ModernMqttMonitor
{
    private AdvancedTerminalUI $ui;

    private array $metrics = [];

    private array $dataHistory = [];

    private bool $running = false;

    private int $refreshInterval = 1;

    private string $currentView = 'overview';

    private array $recentEvents = [];

    private int $maxHistorySize = 60;

    private array $views = ['overview', 'connections', 'publishing', 'subscriptions', 'performance', 'analytics'];

    private int $currentViewIndex = 0;

    public function __construct()
    {
        $this->ui = new AdvancedTerminalUI();
        $this->initializeMetrics();
        $this->initializeDataHistory();
    }

    /**
     * Start the modern monitoring interface.
     */
    public function start(): void
    {
        $this->running = true;
        $this->setupTerminal();

        echo "\033[?25l"; // Hide cursor

        // Set up signal handlers
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGINT, [$this, 'stop']);
            pcntl_signal(SIGTERM, [$this, 'stop']);
        }

        try {
            $this->showWelcomeScreen();
            $this->mainLoop();
        } finally {
            $this->cleanup();
        }
    }

    /**
     * Set theme for the UI.
     */
    public function setTheme(string $theme): self
    {
        $this->ui->setTheme($theme);
        return $this;
    }

    /**
     * Set refresh interval.
     */
    public function setRefreshInterval(int $seconds): self
    {
        $this->refreshInterval = max(1, min(10, $seconds));
        return $this;
    }

    /**
     * Add metrics for monitoring.
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
     * Stop monitoring.
     */
    public function stop(): void
    {
        $this->running = false;
    }

    /**
     * Show welcome screen with animation.
     */
    private function showWelcomeScreen(): void
    {
        echo "\033[2J\033[H";

        $logo = [
            '██████╗ ████████╗ ██████╗ ██████╗     ███╗   ███╗ ██████╗ ███╗   ██╗██╗████████╗ ██████╗ ██████╗ ',
            '██╔══██╗╚══██╔══╝██╔═══██╗██╔══██╗    ████╗ ████║██╔═══██╗████╗  ██║██║╚══██╔══╝██╔═══██╗██╔══██╗',
            '██████╔╝   ██║   ██║   ██║██████╔╝    ██╔████╔██║██║   ██║██╔██╗ ██║██║   ██║   ██║   ██║██████╔╝',
            '██╔══██╗   ██║   ██║   ██║██╔═══╝     ██║╚██╔╝██║██║   ██║██║╚██╗██║██║   ██║   ██║   ██║██╔══██╗',
            '██████╔╝   ██║   ╚██████╔╝██║         ██║ ╚═╝ ██║╚██████╔╝██║ ╚████║██║   ██║   ╚██████╔╝██║  ██║',
            '╚═════╝    ╚═╝    ╚═════╝ ╚═╝         ╚═╝     ╚═╝ ╚═════╝ ╚═╝  ╚═══╝╚═╝   ╚═╝    ╚═════╝ ╚═╝  ╚═╝',
        ];

        echo "\033[38;5;39m"; // Blue color
        foreach ($logo as $line) {
            echo str_repeat(' ', max(0, (int) ((120 - strlen($line)) / 2))) . $line . "\n";
            usleep(100000); // Animation delay
        }
        echo "\033[0m";

        echo "\n\n";
        echo str_repeat(' ', 45) . "\033[38;5;46m⚡ Advanced MQTT Monitoring Dashboard ⚡\033[0m\n";
        echo str_repeat(' ', 52) . "\033[38;5;226mPowered by Advanced Terminal UI\033[0m\n";
        echo "\n\n";
        echo str_repeat(' ', 50) . "\033[38;5;245mPress any key to continue...\033[0m";

        // Check if we're in an interactive terminal
        if (posix_isatty(STDIN)) {
            fread(STDIN, 1);
        } else {
            // Non-interactive environment, wait 2 seconds then continue
            sleep(2);
        }
    }

    /**
     * Main monitoring loop.
     */
    private function mainLoop(): void
    {
        while ($this->running) {
            $this->updateMetrics();
            $this->updateDataHistory();
            $this->renderCurrentView();
            $this->handleInput();

            // Responsive sleep
            for ($i = 0; $i < $this->refreshInterval * 10; ++$i) {
                usleep(100000); // 0.1 seconds
                if (! $this->running) {
                    break;
                }

                if ($i % 5 == 0) {
                    $this->handleInput();
                }

                if (function_exists('pcntl_signal_dispatch')) {
                    pcntl_signal_dispatch();
                }
            }
        }
    }

    /**
     * Render current view.
     */
    private function renderCurrentView(): void
    {
        echo "\033[H"; // Move to top

        $data = $this->prepareViewData();
        $output = $this->ui->render($data);

        echo $output;
        flush();
    }

    /**
     * Prepare data for current view.
     */
    private function prepareViewData(): array
    {
        $connectionData = $this->metrics['connection']?->toArray() ?? [];
        $publishData = $this->metrics['publish']?->toArray() ?? [];
        $subscriptionData = $this->metrics['subscription']?->toArray() ?? [];
        $performanceData = $this->metrics['performance']?->toArray() ?? [];
        $validationData = $this->metrics['validation']?->toArray() ?? [];

        return [
            'system' => [
                'status' => $this->running ? '🟢 ACTIVE' : '🔴 STOPPED',
                'view' => strtoupper($this->currentView),
                'refresh' => $this->refreshInterval . 's',
            ],
            'metrics' => [
                'connections' => $connectionData,
                'published' => $publishData,
                'subscriptions' => $subscriptionData,
                'performance' => $performanceData,
                'validation' => $validationData,
            ],
            'connections' => [
                'active' => $connectionData['active_connections'] ?? 0,
                'total' => $connectionData['total_attempts'] ?? 0,
                'failed' => $connectionData['failed_connections'] ?? 0,
                'success_rate' => ($connectionData['success_rate'] ?? 0) * 100,
            ],
            'published' => [
                'total' => $publishData['total_published'] ?? 0,
                'failed' => $publishData['failed_publishes'] ?? 0,
                'success_rate' => ($publishData['success_rate'] ?? 0) * 100,
            ],
            'throughput' => $this->calculateThroughput(),
            'response_times' => $this->dataHistory['response_times'] ?? [],
            'message_volume' => $this->dataHistory['message_volume'] ?? [],
            'events' => $this->recentEvents,
        ];
    }

    /**
     * Calculate current throughput.
     */
    private function calculateThroughput(): float
    {
        $publishData = $this->metrics['publish']?->toArray() ?? [];
        $subscriptionData = $this->metrics['subscription']?->toArray() ?? [];

        $publishRate = $publishData['publish_rate'] ?? 0;
        $subscriptionRate = $subscriptionData['subscription_rate'] ?? 0;

        return $publishRate + $subscriptionRate;
    }

    /**
     * Update metrics with simulation.
     */
    private function updateMetrics(): void
    {
        // Simulate realistic MQTT activity with some variance
        $this->simulateConnections();
        $this->simulatePublishing();
        $this->simulateSubscriptions();
        $this->simulateValidation();
        $this->generateEvents();
    }

    /**
     * Simulate connection activity.
     */
    private function simulateConnections(): void
    {
        if (! isset($this->metrics['connection'])) {
            return;
        }

        $connection = $this->metrics['connection'];

        // Simulate connection attempts with realistic patterns
        if (rand(1, 100) <= 15) { // 15% chance per cycle
            if (rand(1, 100) <= 92) { // 92% success rate
                $connection
                    ->recordConnectionAttempt()
                    ->recordSuccessfulConnection(rand(50, 300) / 1000); // 50-300ms

                $this->addEvent('connection', 'Client ' . $this->generateClientId() . ' connected successfully');
            } else {
                $connection
                    ->recordConnectionAttempt()
                    ->recordFailedConnection();

                $this->addEvent('error', 'Connection failed for client ' . $this->generateClientId());
            }
        }

        // Occasional disconnections
        if (rand(1, 100) <= 3) {
            $connection->recordDisconnection();
            $this->addEvent('connection', 'Client disconnected gracefully');
        }
    }

    /**
     * Simulate publishing activity.
     */
    private function simulatePublishing(): void
    {
        if (! isset($this->metrics['publish'])) {
            return;
        }

        $publish = $this->metrics['publish'];

        // Burst publishing patterns
        $burstSize = rand(1, 5);
        for ($i = 0; $i < $burstSize; ++$i) {
            if (rand(1, 100) <= 97) { // 97% success rate
                $topic = $this->generateRandomTopic();
                $size = rand(100, 2000);

                $publish
                    ->recordPublishAttempt()
                    ->recordSuccessfulPublish($topic, rand(0, 2), $size);

                if ($i == 0) { // Only log first message in burst
                    $this->addEvent('publish', "Published to {$topic} (QoS " . rand(0, 2) . ", {$size} bytes)");
                }
            } else {
                $publish
                    ->recordPublishAttempt()
                    ->recordFailedPublish();

                $this->addEvent('error', 'Publish failed - broker unavailable');
            }
        }
    }

    /**
     * Simulate subscription activity.
     */
    private function simulateSubscriptions(): void
    {
        if (! isset($this->metrics['subscription'])) {
            return;
        }

        $subscription = $this->metrics['subscription'];

        if (rand(1, 100) <= 8) { // 8% chance for new subscription
            $topic = $this->generateRandomTopicPattern();
            $poolName = 'pool_' . rand(1, 3);
            $clientId = $this->generateClientId();

            if (rand(1, 100) <= 95) { // 95% success rate
                $subscription
                    ->recordSubscriptionAttempt()
                    ->recordSuccessfulSubscription($poolName, $clientId, [$topic => rand(0, 2)]);

                $this->addEvent('subscribe', "Client {$clientId} subscribed to {$topic}");
            } else {
                $subscription
                    ->recordSubscriptionAttempt()
                    ->recordFailedSubscription($poolName, $clientId, [$topic => rand(0, 2)], 'Topic access denied');

                $this->addEvent('error', "Subscription failed for {$topic} - access denied");
            }
        }
    }

    /**
     * Simulate validation activity.
     */
    private function simulateValidation(): void
    {
        if (! isset($this->metrics['validation'])) {
            return;
        }

        $validation = $this->metrics['validation'];

        $validationTypes = ['connection_config', 'topic_config', 'pool_config', 'client_config'];
        $validationType = $validationTypes[array_rand($validationTypes)];

        if (rand(1, 100) <= 96) { // 96% validation success rate
            $validation->recordValidation($validationType, true);
        } else {
            $errorMessage = match ($validationType) {
                'connection_config' => 'Invalid broker address format',
                'topic_config' => 'Topic name contains illegal characters',
                'pool_config' => 'Pool size exceeds maximum limit',
                'client_config' => 'Client ID already in use',
            };

            $validation->recordValidation($validationType, false, $errorMessage);
            $this->addEvent('error', "Validation failed: {$errorMessage}");
        }
    }

    /**
     * Update data history for charts.
     */
    private function updateDataHistory(): void
    {
        // Response time simulation
        $baseResponseTime = 45;
        $variance = rand(-20, 30);
        $responseTime = max(10, $baseResponseTime + $variance + sin(time() / 10) * 15);

        $this->addToHistory('response_times', $responseTime);

        // Message volume simulation
        $baseVolume = 120;
        $timeOfDay = sin((time() % 86400) / 86400 * 2 * M_PI) * 50; // Daily pattern
        $randomVariance = rand(-30, 40);
        $volume = max(10, $baseVolume + $timeOfDay + $randomVariance);

        $this->addToHistory('message_volume', $volume);

        // Connection count history
        $connectionData = $this->metrics['connection']?->toArray() ?? [];
        $this->addToHistory('connections', $connectionData['active_connections'] ?? 0);

        // Throughput history
        $this->addToHistory('throughput', $this->calculateThroughput());
    }

    /**
     * Add data point to history.
     */
    private function addToHistory(string $key, float $value): void
    {
        if (! isset($this->dataHistory[$key])) {
            $this->dataHistory[$key] = [];
        }

        $this->dataHistory[$key][] = $value;

        // Keep only recent history
        if (count($this->dataHistory[$key]) > $this->maxHistorySize) {
            $this->dataHistory[$key] = array_slice($this->dataHistory[$key], -$this->maxHistorySize);
        }
    }

    /**
     * Generate random events for demo mode.
     */
    private function generateEvents(): void
    {
        if (rand(1, 10) === 1) {
            $events = [
                ['info', 'Client connected: ' . $this->generateClientId()],
                ['success', 'Message published to ' . $this->generateRandomTopic()],
                ['info', 'Subscription added: ' . $this->generateRandomTopicPattern()],
                ['warning', 'Connection timeout for client_' . rand(100, 999)],
                ['info', 'QoS 2 message delivered'],
                ['success', 'Heartbeat received from gateway_' . rand(1, 50)],
            ];

            $event = $events[array_rand($events)];
            $this->addEvent($event[0], $event[1]);
        }
    }

    /**
     * Add event to recent events.
     */
    private function addEvent(string $type, string $message): void
    {
        $this->recentEvents[] = [
            'time' => date('H:i:s'),
            'type' => $type,
            'message' => $message,
        ];

        // Keep only recent events
        if (count($this->recentEvents) > 20) {
            $this->recentEvents = array_slice($this->recentEvents, -20);
        }
    }

    /**
     * Generate random client ID.
     */
    private function generateClientId(): string
    {
        $prefixes = ['client', 'device', 'sensor', 'gateway', 'node'];
        return $prefixes[array_rand($prefixes)] . '_' . str_pad((string) rand(1, 999), 3, '0', STR_PAD_LEFT);
    }

    /**
     * Generate random topic.
     */
    private function generateRandomTopic(): string
    {
        $topics = [
            'sensor/temperature',
            'sensor/humidity',
            'sensor/pressure',
            'device/status',
            'device/heartbeat',
            'alerts/critical',
            'alerts/warning',
            'logs/application',
            'logs/system',
            'metrics/performance',
            'data/telemetry',
            'config/update',
        ];

        return $topics[array_rand($topics)] . '/' . rand(1, 100);
    }

    /**
     * Generate random topic pattern for subscriptions.
     */
    private function generateRandomTopicPattern(): string
    {
        $patterns = [
            'sensor/+',
            'device/#',
            'alerts/+',
            'logs/#',
            'sensor/temperature/+',
            'device/status/+',
            'data/+/telemetry',
            '+/status',
            'sensor/+/data',
            'alerts/critical/+',
        ];

        return $patterns[array_rand($patterns)];
    }

    /**
     * Handle keyboard input.
     */
    private function handleInput(): void
    {
        while (($key = fread(STDIN, 1)) !== false && $key !== '') {
            switch (strtolower($key)) {
                case 'q':
                case "\x03": // Ctrl+C
                    $this->stop();
                    return;
                case "\x1b": // ESC sequence
                    $this->handleEscapeSequence();
                    break;
                case '1':
                case '2':
                case '3':
                    $themeMap = ['1' => 'dark', '2' => 'light', '3' => 'cyberpunk'];
                    $this->setTheme($themeMap[$key]);
                    break;
                case '+':
                case '=':
                    $this->refreshInterval = max(1, $this->refreshInterval - 1);
                    break;
                case '-':
                case '_':
                    $this->refreshInterval = min(10, $this->refreshInterval + 1);
                    break;
                case 'r':
                    $this->resetMetrics();
                    break;
                case 'e':
                    $this->exportMetrics();
                    break;
            }
        }
    }

    /**
     * Handle escape sequences (arrow keys, function keys).
     */
    private function handleEscapeSequence(): void
    {
        $seq = fread(STDIN, 2);
        if ($seq === '[A') { // Up arrow
            $this->currentViewIndex = ($this->currentViewIndex - 1 + count($this->views)) % count($this->views);
            $this->currentView = $this->views[$this->currentViewIndex];
        } elseif ($seq === '[B') { // Down arrow
            $this->currentViewIndex = ($this->currentViewIndex + 1) % count($this->views);
            $this->currentView = $this->views[$this->currentViewIndex];
        }
    }

    /**
     * Reset all metrics.
     */
    private function resetMetrics(): void
    {
        foreach ($this->metrics as $metric) {
            if (method_exists($metric, 'reset')) {
                $metric->reset();
            }
        }
        $this->dataHistory = [];
        $this->recentEvents = [];
        $this->addEvent('system', 'All metrics reset');
    }

    /**
     * Export current metrics.
     */
    private function exportMetrics(): void
    {
        $filename = 'mqtt_metrics_' . date('Y-m-d_H-i-s') . '.json';
        $data = $this->prepareViewData();
        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
        $this->addEvent('system', "Metrics exported to {$filename}");
    }

    /**
     * Setup terminal.
     */
    private function setupTerminal(): void
    {
        if (function_exists('system')) {
            system('stty -icanon -echo');
        }

        if (function_exists('stream_set_blocking')) {
            stream_set_blocking(STDIN, false);
        }
    }

    /**
     * Cleanup terminal.
     */
    private function cleanup(): void
    {
        echo "\033[?25h"; // Show cursor
        echo "\033[2J\033[H"; // Clear screen
        echo "\033[0m"; // Reset colors

        if (function_exists('system')) {
            system('stty icanon echo');
        }

        if (function_exists('stream_set_blocking')) {
            stream_set_blocking(STDIN, true);
        }

        echo "\n\033[38;5;46m✨ Thanks for using MQTT Monitor Pro! ✨\033[0m\n";
        echo "\033[38;5;245mHave a great day! 🚀\033[0m\n\n";
    }

    /**
     * Initialize metrics with defaults.
     */
    private function initializeMetrics(): void
    {
        $this->metrics = [
            'connection' => new ConnectionMetrics(),
            'performance' => new PerformanceMetrics(),
            'publish' => new PublishMetrics(),
            'subscription' => new SubscriptionMetrics(),
            'validation' => new ValidationMetrics(),
        ];
    }

    /**
     * Initialize data history.
     */
    private function initializeDataHistory(): void
    {
        $this->dataHistory = [
            'response_times' => [],
            'message_volume' => [],
            'connections' => [],
            'throughput' => [],
        ];
    }
}
