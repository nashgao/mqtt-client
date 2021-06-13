<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Pool;

use Hyperf\Di\Container;
use Hyperf\Di\Exception\NotFoundException;
use Psr\Container\ContainerInterface;

class PoolFactory
{
    protected ContainerInterface $container;

    /**
     * @var MQTTPool[]
     */
    protected array $pools = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @throws NotFoundException
     */
    public function getPool(string $name): MQTTPool
    {
        if (isset($this->pools[$name])) {
            return $this->pools[$name];
        }

        if ($this->container instanceof Container) {
            $pool = $this->container->make(MQTTPool::class, ['name' => $name]);
        } else {
            $pool = new MQTTPool($this->container, $name);
        }
        return $this->pools[$name] = $pool;
    }
}
