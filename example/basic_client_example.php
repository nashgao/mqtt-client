<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Nashgao\MQTT\Client;
use Nashgao\MQTT\Config\ClientConfig;
use Nashgao\MQTT\Config\TopicPublishConfig;
use Nashgao\MQTT\Config\TopicSubscriptionConfig;
use Nashgao\MQTT\Exception\InvalidMQTTConnectionException;

echo "🚀 Basic MQTT Client Example\n";
echo str_repeat('=', 50) . "\n\n";

try {
    // 1. Create client configuration
    echo "📝 Creating client configuration...\n";
    $simpsConfig = new Simps\MQTT\Config\ClientConfig();
    $simpsConfig->setHost('test.mosquitto.org')  // Public MQTT broker
        ->setPort(1883)
        ->setClientId('php_basic_example_' . uniqid())
        ->setUserName('')  // No auth required for test broker
        ->setPassword('')
        ->setTimeout(60);

    $clientConfig = new ClientConfig(
        'test.mosquitto.org',
        1883,
        $simpsConfig
    );

    echo "   ✅ Host: {$clientConfig->host}\n";
    echo "   ✅ Port: {$clientConfig->port}\n";
    echo "   ✅ Client ID: {$simpsConfig->getClientId()}\n\n";

    // 2. Create MQTT client
    echo "🔌 Creating MQTT client...\n";
    $client = new Client();

    // 3. Connect to broker
    echo "🌐 Connecting to MQTT broker...\n";
    $client->connect($clientConfig);
    echo "   ✅ Connected successfully!\n\n";

    // 4. Subscribe to a topic
    echo "📥 Subscribing to topics...\n";
    $subscriptionConfig = new TopicSubscriptionConfig();
    $subscriptionConfig->topic = 'test/php/basic/+';
    $subscriptionConfig->qos = 1;

    $client->subscribe($subscriptionConfig);
    echo "   ✅ Subscribed to: {$subscriptionConfig->topic}\n\n";

    // 5. Publish messages
    echo "📤 Publishing messages...\n";
    $messages = [
        'test/php/basic/temperature' => json_encode(['temperature' => 23.5, 'unit' => 'C', 'timestamp' => time()]),
        'test/php/basic/humidity' => json_encode(['humidity' => 65.2, 'unit' => '%', 'timestamp' => time()]),
        'test/php/basic/status' => json_encode(['status' => 'online', 'device_id' => 'sensor_001', 'timestamp' => time()]),
    ];

    foreach ($messages as $topic => $payload) {
        $publishConfig = new TopicPublishConfig();
        $publishConfig->topic = $topic;
        $publishConfig->payload = $payload;
        $publishConfig->qos = 1;
        $publishConfig->retain = false;

        $client->publish($publishConfig);
        echo "   ✅ Published to {$topic}: " . substr($payload, 0, 50) . "...\n";
        usleep(500000); // Wait 0.5 seconds between messages
    }

    echo "\n📊 Example completed successfully!\n";
    echo "💡 Check your MQTT client or dashboard to see the published messages.\n";

    // 6. Disconnect
    echo "\n🔌 Disconnecting...\n";
    $client->disconnect();
    echo "   ✅ Disconnected successfully!\n";
} catch (InvalidMQTTConnectionException $e) {
    echo "❌ Connection Error: {$e->getMessage()}\n";
    echo "💡 Tip: Make sure you have internet connection and the broker is accessible.\n";
} catch (Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n";
    echo "📋 Stack trace:\n{$e->getTraceAsString()}\n";
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "📚 Example: Basic MQTT Client - Complete\n";
