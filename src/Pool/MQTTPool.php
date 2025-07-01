<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Pool;

use Hyperf\Collection\Arr;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\ConnectionInterface;
use Hyperf\Pool\Pool;
use Nashgao\MQTT\Exception\InvalidConfigException;
use Nashgao\MQTT\Frequency;
use Nashgao\MQTT\MQTTConnection;
use Nashgao\MQTT\Utils\ConfigValidator;
use Psr\Container\ContainerInterface;

use function Hyperf\Support\make;

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

        // Validate pool configuration
        $options = Arr::get($this->config, 'pool', []);
        $validatedOptions = ConfigValidator::validatePoolConfig($options);

        $this->frequency = make(Frequency::class, [$this]);
        parent::__construct($container, $validatedOptions);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAvailableConnectionNum(): int
    {
        return $this->option->getMaxConnections() - $this->currentConnections;
    }

    protected function createConnection(): ConnectionInterface
    {
        return new MQTTConnection($this->container, $this, $this->config);
    }
}
