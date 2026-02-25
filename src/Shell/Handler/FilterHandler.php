<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Handler;

use NashGao\InteractiveShell\Parser\ParsedCommand;
use Nashgao\MQTT\Shell\HandlerContext;
use Nashgao\MQTT\Shell\HandlerResult;
use Nashgao\MQTT\Shell\Mqtt\TopicMatcher;

/**
 * Handler for filter commands with SQL-like syntax.
 *
 * Commands (pipeline model - filters stack by default):
 * - filter where <expression>     Stack filter (adds AND if filter exists)
 * - filter set <expression>       Replace filter (explicit replace)
 * - filter or <expression>        Stack with OR operator
 * - filter not <expression>       Stack with AND NOT operator
 * - filter add and <expression>   Add AND clause (explicit)
 * - filter add or <expression>    Add OR clause (explicit)
 * - filter add and not <expression> Add AND NOT clause (explicit)
 * - filter remove <expression>    Remove a clause
 * - filter save <name>            Save current filter as preset
 * - filter apply <name>           Apply a saved preset
 * - filter list                   List saved presets
 * - filter delete <name>          Delete a saved preset
 * - filter show                   Show current filter
 * - filter clear                  Clear all filters
 * - filter help                   Show help
 */
final class FilterHandler implements HandlerInterface
{
    public function getCommands(): array
    {
        return ['filter'];
    }

    public function handle(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $subcommandRaw = $command->getArgument(0);

        // Default to 'show' if no subcommand
        if ($subcommandRaw === null) {
            return $this->handleShow($context);
        }

        $subcommand = is_string($subcommandRaw) ? strtolower($subcommandRaw) : '';

        return match ($subcommand) {
            'where' => $this->handleWhere($command, $context),
            'set' => $this->handleSet($command, $context),
            'or' => $this->handleShorthandOr($command, $context),
            'not' => $this->handleShorthandNot($command, $context),
            'grep' => $this->handleGrep($command, $context),
            'add' => $this->handleAdd($command, $context),
            'remove' => $this->handleRemove($command, $context),
            'save' => $this->handleSave($command, $context),
            'apply' => $this->handleApply($command, $context),
            'list' => $this->handleList($context),
            'delete' => $this->handleDelete($command, $context),
            'show' => $this->handleShow($context),
            'clear', 'none' => $this->handleClear($context),
            'help' => $this->handleHelp($context),
            default => $this->handleLegacyOrUnknown($command, $context, is_string($subcommandRaw) ? $subcommandRaw : ''),
        };
    }

    /**
     * Handle 'filter where <expression>'.
     *
     * Stacks filters by default (pipeline model):
     * - If no filter exists: sets the base filter
     * - If filter exists: adds AND clause (stacking)
     *
     * Use 'filter set' for explicit replace behavior.
     */
    private function handleWhere(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $expression = $this->getExpressionAfter($command, 1);

        if ($expression === '') {
            $context->output->writeln('<error>Usage: filter where <expression></error>');
            $context->output->writeln("Example: filter where topic like 'sensors/#'");
            return HandlerResult::failure('Missing expression');
        }

        // Validate MQTT topic patterns
        $validationError = $this->validateTopicPatterns($expression);
        if ($validationError !== null) {
            $context->output->writeln("<error>{$validationError}</error>");
            return HandlerResult::failure($validationError);
        }

        try {
            // Stack if filters exist, otherwise set base filter
            if ($context->filter->hasFilters()) {
                $context->filter->addAnd($expression);
            } else {
                $context->filter->where($expression);
            }
            $this->showFilterStatus($context);
            return HandlerResult::success();
        } catch (\InvalidArgumentException $e) {
            return HandlerResult::failure('Parse error: ' . $e->getMessage());
        }
    }

