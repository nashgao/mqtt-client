<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Handler;

use NashGao\InteractiveShell\Parser\ParsedCommand;
use Nashgao\MQTT\Shell\HandlerContext;
use Nashgao\MQTT\Shell\HandlerResult;

/**
 * Handler for statistics commands.
 *
 * Commands:
 * - stats - Show current statistics
 * - stats topics - Show per-topic statistics
 * - stats reset - Reset statistics
 */
final class StatsHandler implements HandlerInterface
{
    public function getCommands(): array
    {
        return ['stats'];
    }

    public function handle(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $arg = $command->getArgument(0);

        // Reset statistics
        if ($arg === 'reset') {
            $context->stats->reset();
            $context->output->writeln('<info>Statistics reset</info>');
            return HandlerResult::success();
        }

        // Show topic statistics
        if ($arg === 'topics') {
            $this->showTopicStats($context);
            return HandlerResult::success();
        }

        // Show full statistics
        $context->output->writeln($context->stats->formatDisplay());

        return HandlerResult::success();
    }

    public function getDescription(): string
    {
        return 'Show message statistics';
    }

    public function getUsage(): array
    {
        return [
            'stats           Show full statistics summary',
            'stats topics    Show per-topic message counts',
            'stats reset     Reset all statistics',
        ];
    }

    private function showTopicStats(HandlerContext $context): void
    {
        $topics = $context->stats->getTopTopics(20);

        if (empty($topics)) {
            $context->output->writeln('<comment>No topic statistics available</comment>');
            return;
        }

        $context->output->writeln('+' . str_repeat('-', 50) . '+');
        $context->output->writeln('|' . str_pad('Topic Statistics', 50, ' ', STR_PAD_BOTH) . '|');
        $context->output->writeln('+' . str_repeat('-', 50) . '+');

        foreach ($topics as $topic => $count) {
            $truncatedTopic = mb_strlen($topic) > 38 ? mb_substr($topic, 0, 35) . '...' : $topic;
            $context->output->writeln(sprintf('| %-38s %8d |', $truncatedTopic, $count));
        }

        $context->output->writeln('+' . str_repeat('-', 50) . '+');
    }
}
