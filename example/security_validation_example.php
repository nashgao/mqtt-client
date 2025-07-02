<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Nashgao\MQTT\Config\ClientConfig;
use Nashgao\MQTT\Config\TopicPublishConfig;
use Nashgao\MQTT\Exception\InvalidConfigException;
use Nashgao\MQTT\Metrics\ValidationMetrics;
use Nashgao\MQTT\Utils\ConfigValidator;

echo "🔒 Security & Validation Example\n";
echo str_repeat('=', 50) . "\n\n";

try {
    // 1. Initialize validation components
    echo "🔧 Initializing validation components...\n";
    $validator = new ConfigValidator();
    $validationMetrics = new ValidationMetrics();
    echo "   ✅ Validator and metrics initialized\n\n";

    // 2. Demonstrate client configuration validation
    echo "🔍 Testing client configuration validation...\n";

    // Valid configuration
    $simpsConfig = new Simps\MQTT\Config\ClientConfig();
    $simpsConfig->setHost('secure.mqtt.broker.com')
        ->setPort(8883)  // Secure port
        ->setClientId('secure_client_' . uniqid())
        ->setUserName('valid_user')
        ->setPassword('SecureP@ssw0rd123!')
        ->setTimeout(30);

    $validConfig = new ClientConfig(
        'secure.mqtt.broker.com',
        8883,
        $simpsConfig
    );

    $validationResult = $validator->validateClientConfig($validConfig);
    $validationMetrics->recordValidation('client_config', $validationResult['valid']);

    echo "   📋 Valid Configuration Test:\n";
    echo '      Result: ' . ($validationResult['valid'] ? '✅ Valid' : '❌ Invalid') . "\n";
    if (! empty($validationResult['errors'])) {
        foreach ($validationResult['errors'] as $error) {
            echo "      Error: {$error}\n";
        }
    }
    echo "\n";

    // Invalid configurations - create simple test configs for validation
    $invalidConfigs = [];
    $testCases = [
        'empty_host' => ['host' => '', 'port' => 1883, 'client_id' => 'test'],
        'invalid_port' => ['host' => 'test.com', 'port' => 99999, 'client_id' => 'test'],
        'empty_client_id' => ['host' => 'test.com', 'port' => 1883, 'client_id' => ''],
        'weak_password' => ['host' => 'test.com', 'port' => 1883, 'client_id' => 'test'],
    ];

    foreach ($testCases as $name => $testCase) {
        $testSimpsConfig = new Simps\MQTT\Config\ClientConfig();
        $testSimpsConfig->setHost($testCase['host'])
            ->setPort($testCase['port'])
            ->setClientId($testCase['client_id']);

        if ($name === 'weak_password') {
            $testSimpsConfig->setUserName('user')->setPassword('123');
        }

        $invalidConfigs[$name] = new ClientConfig(
            $testCase['host'],
            $testCase['port'],
            $testSimpsConfig
        );
    }

    foreach ($invalidConfigs as $testName => $config) {
        $result = $validator->validateClientConfig($config);
        $validationMetrics->recordValidation('client_config', $result['valid']);

        echo "   📋 {$testName} Test:\n";
        echo '      Result: ' . ($result['valid'] ? '✅ Valid' : '❌ Invalid') . "\n";
        if (! empty($result['errors'])) {
            foreach ($result['errors'] as $error) {
                echo "      Error: {$error}\n";
            }
        }
        echo "\n";
    }

    // 3. Topic validation and security
    echo "🏷️  Testing topic validation and security...\n";

    $topicTests = [
        'valid_sensor_topic' => 'sensors/temperature/room1',
        'valid_wildcard_subscription' => 'sensors/+/room1',
        'valid_multilevel_wildcard' => 'sensors/#',
        'invalid_empty_topic' => '',
        'invalid_null_bytes' => "sensors/temp\x00/room1",
        'invalid_unicode_injection' => 'sensors/temp/../../../etc/passwd',
        'valid_system_topic' => '$SYS/broker/load/messages/received',
        'potentially_dangerous_topic' => 'system/admin/delete/all',
    ];

    foreach ($topicTests as $testName => $topic) {
        $publishConfig = new TopicPublishConfig();
        $publishConfig->topic = $topic;
        $publishConfig->payload = 'test';
        $publishConfig->qos = 0;

        $result = $validator->validateTopicConfig($publishConfig);
        $validationMetrics->recordValidation('topic_config', $result['valid']);

        echo "   📋 {$testName}: '{$topic}'\n";
        echo '      Result: ' . ($result['valid'] ? '✅ Valid' : '❌ Invalid') . "\n";
        if (! empty($result['errors'])) {
            foreach ($result['errors'] as $error) {
                echo "      Error: {$error}\n";
            }
        }
        if (! empty($result['warnings'])) {
            foreach ($result['warnings'] as $warning) {
                echo "      Warning: {$warning}\n";
            }
        }
        echo "\n";
    }

    // 4. Payload validation and sanitization
    echo "📦 Testing payload validation and sanitization...\n";

    $payloadTests = [
        'valid_json' => json_encode(['temperature' => 23.5, 'unit' => 'C']),
        'valid_plain_text' => 'Temperature: 23.5°C',
        'valid_binary' => base64_encode('binary_data_here'),
        'large_payload' => str_repeat('x', 1024 * 1024), // 1MB
        'malicious_script' => '<script>alert("xss")</script>',
        'sql_injection' => "'; DROP TABLE users; --",
        'null_bytes' => "data\x00with\x00nulls",
        'unicode_test' => '温度: 23.5°C 🌡️',
    ];

    foreach ($payloadTests as $testName => $payload) {
        $publishConfig = new TopicPublishConfig();
        $publishConfig->topic = 'test/payload';
        $publishConfig->payload = $payload;
        $publishConfig->qos = 0;

        $result = $validator->validatePayload($publishConfig);
        $validationMetrics->recordValidation('payload_validation', $result['valid']);

        echo "   📋 {$testName}:\n";
        echo '      Size: ' . strlen($payload) . " bytes\n";
        echo '      Result: ' . ($result['valid'] ? '✅ Valid' : '❌ Invalid') . "\n";
        if (! empty($result['errors'])) {
            foreach ($result['errors'] as $error) {
                echo "      Error: {$error}\n";
            }
        }
        if (! empty($result['warnings'])) {
            foreach ($result['warnings'] as $warning) {
                echo "      Warning: {$warning}\n";
            }
        }
        if (isset($result['sanitized']) && $result['sanitized'] !== $payload) {
            echo "      Sanitized: Yes\n";
        }
        echo "\n";
    }

    // 5. SSL/TLS Configuration validation
    echo "🔐 Testing SSL/TLS configuration validation...\n";

    $sslConfigs = [
        'valid_ssl_config' => [
            'host' => 'secure.mqtt.broker.com',
            'port' => 8883,
            'ssl' => true,
            'cert_file' => '/path/to/client.crt',
            'key_file' => '/path/to/client.key',
            'ca_file' => '/path/to/ca.crt',
            'verify_peer' => true,
            'verify_host' => true,
        ],
        'insecure_ssl_config' => [
            'host' => 'test.broker.com',
            'port' => 8883,
            'ssl' => true,
            'verify_peer' => false,
            'verify_host' => false,
        ],
        'missing_certificates' => [
            'host' => 'secure.mqtt.broker.com',
            'port' => 8883,
            'ssl' => true,
        ],
    ];

    foreach ($sslConfigs as $testName => $sslConfig) {
        $result = $validator->validateSSLConfig($sslConfig);
        $validationMetrics->recordValidation('ssl_config', $result['valid']);

        echo "   📋 {$testName}:\n";
        echo '      Result: ' . ($result['valid'] ? '✅ Valid' : '❌ Invalid') . "\n";
        if (! empty($result['errors'])) {
            foreach ($result['errors'] as $error) {
                echo "      Error: {$error}\n";
            }
        }
        if (! empty($result['warnings'])) {
            foreach ($result['warnings'] as $warning) {
                echo "      Warning: {$warning}\n";
            }
        }
        echo "\n";
    }

    // 6. Authentication validation
    echo "🔑 Testing authentication validation...\n";

    $authTests = [
        'strong_credentials' => ['username' => 'admin_user', 'password' => 'StrongP@ssw0rd123!'],
        'weak_password' => ['username' => 'user', 'password' => '123'],
        'empty_credentials' => ['username' => '', 'password' => ''],
        'username_injection' => ['username' => 'admin\'; DROP TABLE users; --', 'password' => 'password'],
        'long_username' => ['username' => str_repeat('a', 256), 'password' => 'password'],
    ];

    foreach ($authTests as $testName => $credentials) {
        $result = $validator->validateCredentials($credentials['username'], $credentials['password']);
        $validationMetrics->recordValidation('auth_validation', $result['valid']);

        echo "   📋 {$testName}:\n";
        echo "      Username: '{$credentials['username']}'\n";
        echo '      Password: ' . (empty($credentials['password']) ? 'empty' : str_repeat('*', strlen($credentials['password']))) . "\n";
        echo '      Result: ' . ($result['valid'] ? '✅ Valid' : '❌ Invalid') . "\n";
        if (! empty($result['errors'])) {
            foreach ($result['errors'] as $error) {
                echo "      Error: {$error}\n";
            }
        }
        echo "\n";
    }

    // 7. Security recommendations
    echo "🛡️  Security Recommendations:\n";
    echo str_repeat('-', 40) . "\n";
    echo "1. ✅ Always use SSL/TLS for production (port 8883)\n";
    echo "2. ✅ Implement strong authentication (username/password or certificates)\n";
    echo "3. ✅ Validate all input data (topics, payloads, configuration)\n";
    echo "4. ✅ Use topic-based access control (ACLs)\n";
    echo "5. ✅ Monitor and log security events\n";
    echo "6. ✅ Limit payload sizes to prevent DoS attacks\n";
    echo "7. ✅ Sanitize data before processing\n";
    echo "8. ✅ Use secure client IDs (avoid predictable patterns)\n";
    echo "9. ✅ Implement rate limiting for connections and messages\n";
    echo "10. ✅ Regular security audits and updates\n\n";

    // 8. Validation metrics summary
    echo "📊 Validation Metrics Summary:\n";
    echo str_repeat('-', 40) . "\n";

    $validationTypes = ['client_config', 'topic_config', 'payload_validation', 'ssl_config', 'auth_validation'];
    foreach ($validationTypes as $type) {
        $stats = $validationMetrics->getValidationCount($type);
        $successRate = $stats['total_count'] > 0 ? round(($stats['success_count'] / $stats['total_count']) * 100, 1) : 0;
        echo "{$type}:\n";
        echo "  Total validations: {$stats['total_count']}\n";
        echo "  Successful: {$stats['success_count']}\n";
        echo "  Failed: {$stats['failure_count']}\n";
        echo "  Success rate: {$successRate}%\n\n";
    }

    echo "🔒 Security & Validation Example completed successfully!\n";
} catch (InvalidConfigException $e) {
    echo "❌ Configuration Error: {$e->getMessage()}\n";
} catch (Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n";
    echo "📋 Stack trace:\n{$e->getTraceAsString()}\n";
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "📚 Example: Security & Validation - Complete\n";