    /**
     * Handle 'filter set <expression>'.
     *
     * Explicitly replaces any existing filter.
     */
    private function handleSet(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $expression = $this->getExpressionAfter($command, 1);

        if ($expression === '') {
            $context->output->writeln('<error>Usage: filter set <expression></error>');
            $context->output->writeln("Example: filter set topic like 'sensors/#'");
            return HandlerResult::failure('Missing expression');
        }

        // Validate MQTT topic patterns
        $validationError = $this->validateTopicPatterns($expression);
        if ($validationError !== null) {
            $context->output->writeln("<error>{$validationError}</error>");
            return HandlerResult::failure($validationError);
        }

        try {
            $context->filter->where($expression);  // Always replaces
            $this->showFilterStatus($context);
            return HandlerResult::success();
        } catch (\InvalidArgumentException $e) {
            return HandlerResult::failure('Parse error: ' . $e->getMessage());
        }
    }

    /**
     * Handle 'filter or <expression>' shorthand.
     */
    private function handleShorthandOr(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $expression = $this->getExpressionAfter($command, 1);

        if ($expression === '') {
            $context->output->writeln('<error>Usage: filter or <expression></error>');
            return HandlerResult::failure('Missing expression');
        }

        // Validate MQTT topic patterns
        $validationError = $this->validateTopicPatterns($expression);
        if ($validationError !== null) {
            $context->output->writeln("<error>{$validationError}</error>");
            return HandlerResult::failure($validationError);
        }

        if (!$context->filter->hasFilters()) {
            $context->output->writeln("<error>No base filter. Use 'filter where' first.</error>");
            return HandlerResult::failure("No base filter. Use 'filter where' first.");
        }

        try {
            $context->filter->addOr($expression);
            $this->showFilterStatus($context);
            return HandlerResult::success();
        } catch (\InvalidArgumentException $e) {
            return HandlerResult::failure($e->getMessage());
        }
    }

    /**
     * Handle 'filter not <expression>' shorthand.
     */
    private function handleShorthandNot(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $expression = $this->getExpressionAfter($command, 1);

        if ($expression === '') {
            $context->output->writeln('<error>Usage: filter not <expression></error>');
            return HandlerResult::failure('Missing expression');
        }

        // Validate MQTT topic patterns
        $validationError = $this->validateTopicPatterns($expression);
        if ($validationError !== null) {
            $context->output->writeln("<error>{$validationError}</error>");
            return HandlerResult::failure($validationError);
        }

        if (!$context->filter->hasFilters()) {
            $context->output->writeln("<error>No base filter. Use 'filter where' first.</error>");
            return HandlerResult::failure("No base filter. Use 'filter where' first.");
        }

        try {
            $context->filter->addNot($expression);
            $this->showFilterStatus($context);
            return HandlerResult::success();
        } catch (\InvalidArgumentException $e) {
            return HandlerResult::failure($e->getMessage());
        }
    }

    /**
     * Handle 'filter grep <pattern>'.
     *
     * Searches for pattern in message_raw (original message text).
     * Stacks with existing filters using AND.
     */
    private function handleGrep(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $pattern = $this->getExpressionAfter($command, 1);

        if ($pattern === '') {
            $context->output->writeln('<error>Usage: filter grep <pattern></error>');
            $context->output->writeln('Example: filter grep error');
            $context->output->writeln('Example: filter grep temperature');
            return HandlerResult::failure('Missing pattern');
        }

        // Escape single quotes in pattern for SQL-like expression
        $escapedPattern = str_replace("'", "''", $pattern);
        $expression = "message_raw like '%{$escapedPattern}%'";

        try {
            if ($context->filter->hasFilters()) {
                $context->filter->addAnd($expression);
            } else {
                $context->filter->where($expression);
            }
            $this->showFilterStatus($context);
            return HandlerResult::success();
        } catch (\InvalidArgumentException $e) {
            return HandlerResult::failure($e->getMessage());
        }
    }

