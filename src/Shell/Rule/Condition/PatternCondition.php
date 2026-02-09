<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Rule\Condition;

use Nashgao\MQTT\Shell\Rule\Expression\FieldExtractor;

/**
 * Pattern matching condition for LIKE and REGEX operations
 * Supports: LIKE, NOT LIKE, REGEX
 */
final readonly class PatternCondition implements ConditionInterface
{
    /**
     * @param string $field Field path to match against (e.g., 'payload.data')
     * @param string $operator Pattern operator (LIKE, NOT LIKE, REGEX)
     * @param string $pattern Pattern to match
     */
    public function __construct(
        public string $field,
        public string $operator,
        public string $pattern,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function evaluate(array $context): bool
    {
        $fieldValue = FieldExtractor::extract($context, $this->field);

        if ($fieldValue === null || !is_string($fieldValue)) {
            return false;
        }

        return match (strtoupper($this->operator)) {
            'LIKE' => $this->matchLike($fieldValue),
            'NOT LIKE' => !$this->matchLike($fieldValue),
            'REGEX' => $this->matchRegex($fieldValue),
            default => false,
        };
    }

    /**
     * Match LIKE pattern (% = wildcard)
     *
     * @param string $value Value to match
     * @return bool True if matches
     */
    private function matchLike(string $value): bool
    {
        // Convert LIKE pattern to regex
        $pattern = str_replace(['%', '_'], ['.*', '.'], $this->pattern);
        $regex = '/^' . preg_quote($pattern, '/') . '$/i';
        $regex = str_replace(['\.\*', '\.'], ['.*', '.'], $regex);

        return preg_match($regex, $value) === 1;
    }

    /**
     * Match REGEX pattern
     *
     * @param string $value Value to match
     * @return bool True if matches
     */
    private function matchRegex(string $value): bool
    {
        return preg_match($this->pattern, $value) === 1;
    }

    /**
     * {@inheritDoc}
     */
    public function toString(): string
    {
        return "{$this->field} {$this->operator} '{$this->pattern}'";
    }
}
