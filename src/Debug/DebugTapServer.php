<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Debug;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Unix socket server for broadcasting MQTT messages to debug shell clients.
 *
 * The server accepts multiple client connections and broadcasts MQTT events
 * in real-time, allowing developers to monitor traffic with interactive filtering.
 *
 * Supports bidirectional communication:
 * - Broadcasts MQTT events to all connected debug shells
 * - Accepts commands from shells (publish, subscribe, etc.)
 * - Executes MQTT operations via registered callbacks
 */
final class DebugTapServer
{
    private const DEFAULT_SOCKET_PATH = '/tmp/mqtt-debug.sock';

    private ?\Socket $serverSocket = null;

    /** @var array<int, \Socket> */
    private array $clients = [];

    private bool $running = false;

    private LoggerInterface $logger;

    private string $socketPath;

    private bool $enabled;

    /**
     * Callback for executing MQTT commands from the shell.
     *
     * @var null|callable(string, array<string, mixed>): array<string, mixed>
     */
    private $commandCallback = null;

    /** @var array<string, int> Command execution statistics */
    private array $commandStats = [];

    public function __construct(
        ?ConfigInterface $config = null,
        ?StdoutLoggerInterface $stdoutLogger = null,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? $stdoutLogger ?? new NullLogger();
        $socketPath = $config?->get('mqtt.default.debug.socket_path', self::DEFAULT_SOCKET_PATH);
        $this->socketPath = is_string($socketPath) ? $socketPath : self::DEFAULT_SOCKET_PATH;
        $this->enabled = (bool) ($config?->get('mqtt.default.debug.enabled', false) ?? false);
    }

    /**
     * Check if debug tap is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Start the Unix socket server.
     */
    public function start(): void
    {
        if (! $this->enabled) {
            $this->logger->info('Debug tap server is disabled');
            return;
        }

        if ($this->serverSocket !== null) {
            return;
        }

        // Remove stale socket file
        if (file_exists($this->socketPath)) {
            @unlink($this->socketPath);
        }

        $socket = @socket_create(AF_UNIX, SOCK_STREAM, 0);
        if ($socket === false) {
            $this->logger->error('Failed to create debug tap socket: ' . socket_strerror(socket_last_error()));
            return;
        }

        if (@socket_bind($socket, $this->socketPath) === false) {
            $error = socket_strerror(socket_last_error($socket));
            socket_close($socket);
            $this->logger->error("Failed to bind debug tap socket to {$this->socketPath}: {$error}");
            return;
        }

        if (@socket_listen($socket, 10) === false) {
            $error = socket_strerror(socket_last_error($socket));
            socket_close($socket);
            @unlink($this->socketPath);
            $this->logger->error("Failed to listen on debug tap socket: {$error}");
            return;
        }

        // Set non-blocking mode
        socket_set_nonblock($socket);

        $this->serverSocket = $socket;
        $this->running = true;

        $this->logger->info("Debug tap server started on {$this->socketPath}");
    }

    /**
     * Stop the Unix socket server.
     */
    public function stop(): void
    {
        $this->running = false;

        // Close all client connections
        foreach ($this->clients as $id => $client) {
            $this->disconnectClient($id);
        }

        // Close server socket
        if ($this->serverSocket !== null) {
            socket_close($this->serverSocket);
            $this->serverSocket = null;
        }

        // Remove socket file
        if (file_exists($this->socketPath)) {
            @unlink($this->socketPath);
        }

        $this->logger->info('Debug tap server stopped');
    }

    /**
     * Accept pending connections and process client commands.
     * Call this periodically in your main loop.
     */
    public function tick(): void
    {
        if (! $this->running || $this->serverSocket === null) {
            return;
        }

        $this->acceptConnections();
        $this->processClientCommands();
    }

