<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Provider;

class RandomClientIdProvider implements ClientIdProviderInterface
{
    public function generate(string $prefix = null): string
    {
        $clientId = uniqid();
        return (isset($prefix) && ! empty($prefix)) ? $prefix . $clientId : $clientId;
    }
}
