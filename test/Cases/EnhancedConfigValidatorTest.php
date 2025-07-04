<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Test\Cases;

use Nashgao\MQTT\Exception\InvalidConfigException;
use Nashgao\MQTT\Metrics\ValidationMetrics;
use Nashgao\MQTT\Test\AbstractTestCase;
use Nashgao\MQTT\Utils\ConfigValidator;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * @internal
 */
#[CoversNothing]
class EnhancedConfigValidatorTest extends AbstractTestCase
{
    private ValidationMetrics $validationMetrics;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validationMetrics = new ValidationMetrics();
        ConfigValidator::setMetrics($this->validationMetrics);
    }

    protected function tearDown(): void
    {
        // Reset to avoid affecting other tests
        try {
            ConfigValidator::setMetrics($this->validationMetrics);
        } catch (\Exception $e) {
            // Ignore if already null
        }
        parent::tearDown();
    }

    public function testEnhancedValidatorBackwardCompatibility()
    {
        // Test that enhanced validator works exactly like the original
        $validConfig = [
            'host' => 'localhost',
            'port' => 1883,
            'client_id' => 'test_client',
            'keep_alive' => 60,
        ];

        $result = ConfigValidator::validateConnectionConfig($validConfig);
        $this->assertEquals($validConfig, $result);

        // Verify metrics were recorded
        $count = $this->validationMetrics->getValidationCount('connection_config');
        $this->assertEquals(1, $count['total']);
        $this->assertEquals(1, $count['successful']);
        $this->assertEquals(0, $count['failed']);
    }

    public function testEnhancedValidatorWithoutExternalLibrary()
    {
        // Test enhanced validator behavior when no external library is set
        $validConfig = [
            'host' => '192.168.1.100',
            'port' => 1883,
        ];

        $result = ConfigValidator::validateConnectionConfig($validConfig);
        $this->assertEquals($validConfig, $result);

        // Should work with built-in validation only
        $stats = ConfigValidator::getValidationStats();
        $this->assertEquals('built-in', $stats['external_validator']['type']);
        $this->assertFalse($stats['external_validator']['enabled']);
    }

    public function testFactoryMethods()
    {
        // Test factory methods (they should not fail even if libraries aren't installed)
        $validator1 = ConfigValidator::withRespectValidation();
        $this->assertInstanceOf(ConfigValidator::class, $validator1);

        $validator2 = ConfigValidator::withValitronValidation();
        $this->assertInstanceOf(ConfigValidator::class, $validator2);

        $validator3 = ConfigValidator::withRakitValidation();
        $this->assertInstanceOf(ConfigValidator::class, $validator3);
    }

    public function testExternalValidatorConfiguration()
    {
        // Test setting external validator
        $mockValidator = new \stdClass();
        ConfigValidator::setExternalValidator($mockValidator, 'mock');

        $stats = ConfigValidator::getValidationStats();
        $this->assertEquals('mock', $stats['external_validator']['type']);
        $this->assertTrue($stats['external_validator']['enabled']);
        $this->assertEquals('stdClass', $stats['external_validator']['class']);
    }

    public function testInvalidConfigurationWithEnhancedValidator()
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessageMatches('/Enhanced validation failed/');

        ConfigValidator::validateConnectionConfig([
            'host' => '',  // Invalid empty host
            'port' => 1883,
        ]);
    }

    public function testTopicConfigValidationEnhanced()
    {
        $validConfig = [
            'topic' => 'test/topic',
            'qos' => 1,
            'retain_handling' => 2,
            'multisub_num' => 5,
        ];

        $result = ConfigValidator::validateTopicConfig($validConfig);
        $this->assertEquals($validConfig, $result);

        // Verify metrics were recorded
        $count = $this->validationMetrics->getValidationCount('topic_config');
        $this->assertEquals(1, $count['total']);
        $this->assertEquals(1, $count['successful']);
        $this->assertEquals(0, $count['failed']);
    }

    public function testInvalidTopicConfigurationEnhanced()
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessageMatches('/Enhanced topic validation failed/');

        ConfigValidator::validateTopicConfig([
            'topic' => 'test/topic',
            'qos' => 5,  // Invalid QoS
        ]);
    }

    public function testExternalValidationWithUnknownValidator()
    {
        // Set an unknown validator type
        ConfigValidator::setExternalValidator(new \stdClass(), 'unknown');

        // Should still work with just our built-in validation
        $validConfig = [
            'host' => 'localhost',
            'port' => 1883,
        ];

        $result = ConfigValidator::validateConnectionConfig($validConfig);
        $this->assertEquals($validConfig, $result);
    }

    public function testValidationStatsIntegration()
    {
        // Perform some validations
        ConfigValidator::validateConnectionConfig(['host' => 'localhost', 'port' => 1883]);
        ConfigValidator::validateTopicConfig(['qos' => 1]);

        try {
            ConfigValidator::validateConnectionConfig(['host' => '', 'port' => 1883]);
        } catch (InvalidConfigException $e) {
            // Expected failure
        }

        // Test validation statistics
        $stats = ConfigValidator::getValidationStats();
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('external_validator', $stats);
        $this->assertArrayHasKey('type', $stats['external_validator']);
        $this->assertArrayHasKey('enabled', $stats['external_validator']);

        // Should have recorded multiple validations
        $this->assertEquals(3, $this->validationMetrics->getTotalValidations());
        $this->assertEquals(1, $this->validationMetrics->getTotalErrors());
    }

    public function testPerformanceMetricsCollection()
    {
        $startTime = microtime(true);

        // Perform multiple validations to test performance tracking
        for ($i = 0; $i < 10; ++$i) {
            ConfigValidator::validateConnectionConfig([
                'host' => 'localhost',
                'port' => 1883,
                'client_id' => "client_{$i}",
            ]);
        }

        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;

        // Enhanced validation should be reasonably fast (< 100ms for 10 validations)
        $this->assertLessThan(0.1, $totalTime);

        // Should have recorded all validations
        $this->assertEquals(10, $this->validationMetrics->getValidationCount('connection_config')['total']);
        $this->assertEquals(10, $this->validationMetrics->getValidationCount('connection_config')['successful']);
    }

    public function testMQTTSpecificValidationRules()
    {
        // Test that MQTT-specific rules are preserved in enhanced validator

        // Valid MQTT-specific values
        $mqttConfigs = [
            ['qos' => 0],  // Valid QoS
            ['qos' => 1],  // Valid QoS
            ['qos' => 2],  // Valid QoS
            ['retain_handling' => 0],
            ['retain_handling' => 1],
            ['retain_handling' => 2],
            ['multisub_num' => 1],
            ['multisub_num' => 10],
        ];

        foreach ($mqttConfigs as $config) {
            $result = ConfigValidator::validateTopicConfig($config);
            $this->assertEquals($config, $result);
        }

        // Invalid MQTT-specific values should fail
        $invalidConfigs = [
            ['qos' => 3],     // Invalid QoS
            ['qos' => -1],    // Invalid QoS
            ['retain_handling' => 3],  // Invalid retain handling
            ['multisub_num' => 0],     // Invalid multisub number
            ['multisub_num' => -5],    // Invalid multisub number
        ];

        foreach ($invalidConfigs as $config) {
            try {
                ConfigValidator::validateTopicConfig($config);
                $this->fail('Expected InvalidConfigException for config: ' . json_encode($config));
            } catch (InvalidConfigException $e) {
                $this->assertInstanceOf(InvalidConfigException::class, $e);
            }
        }
    }

    public function testClientIdLengthValidation()
    {
        // Test MQTT-specific client ID length validation (23 characters max)
        $validClientId = str_repeat('a', 23);  // Exactly 23 chars
        $config = ['host' => 'localhost', 'port' => 1883, 'client_id' => $validClientId];

        $result = ConfigValidator::validateConnectionConfig($config);
        $this->assertEquals($config, $result);

        // Test invalid client ID (too long)
        $this->expectException(InvalidConfigException::class);
        $tooLongClientId = str_repeat('a', 24);  // 24 characters, exceeds limit
        ConfigValidator::validateConnectionConfig([
            'host' => 'localhost',
            'port' => 1883,
            'client_id' => $tooLongClientId,
        ]);
    }

    public function testIntegrationWithMetrics()
    {
        // Reset metrics for clean test
        $this->validationMetrics->reset();

        // Perform various enhanced validations
        ConfigValidator::validateConnectionConfig(['host' => 'localhost', 'port' => 1883]);
        ConfigValidator::validateTopicConfig(['qos' => 1]);

        try {
            ConfigValidator::validateTopicConfig(['qos' => 5]);  // Invalid
        } catch (InvalidConfigException $e) {
            // Expected
        }

        // Verify metrics integration
        $this->assertEquals(3, $this->validationMetrics->getTotalValidations());
        $this->assertEquals(1, $this->validationMetrics->getTotalErrors());
        $this->assertGreaterThan(0.6, $this->validationMetrics->getOverallSuccessRate());
        $this->assertLessThan(1.0, $this->validationMetrics->getOverallSuccessRate());

        // Test validation success rates by type
        $connectionRate = $this->validationMetrics->getValidationSuccessRate('connection_config');
        $topicRate = $this->validationMetrics->getValidationSuccessRate('topic_config');

        $this->assertEquals(1.0, $connectionRate);  // Connection validation succeeded
        $this->assertEquals(0.5, $topicRate);      // 1 success, 1 failure = 50%
    }
}
