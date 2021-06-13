<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Pool;

use Hyperf\Contract\ConnectionInterface;
use Hyperf\Pool\Pool;
use Nashgao\MQTT\Frequency;
use Nashgao\MQTT\MQTTConnection;
use Psr\Container\ContainerInterface;

class MQTTPool extends Pool
{
    protected string $name;

    protected array $config;

    public function __construct(ContainerInterface $container, string $name, array $config)
    {
        $this->name = $name;
        $this->config = $config;
        $options = $config['pool'] ?? [];
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