    /**
     * Handle 'filter add and/or <expression>'.
     */
    private function handleAdd(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $operatorRaw = $command->getArgument(1);
        $operator = is_string($operatorRaw) ? strtolower($operatorRaw) : '';

        if (!in_array($operator, ['and', 'or'], true)) {
            $context->output->writeln('<error>Usage: filter add and|or <expression></error>');
            $context->output->writeln('       filter add and not <expression>');
            return HandlerResult::failure('Invalid operator');
        }

        // Check for 'and not'
        $isNot = false;
        $startIndex = 2;
        $arg2Raw = $command->getArgument(2);
        if ($operator === 'and' && is_string($arg2Raw) && strtolower($arg2Raw) === 'not') {
            $isNot = true;
            $startIndex = 3;
        }

        $expression = $this->getExpressionAfter($command, $startIndex);

        if ($expression === '') {
            $context->output->writeln('<error>Missing expression after operator</error>');
            return HandlerResult::failure('Missing expression');
        }

        // Validate MQTT topic patterns
        $validationError = $this->validateTopicPatterns($expression);
        if ($validationError !== null) {
            $context->output->writeln("<error>{$validationError}</error>");
            return HandlerResult::failure($validationError);
        }

        try {
            if ($isNot) {
                $context->filter->addNot($expression);
            } elseif ($operator === 'and') {
                $context->filter->addAnd($expression);
            } else {
                $context->filter->addOr($expression);
            }

            $this->showFilterStatus($context);
            return HandlerResult::success();
        } catch (\InvalidArgumentException $e) {
            return HandlerResult::failure($e->getMessage());
        }
    }

    /**
     * Handle 'filter remove [<expression>|<index>]'.
     *
     * Supports three modes:
     * - filter remove            Interactive picker
     * - filter remove 2          Remove by index (1-based)
     * - filter remove qos = 1    Remove by expression
     */
    private function handleRemove(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $clauses = $context->filter->getClauses();

        if (empty($clauses)) {
            $context->output->writeln('<comment>No filters to remove</comment>');
            return HandlerResult::success();
        }

        $arg = $command->getArgument(1);

        // No argument → interactive mode
        if ($arg === null) {
            return $this->handleInteractiveRemove($context, $clauses);
        }

        // Positive integer → remove by index
        if (is_string($arg) && ctype_digit($arg)) {
            return $this->handleRemoveByIndex((int) $arg, $context, $clauses);
        }

        // String → remove by expression (existing behavior)
        $expression = $this->getExpressionAfter($command, 1);

        if ($expression === '') {
            $context->output->writeln('<error>Usage: filter remove [<expression>|<index>]</error>');
            $context->output->writeln('  filter remove              Interactive picker');
            $context->output->writeln('  filter remove 2            Remove by index');
            $context->output->writeln("  filter remove qos = 1      Remove by expression");
            return HandlerResult::failure('Missing expression');
        }

        $beforeCount = count($clauses);
        $context->filter->remove($expression);
        $afterCount = count($context->filter->getClauses());

        if ($beforeCount === $afterCount) {
            $context->output->writeln("<comment>No clause matched: {$expression}</comment>");
        } else {
            $context->output->writeln("<info>Removed: {$expression}</info>");
        }

        $this->showFilterStatus($context);
        return HandlerResult::success();
    }

    /**
     * Handle interactive clause removal by showing numbered list.
     *
     * @param array<object> $clauses Filter clauses with expression and operator properties
     */
    private function handleInteractiveRemove(HandlerContext $context, array $clauses): HandlerResult
    {
        $context->output->writeln('<info>Filter clauses:</info>');
        foreach ($clauses as $i => $clause) {
            $operator = $clause->isBase() ? '(base)' : "({$clause->operator})";
            $context->output->writeln(sprintf('  [%d] %-40s %s', $i + 1, $clause->expression, $operator));
        }
        $context->output->writeln('');
        $context->output->writeln('<comment>Use: filter remove <index> to remove a clause</comment>');

        return HandlerResult::success();
    }

