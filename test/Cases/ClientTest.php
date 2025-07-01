<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Test\Cases;

use Hyperf\Contract\ConnectionInterface;
use Nashgao\MQTT\Client;
use Nashgao\MQTT\Exception\InvalidMethodException;
use Nashgao\MQTT\Exception\InvalidMQTTConnectionException;
use Nashgao\MQTT\MQTTConnection;
use Nashgao\MQTT\Pool\MQTTPool;
use Nashgao\MQTT\Pool\PoolFactory;
use Nashgao\MQTT\Test\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * @internal
 */
#[CoversNothing]
class ClientTest extends AbstractTestCase
{
    public function testClientCanBeInstantiated()
    {
        $poolFactory = $this->createMock(PoolFactory::class);
        $client = new Client($poolFactory);

        $this->assertInstanceOf(Client::class, $client);
    }

    public function testClientSetPoolName()
    {
        $poolFactory = $this->createMock(PoolFactory::class);
        $client = new Client($poolFactory);

        $result = $client->setPoolName('test-pool');

        $this->assertInstanceOf(Client::class, $result);
    }

    public function testInvalidMethodThrowsException()
    {
        $poolFactory = $this->createMock(PoolFactory::class);
        $client = new Client($poolFactory);

        $this->expectException(InvalidMethodException::class);
        $this->expectExceptionMessage('method invalidMethod does not exist');

        $client->invalidMethod();
    }

    public function testValidMethodsExist()
    {
        $poolFactory = $this->createMock(PoolFactory::class);
        $client = new Client($poolFactory);

        // Test that these methods exist in the allowed methods list
        $reflection = new \ReflectionClass($client);
        $method = $reflection->getMethod('methods');
        $method->setAccessible(true);
        $allowedMethods = $method->invoke($client);

        $this->assertTrue(in_array('subscribe', $allowedMethods));
        $this->assertTrue(in_array('publish', $allowedMethods));
        $this->assertTrue(in_array('unSubscribe', $allowedMethods));
        $this->assertTrue(in_array('connect', $allowedMethods));
        $this->assertTrue(in_array('multiSub', $allowedMethods));
    }

    public function testSubscribeWithInsufficientConnections()
    {
        $poolFactory = $this->createMock(PoolFactory::class);
        $pool = $this->createMock(MQTTPool::class);

        $poolFactory->method('getPool')->willReturn($pool);
        $pool->method('getAvailableConnectionNum')->willReturn(1); // Less than required 2

        $client = new Client($poolFactory);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Connection pool exhausted. Cannot establish new connection before wait_timeout.');

        $client->subscribe(['topic1' => ['qos' => 1]]);
    }

    public function testMultiSubWithInsufficientConnections()
    {
        $poolFactory = $this->createMock(PoolFactory::class);
        $pool = $this->createMock(MQTTPool::class);

        $poolFactory->method('getPool')->willReturn($pool);
        $pool->method('getAvailableConnectionNum')->willReturn(1); // Less than required 2

        $client = new Client($poolFactory);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Connection pool exhausted. Cannot establish new connection before wait_timeout.');

        $client->multiSub(['topic1' => ['qos' => 1]], []);
    }

    public function testInvalidConnectionThrowsException()
    {
        $poolFactory = $this->createMock(PoolFactory::class);
        $pool = $this->createMock(MQTTPool::class);

        $poolFactory->method('getPool')->willReturn($pool);
        // Return a connection that's not an instance of MQTTConnection
        $invalidConnection = $this->createMock(ConnectionInterface::class);
        $pool->method('get')->willReturn($invalidConnection);
        $pool->method('getAvailableConnectionNum')->willReturn(5);

        $client = new Client($poolFactory);

        $this->expectException(InvalidMQTTConnectionException::class);
        $this->expectExceptionMessage('invalid mqtt connection');

        $client->connect(true, []);
    }
}
