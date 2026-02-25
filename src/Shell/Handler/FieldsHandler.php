<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Handler;

use NashGao\InteractiveShell\Parser\ParsedCommand;
use Nashgao\MQTT\Shell\HandlerContext;
use Nashgao\MQTT\Shell\HandlerResult;

/**
 * Handler for field filtering in JSON payloads.
 *
 * Commands:
 * - fields              Show current field filter settings
 * - fields show X,Y     Only show specified fields
 * - fields hide X,Y     Hide specified fields
 * - fields clear        Clear all field filters
 *
 * This allows focusing on relevant data in large JSON payloads.
 */
final class FieldsHandler implements HandlerInterface
{
    /** @var array<string> Fields to show (empty = show all) */
    private array $showFields = [];

    /** @var array<string> Fields to hide */
    private array $hideFields = [];

    /**
     * @return array<string>
     */
    public function getCommands(): array
    {
        return ['fields', 'field'];
    }

    public function handle(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $action = $command->getArgument(0);

        // No action - show current settings
        if ($action === null) {
            return $this->showStatus($context);
        }

        $actionStr = is_scalar($action) ? strtolower((string) $action) : '';

        return match ($actionStr) {
            'show' => $this->handleShow($command, $context),
            'hide' => $this->handleHide($command, $context),
            'clear', 'reset' => $this->handleClear($context),
            'only' => $this->handleShow($command, $context), // Alias for show
            default => HandlerResult::failure(
                "Unknown action: {$actionStr}. Use: fields show/hide/clear"
            ),
        };
    }

    /**
     * Get the list of fields to show.
     *
     * @return array<string>
     */
    public function getShowFields(): array
    {
        return $this->showFields;
    }

    /**
     * Get the list of fields to hide.
     *
     * @return array<string>
     */
    public function getHideFields(): array
    {
        return $this->hideFields;
    }

    /**
     * Check if field filtering is active.
     */
    public function hasFilters(): bool
    {
        return !empty($this->showFields) || !empty($this->hideFields);
    }

    /**
     * Apply field filtering to data.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function filterFields(array $data): array
    {
        if (empty($this->showFields) && empty($this->hideFields)) {
            return $data;
        }

        // If show fields are specified, only keep those
        if (!empty($this->showFields)) {
            return $this->filterToShowFields($data);
        }

        // Otherwise, hide specified fields
        return $this->filterHideFields($data);
    }

    private function showStatus(HandlerContext $context): HandlerResult
    {
        $context->output->writeln('<info>Field Filter Settings:</info>');
        $context->output->writeln('');

        if (empty($this->showFields) && empty($this->hideFields)) {
            $context->output->writeln('  No field filters active (showing all fields)');
        } else {
            if (!empty($this->showFields)) {
                $context->output->writeln('  <comment>Show only:</comment> ' . implode(', ', $this->showFields));
            }
            if (!empty($this->hideFields)) {
                $context->output->writeln('  <comment>Hidden:</comment> ' . implode(', ', $this->hideFields));
            }
        }

        $context->output->writeln('');
        $context->output->writeln('Usage:');
        $context->output->writeln('  fields show temp,humidity   Only show these fields');
        $context->output->writeln('  fields hide deviceId,meta   Hide these fields');
        $context->output->writeln('  fields clear                Clear all filters');

        return HandlerResult::success();
    }

    private function handleShow(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $fieldsArg = $command->getArgument(1);

        if ($fieldsArg === null) {
            if (empty($this->showFields)) {
                $context->output->writeln('<comment>No show filter active</comment>');
            } else {
                $context->output->writeln(sprintf(
                    '<info>Showing only:</info> %s',
                    implode(', ', $this->showFields)
                ));
            }
            $context->output->writeln('');
            $context->output->writeln('Usage: fields show field1,field2,...');
            return HandlerResult::success();
        }

        $fields = $this->parseFieldList($fieldsArg);

        if (empty($fields)) {
            return HandlerResult::failure('Please specify at least one field');
        }

        $this->showFields = $fields;
        $this->hideFields = []; // Clear hide fields when setting show fields

        $context->output->writeln(sprintf(
            '<info>Now showing only:</info> %s',
            implode(', ', $this->showFields)
        ));

        return HandlerResult::success();
    }

    private function handleHide(ParsedCommand $command, HandlerContext $context): HandlerResult
    {
        $fieldsArg = $command->getArgument(1);

        if ($fieldsArg === null) {
            if (empty($this->hideFields)) {
                $context->output->writeln('<comment>No hide filter active</comment>');
            } else {
                $context->output->writeln(sprintf(
                    '<info>Hiding:</info> %s',
                    implode(', ', $this->hideFields)
                ));
            }
            $context->output->writeln('');
            $context->output->writeln('Usage: fields hide field1,field2,...');
            return HandlerResult::success();
        }

        $fields = $this->parseFieldList($fieldsArg);

        if (empty($fields)) {
            return HandlerResult::failure('Please specify at least one field');
        }

        $this->hideFields = $fields;
        $this->showFields = []; // Clear show fields when setting hide fields

        $context->output->writeln(sprintf(
            '<info>Now hiding:</info> %s',
            implode(', ', $this->hideFields)
        ));

        return HandlerResult::success();
    }

    private function handleClear(HandlerContext $context): HandlerResult
    {
        $this->showFields = [];
        $this->hideFields = [];

        $context->output->writeln('<info>Field filters cleared (showing all fields)</info>');

        return HandlerResult::success();
    }

    /**
     * Parse comma-separated field list.
     *
     * @return array<string>
     */
    private function parseFieldList(mixed $arg): array
    {
        if (!is_scalar($arg)) {
            return [];
        }

        $str = (string) $arg;
        $fields = array_map('trim', explode(',', $str));

        return array_filter($fields, fn($f) => $f !== '');
    }

