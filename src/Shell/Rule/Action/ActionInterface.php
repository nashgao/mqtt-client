<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Rule\Action;

use NashGao\InteractiveShell\Message\Message;

/**
 * Interface for rule actions that can be executed when rules match
 */
interface ActionInterface
{
    /**
     * Execute the action with matched data
     *
     * @param array<string, mixed> $data Extracted data from SELECT fields
     * @param Message $originalMessage Original MQTT message that triggered the rule
     * @return void
     */
    public function execute(array $data, Message $originalMessage): void;

    /**
     * Get the action name for identification
     *
     * @return string Action name
     */
    public function getName(): string;
}
