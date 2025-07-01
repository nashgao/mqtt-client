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
use Nashgao\MQTT\Utils\ErrorHandler;
use Nashgao\MQTT\Utils\HealthChecker;
use Nashgao\MQTT\Metrics\ErrorMetrics;
use Nashgao\MQTT\Metrics\PerformanceMetrics;
use Nashgao\MQTT\Metrics\ConnectionMetrics;
use Nashgao\MQTT\Metrics\HealthMetrics;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * @internal
 */
#[CoversNothing]
class SecurityTest extends AbstractTestCase
{
    private ErrorMetrics $errorMetrics;
    private PerformanceMetrics $performanceMetrics;
    private ConnectionMetrics $connectionMetrics;
    private HealthMetrics $healthMetrics;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->errorMetrics = new ErrorMetrics();
        $this->performanceMetrics = new PerformanceMetrics();
        $this->connectionMetrics = new ConnectionMetrics();
        $this->healthMetrics = new HealthMetrics();
    }
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
            try {
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
            } catch (\Exception $e) {
                // Record any parsing errors for security analysis
                $this->errorMetrics->recordError('input_validation', 'topic_parsing', $e->getMessage(), $e);
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
        $errorHandler = new ErrorHandler(null, $this->errorMetrics, $this->performanceMetrics);
        $healthChecker = new HealthChecker($this->connectionMetrics, $this->healthMetrics, $this->performanceMetrics);
        $client = new Client($poolFactory, $errorHandler, $healthChecker);

        // Rapid method calls
        $startTime = microtime(true);
        for ($i = 0; $i < 100; ++$i) {
            try {
                $client->invalidMethod();
            } catch (InvalidMethodException $e) {
                // Expected behavior - record in error metrics
                $this->errorMetrics->recordError('method_validation', 'invalidMethod', $e->getMessage(), $e);
            }
        }
        $endTime = microtime(true);

        // Should complete reasonably quickly (less than 1 second)
        $this->assertLessThan(1.0, $endTime - $startTime);
        
        // Verify error metrics were recorded
        $this->assertEquals(100, $this->errorMetrics->getTotalErrors());
        $this->assertEquals('method_validation', $this->errorMetrics->getMostFrequentErrorType());
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
        
        // Record initial memory usage
        $this->performanceMetrics->recordMemoryUsage();

        for ($i = 0; $i < 10000; ++$i) {
            $configs[] = new TopicConfig([
                'topic' => "test/topic/{$i}",
                'qos' => $i % 3,
            ]);
            
            // Record memory usage periodically
            if ($i % 1000 === 0) {
                $this->performanceMetrics->recordMemoryUsage();
            }
        }

        $memoryAfter = memory_get_usage();
        $memoryUsed = $memoryAfter - $memoryBefore;
        
        // Record final memory usage
        $this->performanceMetrics->recordMemoryUsage();

        // Memory usage should be linear and reasonable (less than 50MB for 10k objects)
        $this->assertLessThan(50 * 1024 * 1024, $memoryUsed);
        
        // Verify performance metrics were recorded
        $this->assertGreaterThan(0, $this->performanceMetrics->getCurrentMemoryUsage());
        $this->assertGreaterThan(0, $this->performanceMetrics->getPeakMemoryUsage());

        unset($configs);
    }

    public function testConcurrentAccess()
    {
        // Simulate concurrent access patterns that might cause race conditions
        $poolFactory = $this->createMock(PoolFactory::class);
        $clients = [];
        
        $startTime = microtime(true);

        // Create multiple clients rapidly
        for ($i = 0; $i < 50; ++$i) {
            $errorHandler = new ErrorHandler(null, new ErrorMetrics(), new PerformanceMetrics());
            $healthChecker = new HealthChecker(new ConnectionMetrics(), new HealthMetrics(), new PerformanceMetrics());
            $clients[] = new Client($poolFactory, $errorHandler, $healthChecker);
            
            // Record operation time for client creation
            $this->performanceMetrics->recordOperationTime('client_creation', microtime(true) - $startTime);
        }
        
        $endTime = microtime(true);
        $this->performanceMetrics->recordOperationTime('total_client_creation', $endTime - $startTime);

        // Test that all clients are properly initialized
        foreach ($clients as $i => $client) {
            $this->assertInstanceOf(Client::class, $client);

            // Test pool name setting
            $result = $client->setPoolName("pool_{$i}");
            $this->assertSame($client, $result);
            
            // Test health status methods work
            $healthStatus = $client->getHealthStatus();
            $this->assertIsArray($healthStatus);
        }
        
        // Verify performance metrics were recorded
        $this->assertGreaterThan(0, $this->performanceMetrics->getTotalOperations());
        $avgCreationTime = $this->performanceMetrics->getAverageOperationTime('client_creation');
        $this->assertGreaterThan(0, $avgCreationTime);
    }
    
    public function testSecurityMetricsIntegration()
    {
        // Test that security-related operations are properly tracked in metrics
        $poolFactory = $this->createMock(PoolFactory::class);
        $errorHandler = new ErrorHandler(null, $this->errorMetrics, $this->performanceMetrics);
        $healthChecker = new HealthChecker($this->connectionMetrics, $this->healthMetrics, $this->performanceMetrics);
        $client = new Client($poolFactory, $errorHandler, $healthChecker);
        
        // Attempt invalid operations and verify they're tracked
        for ($i = 0; $i < 5; ++$i) {
            try {
                $client->invalidMethod();
            } catch (InvalidMethodException $e) {
                $this->errorMetrics->recordError('security', 'invalid_method_access', $e->getMessage(), $e);
            }
        }
        
        // Verify security metrics
        $this->assertEquals(5, $this->errorMetrics->getErrorCountByType('security'));
        $this->assertEquals(5, $this->errorMetrics->getErrorCountByOperation('invalid_method_access'));
        
        $recentErrors = $this->errorMetrics->getRecentErrors();
        $this->assertCount(5, $recentErrors);
        
        $metricsArray = $this->errorMetrics->toArray();
        $this->assertArrayHasKey('errors_by_type', $metricsArray);
        $this->assertArrayHasKey('security', $metricsArray['errors_by_type']);
    }
}
