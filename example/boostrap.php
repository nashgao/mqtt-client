<?php

declare(strict_types=1);

use Hyperf\Config\Config;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Container;
use Hyperf\Di\Definition\DefinitionSourceFactory;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Event\ListenerProvider;
use Hyperf\Context\ApplicationContext;
use Nashgao\MQTT\Listener\OnDisconnectListener;
use Nashgao\MQTT\Listener\OnPublishListener;
use Nashgao\MQTT\Listener\OnSubscribeListener;
use Nashgao\MQTT\Listener\PublishListener;
use Nashgao\MQTT\Listener\SubscribeListener;
use Nashgao\MQTT\Provider\ClientIdProviderInterface;
use Nashgao\MQTT\Provider\RandomClientIdProvider;
use Psr\EventDispatcher\ListenerProviderInterface;

use function Hyperf\Support\make;

require_once dirname(__DIR__) . '/vendor/autoload.php';

const SIMPS_MQTT_LOCAL_HOST = '127.0.0.1';
const SIMPS_MQTT_REMOTE_HOST = 'broker.emqx.io';
const SIMPS_MQTT_PORT = 1883;

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__));
(function () {
    $container = null;
    try {
        $container = ApplicationContext::getContainer();
    } catch (\Throwable) {
    }

    if (! isset($container)) {
        Hyperf\Di\ClassLoader::init();
        /** @var Psr\Container\ContainerInterface $container */
        $container = new Container((new DefinitionSourceFactory())());
        /** @var Container $container */
        $container = ApplicationContext::setContainer($container);
        $container->get(Hyperf\Contract\ApplicationInterface::class);
        /** @var Config $config */
        $config = $container->get(ConfigInterface::class);
        $config->set('mqtt', require_once __DIR__ . '/config.php');

        /* set up dependencies */
        $container->set(ClientIdProviderInterface::class, new RandomClientIdProvider());

        /* set up listener */
        /** @var ListenerProvider $provider */
        $provider = $container->get(ListenerProviderInterface::class);
        $listeners = [
            SubscribeListener::class,
            PublishListener::class,
            OnDisconnectListener::class,
            OnSubscribeListener::class,
            OnPublishListener::class,
            require_once __DIR__ . '/on_receive.php',
        ];
        foreach ($listeners as $listener) {
            if (is_string($listener)) {
                $listener = make($listener);

            /** @var ListenerInterface $listener */}
            foreach ($listener->listen() as $event) {
                $provider->on($event, [$listener, 'process']);
            }
        }
    }
})();
