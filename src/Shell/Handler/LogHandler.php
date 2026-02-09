<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Handler;

use NashGao\InteractiveShell\Parser\ParsedCommand;
use Nashgao\MQTT\Shell\HandlerContext;
use Nashgao\MQTT\Shell\HandlerResult;

/**
 * Handler for logging commands.
 *
 * Commands:
 * - log start <file> - Start logging messages to file
 * - log stop - Stop logging
 * - log status - Show logging status
 */
final class LogHandler implements HandlerInterface
{
    private ?string $logFile = null;

    /** @var null|resource */
    private $fileHandle;

    private int $loggedMessages = 0;

    public function getCommands(): array
    {
        return ['log'];
    }

    public function handle(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $rawSubCommand = $command->getArgument(0);
        $subCommand = $rawSubCommand !== null && is_scalar($rawSubCommand) ? (string) $rawSubCommand : 'status';

        return match (strtolower($subCommand)) {
            'start' => $this->handleStart($command, $context),
            'stop' => $this->handleStop($context),
            'status' => $this->handleStatus($context),
            default => $this->handleUnknown($subCommand, $context),
        };
    }

    public function getDescription(): string
    {
        return 'Log messages to a file';
    }

    public function getUsage(): array
    {
        return [
            'log start <file>   Start logging messages to file',
            'log stop           Stop logging to file',
            'log status         Show current logging status',
        ];
    }

    /**
     * Check if logging is active.
     */
    public function isLogging(): bool
    {
        return $this->fileHandle !== null;
    }

    /**
     * Log a message if logging is active.
     */
    public function logMessage(string $formatted): void
    {
        if ($this->fileHandle === null) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s.v');
        $line = "[{$timestamp}] {$formatted}\n";

        fwrite($this->fileHandle, $line);
        ++$this->loggedMessages;
    }

    /**
     * Get the current log file path.
     */
    public function getLogFile(): ?string
    {
        return $this->logFile;
    }

    /**
     * Get the number of logged messages.
     */
    public function getLoggedCount(): int
    {
        return $this->loggedMessages;
    }

    private function handleStart(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        if ($this->fileHandle !== null) {
            $context->output->writeln(sprintf(
                '<comment>Already logging to: %s (%d messages)</comment>',
                $this->logFile,
                $this->loggedMessages
            ));
            return HandlerResult::success();
        }

        $rawFile = $command->getArgument(1);

        if ($rawFile === null || $rawFile === '') {
            $context->output->writeln('<error>File path is required</error>');
            $context->output->writeln('Usage: log start <file>');
            return HandlerResult::failure('File path is required');
        }

        $file = is_scalar($rawFile) ? (string) $rawFile : '';

        // Expand ~ to home directory
        if (str_starts_with($file, '~/')) {
            $home = getenv('HOME');
            if ($home !== false) {
                $file = $home . substr($file, 1);
            }
        }

        // Create directory if needed
        $dir = dirname($file);
        if (! is_dir($dir)) {
            if (! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
                $context->output->writeln(sprintf('<error>Cannot create directory: %s</error>', $dir));
                return HandlerResult::failure("Cannot create directory: {$dir}");
            }
        }

        $handle = @fopen($file, 'a');
        if ($handle === false) {
            $context->output->writeln(sprintf('<error>Cannot open file for writing: %s</error>', $file));
            return HandlerResult::failure("Cannot open file: {$file}");
        }

        $this->fileHandle = $handle;
        $this->logFile = $file;
        $this->loggedMessages = 0;

        // Write header
        $header = sprintf(
            "# MQTT Debug Log started at %s\n# ================================================================================\n\n",
            date('Y-m-d H:i:s')
        );
        fwrite($this->fileHandle, $header);

        $context->output->writeln(sprintf('<info>Logging started: %s</info>', $file));
        return HandlerResult::success();
    }

    private function handleStop(HandlerContext $context): HandlerResult
    {
        if ($this->fileHandle === null) {
            $context->output->writeln('<comment>Not currently logging</comment>');
            return HandlerResult::success();
        }

        // Write footer
        $footer = sprintf(
            "\n# ================================================================================\n# Log ended at %s\n# Total messages logged: %d\n",
            date('Y-m-d H:i:s'),
            $this->loggedMessages
        );
        fwrite($this->fileHandle, $footer);

        fclose($this->fileHandle);

        $context->output->writeln(sprintf(
            '<info>Logging stopped: %s (%d messages)</info>',
            $this->logFile,
            $this->loggedMessages
        ));

        $this->fileHandle = null;
        $this->logFile = null;
        $this->loggedMessages = 0;

        return HandlerResult::success();
    }

    private function handleStatus(HandlerContext $context): HandlerResult
    {
        if ($this->fileHandle === null) {
            $context->output->writeln('<comment>Not currently logging</comment>');
            $context->output->writeln('Use "log start <file>" to begin logging');
            return HandlerResult::success();
        }

        $context->output->writeln(sprintf(
            '<info>Logging active: %s (%d messages)</info>',
            $this->logFile,
            $this->loggedMessages
        ));

        return HandlerResult::success();
    }

    private function handleUnknown(string $subCommand, HandlerContext $context): HandlerResult
    {
        $context->output->writeln(sprintf('<error>Unknown log command: %s</error>', $subCommand));
        $context->output->writeln('Available commands: start, stop, status');
        return HandlerResult::failure("Unknown log command: {$subCommand}");
    }
}
