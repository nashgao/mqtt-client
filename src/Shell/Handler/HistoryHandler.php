<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Handler;

use NashGao\InteractiveShell\Parser\ParsedCommand;
use Nashgao\MQTT\Shell\HandlerContext;
use Nashgao\MQTT\Shell\HandlerResult;

/**
 * Handler for message history commands.
 *
 * Commands:
 * - history - Show last 20 messages
 * - history --limit=N - Show last N messages
 * - history --search=pattern - Search messages
 * - last - Show last message in detail
 */
final class HistoryHandler implements HandlerInterface
{
    public function getCommands(): array
    {
        return ['history', 'last'];
    }

    public function handle(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $cmd = strtolower($command->command);

        if ($cmd === 'last') {
            return $this->showLastMessage($context);
        }

        $rawLimit = $command->getOption('limit');
        $defaultLimit = $context->config->defaultHistoryCount;
        $limit = $rawLimit !== null && is_numeric($rawLimit) ? (int) $rawLimit : $defaultLimit;
        $search = $command->getOption('search');
        $topic = $command->getOption('topic');

        // Search by pattern (uses searchResultLimit)
        if ($search !== null && is_string($search)) {
            $searchLimit = $rawLimit !== null ? $limit : $context->config->searchResultLimit;
            return $this->searchMessages($context, $search, $searchLimit);
        }

        // Filter by topic (uses topicFilterLimit)
        if ($topic !== null && is_string($topic)) {
            $topicLimit = $rawLimit !== null ? $limit : $context->config->topicFilterLimit;
            return $this->showByTopic($context, $topic, $topicLimit);
        }

        // Show last N messages
        return $this->showHistory($context, $limit);
    }

    public function getDescription(): string
    {
        return 'Show or search message history';
    }

    public function getUsage(): array
    {
        return [
            'history                    Show last 20 messages',
            'history --limit=50         Show last 50 messages',
            'history --search=pattern   Search messages by content',
            'history --topic=sensors/#  Show messages for topic pattern',
            'last                       Show last message in detail',
            'history\G                  Show history in vertical format',
        ];
    }

    private function showHistory(HandlerContext $context, int $limit): HandlerResult
    {
        $messages = $context->messageHistory->getLast($limit);

        if (empty($messages)) {
            $context->output->writeln('<comment>No messages in history</comment>');
            return HandlerResult::success();
        }

        $count = count($messages);
        $context->output->writeln(sprintf('<info>Last %d messages:</info>', $count));
        $context->output->writeln('');

        if ($context->verticalFormat) {
            foreach ($messages as $message) {
                $context->output->writeln($context->formatter->format($message));
                $context->output->writeln('');
            }
        } else {
            // Use compact format with message IDs
            $originalFormat = $context->formatter->getFormat();
            $context->formatter->setFormat('compact');

            foreach ($messages as $id => $message) {
                $context->formatter->setDisplayMessageId($id);
                $context->output->writeln($context->formatter->format($message));
            }

            $context->formatter->setDisplayMessageId(null);
            $context->formatter->setFormat($originalFormat);
        }

        return HandlerResult::success();
    }

    private function showLastMessage(HandlerContext $context): HandlerResult
    {
        $message = $context->messageHistory->getLatest();
        $messageId = $context->messageHistory->getLatestId();

        if ($message === null) {
            $context->output->writeln('<comment>No messages in history</comment>');
            return HandlerResult::success();
        }

        $context->output->writeln(sprintf('<info>Message #%d:</info>', $messageId));
        $context->output->writeln('');

        // Show in vertical format
        $originalFormat = $context->formatter->getFormat();
        $context->formatter->setFormat('vertical');

        $context->output->writeln($context->formatter->format($message));

        $context->formatter->setFormat($originalFormat);

        return HandlerResult::success();
    }

    private function searchMessages(HandlerContext $context, string $pattern, int $limit): HandlerResult
    {
        $messages = $context->messageHistory->search($pattern, $limit);

        if (empty($messages)) {
            $context->output->writeln(sprintf('<comment>No messages matching "%s"</comment>', $pattern));
            return HandlerResult::success();
        }

        $count = count($messages);
        $context->output->writeln(sprintf('<info>Found %d messages matching "%s":</info>', $count, $pattern));
        $context->output->writeln('');

        $originalFormat = $context->formatter->getFormat();
        $context->formatter->setFormat('compact');

        foreach ($messages as $id => $message) {
            $context->formatter->setDisplayMessageId($id);
            $context->output->writeln($context->formatter->format($message));
        }

        $context->formatter->setDisplayMessageId(null);
        $context->formatter->setFormat($originalFormat);

        return HandlerResult::success();
    }

    private function showByTopic(HandlerContext $context, string $topicPattern, int $limit): HandlerResult
    {
        $messages = $context->messageHistory->getByTopic($topicPattern, $limit);

        if (empty($messages)) {
            $context->output->writeln(sprintf('<comment>No messages for topic "%s"</comment>', $topicPattern));
            return HandlerResult::success();
        }

        $count = count($messages);
        $context->output->writeln(sprintf('<info>Found %d messages for topic "%s":</info>', $count, $topicPattern));
        $context->output->writeln('');

        $originalFormat = $context->formatter->getFormat();
        $context->formatter->setFormat('compact');

        foreach ($messages as $id => $message) {
            $context->formatter->setDisplayMessageId($id);
            $context->output->writeln($context->formatter->format($message));
        }

        $context->formatter->setDisplayMessageId(null);
        $context->formatter->setFormat($originalFormat);

        return HandlerResult::success();
    }
}
