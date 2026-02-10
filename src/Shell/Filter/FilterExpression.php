<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Filter;

use NashGao\InteractiveShell\Message\Message;
use Nashgao\MQTT\Shell\Mqtt\TopicMatcher;
use Nashgao\MQTT\Shell\Rule\Expression\FieldExtractor;

/**
 * MQTT-specific filter expression with SQL-like syntax support.
 *
 * Supports:
 * - MQTT topic wildcards (+ and #) in pattern matching
 * - SQL-like expressions: field like 'pattern', field = value
 * - Compound filters with AND, OR, NOT operators
 *
 * Usage:
 *   $filter = new FilterExpression();
 *   $filter->where("topic like 'sensors/#' and qos = 1");
 *   $filter->addOr("topic like 'devices/#'");
 *   $filter->addNot("topic like 'debug/#'");
 *   if ($filter->matches($message)) { ... }
 */
final class FilterExpression
{
    /** @var array<FilterClause> */
    private array $clauses = [];

    /**
     * Set the base filter expression, replacing any existing filter.
     */
    public function where(string $expression): self
    {
        $this->clauses = [];
        $this->clauses[] = new FilterClause($expression, 'BASE');
        return $this;
    }

    /**
     * Add an AND clause to the filter.
     */
    public function addAnd(string $expression): self
    {
        $this->clauses[] = new FilterClause($expression, 'AND');
        return $this;
    }

    /**
     * Add an OR clause to the filter.
     */
    public function addOr(string $expression): self
    {
        $this->clauses[] = new FilterClause($expression, 'OR');
        return $this;
    }

    /**
     * Add an AND NOT clause to the filter.
     */
    public function addNot(string $expression): self
    {
        $this->clauses[] = new FilterClause($expression, 'AND NOT');
        return $this;
    }

    /**
     * Remove a clause by expression.
     */
    public function remove(string $expression): self
    {
        $this->clauses = array_values(array_filter(
            $this->clauses,
            fn(FilterClause $clause) => $clause->expression !== $expression
        ));
        return $this;
    }

    /**
     * Get all clauses.
     *
     * @return array<FilterClause>
     */
    public function getClauses(): array
    {
        return $this->clauses;
    }

    /**
     * Check if any filters are active.
     */
    public function hasFilters(): bool
    {
        return count($this->clauses) > 0;
    }

    /**
     * Get SQL representation of the filter.
     */
    public function toSql(): string
    {
        if (empty($this->clauses)) {
            return '';
        }

        $parts = [];
        foreach ($this->clauses as $clause) {
            if ($clause->isBase()) {
                $parts[] = $clause->expression;
            } else {
                $parts[] = "{$clause->operator} ({$clause->expression})";
            }
        }

        return implode(' ', $parts);
    }

    /**
     * Clear all filters.
     */
    public function clear(): self
    {
        $this->clauses = [];
        return $this;
    }

    /**
     * Clone the filter expression.
     */
    public function clone(): self
    {
        $clone = new self();
        foreach ($this->clauses as $clause) {
            $clone->clauses[] = new FilterClause($clause->expression, $clause->operator);
        }
        return $clone;
    }

    /**
     * Check if a message matches the filter.
     */
    public function matches(Message $message): bool
    {
        if (empty($this->clauses)) {
            return true; // No filter = match all
        }

        $context = $this->buildContext($message);
        $result = false;

        foreach ($this->clauses as $clause) {
            $clauseResult = $this->evaluateExpression($clause->expression, $context);

            if ($clause->isBase()) {
                $result = $clauseResult;
            } elseif ($clause->operator === 'AND') {
                $result = $result && $clauseResult;
            } elseif ($clause->operator === 'OR') {
                $result = $result || $clauseResult;
            } elseif ($clause->operator === 'AND NOT') {
                $result = $result && !$clauseResult;
            }
        }

        return $result;
    }

    /**
     * Build context from message for evaluation.
     *
     * @return array<string, mixed>
     */
    private function buildContext(Message $message): array
    {
        return FieldExtractor::buildContext($message);
    }

    /**
     * Evaluate a filter expression against context.
     *
     * @param array<string, mixed> $context
     */
    private function evaluateExpression(string $expression, array $context): bool
    {
        // Handle compound expressions with AND/OR
        // Split on AND/OR while respecting parentheses
        $parts = $this->splitExpression($expression);

        if (count($parts) === 1) {
            return $this->evaluateCondition($parts[0], $context);
        }

        // Handle multiple parts joined by AND/OR
        $result = true;
        $operator = 'AND';

        foreach ($parts as $part) {
            $upperPart = strtoupper(trim($part));
            if ($upperPart === 'AND') {
                $operator = 'AND';
                continue;
            }
            if ($upperPart === 'OR') {
                $operator = 'OR';
                continue;
            }

            $partResult = $this->evaluateCondition($part, $context);

            if ($operator === 'AND') {
                $result = $result && $partResult;
            } else {
                $result = $result || $partResult;
            }
        }

        return $result;
    }

