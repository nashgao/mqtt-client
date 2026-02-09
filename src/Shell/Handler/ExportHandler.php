<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Handler;

use NashGao\InteractiveShell\Parser\ParsedCommand;
use Nashgao\MQTT\Shell\HandlerContext;
use Nashgao\MQTT\Shell\HandlerResult;

/**
 * Handler for export commands.
 *
 * Commands:
 * - export - Export message history
 * - export --file=path - Export to file
 * - export --format=json|csv - Export format
 * - clear - Clear message history
 */
final class ExportHandler implements HandlerInterface
{
    public function getCommands(): array
    {
        return ['export', 'clear'];
    }

    public function handle(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $cmd = strtolower($command->command);

        if ($cmd === 'clear') {
            $context->messageHistory->clear();
            $context->output->writeln('<info>Message history cleared</info>');
            return HandlerResult::success();
        }

        // Export command
        $format = $command->getOption('format') ?? 'json';
        $file = $command->getOption('file');
        $rawLimit = $command->getOption('limit');
        $limit = $rawLimit !== null && is_numeric($rawLimit) ? (int) $rawLimit : 0;

        $messages = $context->messageHistory->export($limit);

        if (empty($messages)) {
            $context->output->writeln('<comment>No messages to export</comment>');
            return HandlerResult::success();
        }

        $output = $this->formatExport($messages, is_string($format) ? $format : 'json');

        // Write to file or display
        if ($file !== null && is_string($file)) {
            $result = file_put_contents($file, $output);
            if ($result === false) {
                $context->output->writeln(sprintf('<error>Failed to write to file: %s</error>', $file));
                return HandlerResult::failure("Failed to write to file: {$file}");
            }
            $context->output->writeln(sprintf(
                '<info>Exported %d messages to %s</info>',
                count($messages),
                $file
            ));
        } else {
            $context->output->writeln($output);
            $context->output->writeln('');
            $context->output->writeln(sprintf('<info>Exported %d messages</info>', count($messages)));
        }

        return HandlerResult::success();
    }

    public function getDescription(): string
    {
        return 'Export or clear message history';
    }

    public function getUsage(): array
    {
        return [
            'export                      Export history to stdout (JSON)',
            'export --file=messages.json Export to file',
            'export --format=csv         Export as CSV',
            'export --limit=100          Export only last 100 messages',
            'clear                       Clear message history',
        ];
    }

    /**
     * Format messages for export.
     *
     * @param array<array<string, mixed>> $messages
     */
    private function formatExport(array $messages, string $format): string
    {
        return match (strtolower($format)) {
            'csv' => $this->formatCsv($messages),
            default => $this->formatJson($messages),
        };
    }

    /**
     * Format as JSON.
     *
     * @param array<array<string, mixed>> $messages
     */
    private function formatJson(array $messages): string
    {
        return json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '[]';
    }

    /**
     * Format as CSV.
     *
     * @param array<array<string, mixed>> $messages
     */
    private function formatCsv(array $messages): string
    {
        $lines = [];
        $lines[] = 'timestamp,type,source,topic,qos,direction,payload';

        foreach ($messages as $message) {
            $rawTimestamp = $message['timestamp'] ?? '';
            $rawType = $message['type'] ?? '';
            $rawSource = $message['source'] ?? '';
            $timestamp = is_scalar($rawTimestamp) ? (string) $rawTimestamp : '';
            $type = is_scalar($rawType) ? (string) $rawType : '';
            $source = is_scalar($rawSource) ? (string) $rawSource : '';

            $payload = $message['payload'] ?? [];
            $topic = '';
            $qos = '';
            $payloadContent = '';

            if (is_array($payload)) {
                $rawTopic = $payload['topic'] ?? '';
                $rawQos = $payload['qos'] ?? '';
                $topic = is_scalar($rawTopic) ? (string) $rawTopic : '';
                $qos = is_scalar($rawQos) ? (string) $rawQos : '';
                $msgContent = $payload['message'] ?? null;
                $payloadContent = is_string($msgContent) ? $msgContent : (json_encode($payload) ?: '');
            } elseif (is_string($payload)) {
                $payloadContent = $payload;
            } else {
                $payloadContent = json_encode($payload) ?: '';
            }

            $metadata = $message['metadata'] ?? [];
            $rawDirection = is_array($metadata) ? ($metadata['direction'] ?? '') : '';
            $direction = is_scalar($rawDirection) ? (string) $rawDirection : '';

            // Escape CSV values
            $escapedPayload = '"' . str_replace('"', '""', $payloadContent) . '"';
            $escapedTopic = '"' . str_replace('"', '""', $topic) . '"';

            $lines[] = implode(',', [
                $timestamp,
                $type,
                $source,
                $escapedTopic,
                $qos,
                $direction,
                $escapedPayload,
            ]);
        }

        return implode("\n", $lines);
    }
}
