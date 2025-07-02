<?php

declare(strict_types=1);

namespace Simps\MQTT\Config;

trait ConfigDefaults
{
    protected static function getDefaultPoolConfig(): array
    {
        return [
            'min_connections' => 1,
            'max_connections' => 10,
            'connect_timeout' => 10.0,
            'wait_timeout' => 20.0,
            'heartbeat' => -1,
            'max_idle_time' => 60,
        ];
    }

    protected static function getDefaultSwooleConfig(): array
    {
        return [
            'open_mqtt_protocol' => true,
            'package_max_length' => 2 * 1024 * 1024,
            'connect_timeout' => 10.0,
            'write_timeout' => 10.0,
            'read_timeout' => 1.0,
        ];
    }

    protected static function getDefaultConfig(): array
    {
        return [
            'pool' => self::getDefaultPoolConfig(),
            'swoole' => self::getDefaultSwooleConfig(),
        ];
    }
}
