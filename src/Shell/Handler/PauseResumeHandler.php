<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Handler;

use NashGao\InteractiveShell\Parser\ParsedCommand;
use Nashgao\MQTT\Shell\HandlerContext;
use Nashgao\MQTT\Shell\HandlerResult;

/**
 * Handler for pause and resume commands.
 */
final class PauseResumeHandler implements HandlerInterface
{
    public function getCommands(): array
    {
        return ['pause', 'resume'];
    }

    public function handle(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $cmd = strtolower($command->command);

        if ($cmd === 'pause') {
            if ($context->paused) {
                $context->output->writeln('<comment>Already paused</comment>');
                return HandlerResult::success();
            }

            $context->output->writeln('<info>Message display paused. Type "resume" to continue.</info>');
            return HandlerResult::pause();
        }

        if ($cmd === 'resume') {
            if (! $context->paused) {
                $context->output->writeln('<comment>Not paused</comment>');
                return HandlerResult::success();
            }

            $context->output->writeln('<info>Message display resumed</info>');
            return HandlerResult::resume();
        }

        return HandlerResult::success();
    }

    public function getDescription(): string
    {
        return 'Pause or resume message display';
    }

    public function getUsage(): array
    {
        return [
            'pause     Pause message display (messages still recorded)',
            'resume    Resume message display',
        ];
    }
}
