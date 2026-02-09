<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell;

use Nashgao\MQTT\Shell\Debug\StepStateChange;

/**
 * Result returned by command handlers.
 *
 * Contains any state changes that should be applied to the shell.
 */
final readonly class HandlerResult
{
    public function __construct(
        public bool $shouldExit = false,
        public ?bool $pauseState = null,
        public bool $success = true,
        public ?string $message = null,
        public ?StepStateChange $stepChange = null,
    ) {}

    /**
     * Create a success result.
     */
    public static function success(?string $message = null): self
    {
        return new self(success: true, message: $message);
    }

    /**
     * Create a failure result.
     */
    public static function failure(string $message): self
    {
        return new self(success: false, message: $message);
    }

    /**
     * Create an exit result.
     */
    public static function exit(): self
    {
        return new self(shouldExit: true);
    }

    /**
     * Create a pause state change result.
     */
    public static function pause(): self
    {
        return new self(pauseState: true);
    }

    /**
     * Create a resume state change result.
     */
    public static function resume(): self
    {
        return new self(pauseState: false);
    }

    /**
     * Create a step mode enabled result.
     */
    public static function stepEnabled(): self
    {
        return new self(stepChange: new StepStateChange(enabled: true));
    }

    /**
     * Create a step mode disabled result.
     */
    public static function stepDisabled(): self
    {
        return new self(stepChange: new StepStateChange(enabled: false));
    }

    /**
     * Create a step advance result.
     */
    public static function stepAdvance(): self
    {
        return new self(stepChange: new StepStateChange(advance: true));
    }

    /**
     * Create a step resume result.
     */
    public static function stepResume(): self
    {
        return new self(stepChange: new StepStateChange(resume: true));
    }
}
