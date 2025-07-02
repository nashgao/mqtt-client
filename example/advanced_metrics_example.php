<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Nashgao\MQTT\Metrics\ConnectionMetrics;
use Nashgao\MQTT\Metrics\ErrorMetrics;
use Nashgao\MQTT\Metrics\HealthMetrics;
use Nashgao\MQTT\Metrics\PerformanceMetrics;
use Nashgao\MQTT\Metrics\PublishMetrics;
use Nashgao\MQTT\Metrics\SubscriptionMetrics;
use Nashgao\MQTT\Metrics\ValidationMetrics;

echo "📊 Advanced MQTT Metrics Example\n";
echo str_repeat('=', 60) . "\n\n";

try {
    // 1. Initialize all metrics
    echo "🔧 Initializing metrics collectors...\n";
    $connectionMetrics = new ConnectionMetrics();
    $performanceMetrics = new PerformanceMetrics();
    $publishMetrics = new PublishMetrics();
    $subscriptionMetrics = new SubscriptionMetrics();
    $validationMetrics = new ValidationMetrics();
    $errorMetrics = new ErrorMetrics();
    $healthMetrics = new HealthMetrics();

    echo "   ✅ All metrics collectors initialized\n\n";

    // 2. Simulate connection activities
    echo "🔌 Simulating connection activities...\n";
    for ($i = 1; $i <= 10; ++$i) {
        $connectionMetrics->recordConnectionAttempt();

        if ($i <= 8) {
            // Successful connections
            $connectionTime = round(rand(100, 300) / 1000, 3);
            $connectionMetrics->recordSuccessfulConnection($connectionTime);
            echo "   ✅ Connection #{$i} successful ({$connectionTime}s)\n";
        } else {
            // Failed connections
            $connectionMetrics->recordFailedConnection('Connection timeout');
            $errorMetrics->recordError('connection', 'Connection timeout', 'high');
            echo "   ❌ Connection #{$i} failed\n";
        }

        usleep(100000); // 0.1 second delay
    }
    echo "\n";

    // 3. Simulate publishing activities
    echo "📤 Simulating publishing activities...\n";
    $topics = [
        'sensors/temperature',
        'sensors/humidity',
        'devices/status',
        'alerts/warning',
        'system/heartbeat',
    ];

    for ($i = 1; $i <= 20; ++$i) {
        $topic = $topics[array_rand($topics)];
        $qos = rand(0, 2);
        $payloadSize = rand(50, 500);

        $publishMetrics->recordPublishAttempt();

        if ($i <= 17) {
            // Successful publishes
            $publishTime = round(rand(10, 100) / 1000, 3);
            $publishMetrics->recordSuccessfulPublish($topic, $qos, $payloadSize, $publishTime);
            $performanceMetrics->recordLatency($publishTime);
            echo "   ✅ Published to {$topic} (QoS {$qos}, {$payloadSize}B, {$publishTime}s)\n";
        } else {
            // Failed publishes
            $publishMetrics->recordFailedPublish($topic, 'Network error');
            $errorMetrics->recordError('publish', 'Network error', 'medium');
            echo "   ❌ Failed to publish to {$topic}\n";
        }

        usleep(50000); // 0.05 second delay
    }
    echo "\n";

    // 4. Simulate subscription activities
    echo "📥 Simulating subscription activities...\n";
    $subscriptionPatterns = [
        'sensors/+' => 1,
        'devices/#' => 2,
        'alerts/+' => 2,
        'system/heartbeat' => 0,
        'logs/+/error' => 1,
    ];

    foreach ($subscriptionPatterns as $pattern => $qos) {
        $poolId = 'pool_' . rand(1, 3);
        $clientId = 'client_' . rand(100, 999);

        $subscriptionMetrics->recordSubscriptionAttempt();
        $subscriptionMetrics->recordSuccessfulSubscription($poolId, $clientId, [$pattern => $qos]);

        echo "   ✅ Subscribed to {$pattern} (QoS {$qos}) - {$clientId} in {$poolId}\n";
        usleep(100000);
    }
    echo "\n";

    // 5. Simulate validation activities
    echo "✅ Simulating validation activities...\n";
    $validationTypes = [
        'connection_config' => [true, true, true, false, true],
        'topic_config' => [true, true, true, true, true],
        'pool_config' => [true, false, true, true, true],
        'security_config' => [true, true, true, true, false],
    ];

    foreach ($validationTypes as $type => $results) {
        foreach ($results as $result) {
            $validationMetrics->recordValidation($type, $result);
            if (! $result) {
                $errorMetrics->recordError('validation', "Failed {$type} validation", 'low');
            }
        }

        $stats = $validationMetrics->getValidationCount($type);
        echo "   📋 {$type}: {$stats['success_count']}/{$stats['total_count']} passed\n";
    }
    echo "\n";

    // 6. Record health metrics
    echo "💚 Recording health metrics...\n";
    $healthMetrics->recordHealthCheck('connection_pool', true, 0.045);
    $healthMetrics->recordHealthCheck('message_broker', true, 0.023);
    $healthMetrics->recordHealthCheck('validation_service', false, 0.156);
    $healthMetrics->recordHealthCheck('metrics_collector', true, 0.012);

    $healthStatus = $healthMetrics->getOverallHealth();
    echo "   📊 Overall health: {$healthStatus['status']} ({$healthStatus['score']}%)\n";
    echo '   🔍 Unhealthy services: ' . implode(', ', $healthStatus['unhealthy_services']) . "\n\n";

    // 7. Display comprehensive metrics
    echo "📈 Metrics Summary:\n";
    echo str_repeat('-', 40) . "\n";

    // Connection metrics
    $connStats = $connectionMetrics->getConnectionStats();
    echo "🔌 Connections:\n";
    echo "   Total attempts: {$connStats['total_attempts']}\n";
    echo "   Successful: {$connStats['successful_connections']}\n";
    echo "   Failed: {$connStats['failed_connections']}\n";
    echo '   Success rate: ' . round($connStats['success_rate'] * 100, 1) . "%\n";
    echo '   Avg connection time: ' . round($connStats['average_connection_time'], 3) . "s\n\n";

    // Publishing metrics
    $pubStats = $publishMetrics->getPublishingStats();
    echo "📤 Publishing:\n";
    echo "   Total attempts: {$pubStats['total_attempts']}\n";
    echo "   Successful: {$pubStats['successful_publishes']}\n";
    echo "   Failed: {$pubStats['failed_publishes']}\n";
    echo '   Success rate: ' . round($pubStats['success_rate'] * 100, 1) . "%\n";
    echo "   Total payload: {$pubStats['total_payload_size']} bytes\n\n";

    // Performance metrics
    $perfStats = $performanceMetrics->getPerformanceStats();
    echo "⚡ Performance:\n";
    echo '   Avg latency: ' . round($perfStats['average_latency'], 3) . "s\n";
    echo '   Min latency: ' . round($perfStats['min_latency'], 3) . "s\n";
    echo '   Max latency: ' . round($perfStats['max_latency'], 3) . "s\n";
    echo '   95th percentile: ' . round($perfStats['p95_latency'], 3) . "s\n\n";

    // Error metrics
    $errorStats = $errorMetrics->getErrorStats();
    echo "❌ Errors:\n";
    echo "   Total errors: {$errorStats['total_errors']}\n";
    echo "   By severity - High: {$errorStats['by_severity']['high']}, ";
    echo "Medium: {$errorStats['by_severity']['medium']}, ";
    echo "Low: {$errorStats['by_severity']['low']}\n\n";

    // 8. Export metrics
    echo "💾 Exporting metrics...\n";
    $exportData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'connection_metrics' => $connectionMetrics->getConnectionStats(),
        'performance_metrics' => $performanceMetrics->getPerformanceStats(),
        'publish_metrics' => $publishMetrics->getPublishingStats(),
        'subscription_metrics' => $subscriptionMetrics->getSubscriptionStats(),
        'validation_metrics' => [
            'connection_config' => $validationMetrics->getValidationCount('connection_config'),
            'topic_config' => $validationMetrics->getValidationCount('topic_config'),
            'pool_config' => $validationMetrics->getValidationCount('pool_config'),
            'security_config' => $validationMetrics->getValidationCount('security_config'),
        ],
        'error_metrics' => $errorMetrics->getErrorStats(),
        'health_metrics' => $healthMetrics->getOverallHealth(),
    ];

    $exportFile = __DIR__ . '/metrics/advanced_metrics_export_' . date('Y-m-d_H-i-s') . '.json';
    file_put_contents($exportFile, json_encode($exportData, JSON_PRETTY_PRINT));
    echo "   ✅ Metrics exported to: {$exportFile}\n";

    echo "\n📊 Advanced Metrics Example completed successfully!\n";
} catch (Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n";
    echo "📋 Stack trace:\n{$e->getTraceAsString()}\n";
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "📚 Example: Advanced Metrics - Complete\n";
