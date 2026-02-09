<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Handler;

use NashGao\InteractiveShell\Message\Message;
use NashGao\InteractiveShell\Parser\ParsedCommand;
use Nashgao\MQTT\Shell\HandlerContext;
use Nashgao\MQTT\Shell\HandlerResult;

/**
 * Handler for hex dump display commands.
 *
 * Commands:
 * - hex <id> - Show message payload as hex dump
 * - hex last - Show last message as hex dump
 * - hexdump <id> - Alias for hex
 * - hex --bytes=N - Control bytes per line (default 16)
 */
final class HexHandler implements HandlerInterface
{
    public function getCommands(): array
    {
        return ['hex', 'hexdump'];
    }

    public function handle(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $args = $command->arguments;

        if (empty($args)) {
            $context->output->writeln('<error>Usage: hex <message-id|last> [--bytes=N]</error>');
            $context->output->writeln('Example: hex 42');
            $context->output->writeln('Example: hex last --bytes=32');
            return HandlerResult::failure('Missing message ID or "last"');
        }

        $identifier = strtolower($args[0]);
        $rawBytes = $command->getOption('bytes');
        $bytesPerLine = $rawBytes !== null && is_numeric($rawBytes) ? (int) $rawBytes : 16;

        // Validate bytes per line
        if ($bytesPerLine < 1 || $bytesPerLine > 64) {
            $context->output->writeln('<error>Bytes per line must be between 1 and 64</error>');
            return HandlerResult::failure('Invalid bytes per line');
        }

        // Get message from history
        $message = null;
        if ($identifier === 'last') {
            $message = $context->messageHistory->getLatest();
        } elseif (is_numeric($identifier)) {
            $messageId = (int) $identifier;
            $message = $context->messageHistory->get($messageId);
        } else {
            $context->output->writeln(sprintf('<error>Invalid message identifier: %s</error>', $identifier));
            return HandlerResult::failure('Invalid identifier');
        }

        // Check if message was found
        if ($message === null) {
            $context->output->writeln(sprintf('<error>Message not found: %s</error>', $identifier));
            return HandlerResult::success();
        }

        // Extract raw payload
        $payload = $this->extractRawPayload($message);

        // Display hex dump
        $context->output->writeln('');
        $context->output->writeln(sprintf('<info>Message %s - Hex Dump (%d bytes per line):</info>', $identifier, $bytesPerLine));
        $context->output->writeln('');

        $hexOutput = $context->formatter->formatHexBytes($payload, $bytesPerLine);
        $context->output->writeln($hexOutput);
        $context->output->writeln('');

        return HandlerResult::success();
    }

    public function getDescription(): string
    {
        return 'Display message payload as hex dump';
    }

    public function getUsage(): array
    {
        return [
            'hex <id>              Show message payload as hex dump',
            'hex last              Show last message as hex dump',
            'hexdump <id>          Alias for hex',
            'hex <id> --bytes=32   Show with 32 bytes per line (default: 16)',
        ];
    }

    /**
     * Extract raw payload as string from Message.
     */
    private function extractRawPayload(Message $message): string
    {
        $payload = $message->payload;

        if (is_string($payload)) {
            return $payload;
        }

        if (is_array($payload)) {
            // Extract the actual message content from MQTT payload structure
            $content = $payload['message'] ?? $payload;

            if (is_string($content)) {
                return $content;
            }

            // Return JSON representation for non-string content
            return json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
        }

        return (string) json_encode($payload);
    }
}
