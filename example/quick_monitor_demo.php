<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Nashgao\MQTT\Metrics\ConnectionMetrics;
use Nashgao\MQTT\Metrics\PerformanceMetrics;
use Nashgao\MQTT\Metrics\PublishMetrics;
use Nashgao\MQTT\Metrics\SubscriptionMetrics;
use Nashgao\MQTT\Metrics\ValidationMetrics;
use Nashgao\MQTT\Utils\MqttMonitor;

echo "MQTT Real-time Monitor Demo\n";
echo "===========================\n\n";

echo "Creating monitor with sample metrics...\n";

// Create metrics with method chaining for demo
$connectionMetrics = (new ConnectionMetrics())
    ->recordConnectionAttempt()
    ->recordSuccessfulConnection(0.15)
    ->recordConnectionAttempt()
    ->recordSuccessfulConnection(0.12)
    ->recordConnectionAttempt()
    ->recordFailedConnection();

$publishMetrics = (new PublishMetrics())
    ->recordPublishAttempt()
    ->recordSuccessfulPublish('sensor/temperature', 1, 250)
    ->recordPublishAttempt()
    ->recordSuccessfulPublish('device/status', 0, 180)
    ->recordPublishAttempt()
    ->recordSuccessfulPublish('alerts/warning', 2, 320);

$subscriptionMetrics = (new SubscriptionMetrics())
    ->recordSubscriptionAttempt()
    ->recordSuccessfulSubscription('pool1', 'client1', ['sensor/+' => 1])
    ->recordSubscriptionAttempt()
    ->recordSuccessfulSubscription('pool1', 'client2', ['alerts/#' => 2]);

$validationMetrics = (new ValidationMetrics())
    ->recordValidation('connection_config', true)
    ->recordValidation('topic_config', true)
    ->recordValidation('pool_config', false, 'Invalid pool size');

// Create monitor with method chaining
$monitor = (new MqttMonitor())
    ->setRefreshInterval(1)
    ->addMetrics(
        $connectionMetrics,
        new PerformanceMetrics(),
        $publishMetrics,
        $subscriptionMetrics,
        $validationMetrics
    );

echo "Starting real-time monitor in 3 seconds...\n";
echo "Use the following controls:\n";
echo "  - h: Toggle help\n";
echo "  - r: Reset metrics\n";
echo "  - e: Export to file\n";
echo "  - +/-: Adjust refresh rate\n";
echo "  - q: Quit\n\n";

echo '3...';
sleep(1);
echo '2...';
sleep(1);
echo '1...';
sleep(1);
echo "\n\nStarting monitor now!\n";

$monitor->start();
