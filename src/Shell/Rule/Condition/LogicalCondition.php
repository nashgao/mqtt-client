<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Rule\Condition;

/**
 * Logical condition for combining multiple conditions
 * Supports: AND, OR, NOT
 */
final readonly class LogicalCondition implements ConditionInterface
{
    /**
     * @param string $operator Logical operator (AND, OR, NOT)
     * @param array<ConditionInterface> $conditions Child conditions to evaluate
     */
    public function __construct(
        public string $operator,
        public array $conditions,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function evaluate(array $context): bool
    {
        return match (strtoupper($this->operator)) {
            'AND' => $this->evaluateAnd($context),
            'OR' => $this->evaluateOr($context),
            'NOT' => $this->evaluateNot($context),
            default => false,
        };
    }

    /**
     * Evaluate AND condition (all must be true)
     *
     * @param array<string, mixed> $context Message context
     * @return bool True if all conditions are true
     */
    private function evaluateAnd(array $context): bool
    {
        foreach ($this->conditions as $condition) {
            if (!$condition->evaluate($context)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate OR condition (at least one must be true)
     *
     * @param array<string, mixed> $context Message context
     * @return bool True if any condition is true
     */
    private function evaluateOr(array $context): bool
    {
        foreach ($this->conditions as $condition) {
            if ($condition->evaluate($context)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Evaluate NOT condition (negate first condition)
     *
     * @param array<string, mixed> $context Message context
     * @return bool Negated result of first condition
     */
    private function evaluateNot(array $context): bool
    {
        if (empty($this->conditions)) {
            return false;
        }

        return !$this->conditions[0]->evaluate($context);
    }

    /**
     * {@inheritDoc}
     */
    public function toString(): string
    {
        $operator = strtoupper($this->operator);

        if ($operator === 'NOT') {
            return empty($this->conditions)
                ? 'NOT ()'
                : "NOT ({$this->conditions[0]->toString()})";
        }

        $conditionStrings = array_map(
            fn(ConditionInterface $c) => $c->toString(),
            $this->conditions
        );

        return '(' . implode(" {$operator} ", $conditionStrings) . ')';
    }
}
