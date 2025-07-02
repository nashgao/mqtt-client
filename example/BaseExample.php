<?php

declare(strict_types=1);

namespace Simps\MQTT\Example;

abstract class BaseExample
{
    public function execute(): void
    {
        $this->loadBootstrap();
        $this->run([$this, 'main']);
    }

    protected function run(callable $callback): void
    {
        \Swoole\Coroutine\run($callback);
    }

    protected function loadBootstrap(): void
    {
        require_once dirname(__DIR__) . '/bootstrap.php';
    }

    abstract protected function main(): void;
}