    /**
     * Handle removal by 1-based index.
     *
     * @param array<object> $clauses Filter clauses with expression property
     */
    private function handleRemoveByIndex(int $index, HandlerContext $context, array $clauses): HandlerResult
    {
        // Convert 1-based user input to 0-based array index
        $arrayIndex = $index - 1;

        if ($arrayIndex < 0 || $arrayIndex >= count($clauses)) {
            $context->output->writeln(
                "<error>Invalid index: {$index}. Valid range: 1-" . count($clauses) . '</error>'
            );
            return HandlerResult::failure("Invalid index: {$index}");
        }

        $clause = $clauses[$arrayIndex];
        $context->filter->remove($clause->expression);
        $context->output->writeln("<info>Removed: {$clause->expression}</info>");
        $this->showFilterStatus($context);

        return HandlerResult::success();
    }

    /**
     * Handle 'filter save <name>'.
     */
    private function handleSave(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $nameRaw = $command->getArgument(1);
        $name = is_string($nameRaw) ? $nameRaw : '';

        if ($name === '') {
            $context->output->writeln('<error>Usage: filter save <name></error>');
            return HandlerResult::failure('Missing preset name');
        }

        if (!$context->filter->hasFilters()) {
            $context->output->writeln('<error>No filter to save. Set a filter first with "filter where ..."</error>');
            return HandlerResult::failure('No filter to save');
        }

        try {
            $overwriting = $context->presetManager->has($name);
            $context->presetManager->save($name, $context->filter);

            if ($overwriting) {
                $context->output->writeln("<info>Preset '{$name}' updated</info>");
            } else {
                $context->output->writeln("<info>Preset '{$name}' saved</info>");
            }

            return HandlerResult::success();
        } catch (\InvalidArgumentException $e) {
            return HandlerResult::failure($e->getMessage());
        }
    }

    /**
     * Handle 'filter apply <name>'.
     */
    private function handleApply(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $nameRaw = $command->getArgument(1);
        $name = is_string($nameRaw) ? $nameRaw : '';

        if ($name === '') {
            $context->output->writeln('<error>Usage: filter apply <name></error>');
            return HandlerResult::failure('Missing preset name');
        }

        $preset = $context->presetManager->get($name);

        if ($preset === null) {
            $available = $context->presetManager->list();
            if (empty($available)) {
                $context->output->writeln("<error>Preset '{$name}' not found. No presets saved yet.</error>");
            } else {
                $context->output->writeln(sprintf(
                    "<error>Preset '%s' not found. Available: %s</error>",
                    $name,
                    implode(', ', $available)
                ));
            }
            return HandlerResult::failure('Preset not found');
        }

        // Copy the preset's clauses to current filter
        $context->filter->clear();
        $sql = $preset->toSql();
        if ($sql !== '') {
            $context->filter->where($sql);
        }

        $context->output->writeln("<info>Applied preset '{$name}'</info>");
        $this->showFilterStatus($context);
        return HandlerResult::success();
    }

    /**
     * Handle 'filter list'.
     */
    private function handleList(HandlerContext $context): HandlerResult
    {
        $presets = $context->presetManager->getAll();

        if (empty($presets)) {
            $context->output->writeln('<comment>No presets saved</comment>');
            $context->output->writeln("Save current filter with: filter save <name>");
            return HandlerResult::success();
        }

        $context->output->writeln('<info>Saved presets:</info>');
        foreach ($presets as $name => $sql) {
            $context->output->writeln("  <comment>{$name}</comment>: {$sql}");
        }

        return HandlerResult::success();
    }

    /**
     * Handle 'filter delete <name>'.
     */
    private function handleDelete(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $nameRaw = $command->getArgument(1);
        $name = is_string($nameRaw) ? $nameRaw : '';

        if ($name === '') {
            $context->output->writeln('<error>Usage: filter delete <name></error>');
            return HandlerResult::failure('Missing preset name');
        }

        if ($context->presetManager->delete($name)) {
            $context->output->writeln("<info>Preset '{$name}' deleted</info>");
            return HandlerResult::success();
        }

        $context->output->writeln("<error>Preset '{$name}' not found</error>");
        return HandlerResult::failure('Preset not found');
    }

