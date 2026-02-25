<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Examples\DemoShell;

use NashGao\InteractiveShell\Command\CommandResult;
use NashGao\InteractiveShell\Message\Message;
use NashGao\InteractiveShell\Parser\ParsedCommand;
use NashGao\InteractiveShell\Transport\StreamingTransportInterface;

/**
 * Mock transport that generates simulated MQTT messages for demo/testing.
 * No real MQTT broker required.
 */
final class DemoTransport implements StreamingTransportInterface
{
    private bool $connected = false;
    private bool $streaming = false;
    private float $lastMessageTime = 0;

    private MessageGenerator $generator;

    /** @var array<MessageScenario> */
    private array $scenarios = [];

    /** @var array<Message> */
    private array $messageQueue = [];

    /** @var array<callable(Message): void> */
    private array $messageCallbacks = [];

    /** @var array<string, float> Last generation time per scenario */
    private array $scenarioTimers = [];

    public function __construct(
        private readonly float $messageIntervalMs = 500.0,
        private readonly bool $autoGenerate = true,
    ) {
        $this->generator = new MessageGenerator();
    }

    public function connect(): void
    {
        $this->connected = true;
        $this->lastMessageTime = microtime(true);
    }

    public function disconnect(): void
    {
        $this->connected = false;
        $this->streaming = false;
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function send(ParsedCommand $command): CommandResult
    {
        if (!$this->connected) {
            return CommandResult::failure('Not connected');
        }

        return match ($command->command) {
            'subscribe' => CommandResult::success(['subscribed' => true], 'Subscribed (demo mode)'),
            'unsubscribe' => CommandResult::success(['unsubscribed' => true], 'Unsubscribed (demo mode)'),
            'publish' => $this->handlePublish($command),
            'ping' => CommandResult::success(['pong' => true]),
            default => CommandResult::success(['command' => $command->command], 'Command processed (demo mode)'),
        };
    }

    public function ping(): bool
    {
        return $this->connected;
    }

    /**
     * @return array<string, mixed>
     */
    public function getInfo(): array
    {
        return [
            'type' => 'demo',
            'connected' => $this->connected,
            'streaming' => $this->streaming,
            'scenarios' => count($this->scenarios),
            'queue_size' => count($this->messageQueue),
            'messages_generated' => $this->generator->getMessageCount(),
            'auto_generate' => $this->autoGenerate,
            'interval_ms' => $this->messageIntervalMs,
        ];
    }

    public function getEndpoint(): string
    {
        return 'demo://local';
    }

    public function supportsStreaming(): bool
    {
        return true;
    }

    public function sendAsync(ParsedCommand $command): void
    {
        // Handle async commands (non-blocking)
        match ($command->command) {
            'pause' => $this->streaming = false,
            'resume' => $this->streaming = true,
            default => null,
        };
    }

    public function receive(float $timeout = -1): ?Message
    {
        if (!$this->streaming) {
            return null;
        }

        // Check message queue first (for injected messages)
        if ($this->messageQueue !== []) {
            $message = array_shift($this->messageQueue);
            $this->notifyCallbacks($message);
            return $message;
        }

        // Auto-generate messages based on timing
        if ($this->autoGenerate) {
            $message = $this->tryGenerateMessage();
            if ($message !== null) {
                $this->notifyCallbacks($message);
                return $message;
            }
        }

        // Small sleep to prevent busy loop (only for non-blocking)
        if ($timeout >= 0) {
            $sleepMs = $timeout > 0 ? min((int) ($timeout * 100), 50) : 10;
            usleep($sleepMs * 1000);
        }

        return null;
    }

    public function onMessage(callable $callback): void
    {
        $this->messageCallbacks[] = $callback;
    }

    public function startStreaming(): void
    {
        $this->streaming = true;
        $this->lastMessageTime = microtime(true);
        $this->resetScenarioTimers();
    }

    public function stopStreaming(): void
    {
        $this->streaming = false;
    }

    public function isStreaming(): bool
    {
        return $this->streaming;
    }

    /**
     * Inject a specific message into the queue.
     */
    public function injectMessage(Message $message): void
    {
        $this->messageQueue[] = $message;
    }

    /**
     * Inject a custom message with topic and payload.
     */
    public function inject(string $topic, mixed $payload, string $direction = 'incoming', int $qos = 0): void
    {
        $message = $this->generator->generateCustom($topic, $payload, $direction, $qos);
        $this->messageQueue[] = $message;
    }

    /**
     * Add a scenario for message generation.
     */
    public function addScenario(MessageScenario $scenario): void
    {
        $this->scenarios[] = $scenario;
        $this->scenarioTimers[$scenario->name] = microtime(true);
    }

    /**
     * Add multiple scenarios at once.
     *
     * @param array<MessageScenario> $scenarios
     */
    public function addScenarios(array $scenarios): void
    {
        foreach ($scenarios as $scenario) {
            $this->addScenario($scenario);
        }
    }

    /**
     * Clear all scenarios.
     */
    public function clearScenarios(): void
    {
        $this->scenarios = [];
        $this->scenarioTimers = [];
    }

    /**
     * Get all loaded scenarios.
     *
     * @return array<MessageScenario>
     */
    public function getScenarios(): array
    {
        return $this->scenarios;
    }

    /**
     * Get the message generator.
     */
    public function getGenerator(): MessageGenerator
    {
        return $this->generator;
    }

    /**
     * Get current queue size.
     */
    public function getQueueSize(): int
    {
        return count($this->messageQueue);
    }

    /**
     * Clear the message queue.
     */
    public function clearQueue(): void
    {
        $this->messageQueue = [];
    }

    private function handlePublish(ParsedCommand $command): CommandResult
    {
        $topic = $command->getArgument(0, 'demo/topic');
        $payload = $command->getArgument(1, '{}');

        // For publish, we can inject the message back as an "echo"
        if (is_string($topic)) {
            $parsedPayload = is_string($payload) ? json_decode($payload, true) : $payload;
            $this->inject($topic, $parsedPayload ?? $payload, 'outgoing', (int) $command->getOption('qos', 0));
        }

        return CommandResult::success(['published' => true], 'Published (demo mode)');
    }

    private function tryGenerateMessage(): ?Message
    {
        if ($this->scenarios === []) {
            return null;
        }

        $now = microtime(true);
        $intervalSec = $this->messageIntervalMs / 1000.0;

        // Check if base interval has passed
        if (($now - $this->lastMessageTime) < $intervalSec) {
            return null;
        }

        // Find a scenario that's ready to generate
        foreach ($this->scenarios as $scenario) {
            $scenarioInterval = 1.0 / max($scenario->frequency, 0.01);
            $lastTime = $this->scenarioTimers[$scenario->name] ?? 0;

            if (($now - $lastTime) >= $scenarioInterval) {
                $this->lastMessageTime = $now;
                $this->scenarioTimers[$scenario->name] = $now;
                return $this->generator->generate($scenario);
            }
        }

        // If no specific scenario is ready, pick a random one
        $scenario = $this->scenarios[array_rand($this->scenarios)];
        $this->lastMessageTime = $now;
        return $this->generator->generate($scenario);
    }

    private function resetScenarioTimers(): void
    {
        $now = microtime(true);
        foreach ($this->scenarios as $scenario) {
            $this->scenarioTimers[$scenario->name] = $now;
        }
    }

    private function notifyCallbacks(Message $message): void
    {
        foreach ($this->messageCallbacks as $callback) {
            $callback($message);
        }
    }
}
