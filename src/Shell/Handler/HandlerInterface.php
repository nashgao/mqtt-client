<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Handler;

use NashGao\InteractiveShell\Parser\ParsedCommand;
use Nashgao\MQTT\Shell\HandlerContext;
use Nashgao\MQTT\Shell\HandlerResult;

/**
 * Interface for MQTT shell command handlers.
 */
interface HandlerInterface
{
    /**
     * Get the command names this handler responds to.
     *
     * @return array<string>
     */
    public function getCommands(): array;

    /**
     * Handle the command.
     */
    public function handle(ParsedCommand $command, HandlerContext $context): HandlerResult;

    /**
     * Get a short description of this command.
     */
    public function getDescription(): string;

    /**
     * Get usage examples.
     *
     * @return array<string>
     */
    public function getUsage(): array;
}
