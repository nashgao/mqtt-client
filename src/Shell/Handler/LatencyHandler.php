<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Handler;

use NashGao\InteractiveShell\Parser\ParsedCommand;
use Nashgao\MQTT\Shell\HandlerContext;
use Nashgao\MQTT\Shell\HandlerResult;

/**
 * Handler for latency commands.
 *
 * Commands:
 * - latency - Show latency statistics
 * - latency histogram - Show latency histogram distribution
 * - latency reset - Reset latency measurements
 * - latency stats - Show detailed latency statistics (default)
 */
final class LatencyHandler implements HandlerInterface
{
    public function getCommands(): array
    {
        return ['latency'];
    }

    public function handle(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $arg = $command->getArgument(0);

        // Reset latency measurements
        if ($arg === 'reset') {
            $context->stats->reset();
            $context->output->writeln('<info>Latency measurements reset</info>');
            return HandlerResult::success();
        }

        // Show histogram distribution
        if ($arg === 'histogram') {
            $this->showHistogram($context);
            return HandlerResult::success();
        }

        // Show statistics (default)
        $this->showStats($context);

        return HandlerResult::success();
    }

    public function getDescription(): string
    {
        return 'Show latency statistics';
    }

    public function getUsage(): array
    {
        return [
            'latency              Show latency statistics summary',
            'latency stats        Show detailed latency statistics',
            'latency histogram    Show latency distribution histogram',
            'latency reset        Reset latency measurements',
        ];
    }

    private function showStats(HandlerContext $context): void
    {
        $stats = $context->stats->getLatencyStats();

        assert(is_int($stats['count']));
        $count = $stats['count'];
        if ($count === 0) {
            $context->output->writeln('<comment>No latency measurements available</comment>');
            return;
        }

        $min = (float) $stats['min'];
        $max = (float) $stats['max'];
        $avg = (float) $stats['avg'];
        $p50 = (float) $stats['p50'];
        $p95 = (float) $stats['p95'];
        $p99 = (float) $stats['p99'];

        $context->output->writeln('+' . str_repeat('-', 50) . '+');
        $context->output->writeln('|' . str_pad('Latency Statistics', 50, ' ', STR_PAD_BOTH) . '|');
        $context->output->writeln('+' . str_repeat('-', 50) . '+');

        $context->output->writeln(sprintf('| Total Measurements:   %-27d |', $count));
        $context->output->writeln(sprintf('| Minimum:              %-24.2f ms |', $min));
        $context->output->writeln(sprintf('| Maximum:              %-24.2f ms |', $max));
        $context->output->writeln(sprintf('| Average:              %-24.2f ms |', $avg));
        $context->output->writeln('+' . str_repeat('-', 50) . '+');
        $context->output->writeln('| Percentiles:                                     |');
        $context->output->writeln(sprintf('|   P50 (Median):       %-24.2f ms |', $p50));
        $context->output->writeln(sprintf('|   P95:                %-24.2f ms |', $p95));
        $context->output->writeln(sprintf('|   P99:                %-24.2f ms |', $p99));
        $context->output->writeln('+' . str_repeat('-', 50) . '+');
    }

    private function showHistogram(HandlerContext $context): void
    {
        $histogram = $context->stats->getLatencyHistogram(10);

        if (empty($histogram)) {
            $context->output->writeln('<comment>No latency measurements available</comment>');
            return;
        }

        $context->output->writeln('+' . str_repeat('-', 60) . '+');
        $context->output->writeln('|' . str_pad('Latency Distribution Histogram', 60, ' ', STR_PAD_BOTH) . '|');
        $context->output->writeln('+' . str_repeat('-', 60) . '+');

        $maxCount = max($histogram);
        $barWidth = 30;

        foreach ($histogram as $range => $count) {
            $percentage = $maxCount > 0 ? ($count / $maxCount) : 0;
            $barLength = (int) round($percentage * $barWidth);
            $bar = str_repeat('█', $barLength) . str_repeat('░', $barWidth - $barLength);

            $context->output->writeln(sprintf(
                '| %-20s %6d %s |',
                $range,
                $count,
                $bar
            ));
        }

        $context->output->writeln('+' . str_repeat('-', 60) . '+');
    }
}
