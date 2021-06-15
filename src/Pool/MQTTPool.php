<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Pool;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\ConnectionInterface;
use Hyperf\Pool\Pool;
use Hyperf\Utils\Arr;
use Nashgao\MQTT\Exception\InvalidConfigException;
use Nashgao\MQTT\Frequency;
use Nashgao\MQTT\MQTTConnection;
use Psr\Container\ContainerInterface;

class MQTTPool extends Pool
{
    protected string $name;

    protected array $config;

    public function __construct(ContainerInterface $container, string $name)
    {
        $this->name = $name;
        $config = $container->get(ConfigInterface::class);
        $key = sprintf('mqtt.%s', $this->name);
        if (! $config->has($key)) {
            throw new InvalidConfigException(sprintf('config[%s] does not exist', $key));
        }

        $this->config = $config->get($key);
        $options = Arr::get($this->config, 'pool', []);
        $this->frequency = make(Frequency::class, [$this]);
        parent::__construct($container, $options);
    }

    public function getName(): string
    {
        return $this->name;
    }

    protected function createConnection(): ConnectionInterface
    {
        return new MQTTConnection($this->container, $this, $this->config);
    }
}