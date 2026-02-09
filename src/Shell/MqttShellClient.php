<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell;

use NashGao\InteractiveShell\Message\Message;
use NashGao\InteractiveShell\Parser\ParsedCommand;
use NashGao\InteractiveShell\Transport\StreamingTransportInterface;
use Nashgao\MQTT\Shell\Config\ShellConfig;
use Nashgao\MQTT\Shell\Filter\FilterExpression;
use Nashgao\MQTT\Shell\Filter\FilterPresetManager;
use Nashgao\MQTT\Shell\Formatter\MqttMessageFormatter;
use Nashgao\MQTT\Shell\Handler\BookmarkHandler;
use Nashgao\MQTT\Shell\Handler\ExitHandler;
use Nashgao\MQTT\Shell\Handler\ExpandHandler;
use Nashgao\MQTT\Shell\Handler\ExportHandler;
use Nashgao\MQTT\Shell\Handler\FieldsHandler;
use Nashgao\MQTT\Shell\Handler\FilterHandler;
use Nashgao\MQTT\Shell\Handler\FormatHandler;
use Nashgao\MQTT\Shell\Handler\HandlerInterface;
use Nashgao\MQTT\Shell\Handler\JsonPathHandler;
use Nashgao\MQTT\Shell\Handler\HelpHandler;
use Nashgao\MQTT\Shell\Handler\HexHandler;
use Nashgao\MQTT\Shell\Handler\HistoryHandler;
use Nashgao\MQTT\Shell\Handler\LatencyHandler;
use Nashgao\MQTT\Shell\Handler\LogHandler;
use Nashgao\MQTT\Shell\Handler\PauseResumeHandler;
use Nashgao\MQTT\Shell\Handler\PoolHandler;
use Nashgao\MQTT\Shell\Handler\PublishHandler;
use Nashgao\MQTT\Shell\Handler\StatsHandler;
use Nashgao\MQTT\Shell\Handler\StepHandler;
use Nashgao\MQTT\Shell\Handler\SubscribeHandler;
use Nashgao\MQTT\Shell\Handler\VisualizeHandler;
use Nashgao\MQTT\Shell\Handler\RuleHandler;
use Nashgao\MQTT\Shell\Debug\StepThroughState;
use Nashgao\MQTT\Shell\History\MessageHistory;
use Nashgao\MQTT\Shell\Stats\StatsCollector;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * MQTT-specific debug shell client.
 *
 * Features:
 * - MQTT topic wildcard filtering (+ and #)
 * - Message statistics and analytics
 * - Publish/Subscribe commands
 * - Message history and search
 * - MQTT-specific formatting
 */
final class MqttShellClient
{
    /** @var array<string, HandlerInterface> */
    private array $handlers = [];

    /** @var array<string, string> */
    private array $aliases = [];

    private FilterExpression $filter;

    private FilterPresetManager $presetManager;

    private MqttMessageFormatter $formatter;

    private MessageHistory $messageHistory;

    private StatsCollector $stats;

    private LogHandler $logHandler;

    private StepThroughState $stepState;

    private readonly ShellConfig $config;

    private readonly StreamingTransportInterface $transport;

    private readonly string $prompt;

    private readonly int $channelBufferSize;

    private bool $running = false;

    private bool $paused = false;

    private bool $verticalFormat = false;

    /**
     * @param array<string, string> $defaultAliases
     * @param ShellConfig|null $config Shell configuration (uses defaults if null)
     */
    public function __construct(
        StreamingTransportInterface $transport,
        string $prompt = 'mqtt> ',
        array $defaultAliases = [],
        ?ShellConfig $config = null,
    ) {
        $this->transport = $transport;
        $this->prompt = $prompt;
        $this->config = $config ?? ShellConfig::default();
        $this->channelBufferSize = $this->config->channelBufferSize;
        $this->aliases = array_merge($this->getDefaultAliases(), $defaultAliases);

        $this->initialize();
        $this->registerHandlers();
    }

    /**
     * Run the interactive shell.
     */
    public function run(InputInterface $input, OutputInterface $output): int
    {
        $this->running = true;

        // Connect
        try {
            $this->transport->connect();
        } catch (\Throwable $e) {
            $output->writeln("<error>Failed to connect: {$e->getMessage()}</error>");
            return 1;
        }

        // Print welcome
        foreach ($this->getWelcomeLines() as $line) {
            $output->writeln($line);
        }

        // Check for Swoole
        if (extension_loaded('swoole')) {
            return $this->runWithSwoole($output);
        }

        // Fallback to polling mode
        return $this->runPolling($output);
    }

    /**
     * Get the shell configuration.
     */
    public function getConfig(): ShellConfig
    {
        return $this->config;
    }

    /**
     * Set the message filter using SQL-like expression.
     */
    public function setFilter(string $expression): void
    {
        $this->filter->where($expression);
    }

    /**
     * Get the filter.
     */
    public function getFilter(): FilterExpression
    {
        return $this->filter;
    }

    /**
     * Get the preset manager.
     */
    public function getPresetManager(): FilterPresetManager
    {
        return $this->presetManager;
    }

    /**
     * Get message history.
     */
    public function getMessageHistory(): MessageHistory
    {
        return $this->messageHistory;
    }

    /**
     * Get stats collector.
     */
    public function getStats(): StatsCollector
    {
        return $this->stats;
    }

    /**
     * Check if paused.
     */
    public function isPaused(): bool
    {
        return $this->paused;
    }

    /**
     * Set paused state.
     */
    public function setPaused(bool $paused): void
    {
        $this->paused = $paused;
    }

    /**
     * Stop the shell.
     */
    public function stop(): void
    {
        $this->running = false;
    }

    // ─── Initialization ──────────────────────────────────────────────────

    /**
     * Initialize MQTT-specific components.
     */
    private function initialize(): void
    {
        $this->filter = new FilterExpression();
        $this->presetManager = new FilterPresetManager();
        $this->formatter = new MqttMessageFormatter($this->config);
        $this->messageHistory = new MessageHistory($this->config->messageHistoryLimit);
        $this->stats = new StatsCollector(
            $this->config->rateWindowSeconds,
            $this->config->latencyWindowSize,
            $this->config->topTopicsLimit,
            $this->config->topicTruncationThreshold,
        );
        $this->logHandler = new LogHandler();
        $this->stepState = new StepThroughState();
    }

    /**
     * Register MQTT-specific handlers.
     */
    private function registerHandlers(): void
    {
        // Create expand and bookmark handlers with cross-reference
        $bookmarkHandler = new BookmarkHandler();
        $expandHandler = new ExpandHandler();
        $expandHandler->setBookmarkHandler($bookmarkHandler);

        // Create fields handler for field filtering
        $fieldsHandler = new FieldsHandler();

        /** @var array<HandlerInterface> $handlers */
        $handlers = [
            // Monitoring
            new FilterHandler(),
            new PauseResumeHandler(),
            new StatsHandler(),
            new FormatHandler(),
            $this->logHandler,
            new LatencyHandler(),
            // History & Navigation
            new HistoryHandler(),
            $expandHandler,
            $bookmarkHandler,
            new ExportHandler(),
            // MQTT Operations
            new PublishHandler(),
            new SubscribeHandler(),
            // Pool Management
            new PoolHandler(),
            // Advanced Features
            new HexHandler(),
            new StepHandler($this->stepState),
            new VisualizeHandler(),
            // JSON Payload Features
            $fieldsHandler,
            new JsonPathHandler(),
            // Rule Engine
            new RuleHandler(),
            // System
            new HelpHandler($this->handlers),
            new ExitHandler(),
        ];

        foreach ($handlers as $handler) {
            foreach ($handler->getCommands() as $cmd) {
                $this->handlers[$cmd] = $handler;
            }
        }

        // Update help handler with all handlers
        if (isset($this->handlers['help'])) {
            /** @var HelpHandler $helpHandler */
            $helpHandler = $this->handlers['help'];
            $helpHandler->setHandlers($this->handlers);
        }
    }

    // ─── Shell Execution ────────────────────────────────────────────────

    /**
     * Run with Swoole coroutines.
     */
    private function runWithSwoole(OutputInterface $output): int
    {
        // Start streaming
        $this->transport->startStreaming();

        // @phpstan-ignore-next-line (Swoole function only available when ext-swoole is loaded)
        \Swoole\Coroutine\run(function () use ($output): void {
            $channel = new \Swoole\Coroutine\Channel($this->channelBufferSize);

            // Coroutine 1: Message receiver
            go(function () use ($channel): void {
                while ($this->running) {
                    $message = $this->transport->receive(0.1);
                    if ($message !== null) {
                        $channel->push($message);
                    }
                    \Swoole\Coroutine::sleep(0.01);
                }
            });

            // Coroutine 2: Message display
            go(function () use ($channel, $output): void {
                while ($this->running) {
                    $message = $channel->pop(0.1);
                    if ($message instanceof Message) {
                        $this->processMessage($message, $output);
                    }
                }
            });

            // Coroutine 3: Input handler
            go(function () use ($output): void {
                while ($this->running) {
                    $line = $this->readLineNonBlocking();
                    if ($line !== null) {
                        $this->handleInput($line, $output);
                    }
                    \Swoole\Coroutine::sleep(0.01);
                }
            });

            // Wait for exit
            while ($this->running) {
                \Swoole\Coroutine::sleep(0.1);
            }
        });

        $this->cleanup($output);
        return 0;
    }

    /**
     * Run in polling mode (without Swoole).
     */
    private function runPolling(OutputInterface $output): int
    {
        $output->writeln('<comment>Polling mode - install Swoole for better performance</comment>');
        $output->writeln('');

        // Start streaming
        $this->transport->startStreaming();

        // Set non-blocking stdin
        stream_set_blocking(STDIN, false);

        while ($this->running) {
            // Check for messages
            $message = $this->transport->receive(0);
            if ($message !== null) {
                $this->processMessage($message, $output);
            }

            // Check for input
            $line = fgets(STDIN);
            if ($line !== false) {
                $line = rtrim($line, "\r\n");
                $this->handleInput($line, $output);
            }

            // Small delay to prevent CPU spinning
            usleep(10000); // 10ms
        }

        // Restore blocking
        stream_set_blocking(STDIN, true);

        $this->cleanup($output);
        return 0;
    }

    /**
     * Handle user input.
     */
    private function handleInput(string $line, OutputInterface $output): void
    {
        $line = trim($line);
        if ($line === '') {
            return;
        }

        // Apply alias
        $line = $this->applyAlias($line);

        // Parse command
        $parsed = $this->parseCommand($line);
        $command = strtolower($parsed->command);

        // Check for handler
        if (isset($this->handlers[$command])) {
            $context = $this->createContext($output);
            $result = $this->handlers[$command]->handle($parsed, $context);

            // Check for exit request
            if ($result->shouldExit ?? false) {
                $this->running = false;
            }
            return;
        }

        // Unknown command
        $output->writeln("<error>Unknown command: {$command}</error>");
        $output->writeln("Type 'help' for available commands");
    }

    /**
     * Apply alias to input.
     */
    private function applyAlias(string $input): string
    {
        $parts = explode(' ', $input, 2);
        $command = $parts[0];

        if (isset($this->aliases[$command])) {
            $replacement = $this->aliases[$command];
            return isset($parts[1]) ? $replacement . ' ' . $parts[1] : $replacement;
        }

        return $input;
    }

    /**
     * Parse command string into ParsedCommand.
     */
    private function parseCommand(string $input): ParsedCommand
    {
        $parts = preg_split('/\s+/', $input);
        if ($parts === false || empty($parts)) {
            return ParsedCommand::empty();
        }

        $command = array_shift($parts);
        return new ParsedCommand(
            command: $command ?? '',
            arguments: $parts,
            options: [],
            raw: $input,
            hasVerticalTerminator: false,
        );
    }

    /**
     * Create handler context.
     */
    private function createContext(OutputInterface $output): HandlerContext
    {
        return new HandlerContext(
            output: $output,
            transport: $this->transport,
            filter: $this->filter,
            presetManager: $this->presetManager,
            formatter: $this->formatter,
            messageHistory: $this->messageHistory,
            stats: $this->stats,
            config: $this->config,
            verticalFormat: $this->verticalFormat,
            paused: $this->paused,
        );
    }

    /**
     * Process incoming MQTT message through pipeline.
     */
    private function processMessage(Message $message, OutputInterface $output): void
    {
        // Record stats
        $this->stats->record($message);

        // Add to history
        $this->messageHistory->add($message);

        // Check filter
        if (!$this->filter->matches($message)) {
            return;
        }

        // Format message
        $formatted = $this->formatter->format($message);

        // Log to file (always, regardless of pause state)
        if ($this->logHandler->isLogging()) {
            $cleanFormatted = preg_replace('/\033\[[0-9;]*m/', '', $formatted) ?? $formatted;
            $this->logHandler->logMessage($cleanFormatted);
        }

        // Display (only if not paused)
        if (!$this->paused) {
            $output->writeln($formatted);
        }
    }

    /**
     * Cleanup on exit.
     */
    private function cleanup(OutputInterface $output): void
    {
        $this->transport->stopStreaming();
        $this->transport->disconnect();
        $this->printSessionEnd($output);
    }

    /**
     * Get MQTT-specific default aliases.
     *
     * @return array<string, string>
     */
    private function getDefaultAliases(): array
    {
        return [
            // System
            'q' => 'exit',
            'quit' => 'exit',
            '?' => 'help',
            // Monitoring
            'f' => 'filter',
            'p' => 'pause',
            'r' => 'resume',
            's' => 'stats',
            'c' => 'filter clear',
            // History
            'h' => 'history',
            'l' => 'last',
            'll' => 'history --limit=50',
            // MQTT Operations
            'pub' => 'publish',
            'sub' => 'subscribe',
            'unsub' => 'unsubscribe',
            // Step-through mode
            'n' => 'next',
            // Visualization
            'viz' => 'visualize',
            // Content filters
            'g' => 'filter grep ',
        ];
    }

    /**
     * Get MQTT-specific welcome message.
     *
     * @return string[]
     */
    private function getWelcomeLines(): array
    {
        return [
            '',
            '<info>MQTT Debug Shell</info>',
            sprintf('Prompt: <comment>%s</comment>', $this->prompt),
            'Type <comment>help</comment> for available commands, <comment>exit</comment> to quit',
            '',
        ];
    }

    /**
     * Print session end message with stats.
     */
    private function printSessionEnd(OutputInterface $output): void
    {
        $output->writeln('');
        $output->writeln(sprintf(
            'Session ended. Total messages: %d',
            $this->stats->getTotalMessages()
        ));
    }

    /**
     * Non-blocking readline for Swoole.
     */
    private function readLineNonBlocking(): ?string
    {
        static $buffer = '';

        $read = [STDIN];
        $write = $except = [];
        $changed = @stream_select($read, $write, $except, 0, 10000);

        if ($changed > 0) {
            $char = fgetc(STDIN);
            if ($char === false || $char === "\n") {
                $line = $buffer;
                $buffer = '';
                return $line;
            }
            $buffer .= $char;
        }

        return null;
    }
}
