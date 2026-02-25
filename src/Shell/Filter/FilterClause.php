<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Filter;

/**
 * Represents a single filter clause with expression and operator.
 */
final readonly class FilterClause
{
    public function __construct(
        public string $expression,
        public string $operator = 'AND',
    ) {
    }

    /**
     * Check if this is the base (first) clause.
     */
    public function isBase(): bool
    {
        return $this->operator === 'BASE';
    }
}
