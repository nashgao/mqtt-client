<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Handler;

use NashGao\InteractiveShell\Parser\ParsedCommand;
use Nashgao\MQTT\Shell\HandlerContext;
use Nashgao\MQTT\Shell\HandlerResult;

/**
 * Handler for bookmarking interesting messages for later review.
 *
 * Commands:
 * - bookmark       Bookmark last message
 * - bookmark ID    Bookmark specific message by ID
 * - bookmarks      List all bookmarks
 * - unbookmark N   Remove bookmark @N
 * - unbookmark all Clear all bookmarks
 */
final class BookmarkHandler implements HandlerInterface
{
    /** @var array<int, int> Bookmark number => Message ID */
    private array $bookmarks = [];

    private int $nextBookmark = 1;

    /**
     * @return array<string>
     */
    public function getCommands(): array
    {
        return ['bookmark', 'bm', 'bookmarks', 'unbookmark'];
    }

    public function handle(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $cmd = strtolower($command->command);

        if ($cmd === 'bookmarks') {
            return $this->listBookmarks($context);
        }

        if ($cmd === 'unbookmark') {
            return $this->unbookmark($command, $context);
        }

        // bookmark [id]
        $rawArg = $command->getArgument(0);

        if ($rawArg === null) {
            // Bookmark last message
            $messageId = $context->messageHistory->getLatestId();

            if ($messageId === null) {
                return HandlerResult::failure('No messages to bookmark');
            }

            return $this->addBookmark($messageId, $context);
        }

        if (! is_numeric($rawArg)) {
            $argStr = is_scalar($rawArg) ? (string) $rawArg : 'invalid';
            return HandlerResult::failure("Invalid message ID: {$argStr}");
        }

        $id = (int) $rawArg;
        $message = $context->messageHistory->get($id);

        if ($message === null) {
            return HandlerResult::failure("Message #{$id} not found");
        }

        return $this->addBookmark($id, $context);
    }

    private function addBookmark(int $messageId, HandlerContext $context): HandlerResult
    {
        // Check if already bookmarked
        foreach ($this->bookmarks as $num => $existingId) {
            if ($existingId === $messageId) {
                $context->output->writeln(sprintf(
                    '<comment>Message #%d already bookmarked as @%d</comment>',
                    $messageId,
                    $num
                ));
                return HandlerResult::success();
            }
        }

        $bookmarkNum = $this->nextBookmark++;
        $this->bookmarks[$bookmarkNum] = $messageId;

        $context->output->writeln(sprintf(
            '<info>Bookmarked message #%d as @%d</info>',
            $messageId,
            $bookmarkNum
        ));

        return HandlerResult::success();
    }

    private function listBookmarks(HandlerContext $context): HandlerResult
    {
        if (empty($this->bookmarks)) {
            $context->output->writeln('<comment>No bookmarks</comment>');
            return HandlerResult::success();
        }

        $context->output->writeln('<info>Bookmarks:</info>');
        $context->output->writeln('');

        foreach ($this->bookmarks as $num => $messageId) {
            $message = $context->messageHistory->get($messageId);
            if ($message === null) {
                $context->output->writeln(sprintf(
                    '  <comment>@%d</comment> -> #%d <comment>(expired)</comment>',
                    $num,
                    $messageId
                ));
            } else {
                $topic = $this->extractTopic($message);
                $time = $message->timestamp->format('H:i:s');
                $context->output->writeln(sprintf(
                    '  <info>@%d</info> -> #%d [%s] %s',
                    $num,
                    $messageId,
                    $time,
                    $topic
                ));
            }
        }

        $context->output->writeln('');
        $context->output->writeln('<comment>Use "expand @N" to view bookmarked messages</comment>');

        return HandlerResult::success();
    }

    private function unbookmark(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $rawArg = $command->getArgument(0);

        if ($rawArg === null) {
            $context->output->writeln('Usage: unbookmark <N> or unbookmark all');
            return HandlerResult::success();
        }

        $arg = is_scalar($rawArg) ? (string) $rawArg : '';

        if (strtolower($arg) === 'all') {
            $count = count($this->bookmarks);
            $this->bookmarks = [];
            $this->nextBookmark = 1;
            $context->output->writeln(sprintf('<info>Cleared %d bookmark(s)</info>', $count));
            return HandlerResult::success();
        }

        if (! is_numeric($arg)) {
            return HandlerResult::failure("Invalid bookmark number: {$arg}");
        }

        $num = (int) $arg;
        if (! isset($this->bookmarks[$num])) {
            return HandlerResult::failure("Bookmark @{$num} not found");
        }

        unset($this->bookmarks[$num]);
        $context->output->writeln(sprintf('<info>Removed bookmark @%d</info>', $num));

        return HandlerResult::success();
    }

    /**
     * Get message ID for a bookmark number.
     */
    public function getBookmark(int $num): ?int
    {
        return $this->bookmarks[$num] ?? null;
    }

    /**
     * Get message ID for the most recent bookmark.
     */
    public function getLastBookmark(): ?int
    {
        if (empty($this->bookmarks)) {
            return null;
        }
        return end($this->bookmarks);
    }

    /**
     * Get all bookmarks.
     *
     * @return array<int, int>
     */
    public function getBookmarks(): array
    {
        return $this->bookmarks;
    }

    /**
     * Extract topic from message.
     */
    private function extractTopic(object $message): string
    {
        if (property_exists($message, 'payload') && is_array($message->payload)) {
            return is_string($message->payload['topic'] ?? null) ? $message->payload['topic'] : '';
        }
        return '';
    }

    public function getDescription(): string
    {
        return 'Bookmark messages for later review';
    }

    /**
     * @return array<string>
     */
    public function getUsage(): array
    {
        return [
            'bookmark          Bookmark last message',
            'bookmark 42       Bookmark message #42',
            'bm                Short alias for bookmark',
            'bookmarks         List all bookmarks',
            'unbookmark 1      Remove bookmark @1',
            'unbookmark all    Clear all bookmarks',
            'expand @1         View 1st bookmarked message',
            'expand @last      View most recent bookmark',
        ];
    }
}
