<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Test\Cases\Shell\Integration;

use DateTimeImmutable;
use NashGao\InteractiveShell\Message\Message;
use Nashgao\MQTT\Test\AbstractTestCase;

/**
 * Base class for shell integration tests.
 *
 * Provides helper methods for creating messages and contexts
 * to test consumer workflows and component interactions.
 *
 * @internal
 */
abstract class AbstractIntegrationTestCase extends AbstractTestCase
{
    /**
     * Create a message simulating an incoming MQTT publish.
     *
     * @param string $topic Topic path
     * @param mixed $payload Message payload (array or string)
     * @param int $qos QoS level (0, 1, or 2)
     */
    protected function createIncomingMessage(
        string $topic,
        mixed $payload,
        int $qos = 0,
    ): Message {
        return new Message(
            type: 'publish',
            payload: [
                'topic' => $topic,
                'message' => $payload,
                'qos' => $qos,
            ],
            source: 'mqtt',
            timestamp: new DateTimeImmutable(),
            metadata: [
                'direction' => 'incoming',
                'qos' => $qos,
            ],
        );
    }

    /**
     * Create a message simulating an outgoing MQTT publish.
     *
     * @param string $topic Topic path
     * @param mixed $payload Message payload
     * @param int $qos QoS level
     */
    protected function createOutgoingMessage(
        string $topic,
        mixed $payload,
        int $qos = 0,
    ): Message {
        return new Message(
            type: 'publish',
            payload: [
                'topic' => $topic,
                'message' => $payload,
                'qos' => $qos,
            ],
            source: 'mqtt',
            timestamp: new DateTimeImmutable(),
            metadata: [
                'direction' => 'outgoing',
                'qos' => $qos,
            ],
        );
    }

    /**
     * Create a system message.
     *
     * @param string $content System message content
     */
    protected function createSystemMessage(string $content): Message
    {
        return new Message(
            type: 'system',
            payload: $content,
            source: 'mqtt',
            timestamp: new DateTimeImmutable(),
            metadata: [],
        );
    }

    /**
     * Create an error message.
     *
     * @param string $error Error description
     */
    protected function createErrorMessage(string $error): Message
    {
        return new Message(
            type: 'error',
            payload: $error,
            source: 'mqtt',
            timestamp: new DateTimeImmutable(),
            metadata: [],
        );
    }

    /**
     * Create a subscribe message.
     *
     * @param array<string> $topics Topics to subscribe to
     */
    protected function createSubscribeMessage(array $topics): Message
    {
        return new Message(
            type: 'subscribe',
            payload: ['topics' => $topics],
            source: 'mqtt',
            timestamp: new DateTimeImmutable(),
            metadata: [],
        );
    }

    /**
     * Create a disconnect message.
     *
     * @param int $code Disconnect reason code
     */
    protected function createDisconnectMessage(int $code = 0): Message
    {
        return new Message(
            type: 'disconnect',
            payload: ['code' => $code],
            source: 'mqtt',
            timestamp: new DateTimeImmutable(),
            metadata: [],
        );
    }

    /**
     * Create a data message (for timeline/tree visualization).
     *
     * @param string $topic Topic path
     * @param mixed $payload Message payload
     */
    protected function createDataMessage(string $topic, mixed $payload): Message
    {
        return Message::data(
            payload: [
                'topic' => $topic,
                'payload' => $payload,
            ],
            source: 'mqtt',
        );
    }
}
