<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Provider;

interface ClientIdProviderInterface
{
    public function generate(?string $prefix = null): string;
}
