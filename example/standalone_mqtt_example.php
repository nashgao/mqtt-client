<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Simps\MQTT\Client;
use Simps\MQTT\Config\ClientConfig;
use Simps\MQTT\Protocol\ProtocolInterface;
use Simps\MQTT\Protocol\Types;

echo "🚀 Standalone MQTT Example (Framework Independent)\n";
echo str_repeat('=', 60) . "\n\n";

try {
    // 1. Configure MQTT client
    echo "📝 Configuring MQTT client...\n";
    $config = new ClientConfig();
    $config->setClientId('standalone_client_' . uniqid())
        ->setUserName('')  // No auth required for test broker
        ->setPassword('');

    // Set connection settings (these are properties, not methods)
    $config->host = 'test.mosquitto.org';
    $config->port = 1883;
    $config->timeout = 60;
    $config->keepalive = 10;
    $config->protocolLevel = 4; // MQTT 3.1.1

    echo "   ✅ Host: {$config->host}\n";
    echo "   ✅ Port: {$config->port}\n";
    echo "   ✅ Client ID: {$config->getClientId()}\n\n";

    // 2. Create MQTT client
    echo "🔌 Creating MQTT client...\n";
    $mqtt = new Client($config);

    // 3. Connect to broker
    echo "🌐 Connecting to MQTT broker...\n";
    $connectResult = $mqtt->connect();
    if ($connectResult === false) {
        throw new Exception('Failed to connect to MQTT broker');
    }
    echo "   ✅ Connected successfully!\n\n";

    // 4. Subscribe to topics
    echo "📥 Subscribing to topics...\n";
    $subscribeTopics = [
        'standalone/test/temperature' => 1,
        'standalone/test/humidity' => 1,
        'standalone/test/status' => 0,
    ];

    foreach ($subscribeTopics as $topic => $qos) {
        $result = $mqtt->subscribe([$topic => $qos]);
        if ($result) {
            echo "   ✅ Subscribed to: {$topic} (QoS {$qos})\n";
        } else {
            echo "   ❌ Failed to subscribe to: {$topic}\n";
        }
    }
    echo "\n";

    // 5. Publish messages
    echo "📤 Publishing messages...\n";
    $messages = [
        'standalone/test/temperature' => [
            'payload' => json_encode(['temperature' => 23.5, 'unit' => 'C', 'timestamp' => time()]),
            'qos' => 1,
            'retain' => false,
        ],
        'standalone/test/humidity' => [
            'payload' => json_encode(['humidity' => 65.2, 'unit' => '%', 'timestamp' => time()]),
            'qos' => 1,
            'retain' => false,
        ],
        'standalone/test/status' => [
            'payload' => json_encode(['status' => 'online', 'device_id' => 'standalone_001', 'timestamp' => time()]),
            'qos' => 0,
            'retain' => true,
        ],
    ];

    foreach ($messages as $topic => $msgConfig) {
        $result = $mqtt->publish(
            $topic,
            $msgConfig['payload'],
            $msgConfig['qos'],
            $msgConfig['retain']
        );

        if ($result) {
            echo "   ✅ Published to {$topic}: " . substr($msgConfig['payload'], 0, 50) . "...\n";
        } else {
            echo "   ❌ Failed to publish to {$topic}\n";
        }
        usleep(500000); // Wait 0.5 seconds between messages
    }
    echo "\n";

    // 6. Listen for incoming messages (for a short time)
    echo "👂 Listening for incoming messages (5 seconds)...\n";
    $startTime = time();
    $messageCount = 0;

    while ((time() - $startTime) < 5) {
        $buffer = $mqtt->recv();
        if ($buffer !== false) {
            $packet = ProtocolInterface::unpack($buffer);
            if ($packet['type'] === Types::PUBLISH) {
                ++$messageCount;
                echo "   📨 Received message #{$messageCount}:\n";
                echo "      Topic: {$packet['topic']}\n";
                echo "      Payload: {$packet['message']}\n";
                echo "      QoS: {$packet['qos']}\n\n";
            }
        }
        usleep(100000); // 0.1 second polling interval
    }

    if ($messageCount === 0) {
        echo "   ℹ️  No messages received (this is normal for a quick test)\n\n";
    }

    // 7. Unsubscribe from topics
    echo "📤 Unsubscribing from topics...\n";
    foreach (array_keys($subscribeTopics) as $topic) {
        $result = $mqtt->unSubscribe([$topic]);
        if ($result) {
            echo "   ✅ Unsubscribed from: {$topic}\n";
        } else {
            echo "   ❌ Failed to unsubscribe from: {$topic}\n";
        }
    }
    echo "\n";

    // 8. Disconnect
    echo "🔌 Disconnecting...\n";
    $mqtt->close();
    echo "   ✅ Disconnected successfully!\n\n";

    // 9. Summary
    echo "📊 Standalone MQTT Example Summary:\n";
    echo str_repeat('-', 40) . "\n";
    echo "✅ Connected to public MQTT broker\n";
    echo '✅ Subscribed to ' . count($subscribeTopics) . " topics\n";
    echo '✅ Published ' . count($messages) . " messages\n";
    echo "✅ Listened for incoming messages\n";
    echo "✅ Properly cleaned up connections\n\n";

    echo "💡 This example shows how to use MQTT without any framework dependencies.\n";
    echo "   You can integrate this code into any PHP application!\n";
} catch (Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n";
    echo "📋 Stack trace:\n{$e->getTraceAsString()}\n";
} catch (Throwable $e) {
    echo "❌ Fatal error: {$e->getMessage()}\n";
    echo "📋 Stack trace:\n{$e->getTraceAsString()}\n";
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "📚 Example: Standalone MQTT - Complete\n";
echo "\n🔧 For more advanced features, check out:\n";
echo "   - basic_client_example.php (using the wrapper classes)\n";
echo "   - advanced_metrics_example.php (with comprehensive monitoring)\n";
echo "   - pool_management_example.php (connection pooling)\n";
echo "   - security_validation_example.php (security best practices)\n";
