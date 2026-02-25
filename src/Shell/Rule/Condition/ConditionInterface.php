<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Rule\Condition;

/**
 * Interface for rule conditions that can be evaluated against message context
 */
interface ConditionInterface
{
    /**
     * Evaluate the condition against the given context
     *
     * @param array<string, mixed> $context Message context with extracted fields
     * @return bool True if condition matches, false otherwise
     */
    public function evaluate(array $context): bool;

    /**
     * Convert condition to string representation
     *
     * @return string Human-readable condition string
     */
    public function toString(): string;
}
