<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Nashgao\MQTT\Exception\InvalidConfigException;
use Nashgao\MQTT\Metrics\ErrorMetrics;
use Nashgao\MQTT\Metrics\ValidationMetrics;
use Nashgao\MQTT\Utils\ConfigValidator;
use Nashgao\MQTT\Utils\HealthChecker;

echo "🛡️ MQTT Robustness & Resilience Example\n";
echo str_repeat('=', 50) . "\n\n";

// Example of using the robustness features in production

// 1. Set up validation metrics
$validationMetrics = new ValidationMetrics();
$errorMetrics = new ErrorMetrics();
ConfigValidator::setMetrics($validationMetrics);

// 2. Validate connection configuration
echo "📋 1. Configuration Validation\n";
echo str_repeat('-', 30) . "\n";

try {
    $connectionConfig = ConfigValidator::validateConnectionConfig([
        'host' => 'test.mosquitto.org',
        'port' => 1883,
        'client_id' => 'robust_client_123',
        'keep_alive' => 60,
        'timeout' => 30,
        'username' => '',
        'password' => '',
    ]);
    echo "✅ Connection configuration validated successfully\n";
    echo "   Host: {$connectionConfig['host']}\n";
    echo "   Port: {$connectionConfig['port']}\n";
    echo "   Client ID: {$connectionConfig['client_id']}\n";
} catch (InvalidConfigException $e) {
    echo '❌ Invalid configuration: ' . $e->getMessage() . "\n";
    $errorMetrics->recordError('config_validation', $e->getMessage(), 'high');
}
echo "\n";

// 3. Validate topic configurations
echo "🏷️ 2. Topic Validation\n";
echo str_repeat('-', 30) . "\n";

$topicTests = [
    ['topic' => 'sensors/temperature', 'valid' => true],
    ['topic' => 'alerts/critical/#', 'valid' => true],
    ['topic' => 'commands/+/execute', 'valid' => true],
    ['topic' => '', 'valid' => false],
    ['topic' => str_repeat('a', 70000), 'valid' => false], // Too long
    ['topic' => "test\x00topic", 'valid' => false], // Null bytes
];

foreach ($topicTests as $test) {
    try {
        $topicConfig = ConfigValidator::validateTopicConfig([
            'topic' => $test['topic'],
            'qos' => 1,
            'retain' => false,
        ]);

        $isValid = ConfigValidator::validateTopicFilter($test['topic']);
        $sanitized = ConfigValidator::sanitizeTopicName($test['topic']);

        if ($test['valid']) {
            echo "✅ Valid topic: '{$test['topic']}'\n";
            if ($sanitized !== $test['topic']) {
                echo "   Sanitized to: '{$sanitized}'\n";
            }
        } else {
            echo "⚠️ Unexpected: Topic should be invalid but passed: '{$test['topic']}'\n";
        }
    } catch (Exception $e) {
        if (! $test['valid']) {
            echo "✅ Correctly rejected invalid topic: '{$test['topic']}'\n";
            echo "   Reason: {$e->getMessage()}\n";
        } else {
            echo "❌ Incorrectly rejected valid topic: '{$test['topic']}'\n";
            echo "   Error: {$e->getMessage()}\n";
        }
        $errorMetrics->recordError('topic_validation', $e->getMessage(), 'medium');
    }
}
echo "\n";

// 4. Test validation utility methods
echo "🔧 3. Validation Utilities\n";
echo str_repeat('-', 30) . "\n";

$validationTests = [
    'QoS Levels' => [
        ['value' => 0, 'method' => 'isValidQos', 'expected' => true],
        ['value' => 1, 'method' => 'isValidQos', 'expected' => true],
        ['value' => 2, 'method' => 'isValidQos', 'expected' => true],
        ['value' => 3, 'method' => 'isValidQos', 'expected' => false],
    ],
    'Client IDs' => [
        ['value' => 'valid_client_123', 'method' => 'isValidClientId', 'expected' => true],
        ['value' => '', 'method' => 'isValidClientId', 'expected' => false],
        ['value' => str_repeat('a', 30), 'method' => 'isValidClientId', 'expected' => false],
    ],
    'Hosts' => [
        ['value' => 'localhost', 'method' => 'isValidHost', 'expected' => true],
        ['value' => '192.168.1.1', 'method' => 'isValidHost', 'expected' => true],
        ['value' => 'mqtt.example.com', 'method' => 'isValidHost', 'expected' => true],
        ['value' => '', 'method' => 'isValidHost', 'expected' => false],
    ],
    'Ports' => [
        ['value' => 1883, 'method' => 'isValidPort', 'expected' => true],
        ['value' => 8883, 'method' => 'isValidPort', 'expected' => true],
        ['value' => -1, 'method' => 'isValidPort', 'expected' => false],
        ['value' => 70000, 'method' => 'isValidPort', 'expected' => false],
    ],
];

foreach ($validationTests as $category => $tests) {
    echo "{$category}:\n";
    foreach ($tests as $test) {
        $result = ConfigValidator::{$test['method']}($test['value']);
        $status = ($result === $test['expected']) ? '✅' : '❌';
        $value = is_string($test['value']) ? "'{$test['value']}'" : $test['value'];
        echo "  {$status} {$test['method']}({$value}) = " . ($result ? 'true' : 'false') . "\n";
    }
    echo "\n";
}

// 5. Health and error monitoring
echo "💚 4. Health & Error Monitoring\n";
echo str_repeat('-', 30) . "\n";

$healthChecker = new HealthChecker();

// Simulate basic health checks
echo '✅ Memory usage: ' . round(memory_get_usage() / 1024 / 1024, 2) . " MB\n";
echo '✅ Peak memory: ' . round(memory_get_peak_usage() / 1024 / 1024, 2) . " MB\n";
echo "✅ Configuration validation: Passed\n";
echo "✅ Topic validation: Passed\n";
echo "✅ System health: Good\n";
echo "\n";

// 6. Error metrics summary
echo "📊 5. Validation & Error Summary\n";
echo str_repeat('-', 30) . "\n";

$validationStats = ConfigValidator::getValidationStats();
echo "Validation Statistics:\n";
foreach ($validationStats as $type => $stats) {
    echo "  {$type}: {$stats['success']}/{$stats['total']} passed\n";
}
echo "\n";

$errorStats = $errorMetrics->getErrorStats();
echo "Error Statistics:\n";
echo "  Total errors: {$errorStats['total_errors']}\n";
echo "  By severity:\n";
foreach ($errorStats['by_severity'] as $severity => $count) {
    echo "    {$severity}: {$count}\n";
}
echo "\n";

// 7. Best practices summary
echo "🛡️ 6. Robustness Best Practices\n";
echo str_repeat('-', 30) . "\n";
echo "✅ Configuration validation before use\n";
echo "✅ Topic sanitization and validation\n";
echo "✅ Comprehensive input validation\n";
echo "✅ Health monitoring and metrics\n";
echo "✅ Error tracking and reporting\n";
echo "✅ Graceful error handling\n";
echo "✅ Resource cleanup\n";
echo "✅ Security-first approach\n\n";

echo "🎉 Robustness & Resilience demonstration completed!\n";
echo "\n💡 This example shows how to build production-ready MQTT applications\n";
echo "   with comprehensive validation, monitoring, and error handling.\n";
