<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Rule;

use Nashgao\MQTT\Shell\Rule\Action\ActionInterface;
use Nashgao\MQTT\Shell\Rule\Condition\ConditionInterface;
use Nashgao\MQTT\Shell\Rule\Expression\FieldExtractor;
use NashGao\InteractiveShell\Message\Message;

/**
 * Rule definition for MQTT message filtering and processing
 */
final class Rule
{
    /**
     * @param string $name Unique rule identifier
     * @param string $sql Original SQL-like rule definition
     * @param array<string> $selectFields Fields to extract (e.g., ['*'] or ['clientid', 'payload.temp as temperature'])
     * @param string $fromTopic Topic pattern with MQTT wildcards (e.g., 'sensors/#')
     * @param ConditionInterface|null $whereCondition Optional WHERE condition
     * @param array<ActionInterface> $actions Actions to execute when rule matches
     * @param bool $enabled Whether the rule is enabled
     */
    public function __construct(
        public readonly string $name,
        public readonly string $sql,
        public readonly array $selectFields,
        public readonly string $fromTopic,
        public readonly ?ConditionInterface $whereCondition,
        public readonly array $actions,
        public bool $enabled = true,
    ) {
    }

    /**
     * Check if message matches this rule
     *
     * @param Message $message MQTT message to check
     * @return bool True if message matches topic and WHERE condition
     */
    public function matches(Message $message): bool
    {
        if (!$this->enabled) {
            return false;
        }

        // Extract topic from message payload
        $topic = '';
        if (is_array($message->payload) && isset($message->payload['topic'])) {
            $topic = is_string($message->payload['topic']) ? $message->payload['topic'] : '';
        }

        // Check topic pattern match
        if (!$this->matchesTopic($topic)) {
            return false;
        }

        // If no WHERE condition, topic match is sufficient
        if ($this->whereCondition === null) {
            return true;
        }

        // Evaluate WHERE condition
        $context = FieldExtractor::buildContext($message);
        return $this->whereCondition->evaluate($context);
    }

    /**
     * Execute rule and return transformed data based on SELECT fields
     *
     * @param Message $message MQTT message to process
     * @return array<string, mixed> Extracted data based on SELECT fields
     */
    public function execute(Message $message): array
    {
        $context = FieldExtractor::buildContext($message);

        // Handle SELECT *
        if (in_array('*', $this->selectFields, true)) {
            $data = $context;
        } else {
            $data = $this->extractSelectedFields($context);
        }

        // Execute all actions
        foreach ($this->actions as $action) {
            $action->execute($data, $message);
        }

        return $data;
    }

    /**
     * Extract selected fields from context
     *
     * @param array<string, mixed> $context Full message context
     * @return array<string, mixed> Extracted fields with aliases
     */
    private function extractSelectedFields(array $context): array
    {
        $data = [];

        foreach ($this->selectFields as $field) {
            // Check for alias (e.g., "payload.temp as temperature")
            if (str_contains($field, ' as ')) {
                $parts = explode(' as ', $field);
                $fieldPath = trim($parts[0]);
                $alias = trim($parts[1]);

                $data[$alias] = FieldExtractor::extract($context, $fieldPath);
            } else {
                $trimmedField = trim($field);
                $data[$trimmedField] = FieldExtractor::extract($context, $trimmedField);
            }
        }

        return $data;
    }

    /**
     * Check if topic matches the rule's topic pattern
     *
     * @param string $topic Message topic to check
     * @return bool True if topic matches pattern
     */
    private function matchesTopic(string $topic): bool
    {
        // Convert MQTT wildcard pattern to regex
        $pattern = str_replace(
            ['+', '#'],
            ['[^/]+', '.*'],  // + = single level, # = multi level
            $this->fromTopic
        );

        $regex = '/^' . str_replace('/', '\\/', $pattern) . '$/';

        return preg_match($regex, $topic) === 1;
    }
}
