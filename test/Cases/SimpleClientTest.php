<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Test\Cases;

use Nashgao\MQTT\Client;
use Nashgao\MQTT\Exception\InvalidMethodException;
use Nashgao\MQTT\Pool\PoolFactory;
use Nashgao\MQTT\Test\AbstractTestCase;
use Nashgao\MQTT\Utils\ErrorHandler;
use Nashgao\MQTT\Utils\HealthChecker;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * @internal
 */
#[CoversNothing]
class SimpleClientTest extends AbstractTestCase
{
    public function testClientCanBeInstantiated()
    {
        $poolFactory = $this->createMock(PoolFactory::class);
        $errorHandler = $this->createMock(ErrorHandler::class);
        $healthChecker = $this->createMock(HealthChecker::class);
        $client = new Client($poolFactory, $errorHandler, $healthChecker);

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

    public function testFluentInterface()
    {
        $poolFactory = $this->createMock(PoolFactory::class);
        $client = new Client($poolFactory);

        $result = $client->setPoolName('pool1')->setPoolName('pool2');

        $this->assertInstanceOf(Client::class, $result);
    }
}