    /**
     * Handle 'filter show'.
     *
     * Displays numbered list of active filter clauses.
     */
    private function handleShow(HandlerContext $context): HandlerResult
    {
        $clauses = $context->filter->getClauses();

        if (empty($clauses)) {
            $context->output->writeln('No filters (showing all)');
            return HandlerResult::success();
        }

        $context->output->writeln('<info>Active filters:</info>');
        foreach ($clauses as $i => $clause) {
            $operator = $clause->isBase() ? '(base)' : "({$clause->operator})";
            $context->output->writeln(sprintf('  [%d] %-40s %s', $i + 1, $clause->expression, $operator));
        }

        // Show matching message count from history
        $total = $context->messageHistory->count();
        if ($total > 0) {
            $matching = 0;
            // getLast with total count gets all messages
            foreach ($context->messageHistory->getLast($total) as $message) {
                if ($context->filter->matches($message)) {
                    $matching++;
                }
            }
            $context->output->writeln("<comment>Matching: {$matching} of {$total} messages in history</comment>");
        }

        return HandlerResult::success();
    }

    /**
     * Handle 'filter clear'.
     */
    private function handleClear(HandlerContext $context): HandlerResult
    {
        $context->filter->clear();
        $context->output->writeln('<info>Filter cleared - showing all messages</info>');
        return HandlerResult::success();
    }

