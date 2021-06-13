<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Config;

use Simps\MQTT\Config\ClientConfig as SimpsClientConfig;

class ClientConfig
{
    public string $host;

    public int $port;

    public SimpsClientConfig $clientConfig;

    public int $clientType;

    /**
     * ClientConfig constructor.
     */
    public function __construct(string $host, int $port, SimpsClientConfig $clientConfig, int $clientType)
    {
        $this->host = $host;
        $this->port = $port;
        $this->clientConfig = $clientConfig;
        $this->clientType = $clientType;
    }
}
