<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Examples\DemoShell;

use DateTimeImmutable;
use NashGao\InteractiveShell\Message\Message;
use NashGao\InteractiveShell\Parser\ParsedCommand;
use Nashgao\MQTT\Shell\Handler\HandlerInterface;
use Nashgao\MQTT\Shell\HandlerContext;
use Nashgao\MQTT\Shell\HandlerResult;

/**
 * Handler for manually injecting test messages.
 * Only available in demo mode (when using DemoTransport).
 *
 * Commands:
 * - inject <topic> <payload>              Inject a message
 * - inject --qos=1 <topic> <payload>      Inject with QoS
 * - inject --direction=out <topic> <payload>  Inject as outgoing
 * - inject --binary <topic> <hex>         Inject binary payload
 */
final class InjectHandler implements HandlerInterface
{
    public function getCommands(): array
    {
        return ['inject', 'send', 'pub'];
    }

    public function handle(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $transport = $context->transport;

        // Check if we're in demo mode
        if (!$transport instanceof DemoTransport) {
            $context->output->writeln('<error>Inject command only available in demo mode</error>');
            $context->output->writeln('<comment>Use the demo shell script to start in demo mode</comment>');
            return HandlerResult::failure('Inject only available in demo mode');
        }

        $args = $command->arguments;

        // Parse arguments - handle topic and payload
        if (count($args) < 1) {
            $context->output->writeln('<error>Usage: inject <topic> [payload]</error>');
            $context->output->writeln('<comment>Example: inject sensors/temp {"temp": 25.5}</comment>');
            return HandlerResult::failure('Missing topic argument');
        }

        $topic = $args[0];

        // Parse payload (everything after topic, or default empty object)
        $payloadStr = count($args) > 1 ? implode(' ', array_slice($args, 1)) : '{}';

        // Get options
        $qos = (int) ($command->getOption('qos') ?? $command->getOption('q') ?? 0);
        $direction = (string) ($command->getOption('direction') ?? $command->getOption('d') ?? 'incoming');
        $isBinary = $command->hasOption('binary') || $command->hasOption('b');

        // Parse payload
        $payload = $this->parsePayload($payloadStr, $isBinary);

        // Create and inject message
        $message = new Message(
            type: 'publish',
            payload: [
                'topic' => $topic,
                'message' => $payload,
                'qos' => $qos,
            ],
            source: 'demo:inject',
            timestamp: new DateTimeImmutable(),
            metadata: [
                'direction' => $direction,
                'qos' => $qos,
                'injected' => true,
            ],
        );

        $transport->injectMessage($message);

        // Show confirmation
        $directionIcon = $direction === 'outgoing' ? '<<<OUT' : '>>IN';
        $payloadDisplay = is_string($payload) && $isBinary
            ? sprintf('[binary: %d bytes]', strlen($payload))
            : (is_array($payload) ? json_encode($payload, JSON_UNESCAPED_SLASHES) : (string) $payload);

        $context->output->writeln(sprintf(
            '<info>Injected:</info> %s <comment>%s</comment> %s',
            $directionIcon,
            $topic,
            $payloadDisplay
        ));

        return HandlerResult::success("Message injected: {$topic}");
    }

    public function getDescription(): string
    {
        return 'Inject test messages (demo mode only)';
    }

    public function getUsage(): array
    {
        return [
            'inject <topic> <payload>            Inject JSON message',
            'inject sensors/temp {"temp": 25}    Example with JSON payload',
            'inject --qos=1 topic payload        Inject with QoS level',
            'inject --direction=out topic        Inject as outgoing message',
            'inject --binary topic <hex>         Inject binary payload (hex string)',
            '',
            'Aliases: send, pub',
        ];
    }

    private function parsePayload(string $payloadStr, bool $isBinary): mixed
    {
        if ($isBinary) {
            // Parse hex string to binary
            $hex = preg_replace('/\s+/', '', $payloadStr);
            if ($hex === null || $hex === '') {
                return '';
            }
            $binary = @hex2bin($hex);
            return $binary !== false ? $binary : $payloadStr;
        }

        // Try to parse as JSON
        $decoded = json_decode($payloadStr, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        // Return as plain string
        return $payloadStr;
    }
}
