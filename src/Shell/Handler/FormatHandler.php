<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Handler;

use NashGao\InteractiveShell\Parser\ParsedCommand;
use Nashgao\MQTT\Shell\HandlerContext;
use Nashgao\MQTT\Shell\HandlerResult;

/**
 * Handler for output format commands.
 *
 * Commands:
 * - format <type> - Set output format
 */
final class FormatHandler implements HandlerInterface
{
    public function getCommands(): array
    {
        return ['format'];
    }

    public function handle(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $rawArg = $command->getArgument(0);

        if ($rawArg === null) {
            $current = $context->formatter->getFormat();
            $depthLimit = $context->formatter->getDepthLimit();
            $schemaMode = $context->formatter->isSchemaMode();
            $context->output->writeln(sprintf('Current format: %s', $current));
            $context->output->writeln(sprintf('Depth limit: %s', $depthLimit === 0 ? 'unlimited' : (string) $depthLimit));
            $context->output->writeln(sprintf('Schema mode: %s', $schemaMode ? 'enabled' : 'disabled'));
            $context->output->writeln('');
            $context->output->writeln('Available formats:');
            $context->output->writeln('  compact   Single-line format (default)');
            $context->output->writeln('  table     Tabular format');
            $context->output->writeln('  vertical  Detailed format (like MySQL \G)');
            $context->output->writeln('  json      JSON format for export');
            $context->output->writeln('  hex       Hex dump format');
            $context->output->writeln('');
            $context->output->writeln('Depth control:');
            $context->output->writeln('  depth N   Collapse JSON beyond level N (0=unlimited)');
            $context->output->writeln('');
            $context->output->writeln('Schema mode:');
            $context->output->writeln('  schema    Toggle schema mode (show structure only)');
            return HandlerResult::success();
        }

        $arg = is_scalar($rawArg) ? (string) $rawArg : '';

        // Handle "format depth N"
        if (strtolower($arg) === 'depth') {
            $depthArg = $command->getArgument(1);
            if ($depthArg === null || ! is_numeric($depthArg)) {
                $current = $context->formatter->getDepthLimit();
                $context->output->writeln(sprintf('Current depth limit: %s', $current === 0 ? 'unlimited' : (string) $current));
                $context->output->writeln('Usage: format depth <N> (0 = unlimited)');
                return HandlerResult::success();
            }

            $depth = (int) $depthArg;
            $context->formatter->setDepthLimit($depth);

            if ($depth === 0) {
                $context->output->writeln('<info>Depth limit removed (unlimited)</info>');
            } else {
                $context->output->writeln(sprintf('<info>JSON depth limited to %d levels</info>', $depth));
            }

            return HandlerResult::success();
        }

        // Handle "format schema" toggle
        if (strtolower($arg) === 'schema') {
            $current = $context->formatter->isSchemaMode();
            $context->formatter->setSchemaMode(!$current);

            if (!$current) {
                $context->output->writeln('<info>Schema mode enabled (showing structure only, no values)</info>');
            } else {
                $context->output->writeln('<info>Schema mode disabled (showing full values)</info>');
            }

            return HandlerResult::success();
        }

        $validFormats = ['compact', 'table', 'vertical', 'json', 'hex', 'c', 't', 'v', 'j', 'h'];

        if (! in_array(strtolower($arg), $validFormats, true)) {
            $context->output->writeln(sprintf('<error>Invalid format: %s</error>', $arg));
            $context->output->writeln('Valid formats: compact, table, vertical, json, hex');
            $context->output->writeln('For depth control: format depth <N>');
            $context->output->writeln('For schema mode: format schema');
            return HandlerResult::failure("Invalid format: {$arg}");
        }

        $context->formatter->setFormat(strtolower($arg));
        $context->output->writeln(sprintf('<info>Output format set to: %s</info>', $context->formatter->getFormat()));

        return HandlerResult::success();
    }

    public function getDescription(): string
    {
        return 'Set message output format';
    }

    public function getUsage(): array
    {
        return [
            'format            Show current format, depth, and schema settings',
            'format compact    Single-line output (default)',
            'format table      Tabular output',
            'format vertical   Detailed output (like MySQL \G)',
            'format json       JSON output for scripting',
            'format hex        Hex dump output',
            'format depth 2    Collapse JSON beyond level 2',
            'format depth 0    Unlimited depth (default)',
            'format schema     Toggle schema mode (structure only)',
        ];
    }
}
