<?php

declare(strict_types=1);

namespace Nashgao\MQTT;

use Hyperf\Utils\Context;
use Nashgao\MQTT\Exception\InvalidMethodException;
use Nashgao\MQTT\Exception\InvalidMQTTConnectionException;
use Nashgao\MQTT\Pool\PoolFactory;

/**
 * @method subscribe(array $topics, array $properties = [])
 * @method unSubscribe(array $topics, array $properties = [])
 * @method publish(string $topic,string $message,int $qos = 0,int $dup = 0,int $retain = 0,array $properties = [])
 * @method multiSub(array $topics, array $properties = [], int $num = 2)
 * @method receive()
 */
class Client
{
    protected PoolFactory $factory;

    protected string $poolName = 'default';

    protected \Closure $getConnection;

    public function __construct(PoolFactory $factory)
    {
        $this->factory = $factory;
        $this->getConnection = function ($hasContextConnection, $name, $arguments) {
            $connection = $this->getConnection($hasContextConnection);
            try {
                /** @var MQTTConnection $connection */
                $connection = $connection->getConnection();
                $result = $connection->{$name}(...$arguments);
            } finally {
                if (! $hasContextConnection) {
                    $connection->release();
                }
            }

            return $result;
        };
    }

    public function __call($name, $arguments)
    {
        if (! in_array($name, $this->methods())) {
            throw new InvalidMethodException();
        }

        $result = null;
        $hasContextConnection = Context::has($this->getContextKey());

        $num = 1;
        if ($name === 'multiSub') {
            [$topics, $properties, $num] = $arguments;
            $num = $num < 1 ? 1 : $num;
            $name = 'subscribe';
        }
        for ($count = 0; $count < $num; ++$count) {
            $result = ($this->getConnection)($hasContextConnection, $name, $arguments);
        }

        return $result;
    }

    private function methods(): array
    {
        return [
            'subscribe',
            'unsubscribe',
            'publish',
            'receive',
            'multiSub',
        ];
    }

    private function getConnection($hasContextConnection): MQTTConnection
    {
        $connection = null;
        if ($hasContextConnection) {
            $connection = Context::get($this->getContextKey());
        }
        if (! $connection instanceof MQTTConnection) {
            $pool = $this->factory->getPool($this->poolName);
            $connection = $pool->get();
        }

        if (! $connection instanceof MQTTConnection) {
            throw new InvalidMQTTConnectionException('invalid mqtt connection');
        }

        return $connection;
    }

    private function getContextKey(): string
    {
        return sprintf('mqtt.connection.%s', $this->poolName);
    }
}
