<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Test\Cases\Shell\Transport;

use Nashgao\MQTT\Shell\Transport\SwooleUnixSocketTransport;
use Nashgao\MQTT\Test\AbstractTestCase;
use Swoole\Coroutine;
use Swoole\Coroutine\Socket;

/**
 * Integration tests for SwooleUnixSocketTransport.
 *
 * These tests verify that the transport works correctly inside Swoole
 * coroutine contexts where native PHP socket functions are hooked.
 *
 * The original issue: UnixSocketTransport uses native socket_create() which
 * returns a PHP Socket object. When Swoole hooks are enabled, socket_set_option()
 * gets intercepted and calls swoole_socket_set_option() which expects a
 * Swoole\Coroutine\Socket, causing TypeError.
 *
 * Note: Tests run via co-phpunit which already provides a coroutine context.
 *
 * @internal
 * @covers \Nashgao\MQTT\Shell\Transport\SwooleUnixSocketTransport
 */
final class SwooleUnixSocketTransportTest extends AbstractTestCase
{
    private string $socketPath;

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('swoole')) {
            $this->markTestSkipped('Swoole extension required');
        }

        $this->socketPath = sys_get_temp_dir() . '/mqtt-test-' . uniqid() . '.sock';
    }

    protected function tearDown(): void
    {
        if (isset($this->socketPath) && file_exists($this->socketPath)) {
            @unlink($this->socketPath);
        }
        parent::tearDown();
    }

    /**
     * Test that SwooleUnixSocketTransport can connect inside a coroutine.
     *
     * This is the core fix verification: the transport must work inside
     * Swoole coroutine context without TypeError from socket type mismatch.
     */
    public function testConnectAndReceiveDoesNotThrowTypeError(): void
    {
        $server = $this->createTestServer();

        // Server coroutine - accept one connection and send messages
        Coroutine::create(function () use ($server): void {
            /** @var false|Socket $client */
            $client = $server->accept(5.0);
            if ($client !== false) {
                // Send welcome message (JSON like DebugTapServer does)
                $welcome = json_encode([
                    'type' => 'system',
                    'payload' => 'Welcome',
                    'source' => 'test',
                    'timestamp' => date(\DateTimeInterface::ATOM),
                    'metadata' => [],
                ]) . "\n";
                $client->send($welcome);

                // Send a test message after brief delay
                Coroutine::sleep(0.1);
                $testMsg = json_encode([
                    'type' => 'publish',
                    'payload' => ['topic' => 'test/topic', 'message' => 'hello'],
                    'source' => 'mqtt',
                    'timestamp' => date(\DateTimeInterface::ATOM),
                    'metadata' => [],
                ]) . "\n";
                $client->send($testMsg);

                Coroutine::sleep(0.5);
                $client->close();
            }
            $server->close();
        });

        // Give server time to start listening
        Coroutine::sleep(0.05);

        // Client - this is where the original bug would trigger TypeError
        $transport = new SwooleUnixSocketTransport($this->socketPath, 5.0);

        // connect() should not throw TypeError
        $transport->connect();

        $this->assertTrue($transport->isConnected(), 'Transport should be connected');

        // receive() is where the original bug triggered socket_set_option TypeError
        $message = $transport->receive(2.0);

        $this->assertNotNull($message, 'Should receive a message');
        $this->assertSame('publish', $message->type);

        $transport->disconnect();
    }

    /**
     * Test that ping works inside coroutine context.
     */
    public function testPingWorksInCoroutineContext(): void
    {
        $server = $this->createTestServer();

        Coroutine::create(function () use ($server): void {
            /** @var false|Socket $client */
            $client = $server->accept(5.0);
            if ($client !== false) {
                // Welcome
                $client->send(json_encode(['type' => 'system', 'payload' => 'Welcome']) . "\n");

                // Wait for ping and respond with pong
                $data = $client->recv(4096, 2.0);
                if ($data !== false && $data !== '') {
                    $client->send(json_encode(['type' => 'pong']) . "\n");
                }

                Coroutine::sleep(0.5);
                $client->close();
            }
            $server->close();
        });

        Coroutine::sleep(0.05);

        $transport = new SwooleUnixSocketTransport($this->socketPath, 5.0);
        $transport->connect();

        $result = $transport->ping();

        $this->assertTrue($result, 'Ping should succeed inside coroutine');

        $transport->disconnect();
    }

    /**
     * Test streaming mode works inside coroutine.
     */
    public function testStreamingModeInCoroutineContext(): void
    {
        $server = $this->createTestServer();

        Coroutine::create(function () use ($server): void {
            /** @var false|Socket $client */
            $client = $server->accept(5.0);
            if ($client !== false) {
                $client->send(json_encode(['type' => 'system', 'payload' => 'Welcome']) . "\n");
                // Acknowledge subscribe
                $client->recv(4096, 1.0);
                $client->send(json_encode(['type' => 'system', 'payload' => 'Subscribed']) . "\n");
                // Acknowledge unsubscribe
                $client->recv(4096, 1.0);
                $client->send(json_encode(['type' => 'system', 'payload' => 'Unsubscribed']) . "\n");
                Coroutine::sleep(0.5);
                $client->close();
            }
            $server->close();
        });

        Coroutine::sleep(0.05);

        $transport = new SwooleUnixSocketTransport($this->socketPath, 5.0);
        $transport->connect();

        $this->assertFalse($transport->isStreaming());

        $transport->startStreaming();
        $this->assertTrue($transport->isStreaming());

        $transport->stopStreaming();
        $this->assertFalse($transport->isStreaming());

        $transport->disconnect();
    }

    /**
     * Test getInfo returns correct transport type.
     */
    public function testGetInfoReturnsCorrectType(): void
    {
        $transport = new SwooleUnixSocketTransport('/tmp/test.sock', 30.0);
        $info = $transport->getInfo();

        $this->assertSame('swoole_unix_socket', $info['type']);
        $this->assertSame('/tmp/test.sock', $info['path']);
        $this->assertFalse($info['connected']);
        $this->assertFalse($info['streaming']);
    }

    /**
     * Test getEndpoint returns correct format.
     */
    public function testGetEndpointReturnsUnixUri(): void
    {
        $transport = new SwooleUnixSocketTransport('/tmp/test.sock', 30.0);

        $this->assertSame('unix:///tmp/test.sock', $transport->getEndpoint());
    }

    /**
     * Test supportsStreaming returns true.
     */
    public function testSupportsStreaming(): void
    {
        $transport = new SwooleUnixSocketTransport('/tmp/test.sock', 30.0);

        $this->assertTrue($transport->supportsStreaming());
    }

    /**
     * Test receive returns null when not connected.
     */
    public function testReceiveReturnsNullWhenNotConnected(): void
    {
        $transport = new SwooleUnixSocketTransport('/tmp/test.sock', 30.0);

        $result = $transport->receive(0.1);

        $this->assertNull($result);
    }

    /**
     * Test isConnected returns false initially.
     */
    public function testIsConnectedReturnsFalseInitially(): void
    {
        $transport = new SwooleUnixSocketTransport('/tmp/test.sock', 30.0);

        $this->assertFalse($transport->isConnected());
    }

    /**
     * Create a test Unix socket server.
     */
    private function createTestServer(): Socket
    {
        if (file_exists($this->socketPath)) {
            @unlink($this->socketPath);
        }

        $server = new Socket(AF_UNIX, SOCK_STREAM, 0);

        if (! $server->bind($this->socketPath)) {
            $this->fail('Failed to bind server socket: ' . socket_strerror($server->errCode));
        }

        if (! $server->listen(1)) {
            $server->close();
            $this->fail('Failed to listen on server socket: ' . socket_strerror($server->errCode));
        }

        return $server;
    }
}
