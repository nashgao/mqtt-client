<?php

declare(strict_types=1);

namespace Nashgao\MQTT;

use Hyperf\Utils\Context;
use Nashgao\MQTT\Constants\MQTTConstants;
use Nashgao\MQTT\Exception\InvalidMethodException;
use Nashgao\MQTT\Exception\InvalidMQTTConnectionException;
use Nashgao\MQTT\Pool\PoolFactory;
use Swoole\Coroutine;

/**
 * @method subscribe(array $topics, array $properties = [])
 * @method unSubscribe(array $topics, array $properties = [])
 * @method publish(string $topic,string $message,int $qos = 0,int $dup = 0,int $retain = 0,array $properties = [])
 * @method multiSub(array $topics, array $properties = [], int $num = 2)
 * @method connect(bool $clean, array $will = [])
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
                if ($name === MQTTConstants::SUBSCRIBE or $name === MQTTConstants::MULTISUB) {
                    Coroutine::create(
                        function () use ($hasContextConnection, $connection) {
                            for (;;) {
                                $connection->receive();
                                if (! $hasContextConnection) {
                                    $connection->release();
                                }
                            }
                        }
                    );
                } else {
                    if (! $hasContextConnection) {
                        $connection->release();
                    }
                }
            }

            return $result ?? null;
        };
    }

    public function __call($name, $arguments)
    {
        if (! in_array($name, $this->methods())) {
            throw new InvalidMethodException(sprintf('method %s does not exist', $name));
        }

        $result = null;
        $hasContextConnection = Context::has($this->getContextKey());

        $num = 1;
        if ($name === MQTTConstants::MULTISUB) {
            [$topics, $properties, $num] = $arguments;
            $num = $num < 1 ? 1 : $num;
            $name = MQTTConstants::SUBSCRIBE;
        }
        for ($count = 0; $count < $num; ++$count) {
            $result = ($this->getConnection)($hasContextConnection, $name, $arguments);
        }

        return $result ?? null;
    }

    private function methods(): array
    {
        return [
            MQTTConstants::SUBSCRIBE,
            MQTTConstants::MULTISUB,
            MQTTConstants::UNSUBSCRIBE,
            MQTTConstants::PUBLISH,
            MQTTConstants::CONNECT,
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
