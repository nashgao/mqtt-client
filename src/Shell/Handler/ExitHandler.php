<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Handler;

use NashGao\InteractiveShell\Parser\ParsedCommand;
use Nashgao\MQTT\Shell\HandlerContext;
use Nashgao\MQTT\Shell\HandlerResult;

/**
 * Handler for exit commands.
 */
final class ExitHandler implements HandlerInterface
{
    public function getCommands(): array
    {
        return ['exit', 'quit'];
    }

    public function handle(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $context->output->writeln('<info>Goodbye!</info>');
        return HandlerResult::exit();
    }

    public function getDescription(): string
    {
        return 'Exit the debug shell';
    }

    public function getUsage(): array
    {
        return [
            'exit    Disconnect and exit',
            'quit    Same as exit',
            'q       Alias for exit',
        ];
    }
}
