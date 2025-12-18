<?php

declare(strict_types=1);

namespace Nashgao\MQTT;

use Hyperf\Contract\StdoutLoggerInterface;
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

    protected StdoutLoggerInterface $logger;

    protected string $poolName;

    protected ClientConfig $config;

    protected float $timeSincePing;

    protected int $delayTime = 30;

    public function __construct(ClientConfig $config, string $poolName)
    {
        $this->config = $config;
        $this->poolName = $poolName;
        $this->dispatcher = ApplicationContext::getContainer()->get(EventDispatcherInterface::class);
        $this->logger = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);
        $this->channel = new Channel();
        $this->timeSincePing = time();
        parent::__construct($config->host, $config->port, $config->clientConfig, $config->clientType);
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
        $this->channel->push(fn () => $this->dispatcher->dispatch(new OnPublishEvent($this->poolName, $topic, $message, $qos, parent::publish($topic, $message, $qos, $dup, $retain, $properties))));
        return $cont->pop();
    }

    public function subscribe(array $topics, array $properties = []): array|bool
    {
        $cont = new Channel();
        $this->channel->push(fn () => $this->dispatcher->dispatch(new OnSubscribeEvent($this->poolName, parent::getConfig()->getClientId(), $topics, parent::subscribe($topics, $properties))));
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
        // 检查是否需要发送PING请求以保持连接
        // Check if ping is needed
        if ((time() - $this->timeSincePing) >= $this->config->clientConfig->getKeepAlive()) {
            call_user_func([$this, 'executePing']);
        }

        $this->channel->push(
            function () use ($cont) {
                $message = parent::recv();
                // 计算延迟时间
                $delayDateTime = (time() - ($this->config->clientConfig->getKeepAlive() + $this->delayTime));
                // 检查是否需要发送PING请求以保持连接
                // 在接收到消息后，检查是否需要执行ping操作
                if ($this->timeSincePing <= $delayDateTime) {
                    call_user_func([$this, 'executePing']);
                }

                if (! is_bool($message)) {
                    /* qos 1 puback */
                    if ($message['type'] === Types::PUBLISH && $message['qos'] === Qos::QOS_AT_LEAST_ONCE) {
                        parent::send(['type' => Types::PUBACK, 'message_id' => $message['message_id']]);
                    }

                    // 对于QoS为2的消息，发送PUBREC响应
                    /* qos 2 pubrel */
                    if ($message['type'] === Types::PUBLISH && $message['qos'] === Qos::QOS_EXACTLY_ONCE) {
                        parent::send(['type' => Types::PUBREC, 'message_id' => $message['message_id']], false);
                    }

                    // 对于收到的PUBREL消息，发送PUBCOMP响应
                    /* qos 2 pub comp */
                    if ($message['type'] === Types::PUBREL) {
                        parent::send(['type' => Types::PUBCOMP, 'message_id' => $message['message_id']], false);
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

    /**
     * 发送心跳包(此方法重写了父类的心跳包方法，并增加了记录最后一次成功心跳时间的功能。)
     * Author: m
     * DateTime: 2024/11/7 15:54
     * @return bool 如果心跳成功则返回true，否则返回false。
     * Remark
     */
    private function executePing(): bool
    {
        try {
            // 调用父类的心跳方法尝试与服务器进行心跳通信。
            if (!parent::ping()) {
                throw new \Exception("mqtt ping failed");
            }

            // 如果心跳成功，记录当前时间为最后一次成功心跳的时间。
            $this->timeSincePing = time();
            // 输出心跳操作的结果以及自上次成功心跳以来的时间。
            $this->logger->debug("mqtt ping success,timestamp=" . date('Y-m-d H:i:s', intval($this->timeSincePing)));
        } catch (\Exception $e) {
            // 捕获并处理在心跳过程中发生的异常。
            echo "心跳过程中发生错误：" . $e->getMessage() . "\n";
            $this->logger->warning(sprintf("%s: %s(%s) in %s:%s\nStack trace:\n%s",
                    get_class($e),
                    $e->getMessage(),
                    $e->getCode(),
                    $e->getFile(),
                    $e->getLine(),
                    $e->getTraceAsString())
            );
            return false;
        }

        return true;
    }
}
