<?php

declare(strict_types=1);

namespace Nashgao\MQTT;

use Nashgao\MQTT\Listener\OnDisconnectListener;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'listeners' => [
                OnDisconnectListener::class,
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for mqtt.',
                    'source' => __DIR__ . '/../publish/mqtt.php',
                    'destination' => BASE_PATH . '/config/autoload/mqtt.php',
                ],
            ],
        ];
    }
}
