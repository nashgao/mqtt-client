<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Nashgao\MQTT\Metrics\ConnectionMetrics;
use Nashgao\MQTT\Metrics\PerformanceMetrics;
use Nashgao\MQTT\Metrics\PublishMetrics;
use Nashgao\MQTT\Metrics\SubscriptionMetrics;
use Nashgao\MQTT\Metrics\ValidationMetrics;
use Nashgao\MQTT\Utils\MetricsVisualizer;
use Nashgao\MQTT\Utils\MqttMonitor;

echo "MQTT Metrics Visualization Examples\n";
echo "===================================\n\n";

// Create sample metrics with some demo data
$connectionMetrics = new ConnectionMetrics();
$performanceMetrics = new PerformanceMetrics();
$publishMetrics = new PublishMetrics();
$subscriptionMetrics = new SubscriptionMetrics();
$validationMetrics = new ValidationMetrics();

// Simulate some MQTT activity
echo "Simulating MQTT activity...\n";

for ($i = 0; $i < 50; ++$i) {
    // Simulate connections (90% success rate) - using method chaining
    $connectionMetrics->recordConnectionAttempt();
    if (rand(1, 10) > 1) {
        $connectionMetrics->recordSuccessfulConnection(rand(50, 200) / 1000); // 50-200ms
    } else {
        $connectionMetrics->recordFailedConnection();
    }

    // Simulate publishes (95% success rate) - using method chaining
    if (rand(1, 10) > 1) {
        $publishMetrics
            ->recordPublishAttempt()
            ->recordSuccessfulPublish("sensor/temperature/{$i}", rand(0, 2), rand(50, 500))
            ->recordSuccessfulPublish("device/status/{$i}", rand(0, 2), rand(100, 200));
    } else {
        $publishMetrics
            ->recordPublishAttempt()
            ->recordFailedPublish();
    }

    // Simulate subscriptions (98% success rate) - using method chaining
    if (rand(1, 100) > 2) {
        $subscriptionMetrics
            ->recordSubscriptionAttempt()
            ->recordSuccessfulSubscription(
                "pool_{$i}",
                "client_{$i}",
                ["alerts/critical/{$i}" => rand(0, 2)]
            );
    } else {
        $subscriptionMetrics
            ->recordSubscriptionAttempt()
            ->recordFailedSubscription(
                "pool_{$i}",
                "client_{$i}",
                ["alerts/critical/{$i}" => rand(0, 2)],
                'Connection failed'
            );
    }

    // Simulate validations (99% success rate) - using method chaining
    $validSuccess = rand(1, 100) > 1;
    $validationMetrics->recordValidation(
        'config_validation',
        $validSuccess,
        $validSuccess ? '' : 'Invalid configuration parameter'
    );

    // Add some random delay to simulate real-time activity
    if ($i % 10 == 0) {
        echo '.';
        usleep(10000); // 10ms delay
    }
}

echo "\nActivity simulation complete!\n\n";

// Create visualizer and set all metrics
$visualizer = new MetricsVisualizer();
$visualizer
    ->setConnectionMetrics($connectionMetrics)
    ->setPerformanceMetrics($performanceMetrics)
    ->setPublishMetrics($publishMetrics)
    ->setSubscriptionMetrics($subscriptionMetrics)
    ->setValidationMetrics($validationMetrics);

// Example 1: Dashboard view
echo "1. Dashboard View:\n";
echo str_repeat('=', 80) . "\n";
echo $visualizer->generateDashboard();
echo "\n\n";

// Example 2: JSON export
echo "2. JSON Export Example:\n";
echo str_repeat('=', 80) . "\n";
$jsonOutput = $visualizer->generateJson();
echo $jsonOutput;
echo "\n\n";

// Example 3: CSV export
echo "3. CSV Export Example:\n";
echo str_repeat('=', 80) . "\n";
echo $visualizer->generateCsv();
echo "\n\n";

// Example 4: Bar chart visualization
echo "4. Bar Chart Example:\n";
echo str_repeat('=', 80) . "\n";
$topicData = [
    'sensor/temp' => 1250,
    'device/status' => 890,
    'alerts/critical' => 45,
    'logs/app' => 230,
    'metrics/sys' => 670,
];
echo $visualizer->generateBarChart('Message Volume by Topic', $topicData, 40);
echo "\n\n";

// Example 5: Line graph
echo "5. Line Graph Example:\n";
echo str_repeat('=', 80) . "\n";
$responseTimeData = [];
for ($i = 0; $i < 40; ++$i) {
    $responseTimeData[] = 50 + (sin($i / 5) * 20) + rand(-10, 10);
}
echo $visualizer->generateLineGraph('Response Time Trend (ms)', $responseTimeData, 50, 8);
echo "\n\n";

// Example 6: Real-time display preview
echo "6. Real-time Display Preview:\n";
echo str_repeat('=', 80) . "\n";
echo $visualizer->generateRealTimeDisplay();
echo "\n\n";

// Example 7: Export to files
echo "7. File Export Examples:\n";
echo str_repeat('=', 80) . "\n";

$monitor = new MqttMonitor();
$monitor->addMetrics(
    $connectionMetrics,
    $performanceMetrics,
    $publishMetrics,
    $subscriptionMetrics,
    $validationMetrics
);

// Export to different formats
$jsonFile = $monitor->exportMetrics('json', 'mqtt_metrics_example.json');
$csvFile = $monitor->exportMetrics('csv', 'mqtt_metrics_example.csv');
$dashboardFile = $monitor->exportMetrics('dashboard', 'mqtt_metrics_example.txt');

echo "Exported metrics to files:\n";
echo "- JSON: {$jsonFile}\n";
echo "- CSV: {$csvFile}\n";
echo "- Dashboard: {$dashboardFile}\n";
echo "\n";

// Example 8: Usage instructions
echo "8. Real-time Monitor Usage:\n";
echo str_repeat('=', 80) . "\n";
echo "To start the real-time monitor (htop-style), run:\n";
echo "  ./bin/mqtt-monitor\n";
echo "\n";
echo "Or try these options:\n";
echo "  ./bin/mqtt-monitor --interval=2          # 2-second refresh\n";
echo "  ./bin/mqtt-monitor --mode=snapshot       # One-time snapshot\n";
echo "  ./bin/mqtt-monitor --mode=export --format=csv  # Export to CSV\n";
echo "\n";
echo "Monitor keyboard controls:\n";
echo "  q, Q, Ctrl+C  - Quit\n";
echo "  h, H, ?       - Help\n";
echo "  r, R          - Reset metrics\n";
echo "  e, E          - Export to file\n";
echo "  +/-           - Adjust refresh rate\n";
echo "  Space         - Force refresh\n";
echo "\n";

echo "Example completed! Check the exported files for sample output.\n";
