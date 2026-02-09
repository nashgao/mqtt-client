<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Handler;

use NashGao\InteractiveShell\Parser\ParsedCommand;
use Nashgao\MQTT\Shell\HandlerContext;
use Nashgao\MQTT\Shell\HandlerResult;

/**
 * Handler for publish commands.
 *
 * Commands:
 * - publish <topic> <payload> - Publish message
 * - publish <topic> <payload> --qos=1 - Publish with QoS
 * - publish <topic> <payload> --retain - Publish with retain flag
 */
final class PublishHandler implements HandlerInterface
{
    public function getCommands(): array
    {
        return ['publish'];
    }

    public function handle(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $rawTopic = $command->getArgument(0);
        $rawPayload = $command->getArgument(1);

        if ($rawTopic === null || $rawTopic === '') {
            $context->output->writeln('<error>Topic is required</error>');
            $context->output->writeln('Usage: publish <topic> <payload> [--qos=0|1|2] [--retain]');
            return HandlerResult::failure('Topic is required');
        }

        $topic = is_scalar($rawTopic) ? (string) $rawTopic : '';

        // Build payload from remaining arguments if multiple
        if ($rawPayload === null) {
            $payload = '';
        } else {
            // Combine all arguments after topic into payload
            $args = array_map('strval', $command->arguments);
            array_shift($args); // Remove topic
            $payload = implode(' ', $args);
        }

        $qosOption = $command->getOption('qos');
        $qos = $qosOption !== null && is_numeric($qosOption) ? (int) $qosOption : 0;
        $retain = $command->hasOption('retain');

        // Validate QoS
        if ($qos < 0 || $qos > 2) {
            $context->output->writeln('<error>QoS must be 0, 1, or 2</error>');
            return HandlerResult::failure('Invalid QoS');
        }

        // Send publish command to server
        $publishCommand = new ParsedCommand(
            command: 'mqtt_publish',
            arguments: [$topic, $payload],
            options: [
                'qos' => (string) $qos,
                'retain' => $retain ? 'true' : 'false',
            ],
            raw: "mqtt_publish {$topic} {$payload}",
            hasVerticalTerminator: false,
        );

        try {
            $context->transport->sendAsync($publishCommand);

            $context->output->writeln(sprintf(
                '<info>Published to %s (QoS %d%s)</info>',
                $topic,
                $qos,
                $retain ? ', retain' : ''
            ));

            return HandlerResult::success();
        } catch (\Throwable $e) {
            $context->output->writeln(sprintf('<error>Failed to publish: %s</error>', $e->getMessage()));
            return HandlerResult::failure($e->getMessage());
        }
    }

    public function getDescription(): string
    {
        return 'Publish a message to an MQTT topic';
    }

    public function getUsage(): array
    {
        return [
            'publish <topic> <payload>           Publish message with QoS 0',
            'publish <topic> <payload> --qos=1   Publish with specific QoS',
            'publish <topic> <payload> --retain  Publish with retain flag',
            'publish sensors/temp {"value":25}   Publish JSON payload',
        ];
    }
}