    /**
     * Broadcast an MQTT publish event to all connected clients.
     *
     * @param null|array<mixed>|string $message
     * @param array<string, mixed> $metadata
     */
    public function broadcastPublish(
        string $topic,
        null|array|string $message,
        int $qos,
        string $poolName,
        array $metadata = [],
    ): void {
        $this->broadcast([
            'type' => 'publish',
            'payload' => [
                'topic' => $topic,
                'message' => is_array($message) ? json_encode($message) : $message,
                'qos' => $qos,
                'pool' => $poolName,
            ],
            'source' => "mqtt:{$topic}",
            'timestamp' => date(\DateTimeInterface::ATOM),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Broadcast an MQTT subscribe event to all connected clients.
     *
     * @param array<string> $topics
     * @param array<string, mixed> $metadata
     */
    public function broadcastSubscribe(
        array $topics,
        string $clientId,
        string $poolName,
        array $metadata = [],
    ): void {
        $this->broadcast([
            'type' => 'subscribe',
            'payload' => [
                'topics' => $topics,
                'client_id' => $clientId,
                'pool' => $poolName,
            ],
            'source' => 'mqtt:subscribe',
            'timestamp' => date(\DateTimeInterface::ATOM),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Broadcast an MQTT disconnect event to all connected clients.
     *
     * @param array<string, mixed> $metadata
     */
    public function broadcastDisconnect(
        int $type,
        int $code,
        string $poolName,
        array $metadata = [],
    ): void {
        $this->broadcast([
            'type' => 'disconnect',
            'payload' => [
                'disconnect_type' => $type,
                'code' => $code,
                'pool' => $poolName,
            ],
            'source' => 'mqtt:disconnect',
            'timestamp' => date(\DateTimeInterface::ATOM),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Broadcast a system message to all connected clients.
     */
    public function broadcastSystem(string $message): void
    {
        $this->broadcast([
            'type' => 'system',
            'payload' => $message,
            'source' => 'system',
            'timestamp' => date(\DateTimeInterface::ATOM),
            'metadata' => [],
        ]);
    }

    /**
     * Get the number of connected clients.
     */
    public function getClientCount(): int
    {
        return count($this->clients);
    }

    /**
     * Check if the server is running.
     */
    public function isRunning(): bool
    {
        return $this->running;
    }

    /**
     * Get the socket path.
     */
    public function getSocketPath(): string
    {
        return $this->socketPath;
    }

    /**
     * Register a callback for executing MQTT commands from debug shells.
     *
     * The callback receives:
     * - string $command: The command name (mqtt_publish, mqtt_subscribe, etc.)
     * - array $data: Command data including arguments and options
     *
     * The callback should return an array with:
     * - 'success' => bool
     * - 'message' => string (optional)
     * - 'data' => mixed (optional)
     *
     * @param callable(string, array<string, mixed>): array<string, mixed> $callback
     */
    public function setCommandCallback(callable $callback): void
    {
        $this->commandCallback = $callback;
    }

    /**
     * Get command execution statistics.
     *
     * @return array<string, int>
     */
    public function getCommandStats(): array
    {
        return $this->commandStats;
    }

    /**
     * Accept new client connections.
     */
    private function acceptConnections(): void
    {
        if ($this->serverSocket === null) {
            return;
        }

        $client = @socket_accept($this->serverSocket);
        if ($client === false) {
            return; // No pending connections
        }

        socket_set_nonblock($client);

        $id = spl_object_id($client);
        $this->clients[$id] = $client;

        $this->logger->debug("Debug tap client connected: {$id}");

        // Send welcome message
        $this->sendToClient($id, [
            'type' => 'system',
            'payload' => 'Connected to MQTT debug tap server',
            'source' => 'system',
            'timestamp' => date(\DateTimeInterface::ATOM),
            'metadata' => ['client_id' => $id],
        ]);
    }

    /**
     * Process commands from connected clients.
     */
    private function processClientCommands(): void
    {
        foreach ($this->clients as $id => $client) {
            $data = @socket_read($client, 4096);

            if ($data === false) {
                $error = socket_last_error($client);
                // EAGAIN/EWOULDBLOCK means no data available (non-blocking)
                if ($error !== 11 && $error !== 35 && $error !== 0) {
                    $this->disconnectClient($id);
                }
                continue;
            }

            if ($data === '') {
                // Client disconnected
                $this->disconnectClient($id);
                continue;
            }

            // Process each line (commands are newline-delimited JSON)
            $lines = explode("\n", trim($data));
            foreach ($lines as $line) {
                if ($line === '') {
                    continue;
                }

                $this->handleClientCommand($id, $line);
            }
        }
    }

    /**
     * Handle a command from a client.
     */
    private function handleClientCommand(int $clientId, string $data): void
    {
        try {
            /** @var array<string, mixed> $command */
            $command = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return; // Ignore invalid JSON
        }

        $type = $command['type'] ?? 'unknown';

        switch ($type) {
            case 'ping':
                $this->sendToClient($clientId, ['type' => 'pong', 'timestamp' => date(\DateTimeInterface::ATOM)]);
                break;

            case 'subscribe':
                // Client wants to start receiving messages (already receiving by default)
                $this->sendToClient($clientId, [
                    'type' => 'system',
                    'payload' => 'Subscribed to MQTT message stream',
                    'source' => 'system',
                    'timestamp' => date(\DateTimeInterface::ATOM),
                    'metadata' => [],
                ]);
                break;

            case 'unsubscribe':
                // Client wants to pause (we'll keep them connected but they can filter client-side)
                $this->sendToClient($clientId, [
                    'type' => 'system',
                    'payload' => 'Unsubscribed from MQTT message stream',
                    'source' => 'system',
                    'timestamp' => date(\DateTimeInterface::ATOM),
                    'metadata' => [],
                ]);
                break;

            case 'command':
                // Handle debug commands from the shell
                $this->handleDebugCommand($clientId, $command);
                break;
        }
    }

    /**
     * Handle a debug command from a client.
     *
     * @param array<string, mixed> $command
     */
    private function handleDebugCommand(int $clientId, array $command): void
    {
        $cmdName = isset($command['command']) && is_string($command['command'])
            ? $command['command']
            : '';

        // Track command execution
        $this->commandStats[$cmdName] = ($this->commandStats[$cmdName] ?? 0) + 1;

        switch ($cmdName) {
            case 'stats':
                $this->sendToClient($clientId, [
                    'type' => 'response',
                    'payload' => [
                        'connected_clients' => count($this->clients),
                        'socket_path' => $this->socketPath,
                        'running' => $this->running,
                        'command_stats' => $this->commandStats,
                    ],
                    'source' => 'system',
                    'timestamp' => date(\DateTimeInterface::ATOM),
                    'metadata' => ['command' => 'stats'],
                ]);
                break;

            // MQTT Operations - delegate to callback
            case 'mqtt_publish':
            case 'mqtt_subscribe':
            case 'mqtt_unsubscribe':
            case 'mqtt_subscriptions':
            case 'mqtt_pool_list':
            case 'mqtt_pool_status':
            case 'mqtt_pool_switch':
            case 'mqtt_pool_connections':
                $this->handleMqttCommand($clientId, $cmdName, $command);
                break;

            default:
                // Echo unknown commands back
                $this->sendToClient($clientId, [
                    'type' => 'response',
                    'payload' => "Unknown command: {$cmdName}",
                    'source' => 'system',
                    'timestamp' => date(\DateTimeInterface::ATOM),
                    'metadata' => ['command' => $cmdName, 'success' => false],
                ]);
        }
    }

    /**
     * Handle MQTT-specific commands by delegating to the registered callback.
     *
     * @param array<string, mixed> $command
     */
    private function handleMqttCommand(int $clientId, string $cmdName, array $command): void
    {
        if ($this->commandCallback === null) {
            $this->sendToClient($clientId, [
                'type' => 'response',
                'payload' => 'MQTT command execution not available (no callback registered)',
                'source' => 'system',
                'timestamp' => date(\DateTimeInterface::ATOM),
                'metadata' => ['command' => $cmdName, 'success' => false],
            ]);
            return;
        }

        try {
            $result = ($this->commandCallback)($cmdName, $command);

            $success = (bool) ($result['success'] ?? false);
            $message = $result['message'] ?? ($success ? 'Command executed' : 'Command failed');
            $data = $result['data'] ?? null;

            $this->sendToClient($clientId, [
                'type' => 'response',
                'payload' => $data ?? $message,
                'source' => 'system',
                'timestamp' => date(\DateTimeInterface::ATOM),
                'metadata' => [
                    'command' => $cmdName,
                    'success' => $success,
                    'message' => $message,
                ],
            ]);
        } catch (\Throwable $e) {
            $this->logger->error("MQTT command {$cmdName} failed: {$e->getMessage()}");

            $this->sendToClient($clientId, [
                'type' => 'response',
                'payload' => "Command failed: {$e->getMessage()}",
                'source' => 'system',
                'timestamp' => date(\DateTimeInterface::ATOM),
                'metadata' => ['command' => $cmdName, 'success' => false, 'error' => $e->getMessage()],
            ]);
        }
    }

    /**
     * Send data to a specific client.
     *
     * @param array<string, mixed> $data
     */
    private function sendToClient(int $clientId, array $data): bool
    {
        if (! isset($this->clients[$clientId])) {
            return false;
        }

        $json = json_encode($data, JSON_THROW_ON_ERROR) . "\n";
        $result = @socket_write($this->clients[$clientId], $json);

        if ($result === false) {
            $this->disconnectClient($clientId);
            return false;
        }

        return true;
    }

    /**
     * Broadcast data to all connected clients.
     *
     * @param array<string, mixed> $data
     */
    private function broadcast(array $data): void
    {
        if (! $this->running || empty($this->clients)) {
            return;
        }

        $json = json_encode($data, JSON_THROW_ON_ERROR) . "\n";

        foreach ($this->clients as $id => $client) {
            $result = @socket_write($client, $json);
            if ($result === false) {
                $this->disconnectClient($id);
            }
        }
    }

    /**
     * Disconnect a client.
     */
    private function disconnectClient(int $clientId): void
    {
        if (isset($this->clients[$clientId])) {
            @socket_close($this->clients[$clientId]);
            unset($this->clients[$clientId]);
            $this->logger->debug("Debug tap client disconnected: {$clientId}");
        }
    }
}
