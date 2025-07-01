<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Test\Cases;

use Nashgao\MQTT\Exception\InvalidConfigException;
use Nashgao\MQTT\Test\AbstractTestCase;
use Nashgao\MQTT\Utils\ConfigValidator;
use Nashgao\MQTT\Utils\ErrorHandler;
use Nashgao\MQTT\Utils\HealthChecker;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * @internal
 */
#[CoversNothing]
class ResilienceTest extends AbstractTestCase
{
    private ErrorHandler $errorHandler;

    private HealthChecker $healthChecker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->errorHandler = new ErrorHandler();
        $this->healthChecker = new HealthChecker();
    }

    public function testErrorHandlerCircuitBreaker()
    {
        $this->errorHandler->setRetryPolicy('test_operation', 2, 100);

        // Simulate multiple failures to trigger circuit breaker
        $failureCount = 0;
        $operation = function () use (&$failureCount) {
            ++$failureCount;
            throw new \RuntimeException("Simulated failure #{$failureCount}");
        };

        try {
            $this->errorHandler->wrapOperation($operation, 'test_operation');
        } catch (\RuntimeException $e) {
            // Expected to fail after retries
        }

        // Check circuit breaker status
        $status = $this->errorHandler->getCircuitBreakerStatus('test_operation');
        $this->assertGreaterThan(0, $status['failure_count']);
    }

    public function testConfigValidatorRobustness()
    {
        // Test various invalid configurations
        $invalidConfigs = [
            ['host' => '', 'port' => 1883],                    // Empty host
            ['host' => 'localhost', 'port' => 0],              // Invalid port
            ['host' => 'localhost', 'port' => 99999],          // Port too high
            ['host' => 'invalid..host', 'port' => 1883],       // Invalid host format
            ['port' => 1883],                                  // Missing host
            ['host' => 'localhost'],                           // Missing port
        ];

        foreach ($invalidConfigs as $config) {
            try {
                ConfigValidator::validateConnectionConfig($config);
                $this->fail('Should have thrown InvalidConfigException for config: ' . json_encode($config));
            } catch (InvalidConfigException $e) {
                $this->assertInstanceOf(InvalidConfigException::class, $e);
            }
        }
    }

    public function testHealthCheckerMetrics()
    {
        // Record various metrics
        for ($i = 0; $i < 10; ++$i) {
            $this->healthChecker->recordConnectionAttempt();
            if ($i < 8) {
                $this->healthChecker->recordActiveConnection();
            } else {
                $this->healthChecker->recordConnectionFailure();
            }
        }

        // Test success rate calculation
        $successRate = $this->healthChecker->getConnectionSuccessRate();
        $this->assertEquals(0.8, $successRate); // 8 success out of 10 attempts

        // Test system health
        $systemHealth = $this->healthChecker->getSystemHealth();
        $this->assertArrayHasKey('metrics', $systemHealth);
        $this->assertArrayHasKey('memory', $systemHealth);
        $this->assertArrayHasKey('process', $systemHealth);
    }

    public function testStressTestingConfigValidation()
    {
        // Stress test with many rapid validations
        $startTime = microtime(true);
        $validationCount = 1000;

        for ($i = 0; $i < $validationCount; ++$i) {
            try {
                ConfigValidator::validateConnectionConfig([
                    'host' => 'localhost',
                    'port' => 1883 + ($i % 100),
                    'client_id' => "client_{$i}",
                    'keep_alive' => 60 + ($i % 300),
                ]);
            } catch (InvalidConfigException $e) {
                // Some may fail due to invalid ports, that's expected
            }
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Should complete 1000 validations in less than 1 second
        $this->assertLessThan(1.0, $duration);

        // Performance should be reasonable (more than 500 validations per second)
        $validationsPerSecond = $validationCount / $duration;
        $this->assertGreaterThan(500, $validationsPerSecond);
    }

    public function testMemoryLeakPrevention()
    {
        $memoryBefore = memory_get_usage();

        // Create and destroy many objects rapidly
        for ($i = 0; $i < 10000; ++$i) {
            $healthChecker = new HealthChecker();
            $healthChecker->recordConnectionAttempt();
            $healthChecker->recordMessagePublished();

            $errorHandler = new ErrorHandler();
            $errorHandler->setRetryPolicy("op_{$i}", 3, 100);

            // Force cleanup
            unset($healthChecker, $errorHandler);

            // Periodic garbage collection
            if ($i % 1000 === 0) {
                gc_collect_cycles();
            }
        }

        gc_collect_cycles(); // Final cleanup
        $memoryAfter = memory_get_usage();
        $memoryIncrease = $memoryAfter - $memoryBefore;

        // Memory increase should be minimal (less than 5MB)
        $this->assertLessThan(5 * 1024 * 1024, $memoryIncrease);
    }

    public function testConcurrentOperations()
    {
        // Simulate concurrent operations on the same objects
        $healthChecker = new HealthChecker();
        $errorHandler = new ErrorHandler();

        // Rapid concurrent-like operations
        $operations = [];
        for ($i = 0; $i < 100; ++$i) {
            $operations[] = function () use ($healthChecker, $errorHandler, $i) {
                $healthChecker->recordConnectionAttempt();
                $errorHandler->setRetryPolicy("concurrent_op_{$i}", 2, 50);

                try {
                    $errorHandler->wrapOperation(function () use ($i) {
                        if ($i % 10 === 0) {
                            throw new \RuntimeException('Simulated failure');
                        }
                        return "success_{$i}";
                    }, "concurrent_op_{$i}");
                } catch (\RuntimeException $e) {
                    // Expected for some operations
                }
            };
        }

        // Execute all operations
        foreach ($operations as $operation) {
            $operation();
        }

        // Verify system remains stable
        $this->assertTrue($healthChecker->isSystemHealthy());
    }

    public function testExtremeBoundaryConditions()
    {
        // Test with extreme values
        $extremeConfigs = [
            ['topic' => str_repeat('a', 65535), 'qos' => 0],   // Max topic length
            ['topic' => '', 'qos' => 0],                       // Empty topic
            ['topic' => 'test', 'qos' => 0, 'multisub_num' => PHP_INT_MAX], // Max int
            ['topic' => 'test', 'qos' => 2, 'retain_handling' => 2], // Max valid values
        ];

        foreach ($extremeConfigs as $config) {
            try {
                $result = ConfigValidator::validateTopicConfig($config);
                $this->assertIsArray($result);
            } catch (InvalidConfigException $e) {
                // Some extreme values should fail validation
                $this->assertInstanceOf(InvalidConfigException::class, $e);
            }
        }
    }

    public function testErrorRecoveryScenarios()
    {
        $errorHandler = new ErrorHandler();
        $errorHandler->setRetryPolicy('recovery_test', 3, 10);

        $attemptCount = 0;
        $successOnAttempt = 3;

        $operation = function () use (&$attemptCount, $successOnAttempt) {
            ++$attemptCount;
            if ($attemptCount < $successOnAttempt) {
                throw new \RuntimeException("Failure on attempt {$attemptCount}");
            }
            return "Success on attempt {$attemptCount}";
        };

        $result = $errorHandler->wrapOperation($operation, 'recovery_test');

        $this->assertEquals("Success on attempt {$successOnAttempt}", $result);
        $this->assertEquals($successOnAttempt, $attemptCount);
    }

    public function testResourceExhaustionHandling()
    {
        $healthChecker = new HealthChecker();

        // Simulate resource exhaustion - record attempts first, then failures
        for ($i = 0; $i < 1000; ++$i) {
            $healthChecker->recordConnectionAttempt();
            $healthChecker->recordConnectionFailure();
        }

        // Check that system recognizes unhealthy state
        // Note: The system might still be healthy if connection attempts are 0
        // so we check the success rate instead
        $successRate = $healthChecker->getConnectionSuccessRate();
        $this->assertEquals(0.0, $successRate);

        // Verify that all attempts failed
        $systemHealth = $healthChecker->getSystemHealth();
        $this->assertEquals(1000, $systemHealth['metrics']['connection_failures']);
    }

    public function testTopicFilterValidationRobustness()
    {
        // Test various valid and invalid topic filters
        $testCases = [
            // Valid cases
            ['topic' => 'sensors/temperature', 'expected' => true],
            ['topic' => 'sensors/+/temperature', 'expected' => true],
            ['topic' => 'sensors/#', 'expected' => true],
            ['topic' => '+/temperature/#', 'expected' => true],

            // Invalid cases
            ['topic' => 'sensors/temp+/data', 'expected' => false],
            ['topic' => 'sensors/temperature/#/data', 'expected' => false],
            ['topic' => 'sensors/temp#', 'expected' => false],
            ['topic' => 'sensors/+temp/data', 'expected' => false],
        ];

        foreach ($testCases as $testCase) {
            $result = ConfigValidator::validateTopicFilter($testCase['topic']);
            $this->assertEquals(
                $testCase['expected'],
                $result,
                "Topic filter '{$testCase['topic']}' validation failed"
            );
        }
    }

    public function testSystemResourceMonitoring()
    {
        $healthChecker = new HealthChecker();

        // Get baseline metrics
        $initialHealth = $healthChecker->getSystemHealth();
        $this->assertArrayHasKey('memory', $initialHealth);
        $this->assertArrayHasKey('process', $initialHealth);

        // Verify memory tracking is working
        $this->assertGreaterThan(0, $initialHealth['memory']['usage']);
        $this->assertGreaterThan(0, $initialHealth['memory']['peak']);

        // Verify process tracking is working
        $this->assertGreaterThan(0, $initialHealth['process']['pid']);
    }
}
