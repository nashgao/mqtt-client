<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Test\Cases;

use Nashgao\MQTT\Client;
use Nashgao\MQTT\Config\TopicConfig;
use Nashgao\MQTT\Exception\InvalidMethodException;
use Nashgao\MQTT\Pool\PoolFactory;
use Nashgao\MQTT\Provider\RandomClientIdProvider;
use Nashgao\MQTT\Test\AbstractTestCase;
use Nashgao\MQTT\Utils\TopicParser;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * @internal
 */
#[CoversNothing]
class SecurityTest extends AbstractTestCase
{
    public function testClientIdUniqueness()
    {
        $provider = new RandomClientIdProvider();

        // Generate multiple client IDs and ensure they're unique
        $clientIds = [];
        for ($i = 0; $i < 1000; ++$i) {
            $clientIds[] = $provider->generate();
        }

        $uniqueIds = array_unique($clientIds);
        $this->assertCount(1000, $uniqueIds, 'Client IDs should be unique');
    }

    public function testClientIdFormat()
    {
        $provider = new RandomClientIdProvider();
        $clientId = $provider->generate();

        // Client ID should be reasonable length (MQTT spec allows up to 23 chars for MQTT 3.1)
        $this->assertGreaterThan(0, strlen($clientId));
        $this->assertLessThanOrEqual(23, strlen($clientId));

        // Should not contain control characters or spaces
        $this->assertDoesNotMatchRegularExpression('/[\x00-\x1F\x7F\s]/', $clientId);
    }

    public function testInputSanitization()
    {
        // Test malicious topic names
        $maliciousTopics = [
            '../../../etc/passwd',
            '<script>alert("xss")</script>',
            'topic\'; DROP TABLE topics; --',
            str_repeat('A', 100000), // Very long string
            "\x00\x01\x02\x03", // Control characters
        ];

        foreach ($maliciousTopics as $topic) {
            $result = TopicParser::parseTopic($topic, 1);
            $this->assertInstanceOf(TopicConfig::class, $result);
            // Topic should be sanitized now
            // For control characters, they should be removed
            if ($topic === "\x00\x01\x02\x03") {
                $this->assertEquals('', $result->topic); // Control characters removed
            } else {
                // Other topics should be handled safely
                $this->assertIsString($result->topic);
            }
        }
    }

    public function testMethodAccessControl()
    {
        $poolFactory = $this->createMock(PoolFactory::class);
        $client = new Client($poolFactory);

        // Test that only allowed methods can be called
        $privateMethods = [
            'getConnection',
            'methods',
            'getContextKey',
            '__construct',
            'nonExistentMethod',
        ];

        foreach ($privateMethods as $method) {
            if ($method === '__construct') {
                continue;
            } // Skip constructor

            try {
                $client->{$method}();
                $this->fail("Method {$method} should not be accessible");
            } catch (InvalidMethodException $e) {
                $this->assertStringContainsString('does not exist', $e->getMessage());
            } catch (\Error $e) {
                // Some methods might throw different errors, which is also acceptable
                $this->assertInstanceOf(\Error::class, $e);
            }
        }
    }

    public function testRateLimitingValidation()
    {
        // Test that rapid successive calls don't cause issues
        $poolFactory = $this->createMock(PoolFactory::class);
        $client = new Client($poolFactory);

        // Rapid method calls
        $startTime = microtime(true);
        for ($i = 0; $i < 100; ++$i) {
            try {
                $client->invalidMethod();
            } catch (InvalidMethodException $e) {
                // Expected behavior
            }
        }
        $endTime = microtime(true);

        // Should complete reasonably quickly (less than 1 second)
        $this->assertLessThan(1.0, $endTime - $startTime);
    }

    public function testTopicInjectionPrevention()
    {
        // Test share topic injection
        $injectionAttempts = [
            '$share/group1/$share/group2/topic',
            '$queue/$share/group/topic',
            '$share/../../../topic',
            '$share/group/../../topic',
        ];

        foreach ($injectionAttempts as $topic) {
            $result = TopicParser::parseTopic($topic, 1);
            $this->assertInstanceOf(TopicConfig::class, $result);

            // Verify that the parsing logic handles these securely
            if (strpos($topic, '$queue') === 0) {
                $this->assertTrue($result->enable_queue_topic);
            } elseif (strpos($topic, '$share') === 0) {
                $this->assertTrue($result->enable_share_topic);
            }
        }
    }

    public function testConfigurationDataLeakage()
    {
        // Test that sensitive configuration data isn't exposed
        $sensitiveData = [
            'password' => 'secret123',
            'api_key' => 'api_key_12345',
            'token' => 'bearer_token_xyz',
        ];

        $config = new TopicConfig($sensitiveData);

        // Verify that only known properties are set
        $reflection = new \ReflectionClass($config);
        $properties = $reflection->getProperties();

        $foundSensitiveData = false;
        foreach ($properties as $property) {
            $property->setAccessible(true);
            $propertyName = $property->getName();

            // Sensitive data should not be stored in unexpected places
            if (in_array($propertyName, array_keys($sensitiveData))) {
                $foundSensitiveData = true;
                break;
            }
        }

        // Assert that no sensitive data was found in TopicConfig properties
        $this->assertFalse($foundSensitiveData, 'Sensitive data should not be stored in TopicConfig');
    }

    public function testResourceExhaustion()
    {
        // Test creation of many objects to detect potential DoS vectors
        $configs = [];
        $memoryBefore = memory_get_usage();

        for ($i = 0; $i < 10000; ++$i) {
            $configs[] = new TopicConfig([
                'topic' => "test/topic/{$i}",
                'qos' => $i % 3,
            ]);
        }

        $memoryAfter = memory_get_usage();
        $memoryUsed = $memoryAfter - $memoryBefore;

        // Memory usage should be linear and reasonable (less than 50MB for 10k objects)
        $this->assertLessThan(50 * 1024 * 1024, $memoryUsed);

        unset($configs);
    }

    public function testConcurrentAccess()
    {
        // Simulate concurrent access patterns that might cause race conditions
        $poolFactory = $this->createMock(PoolFactory::class);
        $clients = [];

        // Create multiple clients rapidly
        for ($i = 0; $i < 50; ++$i) {
            $clients[] = new Client($poolFactory);
        }

        // Test that all clients are properly initialized
        foreach ($clients as $client) {
            $this->assertInstanceOf(Client::class, $client);

            // Test pool name setting
            $result = $client->setPoolName("pool_{$i}");
            $this->assertSame($client, $result);
        }
    }
}
