<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Handler;

use NashGao\InteractiveShell\Parser\ParsedCommand;
use Nashgao\MQTT\Shell\HandlerContext;
use Nashgao\MQTT\Shell\HandlerResult;
use Nashgao\MQTT\Shell\Mqtt\TopicMatcher;
use Nashgao\MQTT\Shell\Rule\RuleEngine;
use Nashgao\MQTT\Shell\Rule\RuleParser;

/**
 * Handler for rule management commands.
 *
 * Commands:
 * - rule add <name> "<sql>" - Add a new rule
 * - rule remove <name> - Remove a rule
 * - rule list - List all rules
 * - rule enable <name> - Enable a rule
 * - rule disable <name> - Disable a rule
 * - rule test "<sql>" <message_id> - Test rule against message
 * - rule export - Export rules as JSON
 * - rule import <json> - Import rules from JSON
 */
final class RuleHandler implements HandlerInterface
{
    private RuleEngine $engine;
    private RuleParser $parser;

    public function __construct(?RuleEngine $engine = null, ?RuleParser $parser = null)
    {
        $this->engine = $engine ?? new RuleEngine();
        $this->parser = $parser ?? new RuleParser();
    }

    public function getEngine(): RuleEngine
    {
        return $this->engine;
    }

    public function getCommands(): array
    {
        return ['rule', 'rules'];
    }

    public function handle(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $subcommand = $command->getArgument(0);

        if ($subcommand === null || $subcommand === 'list') {
            return $this->listRules($context);
        }

        if (!is_string($subcommand)) {
            return $this->listRules($context);
        }

        return match ($subcommand) {
            'add' => $this->addRule($command, $context),
            'remove', 'delete', 'rm' => $this->removeRule($command, $context),
            'enable' => $this->enableRule($command, $context),
            'disable' => $this->disableRule($command, $context),
            'test' => $this->testRule($command, $context),
            'export' => $this->exportRules($context),
            'import' => $this->importRules($command, $context),
            'show' => $this->showRule($command, $context),
            default => $this->listRules($context),
        };
    }

    public function getDescription(): string
    {
        return 'Manage SQL-like filter rules';
    }

    public function getUsage(): array
    {
        return [
            'rule list                          List all rules',
            'rule add <name> "<sql>"            Add a rule (e.g., rule add temp "SELECT * FROM \'sensors/#\' WHERE payload.temp > 30")',
            'rule remove <name>                 Remove a rule',
            'rule enable <name>                 Enable a rule',
            'rule disable <name>                Disable a rule',
            'rule show <name>                   Show rule details',
            'rule test "<sql>" <msg_id>         Test rule against a message',
            'rule export                        Export rules as JSON',
            'rule import <json>                 Import rules from JSON',
        ];
    }

    private function listRules(HandlerContext $context): HandlerResult
    {
        $rules = $this->engine->getAllRules();

        if (empty($rules)) {
            $context->output->writeln('<comment>No rules defined. Use "rule add <name> \"<sql>\"" to create one.</comment>');
            return HandlerResult::success();
        }

        // Table format
        $context->output->writeln('');
        $context->output->writeln('+' . str_repeat('-', 14) . '+' . str_repeat('-', 10) . '+' . str_repeat('-', 50) . '+');
        $context->output->writeln(sprintf('| %-12s | %-8s | %-48s |', 'Name', 'Status', 'SQL'));
        $context->output->writeln('+' . str_repeat('-', 14) . '+' . str_repeat('-', 10) . '+' . str_repeat('-', 50) . '+');

        foreach ($rules as $rule) {
            $status = $rule->enabled ? '<info>active</info>' : '<comment>disabled</comment>';
            $sql = mb_strlen($rule->sql) > 45 ? mb_substr($rule->sql, 0, 45) . '...' : $rule->sql;
            $context->output->writeln(sprintf('| %-12s | %-8s | %-48s |', $rule->name, $status, $sql));
        }

        $context->output->writeln('+' . str_repeat('-', 14) . '+' . str_repeat('-', 10) . '+' . str_repeat('-', 50) . '+');
        $context->output->writeln('');

        return HandlerResult::success();
    }

