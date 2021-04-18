<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Pool;

use Hyperf\Contract\ConnectionInterface;
use Hyperf\Pool\Connection as BaseConnection;
use Hyperf\Pool\Exception\ConnectionException;
use Hyperf\Pool\Pool;
use Psr\Container\ContainerInterface;
use Swoole\Coroutine\Client as SwooleClient;

class MQTTConnection extends BaseConnection implements ConnectionInterface
{
    protected SwooleClient $connection;

    protected array $config = [
        'connect_timeout' => 5.0,
        'settings' => [],
    ];

    public function __construct(ContainerInterface $container, Pool $pool, array $config)
    {
        parent::__construct($container, $pool);
        $this->config = array_replace($this->config, $config);

        $this->reconnect();
    }

    public function __call($name, $arguments)
    {
        return $this->connection->{$name}(...$arguments);
    }

    public function __get($name)
    {
        return $this->connection->{$name};
    }

    /**
     * @throws ConnectionException
     */
    public function getActiveConnection(): MQTTConnection
    {
        if ($this->check()) {
            return $this;
        }

        if (! $this->reconnect()) {
            throw new ConnectionException('reconnect failed');
        }

        return $this;
    }

    public function reconnect(): bool
    {
    }

    public function close(): bool
    {
        $this->lastUseTime = 0.0;
        $this->connection->close();
        return true;
    }

    public function resetLastUseTime(): void
    {
        $this->lastUseTime = 0.0;
    }
}
