<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Handler;

use NashGao\InteractiveShell\Parser\ParsedCommand;
use Nashgao\MQTT\Shell\HandlerContext;
use Nashgao\MQTT\Shell\HandlerResult;
use Nashgao\MQTT\Shell\Mqtt\TopicMatcher;

/**
 * Handler for subscribe/unsubscribe commands.
 *
 * Commands:
 * - subscribe <topic> - Subscribe to topic
 * - unsubscribe <topic> - Unsubscribe from topic
 * - subscriptions - List active subscriptions
 */
final class SubscribeHandler implements HandlerInterface
{
    public function getCommands(): array
    {
        return ['subscribe', 'unsubscribe', 'subscriptions'];
    }

    public function handle(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $cmd = strtolower($command->command);

        return match ($cmd) {
            'subscribe' => $this->handleSubscribe($command, $context),
            'unsubscribe' => $this->handleUnsubscribe($command, $context),
            'subscriptions' => $this->handleListSubscriptions($context),
            default => HandlerResult::failure('Unknown command'),
        };
    }

    public function getDescription(): string
    {
        return 'Subscribe/unsubscribe to MQTT topics';
    }

    public function getUsage(): array
    {
        return [
            'subscribe <topic>           Subscribe to topic',
            'subscribe <topic> --qos=1   Subscribe with specific QoS',
            'unsubscribe <topic>         Unsubscribe from topic',
            'subscriptions               List active subscriptions',
        ];
    }

    private function handleSubscribe(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $rawTopic = $command->getArgument(0);

        if ($rawTopic === null || $rawTopic === '') {
            $context->output->writeln('<error>Topic is required</error>');
            $context->output->writeln('Usage: subscribe <topic> [--qos=0|1|2]');
            return HandlerResult::failure('Topic is required');
        }

        $topic = is_scalar($rawTopic) ? (string) $rawTopic : '';

        // Validate MQTT topic pattern
        $validation = TopicMatcher::validate($topic);
        if (! $validation->isValid()) {
            $context->output->writeln(sprintf('<error>Invalid MQTT pattern: %s</error>', $validation->error));
            return HandlerResult::failure('Invalid MQTT pattern: ' . $validation->error);
        }

        $qosOption = $command->getOption('qos');
        $qos = $qosOption !== null && is_numeric($qosOption) ? (int) $qosOption : 0;

        // Validate QoS
        if ($qos < 0 || $qos > 2) {
            $context->output->writeln('<error>QoS must be 0, 1, or 2</error>');
            return HandlerResult::failure('Invalid QoS');
        }

        // Send subscribe command to server
        $subscribeCommand = new ParsedCommand(
            command: 'mqtt_subscribe',
            arguments: [$topic],
            options: ['qos' => (string) $qos],
            raw: "mqtt_subscribe {$topic}",
            hasVerticalTerminator: false,
        );

        try {
            $context->transport->sendAsync($subscribeCommand);
            $context->output->writeln(sprintf('<info>Subscribe request sent for: %s (QoS %d)</info>', $topic, $qos));
            return HandlerResult::success();
        } catch (\Throwable $e) {
            $context->output->writeln(sprintf('<error>Failed to subscribe: %s</error>', $e->getMessage()));
            return HandlerResult::failure($e->getMessage());
        }
    }

    private function handleUnsubscribe(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $rawTopic = $command->getArgument(0);

        if ($rawTopic === null || $rawTopic === '') {
            $context->output->writeln('<error>Topic is required</error>');
            $context->output->writeln('Usage: unsubscribe <topic>');
            return HandlerResult::failure('Topic is required');
        }

        $topic = is_scalar($rawTopic) ? (string) $rawTopic : '';

        // Send unsubscribe command to server
        $unsubscribeCommand = new ParsedCommand(
            command: 'mqtt_unsubscribe',
            arguments: [$topic],
            options: [],
            raw: "mqtt_unsubscribe {$topic}",
            hasVerticalTerminator: false,
        );

        try {
            $context->transport->sendAsync($unsubscribeCommand);
            $context->output->writeln(sprintf('<info>Unsubscribe request sent for: %s</info>', $topic));
            return HandlerResult::success();
        } catch (\Throwable $e) {
            $context->output->writeln(sprintf('<error>Failed to unsubscribe: %s</error>', $e->getMessage()));
            return HandlerResult::failure($e->getMessage());
        }
    }

    private function handleListSubscriptions(HandlerContext $context): HandlerResult
    {
        // Send subscriptions list command to server
        $listCommand = new ParsedCommand(
            command: 'mqtt_subscriptions',
            arguments: [],
            options: [],
            raw: 'mqtt_subscriptions',
            hasVerticalTerminator: false,
        );

        try {
            $context->transport->sendAsync($listCommand);
            $context->output->writeln('<info>Subscriptions list requested (response will appear shortly)</info>');
            return HandlerResult::success();
        } catch (\Throwable $e) {
            $context->output->writeln(sprintf('<error>Failed to list subscriptions: %s</error>', $e->getMessage()));
            return HandlerResult::failure($e->getMessage());
        }
    }
}
