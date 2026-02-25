<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Handler;

use NashGao\InteractiveShell\Message\Message;
use NashGao\InteractiveShell\Parser\ParsedCommand;
use Nashgao\MQTT\Shell\HandlerContext;
use Nashgao\MQTT\Shell\HandlerResult;
use Nashgao\MQTT\Shell\Json\JsonPathExtractor;

/**
 * Handler for extracting specific values using JSON path expressions.
 *
 * Commands:
 * - jpath $.path           Extract value from last message
 * - jpath $.path -3        Extract from 3rd from last message
 * - jpath $.path 42        Extract from message #42
 *
 * Examples:
 * - jpath $.temperature
 * - jpath $.sensors.humidity
 * - jpath $.readings[0].value
 * - jpath $.devices[*].status
 */
final class JsonPathHandler implements HandlerInterface
{
    private readonly JsonPathExtractor $extractor;

    public function __construct()
    {
        $this->extractor = new JsonPathExtractor();
    }

    /**
     * @return array<string>
     */
    public function getCommands(): array
    {
        return ['jpath', 'jp', 'path', 'extract'];
    }

    public function handle(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $pathArg = $command->getArgument(0);

        if ($pathArg === null) {
            return $this->showUsage($context);
        }

        $path = is_scalar($pathArg) ? (string) $pathArg : '';

        if (!$this->extractor->isValidPath($path)) {
            return HandlerResult::failure("Invalid JSON path: {$path}");
        }

        // Get the message to extract from
        $messageArg = $command->getArgument(1);
        $message = $this->resolveMessage($messageArg, $context);

        if ($message === null) {
            return HandlerResult::failure('No message found to extract from');
        }

        // Extract the JSON data from message payload
        $data = $this->extractPayloadData($message);

        if ($data === null) {
            return HandlerResult::failure('Message payload is not valid JSON');
        }

        // Apply JSON path extraction
        $result = $this->extractor->extract($data, $path);

        if ($result === null) {
            $context->output->writeln(sprintf(
                '<comment>Path "%s" not found in message</comment>',
                $path
            ));
            return HandlerResult::success();
        }

        // Display result
        $context->output->writeln(sprintf('<info>%s =</info>', $path));
        $context->output->writeln($this->extractor->formatValue($result));

        return HandlerResult::success();
    }

    private function showUsage(HandlerContext $context): HandlerResult
    {
        $context->output->writeln('<info>JSON Path Extractor</info>');
        $context->output->writeln('');
        $context->output->writeln('Usage: jpath <path> [message]');
        $context->output->writeln('');
        $context->output->writeln('Examples:');
        $context->output->writeln('  jpath $.temperature           Extract from last message');
        $context->output->writeln('  jpath $.sensors.humidity      Nested path');
        $context->output->writeln('  jpath $.readings[0].value     Array index');
        $context->output->writeln('  jpath $.devices[*].status     All array elements');
        $context->output->writeln('  jpath $.temperature -3        From 3rd from last');
        $context->output->writeln('  jpath $.temperature 42        From message #42');

        return HandlerResult::success();
    }

    private function resolveMessage(mixed $arg, HandlerContext $context): ?Message
    {
        // No argument - use latest message
        if ($arg === null) {
            return $context->messageHistory->getLatest();
        }

        $argStr = is_scalar($arg) ? (string) $arg : '';

        // Relative: -3 (3rd from last)
        if (str_starts_with($argStr, '-') && is_numeric(substr($argStr, 1))) {
            $offset = (int) substr($argStr, 1);
            if ($offset < 1) {
                return null;
            }

            $messages = $context->messageHistory->getLast($offset);

            if (empty($messages) || count($messages) < $offset) {
                return null;
            }

            return reset($messages);
        }

        // Absolute: message ID
        if (is_numeric($argStr)) {
            return $context->messageHistory->get((int) $argStr);
        }

        return null;
    }

    /**
     * Extract JSON data from message payload.
     *
     * @return array<string, mixed>|null
     */
    private function extractPayloadData(Message $message): ?array
    {
        $payload = $message->payload;

        // Direct array payload
        if (is_array($payload)) {
            // Check for MQTT message wrapper
            $messageContent = $payload['message'] ?? $payload;

            if (is_string($messageContent)) {
                // Try to parse JSON string
                $decoded = json_decode($messageContent, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                }
                return null;
            }

            if (is_array($messageContent)) {
                return $messageContent;
            }

            return null;
        }

        // String payload - try to parse as JSON
        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    public function getDescription(): string
    {
        return 'Extract values using JSON path expressions';
    }

    /**
     * @return array<string>
     */
    public function getUsage(): array
    {
        return [
            'jpath $.temperature           Extract from last message',
            'jpath $.sensors.humidity      Nested path extraction',
            'jpath $.readings[0].value     Array index access',
            'jpath $.devices[*].status     All array elements',
            'jpath $.temperature -3        From 3rd from last message',
            'jpath $.temperature 42        From message #42',
        ];
    }
}