    private function addRule(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $name = $command->getArgument(1);
        if ($name === null || !is_string($name)) {
            $context->output->writeln('<error>Usage: rule add <name> "<sql>"</error>');
            return HandlerResult::failure('Missing rule name');
        }

        // Get the SQL (might be wrapped in outer quotes)
        $args = $command->arguments;
        array_shift($args); // Remove 'add'
        array_shift($args); // Remove name
        $sql = implode(' ', array_map('strval', $args));

        // Only strip outer quotes if the whole string is quoted
        if (strlen($sql) >= 2) {
            $first = $sql[0];
            $last = $sql[strlen($sql) - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $sql = substr($sql, 1, -1);
            }
        }

        if (empty($sql)) {
            $context->output->writeln('<error>Usage: rule add <name> "<sql>"</error>');
            return HandlerResult::failure('Missing SQL');
        }

        try {
            $parsed = $this->parser->parse($sql);

            // Validate MQTT topic pattern in FROM clause
            $fromTopic = $parsed['from'];
            $validation = TopicMatcher::validate($fromTopic);
            if (! $validation->isValid()) {
                $context->output->writeln(sprintf(
                    "<error>Invalid MQTT pattern '%s': %s</error>",
                    $fromTopic,
                    $validation->error
                ));
                return HandlerResult::failure('Invalid MQTT pattern: ' . $validation->error);
            }

            $rule = new \Nashgao\MQTT\Shell\Rule\Rule(
                name: $name,
                sql: $sql,
                selectFields: $parsed['select'],
                fromTopic: $fromTopic,
                whereCondition: $parsed['where'],
                actions: [],
                enabled: true,
            );
            $this->engine->addRule($rule);
            $context->output->writeln(sprintf('<info>Rule \'%s\' created</info>', $name));
            return HandlerResult::success();
        } catch (\Throwable $e) {
            $context->output->writeln(sprintf('<error>Failed to parse rule: %s</error>', $e->getMessage()));
            return HandlerResult::failure($e->getMessage());
        }
    }

    private function removeRule(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $name = $command->getArgument(1);
        if ($name === null || !is_string($name)) {
            $context->output->writeln('<error>Usage: rule remove <name></error>');
            return HandlerResult::failure('Missing rule name');
        }

        if ($this->engine->removeRule($name)) {
            $context->output->writeln(sprintf('<info>Rule \'%s\' removed</info>', $name));
            return HandlerResult::success();
        }

        $context->output->writeln(sprintf('<error>Rule \'%s\' not found</error>', $name));
        return HandlerResult::failure('Rule not found');
    }

    private function enableRule(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $name = $command->getArgument(1);
        if ($name === null || !is_string($name)) {
            $context->output->writeln('<error>Usage: rule enable <name></error>');
            return HandlerResult::failure('Missing rule name');
        }

        if ($this->engine->enableRule($name)) {
            $context->output->writeln(sprintf('<info>Rule \'%s\' enabled</info>', $name));
            return HandlerResult::success();
        }

        $context->output->writeln(sprintf('<error>Rule \'%s\' not found</error>', $name));
        return HandlerResult::failure('Rule not found');
    }

    private function disableRule(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $name = $command->getArgument(1);
        if ($name === null || !is_string($name)) {
            $context->output->writeln('<error>Usage: rule disable <name></error>');
            return HandlerResult::failure('Missing rule name');
        }

        if ($this->engine->disableRule($name)) {
            $context->output->writeln(sprintf('<info>Rule \'%s\' disabled</info>', $name));
            return HandlerResult::success();
        }

        $context->output->writeln(sprintf('<error>Rule \'%s\' not found</error>', $name));
        return HandlerResult::failure('Rule not found');
    }

