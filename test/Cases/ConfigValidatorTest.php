<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Test\Cases;

use Nashgao\MQTT\Exception\InvalidConfigException;
use Nashgao\MQTT\Test\AbstractTestCase;
use Nashgao\MQTT\Utils\ConfigValidator;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * @internal
 */
#[CoversNothing]
class ConfigValidatorTest extends AbstractTestCase
{
    public function testValidConnectionConfig()
    {
        $validConfig = [
            'host' => 'localhost',
            'port' => 1883,
            'client_id' => 'test_client',
            'keep_alive' => 60,
        ];

        $result = ConfigValidator::validateConnectionConfig($validConfig);
        $this->assertEquals($validConfig, $result);
    }

    public function testInvalidConnectionConfigMissingHost()
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessageMatches('/Required field.*host.*missing/');

        ConfigValidator::validateConnectionConfig([
            'port' => 1883,
        ]);
    }

    public function testInvalidConnectionConfigInvalidPort()
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessageMatches('/Invalid port.*99999/');

        ConfigValidator::validateConnectionConfig([
            'host' => 'localhost',
            'port' => 99999,
        ]);
    }

    public function testInvalidConnectionConfigInvalidHost()
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessageMatches('/Invalid host/');

        ConfigValidator::validateConnectionConfig([
            'host' => 'invalid..host..name',
            'port' => 1883,
        ]);
    }

    public function testValidTopicConfig()
    {
        $validConfig = [
            'topic' => 'test/topic',
            'qos' => 1,
            'retain_handling' => 2,
            'multisub_num' => 5,
        ];

        $result = ConfigValidator::validateTopicConfig($validConfig);
        $this->assertEquals($validConfig, $result);
    }

    public function testInvalidTopicConfigInvalidQos()
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessageMatches('/Invalid QoS level.*3/');

        ConfigValidator::validateTopicConfig([
            'topic' => 'test/topic',
            'qos' => 3,
        ]);
    }

    public function testInvalidTopicConfigInvalidRetainHandling()
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessageMatches('/Invalid retain_handling.*5/');

        ConfigValidator::validateTopicConfig([
            'topic' => 'test/topic',
            'retain_handling' => 5,
        ]);
    }

    public function testInvalidTopicConfigInvalidMultisubNum()
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessageMatches('/Invalid multisub_num.*positive integer/');

        ConfigValidator::validateTopicConfig([
            'topic' => 'test/topic',
            'multisub_num' => -1,
        ]);
    }

    public function testValidPoolConfig()
    {
        $validConfig = [
            'min_connections' => 1,
            'max_connections' => 10,
        ];

        $result = ConfigValidator::validatePoolConfig($validConfig);
        $this->assertEquals($validConfig, $result);
    }

    public function testInvalidPoolConfigMaxLessThanMin()
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessageMatches('/max_connections.*min_connections/');

        ConfigValidator::validatePoolConfig([
            'min_connections' => 10,
            'max_connections' => 5,
        ]);
    }

    public function testSanitizeTopicName()
    {
        // Test removal of control characters
        $dirtyTopic = "test\x00topic\x01with\x02control\x03chars";
        $clean = ConfigValidator::sanitizeTopicName($dirtyTopic);
        $this->assertEquals('testtopicwithcontrolchars', $clean);

        // Test trimming whitespace
        $topic = '  test/topic  ';
        $clean = ConfigValidator::sanitizeTopicName($topic);
        $this->assertEquals('test/topic', $clean);

        // Test normal topic remains unchanged
        $topic = 'sensors/temperature/room1';
        $clean = ConfigValidator::sanitizeTopicName($topic);
        $this->assertEquals($topic, $clean);
    }

    public function testValidateTopicFilter()
    {
        // Valid topic filters
        $validFilters = [
            'sensors/temperature',
            'sensors/+/temperature',
            'sensors/#',
            'sensors/+/temperature/#',
            '+/temperature',
            '#',
        ];

        foreach ($validFilters as $filter) {
            $this->assertTrue(ConfigValidator::validateTopicFilter($filter), "Filter '{$filter}' should be valid");
        }
    }

    public function testInvalidTopicFilters()
    {
        // Invalid topic filters
        $invalidFilters = [
            'sensors/temp+/data',     // + not alone in level
            'sensors/temperature/#/data', // # not at end
            'sensors/temp#',          // # not alone in level
            'sensors/+temp/data',     // + not alone in level
        ];

        foreach ($invalidFilters as $filter) {
            $this->assertFalse(ConfigValidator::validateTopicFilter($filter), "Filter '{$filter}' should be invalid");
        }
    }

    public function testHostValidation()
    {
        $validHosts = [
            'localhost',
            '127.0.0.1',
            '192.168.1.1',
            'mqtt.example.com',
            'test-server.local',
        ];

        foreach ($validHosts as $host) {
            $config = ['host' => $host, 'port' => 1883];
            $result = ConfigValidator::validateConnectionConfig($config);
            $this->assertEquals($config, $result);
        }
    }

    public function testPortValidation()
    {
        $validPorts = [1, 1883, 8883, 65535];

        foreach ($validPorts as $port) {
            $config = ['host' => 'localhost', 'port' => $port];
            $result = ConfigValidator::validateConnectionConfig($config);
            $this->assertEquals($config, $result);
        }

        $invalidPorts = [0, -1, 65536, 99999];

        foreach ($invalidPorts as $port) {
            $this->expectException(InvalidConfigException::class);
            ConfigValidator::validateConnectionConfig(['host' => 'localhost', 'port' => $port]);
        }
    }

    public function testClientIdValidation()
    {
        // Valid client ID (within 23 character limit)
        $validClientId = 'client123';
        $config = ['host' => 'localhost', 'port' => 1883, 'client_id' => $validClientId];
        $result = ConfigValidator::validateConnectionConfig($config);
        $this->assertEquals($config, $result);

        // Invalid client ID (too long)
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessageMatches('/Invalid client_id/');

        $tooLongClientId = str_repeat('a', 24); // 24 characters, exceeds limit
        ConfigValidator::validateConnectionConfig([
            'host' => 'localhost',
            'port' => 1883,
            'client_id' => $tooLongClientId,
        ]);
    }

    public function testKeepAliveValidation()
    {
        // Valid keep alive values
        $validKeepAlives = [0, 60, 300, 65535];

        foreach ($validKeepAlives as $keepAlive) {
            $config = ['host' => 'localhost', 'port' => 1883, 'keep_alive' => $keepAlive];
            $result = ConfigValidator::validateConnectionConfig($config);
            $this->assertEquals($config, $result);
        }

        // Invalid keep alive values
        $this->expectException(InvalidConfigException::class);
        ConfigValidator::validateConnectionConfig([
            'host' => 'localhost',
            'port' => 1883,
            'keep_alive' => 65536,
        ]);
    }
}