    /**
     * Filter data to only include show fields.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function filterToShowFields(array $data): array
    {
        $result = [];

        foreach ($this->showFields as $field) {
            // Handle nested paths like "sensors.temp"
            if (str_contains($field, '.')) {
                $value = $this->getNestedValue($data, $field);
                if ($value !== null) {
                    $this->setNestedValue($result, $field, $value);
                }
            } elseif (array_key_exists($field, $data)) {
                $result[$field] = $data[$field];
            }
        }

        return $result;
    }

    /**
     * Filter data to hide specified fields.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function filterHideFields(array $data): array
    {
        $result = $data;

        foreach ($this->hideFields as $field) {
            // Handle nested paths like "sensors.temp"
            if (str_contains($field, '.')) {
                $this->unsetNestedValue($result, $field);
            } else {
                unset($result[$field]);
            }
        }

        return $result;
    }

    /**
     * Get a nested value using dot notation.
     *
     * @param array<string, mixed> $data
     */
    private function getNestedValue(array $data, string $path): mixed
    {
        $keys = explode('.', $path);
        $current = $data;

        foreach ($keys as $key) {
            if (!is_array($current) || !array_key_exists($key, $current)) {
                return null;
            }
            $current = $current[$key];
        }

        return $current;
    }

    /**
     * Set a nested value using dot notation.
     *
     * @param array<string, mixed> $data
     */
    private function setNestedValue(array &$data, string $path, mixed $value): void
    {
        $keys = explode('.', $path);
        $current = &$data;

        foreach ($keys as $i => $key) {
            if ($i === count($keys) - 1) {
                $current[$key] = $value;
            } else {
                if (!isset($current[$key]) || !is_array($current[$key])) {
                    $current[$key] = [];
                }
                $current = &$current[$key];
            }
        }
    }

    /**
     * Unset a nested value using dot notation.
     *
     * @param array<string, mixed> $data
     */
    private function unsetNestedValue(array &$data, string $path): void
    {
        $keys = explode('.', $path);
        $current = &$data;

        foreach ($keys as $i => $key) {
            if ($i === count($keys) - 1) {
                unset($current[$key]);
                return;
            }
            if (!isset($current[$key]) || !is_array($current[$key])) {
                return;
            }
            $current = &$current[$key];
        }
    }

    public function getDescription(): string
    {
        return 'Filter fields in JSON payloads';
    }

    /**
     * @return array<string>
     */
    public function getUsage(): array
    {
        return [
            'fields                     Show current field filter settings',
            'fields show temp,humidity  Only show specified fields',
            'fields hide deviceId,meta  Hide specified fields',
            'fields clear               Clear all field filters',
        ];
    }
}
