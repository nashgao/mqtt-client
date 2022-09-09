<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Listener;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\AfterWorkerStart;
use Nashgao\MQTT\Config\TopicConfig;
use Nashgao\MQTT\Constants\MQTTConstants;
use Nashgao\MQTT\Event\SubscribeEvent;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class AfterWorkerStartListener implements ListenerInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function listen(): array
    {
        return [
            AfterWorkerStart::class,
        ];
    }

    public function process(object $event): void
    {
        $dispatcher = $this->container->get(EventDispatcherInterface::class);
        $config = $this->container->get(ConfigInterface::class);
        foreach ($config->get('mqtt') ?? [] as $poolName => $poolConfig) {
            /* e.g. host => localhost */
            foreach ($poolConfig as $key => $topicsArray) {
                if ($key === MQTTConstants::SUBSCRIBE) {
                    $topics = [];
                    foreach ($topicsArray['topics'] ?? [] as $topic) {
                        if (isset($topic['filter']) && is_callable($topic['filter']) && ! $topic['filter']($topic)) {
                            continue;
                        }

                        if (! $topic['auto_subscribe']) {
                            continue;
                        }

                        /* @var TopicConfig[] $topics */
                        $topics[] = make(TopicConfig::class, [$topic]);
                    }

                    if (! empty($topics)) {
                        $dispatcher->dispatch(new SubscribeEvent($poolName, $topics));
                    }
                }
            }
        }
    }
}
