<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Handler;

use NashGao\InteractiveShell\Message\Message;
use NashGao\InteractiveShell\Parser\ParsedCommand;
use Nashgao\MQTT\Shell\HandlerContext;
use Nashgao\MQTT\Shell\HandlerResult;

/**
 * Handler for expanding truncated message payloads.
 *
 * Commands:
 * - expand       Show last message in full
 * - expand -N    Show Nth from last (e.g., expand -3 = 3rd from last)
 * - expand ID    Show message with specific ID
 * - expand @N    Show Nth bookmarked message (Phase 2)
 * - expand @last Show most recent bookmark (Phase 2)
 */
final class ExpandHandler implements HandlerInterface
{
    private ?BookmarkHandler $bookmarkHandler = null;

    /**
     * @return array<string>
     */
    public function getCommands(): array
    {
        return ['expand', 'exp', 'e', 'x'];
    }

    public function setBookmarkHandler(BookmarkHandler $handler): void
    {
        $this->bookmarkHandler = $handler;
    }

    public function handle(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $rawArg = $command->getArgument(0);

        // No argument = last message
        if ($rawArg === null) {
            $messages = $context->messageHistory->getLast(1);
            if (empty($messages)) {
                return HandlerResult::failure('No messages in history');
            }
            $id = array_key_first($messages);
            $message = reset($messages);
            return $this->expandMessage($message, $context, "Message #{$id}");
        }

        $arg = is_scalar($rawArg) ? (string) $rawArg : '';

        // Bookmark reference: expand @1 or expand @last
        if (str_starts_with($arg, '@')) {
            return $this->expandBookmark($arg, $context);
        }

        // Relative: expand -3 (3rd from last)
        if (str_starts_with($arg, '-') && is_numeric(substr($arg, 1))) {
            $offset = (int) substr($arg, 1);
            if ($offset < 1) {
                return HandlerResult::failure('Offset must be at least 1');
            }

            $messages = $context->messageHistory->getLast($offset);

            if (empty($messages)) {
                return HandlerResult::failure("No message at position -{$offset}");
            }

            if (count($messages) < $offset) {
                return HandlerResult::failure(sprintf(
                    "Only %d messages in history, cannot get -%d",
                    count($messages),
                    $offset
                ));
            }

            // Get the first message (oldest of the last N = Nth from last)
            $id = array_key_first($messages);
            $message = reset($messages);

            return $this->expandMessage($message, $context, "Message #{$id} (-{$offset})");
        }

        // Absolute: expand 42
        if (is_numeric($arg)) {
            $messageId = (int) $arg;
            $message = $context->messageHistory->get($messageId);

            if ($message === null) {
                return HandlerResult::failure("Message #{$messageId} not found in history");
            }

            return $this->expandMessage($message, $context, "Message #{$messageId}");
        }

        return HandlerResult::failure("Invalid argument: {$arg}. Use: expand, expand -N, or expand <id>");
    }

    private function expandBookmark(string $arg, HandlerContext $context): HandlerResult
    {
        if ($this->bookmarkHandler === null) {
            return HandlerResult::failure('Bookmark feature not available');
        }

        $bookmarkRef = substr($arg, 1);
        $messageId = null;

        if ($bookmarkRef === 'last') {
            $messageId = $this->bookmarkHandler->getLastBookmark();
        } elseif (is_numeric($bookmarkRef)) {
            $messageId = $this->bookmarkHandler->getBookmark((int) $bookmarkRef);
        } else {
            return HandlerResult::failure("Invalid bookmark reference: {$arg}. Use @N or @last");
        }

        if ($messageId === null) {
            return HandlerResult::failure("Bookmark @{$bookmarkRef} not found");
        }

        $message = $context->messageHistory->get($messageId);

        if ($message === null) {
            return HandlerResult::failure("Bookmarked message #{$messageId} no longer in history (expired)");
        }

        return $this->expandMessage($message, $context, "Bookmarked message @{$bookmarkRef} (#{$messageId})");
    }

    private function expandMessage(Message $message, HandlerContext $context, string $label): HandlerResult
    {
        $context->output->writeln("<info>{$label}:</info>");
        $context->output->writeln('');

        // Use vertical format for full detail
        $originalFormat = $context->formatter->getFormat();
        $context->formatter->setFormat('vertical');
        $context->output->writeln($context->formatter->format($message));
        $context->formatter->setFormat($originalFormat);

        return HandlerResult::success();
    }

    public function getDescription(): string
    {
        return 'Show full payload for a message';
    }

    /**
     * @return array<string>
     */
    public function getUsage(): array
    {
        return [
            'expand          Show last message in full',
            'expand -1       Same as above',
            'expand -3       Show 3rd from last message',
            'expand 42       Show message #42',
            'x -2            Short alias (x = expand)',
            'expand @1       Show 1st bookmarked message',
            'expand @last    Show most recent bookmark',
        ];
    }
}
