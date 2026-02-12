<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Transport;

use NashGao\InteractiveShell\Command\CommandResult;
use NashGao\InteractiveShell\Message\Message;
use NashGao\InteractiveShell\Parser\ParsedCommand;
use NashGao\InteractiveShell\Transport\StreamingTransportInterface;
use Swoole\Coroutine\Socket;

/**
 * Swoole-compatible Unix Socket transport for debug shell.
 *
 * Uses Swoole\Coroutine\Socket instead of native PHP socket_* functions
 * to avoid type conflicts when running inside Swoole coroutines.
 *
 * Swoole's runtime hooks intercept native socket functions and expect
 * Swoole\Coroutine\Socket objects, causing TypeError when native
 * Socket objects are passed.
 */
final class SwooleUnixSocketTransport implements StreamingTransportInterface
{
    private ?Socket $socket = null;

    private bool $connected = false;

    private bool $streaming = false;

    /** @var callable(Message): void|null */
    private $messageCallback = null;

    private string $buffer = '';

    public function __construct(
        private readonly string $socketPath,
        private readonly float $timeout = 30.0,
    ) {}

    public function connect(): void
    {
        if ($this->socket !== null) {
            return;
        }

        $socket = new Socket(AF_UNIX, SOCK_STREAM, 0);

        if (! $socket->connect($this->socketPath, 0, $this->timeout)) {
            throw new \RuntimeException(
                "Failed to connect to {$this->socketPath}: " . socket_strerror($socket->errCode)
            );
        }

        $this->socket = $socket;
        $this->connected = true;

        // Drain welcome message from server
        $this->readLine(2.0);
    }

    public function disconnect(): void
    {
        if ($this->socket !== null) {
            $this->streaming = false;
            $this->connected = false;
            $this->socket->close();
            $this->socket = null;
            $this->buffer = '';
        }
    }

    public function isConnected(): bool
    {
        return $this->socket !== null && $this->connected;
    }

    public function send(ParsedCommand $command): CommandResult
    {
        if ($this->socket === null) {
            return CommandResult::failure('Not connected');
        }

        $request = [
            'type' => 'command',
            'command' => $command->command,
            'arguments' => $command->arguments,
            'options' => $command->options,
        ];

        $json = json_encode($request, JSON_THROW_ON_ERROR) . "\n";
        $written = $this->socket->send($json);

        if ($written === false) {
            if ($this->isDisconnectionError($this->socket->errCode)) {
                $this->connected = false;
            }
            return CommandResult::failure('Failed to send command: ' . socket_strerror($this->socket->errCode));
        }

        $response = $this->readLine($this->timeout);
        if ($response === null) {
            return CommandResult::failure('No response from server');
        }

        try {
            /** @var array<string, mixed> $data */
            $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
            return CommandResult::fromResponse($data);
        } catch (\JsonException $e) {
            return CommandResult::failure("Invalid response: {$e->getMessage()}");
        }
    }

    public function ping(): bool
    {
        if ($this->socket === null) {
            return false;
        }

        $ping = json_encode(['type' => 'ping']) . "\n";
        $written = $this->socket->send($ping);

        if ($written === false) {
            return false;
        }

        $response = $this->readLine(1.0);
        return $response !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function getInfo(): array
    {
        return [
            'type' => 'swoole_unix_socket',
            'path' => $this->socketPath,
            'connected' => $this->isConnected(),
            'streaming' => $this->streaming,
        ];
    }

    public function getEndpoint(): string
    {
        return "unix://{$this->socketPath}";
    }

    public function supportsStreaming(): bool
    {
        return true;
    }

    public function sendAsync(ParsedCommand $command): void
    {
        if ($this->socket === null) {
            throw new \RuntimeException('Not connected');
        }

        $request = [
            'type' => 'command',
            'command' => $command->command,
            'arguments' => $command->arguments,
            'options' => $command->options,
            'async' => true,
        ];

        $json = json_encode($request, JSON_THROW_ON_ERROR) . "\n";
        $this->socket->send($json);
    }

    public function receive(float $timeout = -1): ?Message
    {
        if ($this->socket === null) {
            return null;
        }

        $effectiveTimeout = $timeout >= 0 ? $timeout : $this->timeout;
        $line = $this->readLine($effectiveTimeout);

        if ($line === null) {
            return null;
        }

        try {
            /** @var array<string, mixed> $data */
            $data = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
            return Message::fromArray($data);
        } catch (\JsonException) {
            return Message::error("Invalid message format: {$line}");
        }
    }

    public function onMessage(callable $callback): void
    {
        $this->messageCallback = $callback;
    }

    /**
     * Invoke the message callback if set.
     */
    public function dispatchMessage(Message $message): void
    {
        if ($this->messageCallback !== null) {
            ($this->messageCallback)($message);
        }
    }

    public function startStreaming(): void
    {
        if ($this->socket === null) {
            throw new \RuntimeException('Not connected');
        }

        $subscribe = json_encode(['type' => 'subscribe']) . "\n";
        $this->socket->send($subscribe);

        $this->streaming = true;
    }

    public function stopStreaming(): void
    {
        if ($this->socket === null) {
            return;
        }

        $unsubscribe = json_encode(['type' => 'unsubscribe']) . "\n";
        $this->socket->send($unsubscribe);

        $this->streaming = false;
    }

    public function isStreaming(): bool
    {
        return $this->streaming;
    }

    /**
     * Read a line from the socket using Swoole's coroutine socket.
     */
    private function readLine(float $timeout): ?string
    {
        if ($this->socket === null) {
            return null;
        }

        $startTime = microtime(true);
        $maxTime = $startTime + $timeout;

        while (true) {
            // Check buffer for complete line
            $newlinePos = strpos($this->buffer, "\n");
            if ($newlinePos !== false) {
                $line = substr($this->buffer, 0, $newlinePos);
                $this->buffer = substr($this->buffer, $newlinePos + 1);
                return $line;
            }

            // Check timeout
            $remaining = $maxTime - microtime(true);
            if ($remaining <= 0) {
                return null;
            }

            // Read more data with remaining timeout
            /** @var false|string $data */
            $data = $this->socket->recv(4096, $remaining);

            if ($data === false) {
                $errCode = $this->socket->errCode;
                // EAGAIN/EWOULDBLOCK means timeout or no data - not an error
                if ($errCode === SOCKET_EAGAIN || $errCode === 0) {
                    continue;
                }
                if ($this->isDisconnectionError($errCode)) {
                    $this->connected = false;
                }
                return null;
            }

            if ($data === '') {
                // Connection closed
                $this->connected = false;
                return null;
            }

            $this->buffer .= $data;
        }
    }

    /**
     * Check if error code indicates disconnection.
     */
    private function isDisconnectionError(int $errCode): bool
    {
        // POSIX error codes for various disconnect scenarios
        return in_array($errCode, [
            104,  // ECONNRESET (Linux)
            54,   // ECONNRESET (BSD/macOS)
            32,   // EPIPE
            107,  // ENOTCONN (Linux)
            57,   // ENOTCONN (BSD/macOS)
        ], true);
    }
}
