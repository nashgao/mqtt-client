<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Handler;

use NashGao\InteractiveShell\Parser\ParsedCommand;
use Nashgao\MQTT\Shell\HandlerContext;
use Nashgao\MQTT\Shell\HandlerResult;

/**
 * Handler for connection pool commands.
 *
 * Commands:
 * - pool list - List all pools
 * - pool status - Show pool health
 * - pool switch <name> - Switch active pool
 * - pool connections - Show active connections
 */
final class PoolHandler implements HandlerInterface
{
    public function getCommands(): array
    {
        return ['pool'];
    }

    public function handle(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $rawSubCommand = $command->getArgument(0);
        $subCommand = $rawSubCommand !== null && is_scalar($rawSubCommand) ? (string) $rawSubCommand : 'list';

        return match (strtolower($subCommand)) {
            'list' => $this->handleList($context),
            'status' => $this->handleStatus($context),
            'switch' => $this->handleSwitch($command, $context),
            'connections', 'conn' => $this->handleConnections($context),
            default => $this->handleUnknown($subCommand, $context),
        };
    }

    public function getDescription(): string
    {
        return 'Manage MQTT connection pools';
    }

    public function getUsage(): array
    {
        return [
            'pool list           List all configured pools',
            'pool status         Show pool health and statistics',
            'pool switch <name>  Switch active pool for debugging',
            'pool connections    Show active connections in pools',
        ];
    }

    private function handleList(HandlerContext $context): HandlerResult
    {
        // Send pool list command to server
        $listCommand = new ParsedCommand(
            command: 'mqtt_pool_list',
            arguments: [],
            options: [],
            raw: 'mqtt_pool_list',
            hasVerticalTerminator: false,
        );

        try {
            $context->transport->sendAsync($listCommand);
            $context->output->writeln('<info>Pool list requested (response will appear shortly)</info>');
            return HandlerResult::success();
        } catch (\Throwable $e) {
            $context->output->writeln(sprintf('<error>Failed to list pools: %s</error>', $e->getMessage()));
            return HandlerResult::failure($e->getMessage());
        }
    }

    private function handleStatus(HandlerContext $context): HandlerResult
    {
        $statusCommand = new ParsedCommand(
            command: 'mqtt_pool_status',
            arguments: [],
            options: [],
            raw: 'mqtt_pool_status',
            hasVerticalTerminator: false,
        );

        try {
            $context->transport->sendAsync($statusCommand);
            $context->output->writeln('<info>Pool status requested (response will appear shortly)</info>');
            return HandlerResult::success();
        } catch (\Throwable $e) {
            $context->output->writeln(sprintf('<error>Failed to get pool status: %s</error>', $e->getMessage()));
            return HandlerResult::failure($e->getMessage());
        }
    }

    private function handleSwitch(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $rawPoolName = $command->getArgument(1);

        if ($rawPoolName === null || $rawPoolName === '') {
            $context->output->writeln('<error>Pool name is required</error>');
            $context->output->writeln('Usage: pool switch <name>');
            return HandlerResult::failure('Pool name is required');
        }

        $poolName = is_scalar($rawPoolName) ? (string) $rawPoolName : '';

        $switchCommand = new ParsedCommand(
            command: 'mqtt_pool_switch',
            arguments: [$poolName],
            options: [],
            raw: "mqtt_pool_switch {$poolName}",
            hasVerticalTerminator: false,
        );

        try {
            $context->transport->sendAsync($switchCommand);
            $context->output->writeln(sprintf('<info>Switching to pool: %s</info>', $poolName));
            return HandlerResult::success();
        } catch (\Throwable $e) {
            $context->output->writeln(sprintf('<error>Failed to switch pool: %s</error>', $e->getMessage()));
            return HandlerResult::failure($e->getMessage());
        }
    }

    private function handleConnections(HandlerContext $context): HandlerResult
    {
        $connectionsCommand = new ParsedCommand(
            command: 'mqtt_pool_connections',
            arguments: [],
            options: [],
            raw: 'mqtt_pool_connections',
            hasVerticalTerminator: false,
        );

        try {
            $context->transport->sendAsync($connectionsCommand);
            $context->output->writeln('<info>Connection list requested (response will appear shortly)</info>');
            return HandlerResult::success();
        } catch (\Throwable $e) {
            $context->output->writeln(sprintf('<error>Failed to list connections: %s</error>', $e->getMessage()));
            return HandlerResult::failure($e->getMessage());
        }
    }

    private function handleUnknown(string $subCommand, HandlerContext $context): HandlerResult
    {
        $context->output->writeln(sprintf('<error>Unknown pool command: %s</error>', $subCommand));
        $context->output->writeln('Available commands: list, status, switch, connections');
        return HandlerResult::failure("Unknown pool command: {$subCommand}");
    }
}
