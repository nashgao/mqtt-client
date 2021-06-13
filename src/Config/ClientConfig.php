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

    public int $clientType;

    /**
     * ClientConfig constructor.
     */
    public function __construct(
        string $host,
        int $port,
        SimpsClientConfig $clientConfig,
        array $subscribe = [],
        array $publish = [],
        int $clientType = null
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->clientConfig = $clientConfig;
        $this->subscribe = $subscribe;
        $this->publish = $publish;
        if (isset($clientType) and is_int($clientType)) {
            $this->clientType = $clientType;
        }
    }
}