    /**
     * Handle 'filter help'.
     */
    private function handleHelp(HandlerContext $context): HandlerResult
    {
        $context->output->writeln('<info>Filter Command Help</info>');
        $context->output->writeln('');
        $context->output->writeln('<comment>Pipeline Model (filters stack by default):</comment>');
        $context->output->writeln("  filter where temp>20      # Sets: temp>20");
        $context->output->writeln("  filter where temp<23      # Stacks: temp>20 AND temp<23");
        $context->output->writeln("  filter where humidity>50  # Stacks: temp>20 AND temp<23 AND humidity>50");
        $context->output->writeln('');
        $context->output->writeln('<comment>Filter Commands:</comment>');
        $context->output->writeln('  filter where <expr>   Stack filter (adds AND if filter exists)');
        $context->output->writeln('  filter set <expr>     Replace filter (explicit replace)');
        $context->output->writeln('  filter or <expr>      Stack with OR operator');
        $context->output->writeln('  filter not <expr>     Stack with AND NOT operator');
        $context->output->writeln('  filter grep <text>    Search for text in message content');
        $context->output->writeln('  filter clear          Clear all filters');
        $context->output->writeln('  filter show           Show current filter');
        $context->output->writeln('');
        $context->output->writeln('<comment>MQTT Protocol Fields:</comment>');
        $context->output->writeln('  topic      - MQTT topic path (supports + and # wildcards)');
        $context->output->writeln('  qos        - Quality of Service (0, 1, 2)');
        $context->output->writeln('  retain     - Retain flag (true/false)');
        $context->output->writeln('  dup        - Duplicate flag (true/false)');
        $context->output->writeln('');
        $context->output->writeln('<comment>Shell Metadata:</comment>');
        $context->output->writeln('  timestamp  - When the shell captured this message');
        $context->output->writeln('  direction  - Message direction (incoming, outgoing)');
        $context->output->writeln('  pool       - Connection pool name');
        $context->output->writeln('  type       - Message type');
        $context->output->writeln('');
        $context->output->writeln('<comment>Payload Access:</comment>');
        $context->output->writeln('  payload.*     - Access fields in JSON payload');
        $context->output->writeln('                Example: payload.temp, payload.sensor.value');
        $context->output->writeln('  message_raw   - Original message as text (for grep/pattern matching)');
        $context->output->writeln('  payload_json  - JSON-encoded payload (for grep/pattern matching)');
        $context->output->writeln('');
        $context->output->writeln('<comment>Basic filtering:</comment>');
        $context->output->writeln("  filter where topic like 'sensors/#'");
        $context->output->writeln("  filter where topic like 'sensors/#' and qos = 1");
        $context->output->writeln("  filter where payload.temp > 30");
        $context->output->writeln("  filter where payload.status = 'error'");
        $context->output->writeln('');
        $context->output->writeln('<comment>Text search (grep):</comment>');
        $context->output->writeln('  filter grep error                     # Find messages containing "error"');
        $context->output->writeln('  filter grep temperature               # Find messages with "temperature"');
        $context->output->writeln('  g error                               # Short alias for grep');
        $context->output->writeln('');
        $context->output->writeln('<comment>Pipeline building (recommended):</comment>');
        $context->output->writeln("  filter where topic like 'sensors/#'  # Base filter");
        $context->output->writeln('  filter where qos = 1                  # Stacks as AND');
        $context->output->writeln('  filter where payload.temp > 20        # Stacks as AND');
        $context->output->writeln("  filter or topic like 'devices/#'      # Adds OR clause");
        $context->output->writeln("  filter not topic like 'debug/#'       # Adds AND NOT clause");
        $context->output->writeln('  filter grep warning                   # Stacks grep as AND');
        $context->output->writeln('');
        $context->output->writeln('<comment>Explicit operators (verbose):</comment>');
        $context->output->writeln("  filter add or topic like 'devices/#'");
        $context->output->writeln('  filter add and qos = 1');
        $context->output->writeln("  filter add and not topic like 'debug/#'");
        $context->output->writeln('');
        $context->output->writeln('<comment>Removing clauses:</comment>');
        $context->output->writeln('  filter remove                         # Interactive picker (↑/↓ + Enter)');
        $context->output->writeln('  filter remove 2                       # Remove by index');
        $context->output->writeln("  filter remove topic like 'devices/#'  # Remove by expression");
        $context->output->writeln('  filter show                           # Show numbered clause list');
        $context->output->writeln('');
        $context->output->writeln('<comment>Time-based filtering:</comment>');
        $context->output->writeln("  filter where timestamp > '10:30'");
        $context->output->writeln("  filter where timestamp > now() - interval '5m'");
        $context->output->writeln("  filter where timestamp between '10:00' and '11:00'");
        $context->output->writeln('');
        $context->output->writeln('<comment>Named presets:</comment>');
        $context->output->writeln('  filter save alerts');
        $context->output->writeln('  filter apply alerts');
        $context->output->writeln('  filter list');
        $context->output->writeln('  filter delete alerts');
        $context->output->writeln('');
        $context->output->writeln('<comment>Operators:</comment>');
        $context->output->writeln('  Comparison: =, !=, >, <, >=, <=');
        $context->output->writeln('  Patterns: like, not like (supports MQTT wildcards + and #)');
        $context->output->writeln('  Logic: and, or, not, ()');
        $context->output->writeln('');

        return HandlerResult::success();
    }

