<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Test\Cases;

use Nashgao\MQTT\Config\PoolConfig;
use Nashgao\MQTT\Config\TopicSubscription;
use Nashgao\MQTT\Config\TopicPublish;
use Nashgao\MQTT\Exception\InvalidConfigException;
use Nashgao\MQTT\Test\AbstractTestCase;
use Nashgao\MQTT\Utils\ConfigValidator;
use Nashgao\MQTT\Metrics\ValidationMetrics;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * @internal
 */
#[CoversNothing]
class PoolConfigTest extends AbstractTestCase
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
        try {
            ConfigValidator::setMetrics($this->validationMetrics);
        } catch (\Exception $e) {
            // Ignore
        }
        parent::tearDown();
    }

    public function testPoolConfigCreation()
    {
        $config = [
            'host' => 'localhost',
            'port' => 1883,
            'username' => 'testuser',
            'password' => 'testpass',
        ];

        $poolConfig = new PoolConfig('test-pool', $config);

        $this->assertEquals('test-pool', $poolConfig->getName());
        $this->assertEquals('localhost', $poolConfig->host);
        $this->assertEquals(1883, $poolConfig->port);
        $this->assertEquals('testuser', $poolConfig->username);
        $this->assertEquals('testpass', $poolConfig->password);
    }

    public function testPoolConfigWithPoolSettings()
    {
        $config = [
            'host' => 'localhost',
            'port' => 1883,
            'pool' => [
                'min_connections' => 2,
                'max_connections' => 20,
                'connect_timeout' => 15,
                'wait_timeout' => 5,
            ],
        ];

        $poolConfig = new PoolConfig('test-pool', $config);

        $this->assertEquals(2, $poolConfig->minConnections);
        $this->assertEquals(20, $poolConfig->maxConnections);
        $this->assertEquals(15, $poolConfig->connectTimeout);
        $this->assertEquals(5, $poolConfig->waitTimeout);
        
        $poolSettings = $poolConfig->getPoolSettings();
        $this->assertEquals(2, $poolSettings['min_connections']);
        $this->assertEquals(20, $poolSettings['max_connections']);
    }

    public function testPoolConfigWithSubscriptions()
    {
        $config = [
            'host' => 'localhost',
            'port' => 1883,
            'subscribe' => [
                'topics' => [
                    [
                        'topic' => 'test/topic1',
                        'qos' => 1,
                        'auto_subscribe' => true,
                    ],
                    [
                        'topic' => 'test/topic2',
                        'qos' => 2,
                        'auto_subscribe' => false,
                    ],
                ],
            ],
        ];

        $poolConfig = new PoolConfig('test-pool', $config);

        $this->assertTrue($poolConfig->hasSubscriptions());
        $this->assertEquals(2, $poolConfig->getSubscriptionConfig()->count());
        
        $autoSubscribeTopics = $poolConfig->getAutoSubscribeTopics();
        $this->assertCount(1, $autoSubscribeTopics);
        $this->assertEquals('test/topic1', $autoSubscribeTopics[0]->getTopic());
    }

    public function testPoolConfigFluentInterface()
    {
        $poolConfig = new PoolConfig('test-pool', ['host' => 'localhost', 'port' => 1883]);

        $result = $poolConfig
            ->setHost('mqtt.example.com')
            ->setPort(8883)
            ->setCredentials('user', 'pass')
            ->setPoolLimits(5, 50);

        $this->assertSame($poolConfig, $result);
        $this->assertEquals('mqtt.example.com', $poolConfig->host);
        $this->assertEquals(8883, $poolConfig->port);
        $this->assertEquals('user', $poolConfig->username);
        $this->assertEquals('pass', $poolConfig->password);
        $this->assertEquals(5, $poolConfig->minConnections);
        $this->assertEquals(50, $poolConfig->maxConnections);
    }

    public function testPoolConfigAddSubscription()
    {
        $poolConfig = new PoolConfig('test-pool', ['host' => 'localhost', 'port' => 1883]);

        $subscription = new TopicSubscription([
            'topic' => 'sensors/temperature',
            'qos' => 1,
            'auto_subscribe' => true,
        ]);

        $poolConfig->addSubscription($subscription);

        $this->assertTrue($poolConfig->hasSubscriptions());
        $this->assertEquals(1, $poolConfig->getSubscriptionConfig()->count());
    }

    public function testPoolConfigAddPublishTopic()
    {
        $poolConfig = new PoolConfig('test-pool', ['host' => 'localhost', 'port' => 1883]);

        $publish = new TopicPublish([
            'topic' => 'commands/device1',
            'qos' => 2,
            'retain' => true,
        ]);

        $poolConfig->addPublishTopic($publish);

        $this->assertEquals(1, $poolConfig->getPublishConfig()->count());
    }

    public function testPoolConfigValidation()
    {
        // Valid configuration
        $validConfig = new PoolConfig('test-pool', [
            'host' => 'localhost',
            'port' => 1883,
        ]);

        $this->assertTrue($validConfig->validate());
        $this->assertTrue($validConfig->isValid());

        // Invalid configuration
        $this->expectException(InvalidConfigException::class);
        new PoolConfig('invalid-pool', [
            'host' => '',  // Empty host should fail
            'port' => 1883,
        ]);
    }

    public function testPoolConfigToArray()
    {
        $config = [
            'host' => 'localhost',
            'port' => 1883,
            'username' => 'testuser',
            'pool' => [
                'min_connections' => 2,
                'max_connections' => 10,
            ],
            'subscribe' => [
                'topics' => [
                    [
                        'topic' => 'test/topic',
                        'qos' => 1,
                        'auto_subscribe' => true,
                    ],
                ],
            ],
        ];

        $poolConfig = new PoolConfig('test-pool', $config);
        $array = $poolConfig->toArray();

        $this->assertEquals('test-pool', $array['name']);
        $this->assertEquals('localhost', $array['connection']['host']);
        $this->assertEquals(1883, $array['connection']['port']);
        $this->assertEquals(2, $array['pool']['min_connections']);
        $this->assertEquals(10, $array['pool']['max_connections']);
        $this->assertArrayHasKey('subscribe', $array);
        $this->assertArrayHasKey('publish', $array);
    }

    public function testPoolConfigGetConnectionConfig()
    {
        $poolConfig = new PoolConfig('test-pool', [
            'host' => 'mqtt.example.com',
            'port' => 8883,
            'username' => 'user',
            'password' => 'pass',
            'keep_alive' => 120,
            'clean_session' => false,
        ]);

        $connectionConfig = $poolConfig->getConnectionConfig();

        $this->assertEquals('mqtt.example.com', $connectionConfig['host']);
        $this->assertEquals(8883, $connectionConfig['port']);
        $this->assertEquals('user', $connectionConfig['username']);
        $this->assertEquals('pass', $connectionConfig['password']);
        $this->assertEquals(120, $connectionConfig['keepalive']);
        $this->assertFalse($connectionConfig['clean_session']);
    }

    public function testPoolConfigCamelCaseConversion()
    {
        $config = [
            'host' => 'localhost',
            'port' => 1883,
            'pool' => [
                'min_connections' => 3,
                'max_connections' => 15,
                'connect_timeout' => 20,
                'wait_timeout' => 8,
                'max_idle_time' => 300,
            ],
        ];

        $poolConfig = new PoolConfig('test-pool', $config);

        // Test that snake_case pool settings are converted to camelCase properties
        $this->assertEquals(3, $poolConfig->minConnections);
        $this->assertEquals(15, $poolConfig->maxConnections);
        $this->assertEquals(20, $poolConfig->connectTimeout);
        $this->assertEquals(8, $poolConfig->waitTimeout);
        $this->assertEquals(300, $poolConfig->maxIdleTime);
    }

    public function testPoolConfigWithComplexSubscriptionConfig()
    {
        $config = [
            'host' => 'localhost',
            'port' => 1883,
            'subscribe' => [
                'global_option' => 'value',
                'topics' => [
                    [
                        'topic' => 'sensors/+/temperature',
                        'qos' => 1,
                        'auto_subscribe' => true,
                        'handler' => 'TemperatureHandler',
                        'metadata' => ['unit' => 'celsius'],
                    ],
                    [
                        'topic' => 'alerts/#',
                        'qos' => 2,
                        'auto_subscribe' => true,
                        'filter' => function($topic) { return $topic['qos'] >= 1; },
                    ],
                ],
            ],
        ];

        $poolConfig = new PoolConfig('test-pool', $config);
        $subscriptionConfig = $poolConfig->getSubscriptionConfig();

        $this->assertEquals(2, $subscriptionConfig->count());
        $this->assertEquals(['global_option' => 'value'], $subscriptionConfig->getGlobalOptions());
        
        $autoTopics = $poolConfig->getAutoSubscribeTopics();
        $this->assertCount(2, $autoTopics);
        
        // Test that handler and metadata are preserved
        $tempTopic = $autoTopics[0];
        $this->assertEquals('TemperatureHandler', $tempTopic->getHandler());
        $this->assertEquals(['unit' => 'celsius'], $tempTopic->getMetadata());
    }
}