    /**
     * Split expression into parts respecting AND/OR operators.
     *
     * @return array<string>
     */
    private function splitExpression(string $expression): array
    {
        $parts = [];
        $current = '';
        $depth = 0;

        $tokens = preg_split('/\s+/', trim($expression));
        if ($tokens === false) {
            return [$expression];
        }

        foreach ($tokens as $token) {
            $depth += substr_count($token, '(') - substr_count($token, ')');
            $upperToken = strtoupper($token);

            if ($depth === 0 && in_array($upperToken, ['AND', 'OR'], true)) {
                if ($current !== '') {
                    $parts[] = trim($current);
                }
                $parts[] = $upperToken;
                $current = '';
            } else {
                $current .= ($current !== '' ? ' ' : '') . $token;
            }
        }

        if ($current !== '') {
            $parts[] = trim($current);
        }

        return $parts;
    }

    /**
     * Evaluate a single condition.
     *
     * @param array<string, mixed> $context
     */
    private function evaluateCondition(string $condition, array $context): bool
    {
        $condition = trim($condition);

        // Remove surrounding parentheses
        while (str_starts_with($condition, '(') && str_ends_with($condition, ')')) {
            $condition = trim(substr($condition, 1, -1));
        }

        // Pattern: field LIKE 'pattern' or field NOT LIKE 'pattern'
        if (preg_match("/^(\S+)\s+(NOT\s+)?LIKE\s+['\"](.+)['\"]$/i", $condition, $matches)) {
            $field = $matches[1];
            $negate = !empty($matches[2]);
            $pattern = $matches[3];

            $value = FieldExtractor::extract($context, $field);
            if (!is_string($value)) {
                return false;
            }

            // Use MQTT topic matching for topic field
            if ($field === 'topic') {
                $result = TopicMatcher::matches($pattern, $value);
            } else {
                $result = $this->matchLikePattern($pattern, $value);
            }

            return $negate ? !$result : $result;
        }

        // Pattern: field = value or field != value
        if (preg_match("/^(\S+)\s*(=|!=|<>|>|<|>=|<=)\s*(.+)$/", $condition, $matches)) {
            $field = $matches[1];
            $operator = $matches[2];
            $expected = trim($matches[3], "'\"\t\n\r ");

            $actual = FieldExtractor::extract($context, $field);

            return $this->compareValues($actual, $operator, $expected);
        }

        return false;
    }

    /**
     * Match a LIKE pattern using SQL wildcards.
     */
    private function matchLikePattern(string $pattern, string $value): bool
    {
        // Convert SQL LIKE to regex: % = .*, _ = .
        $regex = '/^' . str_replace(['%', '_'], ['.*', '.'], preg_quote($pattern, '/')) . '$/i';
        // Unescape the wildcard characters
        $regex = str_replace(['\.\*', '\.'], ['.*', '.'], $regex);

        return preg_match($regex, $value) === 1;
    }

    /**
     * Compare values with operator.
     *
     * @param mixed $actual
     */
    private function compareValues(mixed $actual, string $operator, string $expected): bool
    {
        // Handle boolean comparisons
        if (in_array(strtolower($expected), ['true', 'false'], true)) {
            $expectedBool = strtolower($expected) === 'true';
            $actualBool = is_bool($actual) ? $actual : (bool) $actual;
            return match ($operator) {
                '=' => $actualBool === $expectedBool,
                '!=', '<>' => $actualBool !== $expectedBool,
                default => false,
            };
        }

        // Handle numeric comparisons
        if (is_numeric($expected) && (is_numeric($actual) || $actual === null)) {
            $expectedNum = (float) $expected;
            $actualNum = (float) ($actual ?? 0);
            return match ($operator) {
                '=' => $actualNum == $expectedNum,
                '!=', '<>' => $actualNum != $expectedNum,
                '>' => $actualNum > $expectedNum,
                '<' => $actualNum < $expectedNum,
                '>=' => $actualNum >= $expectedNum,
                '<=' => $actualNum <= $expectedNum,
                default => false,
            };
        }

        // String comparison
        $actualStr = is_string($actual) ? $actual : (string) ($actual ?? '');
        return match ($operator) {
            '=' => $actualStr === $expected,
            '!=', '<>' => $actualStr !== $expected,
            default => false,
        };
    }
}
