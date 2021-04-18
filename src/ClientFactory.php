<?php

declare(strict_types=1);

namespace Nashgao\MQTT;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Utils\ApplicationContext;
use Simps\MQTT\Client;
use Simps\MQTT\Config\ClientConfig;

class ClientFactory
{
    public static function createClient(): Client
    {
        $config = ApplicationContext::getContainer()->get(ConfigInterface::class);
        $mqttConfig = $config->get('mqtt.client.connect');
        $swooleConfig = $config->get('mqtt.client.settings');
        return new Client(
            $mqttConfig['host'],
            $mqttConfig['port'],
            make(ClientConfig::class)
                ->setUserName($mqttConfig['username'])
                ->setPassword($mqttConfig['password'])
                ->setKeepAlive(array_key_exists('keepalive', $mqttConfig) ? $mqttConfig['keepalive'] : 0)
                ->setSwooleConfig($swooleConfig)
                ->setMaxAttempts(array_key_exists('max_attempts', $mqttConfig) ? $mqttConfig['max_attempts'] : 3)
                ->setClientId(static::getClientId())
                ->setProtocolLevel(5)
                ->setProperties([
                    'session_expiry_interval' => 60,
                    'receive_maximum' => 65535,
                    'topic_alias_maximum' => 65535,
                ])
                ->setSockType(SWOOLE_SOCK_TCP | SWOOLE_SSL)
        );
    }

    public static function getClientId(string $prefix = 'mqtt'): string
    {
        return uniqid($prefix);
    }
}
