<?php

declare(strict_types=1);

namespace Nashgao\MQTT;

use Hyperf\Contract\ConnectionInterface;
use Hyperf\Pool\Connection as BaseConnection;
use Hyperf\Pool\Exception\ConnectionException;
use Hyperf\Pool\Pool;
use Nashgao\MQTT\Config\ClientConfig;
use Nashgao\MQTT\Exception\InvalidConfigException;
use Nashgao\MQTT\Provider\ClientIdProviderInterface;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @method subscribe(array $topics, array $properties = [])
 * @method unSubscribe(array $topics, array $properties = [])
 * @method publish(string $topic,string $message,int $qos = 0,int $dup = 0,int $retain = 0,array $properties = [])
 * @method multiSub(array $topics, array $properties = [], int $num = 2)
 * @method loop()
 * @method connect(bool $clean = true, array $will = [])
 * @method receive()
 */
class MQTTConnection extends BaseConnection implements ConnectionInterface
{
    protected ClientProxy $connection;

    protected EventDispatcherInterface $dispatcher;

    protected array $config = [];

    public function __construct(ContainerInterface $container, Pool $pool, array $config)
    {
        parent::__construct($container, $pool);
        $this->config = $config;
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
        $this->connection = $this->container->get(ClientFactory::class)
            ->create(
                new ClientConfig(
                    $this->config['host'],
                    (int)$this->config['port'],
                    $this->createSimpsClientConfig(),
                    $this->config['subscribe'],
                    $this->config['publish'],
                ),
                $this->pool->getName()
            );

        $this->lastUseTime = microtime(true);
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

    private function createSimpsClientConfig(): \Simps\MQTT\Config\ClientConfig
    {
        return (new \Simps\MQTT\Config\ClientConfig())
            ->setUserName($this->config['username'])
            ->setPassword($this->config['password'])
            ->setKeepAlive($this->config['keepalive'] ?? 0)
            ->setMaxAttempts($this->config['max_attempts'] ?? 3)
            ->setProtocolLevel($this->config['protocol_level'] ?? 5)
            ->setProperties($this->config['properties'])
            ->setClientId($this->container->get(ClientIdProviderInterface::class)->generate($this->config['prefix']) ?? '')
            ->setSwooleConfig($this->config['swoole_config'])
            ->setSockType((function () {
                return (isset($this->config['swoole_config']['ssl_enabled']) and $this->config['swoole_config']['ssl_enabled'])
                    ? SWOOLE_SOCK_TCP | SWOOLE_SSL
                    : SWOOLE_SOCK_TCP;
            })());
    }
}
