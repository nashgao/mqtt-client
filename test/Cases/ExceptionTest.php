<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Test\Cases;

use Nashgao\MQTT\Exception\InvalidConfigException;
use Nashgao\MQTT\Exception\InvalidMethodException;
use Nashgao\MQTT\Exception\InvalidMQTTConnectionException;
use Nashgao\MQTT\Test\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * @internal
 */
#[CoversNothing]
class ExceptionTest extends AbstractTestCase
{
    public function testInvalidConfigException()
    {
        $message = 'Configuration is invalid';
        $code = 100;
        $previous = new \Exception('Previous exception');

        $exception = new InvalidConfigException($message, $code, $previous);

        $this->assertInstanceOf(InvalidConfigException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testInvalidMethodException()
    {
        $message = 'Method does not exist';
        $code = 200;

        $exception = new InvalidMethodException($message, $code);

        $this->assertInstanceOf(InvalidMethodException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
    }

    public function testInvalidMQTTConnectionException()
    {
        $message = 'MQTT connection is invalid';
        $code = 300;
        $previous = new \RuntimeException('Connection failed');

        $exception = new InvalidMQTTConnectionException($message, $code, $previous);

        $this->assertInstanceOf(InvalidMQTTConnectionException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testExceptionsWithDefaultValues()
    {
        $configException = new InvalidConfigException();
        $methodException = new InvalidMethodException();
        $connectionException = new InvalidMQTTConnectionException();

        $this->assertEquals('', $configException->getMessage());
        $this->assertEquals(0, $configException->getCode());

        $this->assertEquals('', $methodException->getMessage());
        $this->assertEquals(0, $methodException->getCode());

        $this->assertEquals('', $connectionException->getMessage());
        $this->assertEquals(0, $connectionException->getCode());
    }
}