    private function showRule(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $name = $command->getArgument(1);
        if ($name === null || !is_string($name)) {
            $context->output->writeln('<error>Usage: rule show <name></error>');
            return HandlerResult::failure('Missing rule name');
        }

        $rule = $this->engine->getRule($name);
        if ($rule === null) {
            $context->output->writeln(sprintf('<error>Rule \'%s\' not found</error>', $name));
            return HandlerResult::failure('Rule not found');
        }

        // Vertical display
        $context->output->writeln('');
        $context->output->writeln(str_repeat('*', 20) . ' Rule ' . str_repeat('*', 20));
        $context->output->writeln(sprintf('%12s: %s', 'name', $rule->name));
        $context->output->writeln(sprintf('%12s: %s', 'status', $rule->enabled ? 'active' : 'disabled'));
        $context->output->writeln(sprintf('%12s: %s', 'sql', $rule->sql));
        $context->output->writeln(sprintf('%12s: %s', 'topic', $rule->fromTopic));
        $context->output->writeln(sprintf('%12s: %s', 'fields', implode(', ', $rule->selectFields)));
        $context->output->writeln(sprintf('%12s: %s', 'where', $rule->whereCondition?->toString() ?? '(none)'));
        $context->output->writeln(str_repeat('*', 46));
        $context->output->writeln('');

        return HandlerResult::success();
    }

    private function testRule(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        // Get SQL and message ID
        $args = $command->arguments;
        array_shift($args); // Remove 'test'

        if (count($args) < 2) {
            $context->output->writeln('<error>Usage: rule test "<sql>" <message_id></error>');
            return HandlerResult::failure('Missing arguments');
        }

        $messageIdArg = array_pop($args); // Last arg is message ID
        $messageId = is_numeric($messageIdArg) ? (int) $messageIdArg : null;

        if ($messageId === null) {
            $context->output->writeln('<error>Invalid message ID</error>');
            return HandlerResult::failure('Invalid message ID');
        }

        $sql = implode(' ', array_map('strval', $args));

        // Only strip outer quotes if the whole string is quoted
        if (strlen($sql) >= 2) {
            $first = $sql[0];
            $last = $sql[strlen($sql) - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $sql = substr($sql, 1, -1);
            }
        }

        $message = $context->messageHistory->get($messageId);
        if ($message === null) {
            $context->output->writeln(sprintf('<error>Message #%d not found</error>', $messageId));
            return HandlerResult::failure('Message not found');
        }

        try {
            $parsed = $this->parser->parse($sql);

            // Validate MQTT topic pattern in FROM clause
            $fromTopic = $parsed['from'];
            $validation = TopicMatcher::validate($fromTopic);
            if (! $validation->isValid()) {
                $context->output->writeln(sprintf(
                    "<error>Invalid MQTT pattern '%s': %s</error>",
                    $fromTopic,
                    $validation->error
                ));
                return HandlerResult::failure('Invalid MQTT pattern: ' . $validation->error);
            }

            $rule = new \Nashgao\MQTT\Shell\Rule\Rule(
                name: '__test__',
                sql: $sql,
                selectFields: $parsed['select'],
                fromTopic: $fromTopic,
                whereCondition: $parsed['where'],
                actions: [],
                enabled: true,
            );
            $matches = $rule->matches($message);

            if ($matches) {
                $context->output->writeln('<info>✓ Rule MATCHES message</info>');
                $data = $rule->execute($message);
                $context->output->writeln('Result: ' . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            } else {
                $context->output->writeln('<comment>✗ Rule does NOT match message</comment>');
            }

            return HandlerResult::success();
        } catch (\Throwable $e) {
            $context->output->writeln(sprintf('<error>Error: %s</error>', $e->getMessage()));
            return HandlerResult::failure($e->getMessage());
        }
    }

    private function exportRules(HandlerContext $context): HandlerResult
    {
        $rules = $this->engine->exportRules();
        $json = json_encode($rules, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $context->output->writeln($json ?: '[]');
        return HandlerResult::success();
    }

    private function importRules(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $args = $command->arguments;
        array_shift($args); // Remove 'import'
        $json = implode(' ', array_map('strval', $args));

        if (empty($json)) {
            $context->output->writeln('<error>Usage: rule import <json></error>');
            return HandlerResult::failure('Missing JSON');
        }

        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($data)) {
                throw new \InvalidArgumentException('JSON must be an array');
            }
            $this->engine->importRules($data, $this->parser);
            $context->output->writeln(sprintf('<info>Imported %d rules</info>', count($data)));
            return HandlerResult::success();
        } catch (\Throwable $e) {
            $context->output->writeln(sprintf('<error>Import failed: %s</error>', $e->getMessage()));
            return HandlerResult::failure($e->getMessage());
        }
    }
}
