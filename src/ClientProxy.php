<?php

declare(strict_types=1);

namespace Nashgao\MQTT;

use Hyperf\Context\ApplicationContext;
use Hyperf\Engine\Channel;
use Nashgao\MQTT\Config\ClientConfig;
use Nashgao\MQTT\Event\OnDisconnectEvent;
use Nashgao\MQTT\Event\OnPublishEvent;
use Nashgao\MQTT\Event\OnReceiveEvent;
use Nashgao\MQTT\Event\OnSubscribeEvent;
use Nashgao\MQTT\Utils\Qos;
use Psr\EventDispatcher\EventDispatcherInterface;
use Simps\MQTT\Client;
use Simps\MQTT\Protocol\Types;

class ClientProxy extends Client
{
    public Channel $channel;

    protected EventDispatcherInterface $dispatcher;

    protected string $poolName;

    protected ClientConfig $config;

    protected float $timeSincePing;

    protected int $delayTime = 30;

    public function __construct(ClientConfig $config, string $poolName)
    {
        $this->config = $config;
        $this->poolName = $poolName;
        $this->dispatcher = ApplicationContext::getContainer()->get(EventDispatcherInterface::class);
        $this->channel = new Channel();
        $this->timeSincePing = time();
        parent::__construct($config->host, $config->port, $config->clientConfig, $config->clientType);
    }

    public function loop(): void
    {
        while (true) {
            $closure = $this->channel->pop();
            if (! $closure instanceof \Closure) {
                break;
            }

            $closure->call($this);
        }
    }

    public function connect(bool $clean = true, array $will = [])
    {
        $cont = new Channel();
        $this->channel->push(fn () => $cont->push(parent::connect($clean, $will)));
        return $cont->pop();
    }

    public function publish(
        string $topic,
        string $message,
        int $qos = 2,
        int $dup = 0,
        int $retain = 0,
        array $properties = []
    ) {
        $cont = new Channel();
        $this->channel->push(function () use ($cont, $topic, $message, $qos, $dup, $retain, $properties) {
            $result = parent::publish($topic, $message, $qos, $dup, $retain, $properties);
            $this->dispatcher->dispatch(new OnPublishEvent($this->poolName, $topic, $message, $qos, $result));
            $cont->push($result);
        });
        return $cont->pop();
    }

    public function subscribe(array $topics, array $properties = []): array|bool
    {
        $cont = new Channel();
        $this->channel->push(function () use ($cont, $topics, $properties) {
            $result = parent::subscribe($topics, $properties);
            $this->dispatcher->dispatch(new OnSubscribeEvent($this->poolName, parent::getConfig()->getClientId(), $topics, $result));
            $cont->push($result);
        });
        /** @var array|bool $result */
        $result = $cont->pop();
        return $result;
    }

    public function unsubscribe(array $topics, array $properties = [])
    {
        $cont = new Channel();
        $this->channel->push(fn () => $cont->push(parent::unSubscribe($topics, $properties)));
        return $cont->pop();
    }

    public function receive()
    {
        $cont = new Channel();
        $this->channel->push(
            function () use ($cont) {
                $message = parent::recv();
                // Send ping if keepAlive seconds have passed since last ping
                if ((time() - $this->timeSincePing) >= $this->config->clientConfig->getKeepAlive()) {
                    if (parent::ping()) {
                        $this->timeSincePing = time();
                    } else {
                        return $cont->push(false);
                    }
                }

                if (! is_bool($message)) {
                    /* qos 1 puback */
                    if ($message['type'] === Types::PUBLISH && $message['qos'] === Qos::QOS_AT_LEAST_ONCE) {
                        parent::send(['type' => Types::PUBACK, 'message_id' => $message['message_id']]);
                    }

                    /* qos 2 pubrel */
                    if ($message['type'] === Types::PUBLISH && $message['qos'] === Qos::QOS_EXACTLY_ONCE) {
                        parent::send(['type' => Types::PUBREC, 'message_id' => $message['message_id']]);
                    }

                    /* qos 2 pub comp */
                    if ($message['type'] === Types::PUBREL) {
                        parent::send(
                            [
                                'type' => Types::PUBCOMP,
                                'message_id' => $message['message_id'],
                            ]
                        );
                    }

                    if ($message['type'] === Types::DISCONNECT) {
                        $this->dispatcher->dispatch(
                            new OnDisconnectEvent(
                                $message['type'],
                                $message['code'],
                                $this->poolName,
                                $this->config,
                                $message['qos'] ?? null,
                            )
                        );
                        parent::close($message['code']);
                        return $cont->push(false);
                    }

                    if ($message['type'] === Types::PUBLISH) {
                        $this->dispatcher->dispatch(
                            new OnReceiveEvent(
                                $this->poolName,
                                $message['type'],
                                $message['dup'] ?? null,
                                $message['qos'] ?? null,
                                $message['retain'] ?? null,
                                $message['topic'] ?? null,
                                $message['message_id'] ?? null,
                                $message['properties'] ?? null,
                                $message['message'] ?? null
                            )
                        );
                    }
                }
                return $cont->push($this->timeSincePing);
            }
        );

        return $cont->pop($this->config->clientConfig->getKeepAlive() + $this->delayTime); /* 30 seconds to pop the channel, since subscribe may not be able to receive the disconnect info immediately */
    }
}
