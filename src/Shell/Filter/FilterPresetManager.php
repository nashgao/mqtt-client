<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Filter;

/**
 * Manages named filter presets for the current session.
 *
 * Presets are stored in memory only (not persisted to disk).
 * They allow users to save and quickly switch between different filter configurations.
 *
 * Usage:
 *   $manager->save('alerts', $filterExpression);
 *   $savedFilter = $manager->get('alerts');
 *   $manager->delete('alerts');
 *   $names = $manager->list();
 */
final class FilterPresetManager
{
    /** @var array<string, FilterExpression> */
    private array $presets = [];

    /**
     * Save a filter expression as a named preset.
     *
     * @param string $name Preset name
     * @param FilterExpression $filter Filter to save
     * @throws \InvalidArgumentException If name is invalid
     */
    public function save(string $name, FilterExpression $filter): void
    {
        $name = trim($name);

        if ($name === '') {
            throw new \InvalidArgumentException('Preset name cannot be empty');
        }

        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_-]*$/', $name)) {
            throw new \InvalidArgumentException(
                'Preset name must start with a letter and contain only letters, numbers, underscores, and hyphens'
            );
        }

        $this->presets[$name] = $filter->clone();
    }

    /**
     * Get a saved preset by name.
     *
     * @param string $name Preset name
     * @return FilterExpression|null The saved filter or null if not found
     */
    public function get(string $name): ?FilterExpression
    {
        $name = trim($name);

        if (!isset($this->presets[$name])) {
            return null;
        }

        return $this->presets[$name]->clone();
    }

    /**
     * Delete a saved preset.
     *
     * @param string $name Preset name to delete
     * @return bool True if preset was deleted, false if not found
     */
    public function delete(string $name): bool
    {
        $name = trim($name);

        if (!isset($this->presets[$name])) {
            return false;
        }

        unset($this->presets[$name]);
        return true;
    }

    /**
     * List all saved preset names.
     *
     * @return array<string> List of preset names
     */
    public function list(): array
    {
        return array_keys($this->presets);
    }

    /**
     * Check if a preset exists.
     *
     * @param string $name Preset name
     * @return bool True if preset exists
     */
    public function has(string $name): bool
    {
        return isset($this->presets[trim($name)]);
    }

    /**
     * Get the number of saved presets.
     *
     * @return int Count of presets
     */
    public function count(): int
    {
        return count($this->presets);
    }

    /**
     * Clear all saved presets.
     */
    public function clear(): void
    {
        $this->presets = [];
    }

    /**
     * Get all presets with their SQL representations.
     *
     * @return array<string, string> Map of name => SQL expression
     */
    public function getAll(): array
    {
        $result = [];
        foreach ($this->presets as $name => $filter) {
            $result[$name] = $filter->toSql();
        }
        return $result;
    }
}
