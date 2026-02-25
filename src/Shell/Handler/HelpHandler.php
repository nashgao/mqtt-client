<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Handler;

use NashGao\InteractiveShell\Parser\ParsedCommand;
use Nashgao\MQTT\Shell\HandlerContext;
use Nashgao\MQTT\Shell\HandlerResult;

/**
 * Handler for help commands.
 *
 * Commands:
 * - help - Show all available commands
 * - help <command> - Show help for specific command
 */
final class HelpHandler implements HandlerInterface
{
    /** @var array<string, HandlerInterface> */
    private array $handlers = [];

    /**
     * @param array<string, HandlerInterface> $handlers
     */
    public function __construct(array $handlers = [])
    {
        $this->handlers = $handlers;
    }

    /**
     * Set the handlers (called after all handlers are registered).
     *
     * @param array<string, HandlerInterface> $handlers
     */
    public function setHandlers(array $handlers): void
    {
        $this->handlers = $handlers;
    }

    public function getCommands(): array
    {
        return ['help'];
    }

    public function handle(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $rawTopic = $command->getArgument(0);

        if ($rawTopic === null) {
            $this->showGeneralHelp($context);
        } else {
            $this->showCommandHelp(is_scalar($rawTopic) ? (string) $rawTopic : '', $context);
        }

        return HandlerResult::success();
    }

    public function getDescription(): string
    {
        return 'Show help for commands';
    }

    public function getUsage(): array
    {
        return [
            'help              Show all available commands',
            'help <command>    Show help for a specific command',
        ];
    }

    private function showGeneralHelp(HandlerContext $context): void
    {
        $context->output->writeln('');
        $context->output->writeln('<info>MQTT Debug Shell Commands</info>');
        $context->output->writeln('');

        // Group commands by category
        $categories = [
            'Monitoring' => ['filter', 'pause', 'resume', 'stats', 'format', 'log', 'latency'],
            'History' => ['history', 'last', 'export', 'clear'],
            'MQTT Operations' => ['publish', 'subscribe', 'unsubscribe', 'subscriptions'],
            'Pool Management' => ['pool'],
            'Debugging' => ['hex', 'step', 'next', 'continue', 'inspect'],
            'Visualization' => ['tree', 'flow', 'visualize'],
            'System' => ['help', 'exit'],
        ];

        foreach ($categories as $category => $commands) {
            $context->output->writeln(sprintf('<comment>%s:</comment>', $category));

            foreach ($commands as $cmd) {
                if (isset($this->handlers[$cmd])) {
                    $handler = $this->handlers[$cmd];
                    $description = $handler->getDescription();
                    $context->output->writeln(sprintf('  %-20s %s', $cmd, $description));
                }
            }

            $context->output->writeln('');
        }

        $context->output->writeln('<comment>Aliases:</comment>');
        $context->output->writeln('  f=filter  p=pause  r=resume  s=stats  h=history  l=last');
        $context->output->writeln('  pub=publish  sub=subscribe  unsub=unsubscribe  q=exit');
        $context->output->writeln('  n=next  viz=visualize');
        $context->output->writeln('');
        $context->output->writeln('<comment>Tips:</comment>');
        $context->output->writeln('  - Add \G to any command for vertical output (e.g., history\G)');
        $context->output->writeln('  - Use "step on" to enable step-through debugging');
        $context->output->writeln('  - Use "tree" or "flow" to visualize message patterns');
        $context->output->writeln('  - Use "hex last" to view binary payloads');
        $context->output->writeln('  - Use "help <command>" for detailed usage');
        $context->output->writeln('');
    }

    private function showCommandHelp(string $commandName, HandlerContext $context): void
    {
        $handler = $this->handlers[strtolower($commandName)] ?? null;

        if ($handler === null) {
            $context->output->writeln(sprintf('<error>Unknown command: %s</error>', $commandName));
            $context->output->writeln('Type "help" to see all available commands.');
            return;
        }

        $context->output->writeln('');
        $context->output->writeln(sprintf('<info>%s</info> - %s', $commandName, $handler->getDescription()));
        $context->output->writeln('');
        $context->output->writeln('<comment>Usage:</comment>');

        foreach ($handler->getUsage() as $usage) {
            $context->output->writeln('  ' . $usage);
        }

        $context->output->writeln('');
    }
}
