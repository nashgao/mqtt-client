<?php

declare(strict_types=1);

/**
 * Pool Management Example
 * 
 * This example demonstrates:
 * - MQTT connection pool configuration
 * - Pool lifecycle management
 * - Load balancing across connections
 * - Pool health monitoring
 * - Resilience and failover
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Nashgao\MQTT\Pool\MQTTPool;
use Nashgao\MQTT\Pool\PoolFactory;
use Nashgao\MQTT\Config\PoolConfig;
use Nashgao\MQTT\Config\ClientConfig;
use Nashgao\MQTT\Config\TopicPublishConfig;
use Nashgao\MQTT\Config\TopicSubscriptionConfig;
use Nashgao\MQTT\Utils\HealthChecker;

echo "🏊 MQTT Pool Management Example\n";
echo str_repeat("=", 50) . "\n\n";

try {
    // 1. Create pool configuration
    echo "📝 Creating pool configuration...\n";
    $poolConfig = new PoolConfig('main_pool', [
        'min_connections' => 2,
        'max_connections' => 5,
        'max_idle_time' => 300,
        'connection_timeout' => 30,
        'health_check_interval' => 60,
        'retry_attempts' => 3,
        'retry_delay' => 5
    ]);

    echo "   ✅ Pool ID: {$poolConfig->name}\n";
    echo "   ✅ Min connections: {$poolConfig->minConnections}\n";
    echo "   ✅ Max connections: {$poolConfig->maxConnections}\n";
    echo "   ✅ Health check interval: {$poolConfig->healthCheckInterval}s\n\n";

    // 2. Create client configurations for the pool
    echo "🔧 Creating client configurations...\n";
    $clientConfigs = [];
    
    for ($i = 1; $i <= 3; $i++) {
        $simpsConfig = new \Simps\MQTT\Config\ClientConfig();
        $simpsConfig->setHost('test.mosquitto.org')
                    ->setPort(1883)
                    ->setClientId("pool_client_{$i}_" . uniqid())
                    ->setTimeout(30);
        
        $clientConfig = new ClientConfig(
            'test.mosquitto.org',
            1883,
            $simpsConfig
        );
        
        $clientConfigs[] = $clientConfig;
        echo "   ✅ Client {$i}: {$simpsConfig->getClientId()}\n";
    }
    echo "\n";

    // 3. Create and initialize pool
    echo "🏊 Creating MQTT connection pool...\n";
    $pool = PoolFactory::create($poolConfig);
    
    // Add client configurations to pool
    foreach ($clientConfigs as $config) {
        $pool->addClientConfig($config);
    }
    
    echo "   ✅ Pool created with " . count($clientConfigs) . " client configurations\n\n";

    // 4. Initialize pool connections
    echo "🔌 Initializing pool connections...\n";
    $pool->initialize();
    
    $poolStats = $pool->getPoolStats();
    echo "   ✅ Active connections: {$poolStats['active_connections']}\n";
    echo "   ✅ Available connections: {$poolStats['available_connections']}\n";
    echo "   ✅ Pool utilization: " . round($poolStats['utilization'] * 100, 1) . "%\n\n";

    // 5. Demonstrate load balancing with publishing
    echo "📤 Testing load balancing with publishing...\n";
    $messages = [
        ['topic' => 'pool/test/sensor1', 'payload' => json_encode(['temperature' => 22.5])],
        ['topic' => 'pool/test/sensor2', 'payload' => json_encode(['humidity' => 67.3])],
        ['topic' => 'pool/test/sensor3', 'payload' => json_encode(['pressure' => 1013.2])],
        ['topic' => 'pool/test/status', 'payload' => json_encode(['status' => 'online'])],
        ['topic' => 'pool/test/heartbeat', 'payload' => json_encode(['timestamp' => time()])],
    ];

    foreach ($messages as $i => $message) {
        $publishConfig = new TopicPublishConfig();
        $publishConfig->setTopic($message['topic'])
                     ->setPayload($message['payload'])
                     ->setQos(1);
        
        $client = $pool->getConnection();
        if ($client) {
            $client->publish($publishConfig);
            echo "   ✅ Message " . ($i + 1) . " published to {$message['topic']}\n";
            $pool->releaseConnection($client);
        } else {
            echo "   ❌ No available connection for message " . ($i + 1) . "\n";
        }
        
        usleep(200000); // 0.2 second delay
    }
    echo "\n";

    // 6. Monitor pool health
    echo "💚 Monitoring pool health...\n";
    $healthChecker = new HealthChecker();
    
    // Simulate health checks
    for ($i = 1; $i <= 5; $i++) {
        $healthResult = $healthChecker->checkPoolHealth($pool);
        echo "   📊 Health check #{$i}: ";
        
        if ($healthResult['status'] === 'healthy') {
            echo "✅ Healthy";
        } else {
            echo "⚠️  {$healthResult['status']}";
        }
        
        echo " (Score: {$healthResult['score']}%)\n";
        
        if (!empty($healthResult['issues'])) {
            foreach ($healthResult['issues'] as $issue) {
                echo "      🔍 Issue: {$issue}\n";
            }
        }
        
        usleep(500000); // 0.5 second delay
    }
    echo "\n";

    // 7. Test connection resilience
    echo "🛡️  Testing connection resilience...\n";
    
    // Simulate connection failure and recovery
    echo "   🔴 Simulating connection failure...\n";
    $connection = $pool->getConnection();
    if ($connection) {
        // Simulate disconnection
        try {
            $connection->disconnect();
            echo "   ✅ Connection disconnected (simulated failure)\n";
        } catch (Exception $e) {
            echo "   ℹ️  Connection already disconnected\n";
        }
        
        // Pool should handle the failed connection
        $pool->handleConnectionFailure($connection);
        echo "   ✅ Pool handled connection failure\n";
    }
    
    // Pool should create new connection
    echo "   🔄 Pool attempting recovery...\n";
    $pool->maintainPool();
    
    $recoveryStats = $pool->getPoolStats();
    echo "   📊 Post-recovery stats:\n";
    echo "      Active connections: {$recoveryStats['active_connections']}\n";
    echo "      Available connections: {$recoveryStats['available_connections']}\n";
    echo "      Failed connections: {$recoveryStats['failed_connections']}\n\n";

    // 8. Subscription management across pool
    echo "📥 Testing subscription management...\n";
    $subscriptionTopics = [
        'pool/test/+',
        'pool/status/#',
        'pool/alerts/+'
    ];

    foreach ($subscriptionTopics as $topic) {
        $subscriptionConfig = new TopicSubscriptionConfig();
        $subscriptionConfig->setTopic($topic)->setQos(1);
        
        $client = $pool->getConnection();
        if ($client) {
            $client->subscribe($subscriptionConfig);
            echo "   ✅ Subscribed to {$topic}\n";
            $pool->releaseConnection($client);
        }
    }
    echo "\n";

    // 9. Display final pool statistics
    echo "📊 Final Pool Statistics:\n";
    echo str_repeat("-", 30) . "\n";
    $finalStats = $pool->getPoolStats();
    
    echo "Pool Configuration:\n";
    echo "  Pool ID: {$poolConfig->getPoolId()}\n";
    echo "  Min/Max Connections: {$poolConfig->getMinConnections()}/{$poolConfig->getMaxConnections()}\n";
    echo "  Connection Timeout: {$poolConfig->getConnectionTimeout()}s\n";
    echo "\nCurrent Status:\n";
    echo "  Active Connections: {$finalStats['active_connections']}\n";
    echo "  Available Connections: {$finalStats['available_connections']}\n";
    echo "  Failed Connections: {$finalStats['failed_connections']}\n";
    echo "  Total Requests: {$finalStats['total_requests']}\n";
    echo "  Pool Utilization: " . round($finalStats['utilization'] * 100, 1) . "%\n";
    echo "  Uptime: " . round($finalStats['uptime']) . " seconds\n";

    // 10. Cleanup
    echo "\n🧹 Cleaning up pool...\n";
    $pool->shutdown();
    echo "   ✅ Pool shutdown complete\n";

    echo "\n🏊 Pool Management Example completed successfully!\n";

} catch (Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n";
    echo "📋 Stack trace:\n{$e->getTraceAsString()}\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "📚 Example: Pool Management - Complete\n";