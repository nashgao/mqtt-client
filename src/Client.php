<?php

declare(strict_types=1);

namespace Nashgao\MQTT;

use Nashgao\MQTT\Event\OnDisconnectEvent;
use Nashgao\MQTT\Event\OnReceiveEvent;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Simps\MQTT\Client as SimpsClient;
use Simps\MQTT\Protocol\Types;
use Swoole\Coroutine\Channel;

class Client
{
    const QOS_AT_MOST_ONCE = 0,
          QOS_AT_LEAST_ONCE = 1,
          QOS_EXACTLY_ONCE = 2;

    protected Channel $channel;

    protected SimpsClient $client;

    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->channel = new Channel();
    }

    public function loop()
    {
        while (true) {
            /** @var Closure $closure */
            $closure = $this->channel->pop();
            if (! $closure) {
                break;
            }
            $closure->call($this);
        }
    }

    public function socketConnect()
    {
        $cont = new Channel();
        $this->channel->push(function () use ($cont) {
            $this->client = ClientFactory::createClient();
            $cont->push(true);
        });
        $cont->pop();
    }

    public function connect(bool $clean = true, array $will = [])
    {
        $cont = new Channel();
        $this->channel->push(function () use ($will, $clean, $cont) {
            $this->client->connect($clean, $will);
            $cont->push(true);
        });
        $cont->pop();
    }

    public function publish(string $topic, string $message, int $qos = 2)
    {
        $cont = new Channel();
        $this->channel->push(function () use ($cont, $topic, $message, $qos) {
            $this->client->publish($topic, $message, $qos);
            $cont->push(true);
        });
        $cont->pop();
    }

    public function subscribe(array $topics)
    {
        $cont = new Channel();
        $this->channel->push(function () use ($cont, $topics) {
            $res = $this->client->subscribe($topics);
            $cont->push($res);
        });
        $cont->pop();
    }

    public function unsubscribe(array $topics)
    {
        $cont = new Channel();
        $this->channel->push(function () use ($cont, $topics) {
            $res = $this->client->unSubscribe($topics);
            $cont->push($res);
        });
        $cont->pop();
    }

    public function recv()
    {
        $cont = new Channel();
        $this->channel->push(function () use ($cont) {
            $message = $this->client->recv();
            if (! is_bool($message)) {
                if ($message['type'] === Types::PUBLISH and $message['qos'] === static::QOS_AT_LEAST_ONCE) {
                    $this->client->send(['type' => Types::PUBACK, 'message_id' => $message['message_id'], false]);
                }

                if ($message['type'] === Types::DISCONNECT) {
                    $this->container->get(EventDispatcherInterface::class)->dispatch(new OnDisconnectEvent($message['type'],$message['code'],$message['qos'], $this->client));
                    return $cont->push($message);
                }
            }

            $this->container->get(EventDispatcherInterface::class)->dispatch(new OnReceiveEvent($message['type'], $message['dup'], $message['qos'], $message['retain'], $message['topic'], $message['message_id'], $message['properties'], $message['message']));
            return $cont->push($message);
        });

        return $cont->pop();
    }
}
