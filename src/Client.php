<?php

declare(strict_types=1);

namespace Nashgao\MQTT;

use Hyperf\Context\Context;
use Nashgao\MQTT\Constants\MQTTConstants;
use Nashgao\MQTT\Exception\InvalidMethodException;
use Nashgao\MQTT\Exception\InvalidMQTTConnectionException;
use Nashgao\MQTT\Pool\PoolFactory;
use Swoole\Coroutine;

/**
 * @method subscribe(array $topics, array $properties = [])
 * @todo:verify the unsub function
 * @method unSubscribe(array $topics, array $properties = [])
 * @method publish(string $topic,string $message,int $qos = 0,int $dup = 0,int $retain = 0,array $properties = [])
 * @method multiSub(array $topics, array $properties, int $num = 2)
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
        $this->getConnection = function ($hasContextConnection, $name, $arguments): void {
            // check the available connection num
            $pool = $this->factory->getPool($this->poolName);
            if (($name === MQTTConstants::SUBSCRIBE or $name === MQTTConstants::MULTISUB) and $pool->getAvailableConnectionNum() < 2) {
                throw new \RuntimeException('Connection pool exhausted. Cannot establish new connection before wait_timeout.');
            }
            $connection = $this->getConnection($hasContextConnection)->getConnection();
            try {
                Coroutine::create(
                    function () use ($connection, $name, $arguments) {
                        /* @var MQTTConnection $connection */
                        $connection->{$name}(...$arguments);
                    }
                );
            } finally {
                if ($name === MQTTConstants::SUBSCRIBE) {
                    Coroutine::create(
                        function () use ($connection) {
                            for (;;) {
                                if (! $connection->receive()) {
                                    break;
                                }
                            }

                            $connection->close();
                            $connection->release();
                        }
                    );
                }
            }
        };
    }

    public function __call(string $name, mixed $arguments): void
    {
        if (! in_array($name, $this->methods())) {
            throw new InvalidMethodException(sprintf('method %s does not exist', $name));
        }

        $hasContextConnection = Context::has($this->getContextKey());
        if ($name = $name === MQTTConstants::MULTISUB ? MQTTConstants::SUBSCRIBE : $name) {
            $num = count($arguments) !== 3 ? 1 : last($arguments); // set multi sub default as 2
        }

        for ($count = 0; $count < ($num ?? 1); ++$count) {
            ($this->getConnection)($hasContextConnection, $name, $arguments);
        }
    }

    public function setPoolName(string $poolName): static
    {
        $this->poolName = $poolName;
        return $this;
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
