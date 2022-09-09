<?php

declare(strict_types=1);

namespace Nashgao\MQTT;

use Nashgao\MQTT\Listener\AfterWorkerStartListener;
use Nashgao\MQTT\Listener\OnDisconnectListener;
use Nashgao\MQTT\Listener\OnPublishListener;
use Nashgao\MQTT\Listener\OnSubscribeListener;
use Nashgao\MQTT\Listener\PublishListener;
use Nashgao\MQTT\Listener\ServerIdListener;
use Nashgao\MQTT\Listener\SubscribeListener;
use Nashgao\MQTT\Provider\ClientIdProviderInterface;
use Nashgao\MQTT\Provider\RandomClientIdProvider;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'listeners' => [
                OnSubscribeListener::class,
                OnPublishListener::class,
                OnDisconnectListener::class,
                SubscribeListener::class,
                PublishListener::class,
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