    /**
     * Handle legacy field:pattern syntax or unknown subcommands.
     */
    private function handleLegacyOrUnknown(
        ParsedCommand $command,
        HandlerContext $context,
        string $subcommand
    ): HandlerResult {
        // Check if it's the legacy field:pattern syntax
        if (str_contains($subcommand, ':')) {
            $context->output->writeln('<comment>Legacy syntax detected. Converting to new format...</comment>');

            $parts = [];
            foreach ($command->arguments as $arg) {
                if (is_string($arg) && str_contains($arg, ':')) {
                    [$field, $pattern] = explode(':', $arg, 2);
                    // Escape single quotes in pattern
                    $escapedPattern = str_replace("'", "''", $pattern);
                    // Convert to SQL-like syntax
                    if ($field === 'grep' || $field === 'contains') {
                        // Use message_raw for text search (not payload which is an array)
                        $parts[] = "message_raw like '%{$escapedPattern}%'";
                    } elseif ($field === 'qos') {
                        $parts[] = "qos = {$pattern}";
                    } else {
                        $parts[] = "{$field} like '{$escapedPattern}'";
                    }
                }
            }

            if (!empty($parts)) {
                $expression = implode(' and ', $parts);
                try {
                    // Stack if filters exist, otherwise set base filter
                    if ($context->filter->hasFilters()) {
                        $context->filter->addAnd($expression);
                    } else {
                        $context->filter->where($expression);
                    }
                    $this->showFilterStatus($context);
                    $context->output->writeln('');
                    $context->output->writeln("<comment>New syntax: filter where {$expression}</comment>");
                    return HandlerResult::success();
                } catch (\InvalidArgumentException $e) {
                    return HandlerResult::failure('Parse error: ' . $e->getMessage());
                }
            }
        }

        $context->output->writeln("<error>Unknown subcommand: {$subcommand}</error>");
        $context->output->writeln('Type "filter help" for available commands');
        return HandlerResult::failure("Unknown subcommand: {$subcommand}");
    }

    /**
     * Display current filter status with match count.
     */
    private function showFilterStatus(HandlerContext $context): void
    {
        $context->output->writeln('<info>Filter set: ' . $context->filter->toSql() . '</info>');

        // Show matching count from history
        $total = $context->messageHistory->count();
        if ($total > 0) {
            $matching = 0;
            // getLast with total count gets all messages
            foreach ($context->messageHistory->getLast($total) as $message) {
                if ($context->filter->matches($message)) {
                    $matching++;
                }
            }
            $context->output->writeln("<comment>Matching: {$matching} of {$total} messages in history</comment>");
        }
    }

    /**
     * Get expression from arguments starting at a given index.
     */
    private function getExpressionAfter(ParsedCommand $command, int $startIndex): string
    {
        /** @var array<int, string> $parts */
        $parts = array_slice($command->arguments, $startIndex);
        return trim(implode(' ', $parts));
    }

    /**
     * Validate MQTT topic patterns in an expression.
     *
     * Extracts patterns from "topic like 'pattern'" and validates them.
     *
     * @return string|null Error message if invalid, null if valid
     */
    private function validateTopicPatterns(string $expression): ?string
    {
        // Match patterns like: topic like 'pattern' or topic LIKE "pattern"
        if (preg_match_all("/topic\s+(?:like|not\s+like)\s+['\"]([^'\"]+)['\"]/i", $expression, $matches)) {
            foreach ($matches[1] as $pattern) {
                $validation = TopicMatcher::validate($pattern);
                if (! $validation->isValid()) {
                    return "Invalid MQTT pattern '{$pattern}': {$validation->error}";
                }
            }
        }
        return null;
    }

    public function getDescription(): string
    {
        return 'Set or show message filter (SQL-like syntax)';
    }

    public function getUsage(): array
    {
        return [
            "filter where topic like 'sensors/#'     Stack filter (AND if exists)",
            "filter set topic like 'sensors/#'       Replace filter (explicit)",
            "filter or topic like 'devices/#'        Stack with OR",
            "filter not topic like 'debug/#'         Stack with AND NOT",
            'filter grep error                       Search for text (stacks)',
            'filter add and qos = 1                  Add AND clause (verbose)',
            'filter remove                           Interactive clause picker',
            'filter remove 2                         Remove by index',
            'filter remove qos = 1                   Remove by expression',
            'filter save alerts                      Save as preset',
            'filter apply alerts                     Apply preset',
            'filter list                             List presets',
            'filter show                             Show numbered filter list',
            'filter clear                            Clear filter',
            'filter help                             Show detailed help',
        ];
    }
}
