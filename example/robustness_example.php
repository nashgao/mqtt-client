<?php

declare(strict_types=1);

use Nashgao\MQTT\Client;
use Nashgao\MQTT\Exception\InvalidConfigException;
use Nashgao\MQTT\Pool\PoolFactory;
use Nashgao\MQTT\Utils\ConfigValidator;
use Nashgao\MQTT\Utils\ErrorHandler;
use Nashgao\MQTT\Utils\HealthChecker;
use Nashgao\MQTT\Utils\TopicParser;
use Psr\Log\NullLogger;

// Example of using the robustness features in production

// 1. Validate configuration before using it
try {
    $connectionConfig = ConfigValidator::validateConnectionConfig([
        'host' => 'mqtt.example.com',
        'port' => 1883,
        'client_id' => 'robust_client_123',
        'keep_alive' => 60,
    ]);
    echo "✅ Connection configuration validated\n";
} catch (InvalidConfigException $e) {
    echo '❌ Invalid configuration: ' . $e->getMessage() . "\n";
    exit(1);
}

// 2. Create robust MQTT client with error handling and health monitoring
$errorHandler = new ErrorHandler(new NullLogger());
$healthChecker = new HealthChecker();

// Set custom retry policies for different operations
$errorHandler->setRetryPolicy('mqtt_publish', 5, 1000);    // 5 retries for publish
$errorHandler->setRetryPolicy('mqtt_subscribe', 3, 2000);  // 3 retries for subscribe
$errorHandler->setRetryPolicy('mqtt_connect', 10, 500);    // 10 retries for connect

// Create client with integrated robustness features
$poolFactory = new PoolFactory();
$client = new Client($poolFactory, $errorHandler, $healthChecker);

// 3. Validate and sanitize topics before use
try {
    $topics = [
        'sensors/temperature',
        'alerts/critical/#',
        'commands/+/execute',
    ];

    foreach ($topics as $topic) {
        if (! ConfigValidator::validateTopicFilter($topic)) {
            throw new InvalidArgumentException("Invalid topic filter: {$topic}");
        }

        $sanitizedTopic = ConfigValidator::sanitizeTopicName($topic);
        echo "✅ Topic validated and sanitized: {$sanitizedTopic}\n";
    }
} catch (Exception $e) {
    echo '❌ Topic validation failed: ' . $e->getMessage() . "\n";
}

// 4. Use the client with automatic error handling and health monitoring
try {
    // These operations now include automatic:
    // - Error handling with retries
    // - Health monitoring
    // - Configuration validation
    // - Topic sanitization

    $client->connect(true, []);
    echo "✅ Connected to MQTT broker\n";

    $client->subscribe(['sensors/temperature' => ['qos' => 1]], []);
    echo "✅ Subscribed to topic\n";

    $client->publish('sensors/temperature', '23.5', 1);
    echo "✅ Published message\n";

    // Check health status
    $healthStatus = $client->getHealthStatus();
    echo "📊 System Health:\n";
    echo '  - Memory usage: ' . formatBytes($healthStatus['memory']['usage']) . "\n";
    echo '  - Active connections: ' . $healthStatus['metrics']['active_connections'] . "\n";
    echo '  - Connection attempts: ' . $healthStatus['metrics']['connection_attempts'] . "\n";
    echo '  - Success rate: ' . ($client->getConnectionSuccessRate() * 100) . "%\n";

    if ($client->isHealthy()) {
        echo "✅ Client is healthy\n";
    } else {
        echo "⚠️ Client health issues detected\n";
    }
} catch (Exception $e) {
    echo '❌ MQTT operation failed: ' . $e->getMessage() . "\n";

    // The error has already been handled by ErrorHandler with retries
    // and logged appropriately
}

// 5. Advanced topic parsing with validation
try {
    $complexTopic = '$share/worker-group/data/processing/queue';
    $parsedConfig = TopicParser::parseTopic($complexTopic, 2);

    echo "✅ Parsed complex topic:\n";
    echo "  - Original: {$complexTopic}\n";
    echo "  - Extracted topic: {$parsedConfig->topic}\n";
    echo "  - QoS: {$parsedConfig->qos}\n";
    echo '  - Share topic enabled: ' . ($parsedConfig->enable_share_topic ? 'Yes' : 'No') . "\n";
} catch (Exception $e) {
    echo '❌ Topic parsing failed: ' . $e->getMessage() . "\n";
}

// 6. Circuit breaker status monitoring
$circuitBreakerStatus = $errorHandler->getCircuitBreakerStatus('mqtt_publish');
echo "🔌 Circuit Breaker Status for 'mqtt_publish':\n";
echo "  - State: {$circuitBreakerStatus['state']}\n";
echo "  - Failure count: {$circuitBreakerStatus['failure_count']}\n";

function formatBytes(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $pow = floor(log($bytes) / log(1024));
    return round($bytes / (1024 ** $pow), 2) . ' ' . $units[$pow];
}

echo "\n🎉 Robustness features demonstration completed!\n";
echo "\nKey benefits:\n";
echo "- ✅ Automatic configuration validation\n";
echo "- ✅ Error handling with exponential backoff\n";
echo "- ✅ Circuit breaker protection\n";
echo "- ✅ Health monitoring and metrics\n";
echo "- ✅ Topic sanitization and validation\n";
echo "- ✅ Memory leak prevention\n";
echo "- ✅ Production-ready resilience\n";
