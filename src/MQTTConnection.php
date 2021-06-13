<?php

declare(strict_types=1);

namespace Nashgao\MQTT;

use Hyperf\Contract\ConnectionInterface;
use Hyperf\Pool\Connection as BaseConnection;
use Hyperf\Pool\Exception\ConnectionException;
use Hyperf\Pool\Pool;
use Nashgao\MQTT\Config\ClientConfig;
use Nashgao\MQTT\Exception\InvalidConfigException;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Swoole\Coroutine\Channel;

class MQTTConnection extends BaseConnection implements ConnectionInterface
{
    protected Channel $channel;

    protected ClientProxy $connection;

    protected EventDispatcherInterface $dispatcher;

    protected array $config = [
        'connect_timeout' => 5.0,
        'settings' => [],
    ];

    public function __construct(ContainerInterface $container, Pool $pool, array $config)
    {
        parent::__construct($container, $pool);
        $this->config = array_replace_recursive($this->config, $config) ?? [];
        if (empty($this->config)) {
            throw new InvalidConfigException();
        }
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
        $this->connection = new ClientProxy(new ClientConfig(...$this->config));
        return true;
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
