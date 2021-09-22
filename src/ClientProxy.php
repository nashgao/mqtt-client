<?php

declare(strict_types=1);

namespace Nashgao\MQTT;

use Hyperf\Utils\ApplicationContext;
use Nashgao\MQTT\Config\ClientConfig;
use Nashgao\MQTT\Event\OnDisconnectEvent;
use Nashgao\MQTT\Event\OnReceiveEvent;
use Nashgao\MQTT\Event\OnSubscribeEvent;
use Nashgao\MQTT\Utils\Qos;
use Psr\EventDispatcher\EventDispatcherInterface;
use Simps\MQTT\Protocol\Types;
use Swoole\Coroutine\Channel;

class ClientProxy extends \Simps\MQTT\Client
{
    public Channel $channel;

    protected EventDispatcherInterface $dispatcher;

    protected string $poolName;

    protected ClientConfig $config;

    protected float $timeSincePing;

    public function __construct(ClientConfig $config, string $poolName)
    {
        $this->config = $config;
        $this->poolName = $poolName;
        $this->dispatcher = ApplicationContext::getContainer()->get(EventDispatcherInterface::class);
        $this->channel = new Channel();
        $this->timeSincePing = time();
        parent::__construct($config->host, $config->port, $config->clientConfig, $config->clientType);
    }

    public function loop()
    {
        for (;;) {
            /** @var Closure $closure */
            $closure = $this->channel->pop();
            if (! $closure) {
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
        $this->channel->push(fn () => $cont->push(parent::publish($topic, $message, $qos, $dup, $retain, $properties)));
        return $cont->pop();
    }

    public function subscribe(array $topics, array $properties = []): bool | array
    {
        $cont = new Channel();
        $this->channel->push(
            function () use ($cont, $topics, $properties) {
                $this->dispatcher->dispatch(new OnSubscribeEvent($this->poolName, parent::getConfig()->getClientId(), $topics, parent::subscribe($topics, $properties)));
            }
        );
        return $cont->pop();
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
                if ($this->timeSincePing <= (time() - $this->config->clientConfig->getKeepAlive())) {
                    if (parent::ping()) {
                        $this->timeSincePing = time();
                    } else {
                        return $cont->push(false);
                    }
                }
                if (! is_bool($message)) {
                    if ($message['type'] === Types::PUBLISH and $message['qos'] === Qos::QOS_AT_LEAST_ONCE) {
                        parent::send(['type' => Types::PUBACK, 'message_id' => $message['message_id']], true);
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

                    $this->dispatcher->dispatch(
                        new OnReceiveEvent(
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
                return $cont->push($this->timeSincePing);
            }
        );

        return $cont->pop($this->config->clientConfig->getKeepAlive());
    }
}
