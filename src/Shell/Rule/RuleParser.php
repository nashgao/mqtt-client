<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Rule;

use Nashgao\MQTT\Shell\Rule\Condition\ComparisonCondition;
use Nashgao\MQTT\Shell\Rule\Condition\ConditionInterface;
use Nashgao\MQTT\Shell\Rule\Condition\LogicalCondition;
use Nashgao\MQTT\Shell\Rule\Condition\PatternCondition;

/**
 * Parser for SQL-like rule syntax
 * Supports: SELECT fields FROM 'topic' WHERE conditions
 */
final class RuleParser
{
    /**
     * Parse SQL-like rule definition
     *
     * @param string $sql SQL-like rule string
     * @return array{select: array<string>, from: string, where: ConditionInterface|null} Parsed components
     * @throws \InvalidArgumentException If SQL syntax is invalid
     */
    public function parse(string $sql): array
    {
        $sql = trim($sql);

        return [
            'select' => $this->parseSelect($sql),
            'from' => $this->parseFrom($sql),
            'where' => $this->parseWhere($sql),
        ];
    }

    /**
     * Extract SELECT field list
     *
     * @param string $sql SQL string
     * @return array<string> Field names (may include aliases)
     * @throws \InvalidArgumentException If SELECT clause is invalid
     */
    private function parseSelect(string $sql): array
    {
        if (!preg_match('/SELECT\s+(.+?)\s+FROM/i', $sql, $matches)) {
            throw new \InvalidArgumentException('Invalid SELECT clause');
        }

        $fieldsStr = trim($matches[1]);

        if ($fieldsStr === '*') {
            return ['*'];
        }

        // Split by comma, but respect quotes
        $fields = $this->splitByComma($fieldsStr);

        return array_map('trim', $fields);
    }

    /**
     * Extract FROM topic pattern
     *
     * @param string $sql SQL string
     * @return string Topic pattern
     * @throws \InvalidArgumentException If FROM clause is invalid
     */
    private function parseFrom(string $sql): string
    {
        if (!preg_match('/FROM\s+[\'"]([^\'"]+)[\'"]/i', $sql, $matches)) {
            throw new \InvalidArgumentException('Invalid FROM clause - topic must be quoted');
        }

        return $matches[1];
    }

    /**
     * Extract and parse WHERE condition
     *
     * @param string $sql SQL string
     * @return ConditionInterface|null Parsed condition or null if no WHERE clause
     */
    private function parseWhere(string $sql): ?ConditionInterface
    {
        if (!preg_match('/WHERE\s+(.+)$/is', $sql, $matches)) {
            return null;
        }

        $whereClause = trim($matches[1]);
        return $this->parseCondition($whereClause);
    }

    /**
     * Parse condition expression recursively
     *
     * @param string $expr Condition expression
     * @return ConditionInterface Parsed condition
     */
    private function parseCondition(string $expr): ConditionInterface
    {
        $expr = trim($expr);

        // Handle parentheses
        if (str_starts_with($expr, '(') && str_ends_with($expr, ')')) {
            $inner = substr($expr, 1, -1);
            return $this->parseCondition($inner);
        }

        // Handle NOT operator
        if (preg_match('/^NOT\s+(.+)$/i', $expr, $matches)) {
            $innerCondition = $this->parseCondition(trim($matches[1]));
            return new LogicalCondition('NOT', [$innerCondition]);
        }

        // Handle AND/OR operators (find top-level operators)
        $orPosition = $this->findTopLevelOperator($expr, 'OR');
        if ($orPosition !== false) {
            $left = trim(substr($expr, 0, $orPosition));
            $right = trim(substr($expr, $orPosition + 2));
            return new LogicalCondition('OR', [
                $this->parseCondition($left),
                $this->parseCondition($right),
            ]);
        }

        $andPosition = $this->findTopLevelOperator($expr, 'AND');
        if ($andPosition !== false) {
            $left = trim(substr($expr, 0, $andPosition));
            $right = trim(substr($expr, $andPosition + 3));
            return new LogicalCondition('AND', [
                $this->parseCondition($left),
                $this->parseCondition($right),
            ]);
        }

        // Parse comparison or pattern condition
        return $this->parseSimpleCondition($expr);
    }

    /**
     * Parse simple comparison or pattern condition
     *
     * @param string $expr Simple condition expression
     * @return ConditionInterface Parsed condition
     * @throws \InvalidArgumentException If condition syntax is invalid
     */
    private function parseSimpleCondition(string $expr): ConditionInterface
    {
        // Pattern conditions: LIKE, NOT LIKE, REGEX
        if (preg_match('/(.+?)\s+(NOT\s+LIKE|LIKE|REGEX)\s+[\'"](.+?)[\'"]/i', $expr, $matches)) {
            return new PatternCondition(
                trim($matches[1]),
                strtoupper(trim($matches[2])),
                $matches[3]
            );
        }

        // Comparison conditions: =, !=, >, <, >=, <=
        if (preg_match('/(.+?)\s*(=|!=|>=|<=|>|<)\s*(.+)$/', $expr, $matches)) {
            $field = trim($matches[1]);
            $operator = trim($matches[2]);
            $value = trim($matches[3]);

            // Remove quotes from string values
            if (preg_match('/^[\'"](.+)[\'"]$/', $value, $valueMatches)) {
                $value = $valueMatches[1];
            } elseif (is_numeric($value)) {
                $value = str_contains($value, '.') ? (float) $value : (int) $value;
            }

            return new ComparisonCondition($field, $operator, $value);
        }

        throw new \InvalidArgumentException("Invalid condition syntax: {$expr}");
    }

    /**
     * Find top-level operator position (not inside parentheses)
     *
     * @param string $expr Expression to search
     * @param string $operator Operator to find (AND, OR)
     * @return int|false Position of operator or false if not found
     */
    private function findTopLevelOperator(string $expr, string $operator): int|false
    {
        $depth = 0;
        $len = strlen($expr);
        $opLen = strlen($operator);

        for ($i = 0; $i < $len; $i++) {
            if ($expr[$i] === '(') {
                $depth++;
            } elseif ($expr[$i] === ')') {
                $depth--;
            } elseif ($depth === 0) {
                // Check if operator starts at this position
                $substr = substr($expr, $i, $opLen);
                if (strcasecmp($substr, $operator) === 0) {
                    // Verify it's a word boundary
                    $before = $i > 0 ? $expr[$i - 1] : ' ';
                    $after = $i + $opLen < $len ? $expr[$i + $opLen] : ' ';
                    if (ctype_space($before) && ctype_space($after)) {
                        return $i;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Split string by comma, respecting quotes
     *
     * @param string $str String to split
     * @return array<string> Split parts
     */
    private function splitByComma(string $str): array
    {
        $parts = [];
        $current = '';
        $inQuotes = false;
        $quoteChar = '';

        for ($i = 0; $i < strlen($str); $i++) {
            $char = $str[$i];

            if (($char === '"' || $char === "'") && ($i === 0 || $str[$i - 1] !== '\\')) {
                if (!$inQuotes) {
                    $inQuotes = true;
                    $quoteChar = $char;
                } elseif ($char === $quoteChar) {
                    $inQuotes = false;
                }
                $current .= $char;
            } elseif ($char === ',' && !$inQuotes) {
                $parts[] = $current;
                $current = '';
            } else {
                $current .= $char;
            }
        }

        if ($current !== '') {
            $parts[] = $current;
        }

        return $parts;
    }
}
