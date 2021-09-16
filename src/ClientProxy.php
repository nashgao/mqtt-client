<?php

declare(strict_types=1);

namespace Nashgao\MQTT;

use Hyperf\Utils\ApplicationContext;
use Nashgao\MQTT\Config\ClientConfig;
use Nashgao\MQTT\Event\OnDisconnectEvent;
use Nashgao\MQTT\Event\OnReceiveEvent;
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

    public function __construct(ClientConfig $config, string $poolName)
    {
        $this->config = $config;
        $this->poolName = $poolName;
        $this->dispatcher = ApplicationContext::getContainer()->get(EventDispatcherInterface::class);
        $this->channel = new Channel();
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
        $this->channel->push(
            function () use ($will, $clean, $cont) {
                parent::connect($clean, $will);
                $cont->push(true);
            }
        );
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
        $this->channel->push(
            function () use ($properties, $retain, $dup, $cont, $topic, $message, $qos) {
                parent::publish($topic, $message, $qos, $dup, $retain, $properties);
                $cont->push(true);
            }
        );
        return $cont->pop();
    }

    public function subscribe(array $topics, array $properties = []): bool | array
    {
        $cont = new Channel();
        $this->channel->push(
            function () use ($properties, $cont, $topics) {
                $cont->push(parent::subscribe($topics, $properties));
            }
        );
        return $cont->pop();
    }

    public function unsubscribe(array $topics, array $properties = [])
    {
        $cont = new Channel();
        $this->channel->push(
            function () use ($properties, $cont, $topics) {
                $cont->push(parent::unSubscribe($topics, $properties));
            }
        );
        return $cont->pop();
    }

    public function receive()
    {
        $cont = new Channel();
        $this->channel->push(
            function () use ($cont) {
                $message = parent::recv();
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
                        return $cont->push($message);
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
                return $cont->push($message);
            }
        );

        return $cont->pop($this->config->clientConfig->getKeepAlive());
    }
}
