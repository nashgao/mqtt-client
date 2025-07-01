<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Config;

use Simps\MQTT\Config\ClientConfig as SimpsClientConfig;

readonly class ClientConfig
{
    public function __construct(
        public string $host,
        public int $port,
        public SimpsClientConfig $clientConfig,
        public array $subscribe = [],
        public array $publish = [],
        public bool $cleanSession = false,
        public array $will = [],
        public int $clientType = 1 // 1 means coroutine client
    ) {}
}
