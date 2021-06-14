<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Config;

use Simps\MQTT\Config\ClientConfig as SimpsClientConfig;

class ClientConfig
{
    public string $host;

    public int $port;

    public SimpsClientConfig $clientConfig;

    public array $subscribe;

    public array $publish;

    public bool $cleanSession;

    public array $will;

    public int $clientType;

    public function __construct(
        string $host,
        int $port,
        SimpsClientConfig $clientConfig,
        array $subscribe = [],
        array $publish = [],
        bool $cleanSession = false,
        array $will = [],
        int $clientType = 1 // 1 means coroutine client
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->clientConfig = $clientConfig;
        $this->subscribe = $subscribe;
        $this->publish = $publish;
        $this->cleanSession = $cleanSession;
        $this->will = $will;
        $this->clientType = $clientType;
    }
}
