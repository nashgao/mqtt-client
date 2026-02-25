<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Rule\Condition;

use Nashgao\MQTT\Shell\Rule\Expression\FieldExtractor;

/**
 * Comparison condition for numeric and string comparisons
 * Supports: =, !=, >, <, >=, <=
 */
final readonly class ComparisonCondition implements ConditionInterface
{
    /**
     * @param string $field Field path to compare (e.g., 'payload.temperature')
     * @param string $operator Comparison operator (=, !=, >, <, >=, <=)
     * @param mixed $value Value to compare against
     */
    public function __construct(
        public string $field,
        public string $operator,
        public mixed $value,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function evaluate(array $context): bool
    {
        $fieldValue = FieldExtractor::extract($context, $this->field);

        if ($fieldValue === null) {
            return false;
        }

        return match ($this->operator) {
            '=' => $fieldValue == $this->value,
            '!=' => $fieldValue != $this->value,
            '>' => $fieldValue > $this->value,
            '<' => $fieldValue < $this->value,
            '>=' => $fieldValue >= $this->value,
            '<=' => $fieldValue <= $this->value,
            default => false,
        };
    }

    /**
     * {@inheritDoc}
     */
    public function toString(): string
    {
        if (is_string($this->value)) {
            $valueStr = "'{$this->value}'";
        } elseif (is_numeric($this->value)) {
            $valueStr = (string) $this->value;
        } elseif (is_bool($this->value)) {
            $valueStr = $this->value ? 'true' : 'false';
        } elseif (is_array($this->value)) {
            $valueStr = json_encode($this->value) ?: '[]';
        } elseif ($this->value === null) {
            $valueStr = 'null';
        } else {
            $valueStr = 'unknown';
        }

        return "{$this->field} {$this->operator} {$valueStr}";
    }
}
