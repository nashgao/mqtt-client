<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Debug;

/**
 * Represents a change to step-through debugging state.
 *
 * Used by StepHandler to communicate state changes to the shell.
 */
final readonly class StepStateChange
{
    public function __construct(
        public ?bool $enabled = null,
        public bool $advance = false,
        public bool $resume = false,
    ) {}
}
