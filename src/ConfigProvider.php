<?php

declare(strict_types=1);

namespace Nashgao\MQTT;

use Nashgao\MQTT\Listener\AfterWorkerStartListener;
use Nashgao\MQTT\Listener\OnDisconnectListener;
use Nashgao\MQTT\Listener\ServerIdListener;
use Nashgao\MQTT\Provider\ClientIdProviderInterface;
use Nashgao\MQTT\Provider\RandomClientIdProvider;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'listeners' => [
                OnDisconnectListener::class,
                AfterWorkerStartListener::class,
                ServerIdListener::class,
            ],
            'dependencies' => [
                ClientIdProviderInterface::class => RandomClientIdProvider::class,
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
