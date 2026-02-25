<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Rule;

use NashGao\InteractiveShell\Message\Message;

/**
 * Main rule engine for processing MQTT messages against defined rules
 */
final class RuleEngine
{
    /**
     * @var array<string, Rule> Registered rules indexed by name
     */
    private array $rules = [];

    /**
     * Add a new rule to the engine
     *
     * @param Rule $rule Rule to add
     * @return void
     * @throws \InvalidArgumentException If rule with same name already exists
     */
    public function addRule(Rule $rule): void
    {
        if (isset($this->rules[$rule->name])) {
            throw new \InvalidArgumentException("Rule '{$rule->name}' already exists");
        }

        $this->rules[$rule->name] = $rule;
    }

    /**
     * Remove a rule from the engine
     *
     * @param string $name Rule name to remove
     * @return bool True if rule was removed, false if not found
     */
    public function removeRule(string $name): bool
    {
        if (!isset($this->rules[$name])) {
            return false;
        }

        unset($this->rules[$name]);
        return true;
    }

    /**
     * Get a rule by name
     *
     * @param string $name Rule name
     * @return Rule|null Rule instance or null if not found
     */
    public function getRule(string $name): ?Rule
    {
        return $this->rules[$name] ?? null;
    }

    /**
     * Get all registered rules
     *
     * @return array<string, Rule> All rules indexed by name
     */
    public function getAllRules(): array
    {
        return $this->rules;
    }

    /**
     * Enable a rule
     *
     * @param string $name Rule name to enable
     * @return bool True if rule was enabled, false if not found
     */
    public function enableRule(string $name): bool
    {
        if (!isset($this->rules[$name])) {
            return false;
        }

        $this->rules[$name]->enabled = true;
        return true;
    }

    /**
     * Disable a rule
     *
     * @param string $name Rule name to disable
     * @return bool True if rule was disabled, false if not found
     */
    public function disableRule(string $name): bool
    {
        if (!isset($this->rules[$name])) {
            return false;
        }

        $this->rules[$name]->enabled = false;
        return true;
    }

    /**
     * Process message against all enabled rules
     *
     * @param Message $message MQTT message to process
     * @return array<string, array<string, mixed>> Matched rules and their extracted data (rule name => data)
     */
    public function process(Message $message): array
    {
        $matches = [];

        foreach ($this->rules as $rule) {
            if ($rule->matches($message)) {
                $matches[$rule->name] = $rule->execute($message);
            }
        }

        return $matches;
    }

    /**
     * Export all rules to array format for JSON/YAML serialization
     *
     * @return array<string, array<string, mixed>> Exportable rule data
     */
    public function exportRules(): array
    {
        $exported = [];

        foreach ($this->rules as $rule) {
            $actionNames = array_map(
                fn($action) => $action->getName(),
                $rule->actions
            );

            $exported[$rule->name] = [
                'name' => $rule->name,
                'sql' => $rule->sql,
                'enabled' => $rule->enabled,
                'select' => $rule->selectFields,
                'from' => $rule->fromTopic,
                'where' => $rule->whereCondition?->toString(),
                'actions' => $actionNames,
            ];
        }

        return $exported;
    }

    /**
     * Import rules from array data
     * Note: This imports basic rule structure but not actions
     * Actions must be re-attached after import
     *
     * @param array<string, array<string, mixed>> $data Exported rule data
     * @param RuleParser $parser Parser for SQL syntax
     * @return void
     */
    public function importRules(array $data, RuleParser $parser): void
    {
        foreach ($data as $ruleData) {
            if (!is_array($ruleData)) {
                continue;
            }

            // Validate required fields
            if (!isset($ruleData['sql']) || !is_string($ruleData['sql'])) {
                continue;
            }

            if (!isset($ruleData['name']) || !is_string($ruleData['name'])) {
                continue;
            }

            $parsed = $parser->parse($ruleData['sql']);

            // Validate enabled field
            $enabled = true;
            if (isset($ruleData['enabled']) && is_bool($ruleData['enabled'])) {
                $enabled = $ruleData['enabled'];
            }

            $rule = new Rule(
                name: $ruleData['name'],
                sql: $ruleData['sql'],
                selectFields: $parsed['select'],
                fromTopic: $parsed['from'],
                whereCondition: $parsed['where'],
                actions: [], // Actions need to be re-attached
                enabled: $enabled,
            );

            $this->rules[$rule->name] = $rule;
        }
    }

    /**
     * Get count of registered rules
     *
     * @return int Number of rules
     */
    public function getRuleCount(): int
    {
        return count($this->rules);
    }

    /**
     * Get count of enabled rules
     *
     * @return int Number of enabled rules
     */
    public function getEnabledRuleCount(): int
    {
        $count = 0;
        foreach ($this->rules as $rule) {
            if ($rule->enabled) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Clear all rules from the engine
     *
     * @return void
     */
    public function clearRules(): void
    {
        $this->rules = [];
    }
}
