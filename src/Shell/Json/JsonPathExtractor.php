<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Json;

/**
 * Simple JSON Path extractor for MQTT message payloads.
 *
 * Supports a subset of JSON Path syntax:
 * - $.key - Root level key
 * - $.key.subkey - Nested key
 * - $.array[0] - Array index
 * - $.array[*] - All array elements
 * - $.key.*.subkey - Wildcard in path
 *
 * Examples:
 * - $.temperature
 * - $.sensors.temperature
 * - $.readings[0].value
 * - $.devices[*].status
 */
final class JsonPathExtractor
{
    /**
     * Extract value(s) from data using JSON path.
     *
     * @param mixed $data The data to extract from
     * @param string $path The JSON path expression (e.g., $.sensors.temperature)
     * @return mixed The extracted value(s) or null if not found
     */
    public function extract(mixed $data, string $path): mixed
    {
        // Normalize path
        $path = trim($path);

        // Remove leading $. if present
        if (str_starts_with($path, '$.')) {
            $path = substr($path, 2);
        } elseif (str_starts_with($path, '$')) {
            $path = substr($path, 1);
        }

        // Handle empty path (return entire data)
        if ($path === '' || $path === '.') {
            return $data;
        }

        // Parse path into segments
        $segments = $this->parsePath($path);

        return $this->extractFromSegments($data, $segments);
    }

    /**
     * Check if a JSON path expression is valid.
     */
    public function isValidPath(string $path): bool
    {
        $path = trim($path);

        // Must start with $ or be a simple path
        if (!str_starts_with($path, '$') && !str_starts_with($path, '.')) {
            // Allow simple paths like "temperature" or "sensors.value"
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z_][a-zA-Z0-9_]*|\[\d+\]|\[\*\])*$/', $path)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Parse a JSON path into segments.
     *
     * @return array<int, array{type: string, value: string|int}>
     */
    private function parsePath(string $path): array
    {
        $segments = [];
        $current = '';
        $length = strlen($path);
        $i = 0;

        while ($i < $length) {
            $char = $path[$i];

            if ($char === '.') {
                if ($current !== '') {
                    $segments[] = ['type' => 'key', 'value' => $current];
                    $current = '';
                }
                ++$i;
                continue;
            }

            if ($char === '[') {
                if ($current !== '') {
                    $segments[] = ['type' => 'key', 'value' => $current];
                    $current = '';
                }

                // Find closing bracket
                $closePos = strpos($path, ']', $i);
                if ($closePos === false) {
                    // Invalid path, return what we have
                    break;
                }

                $indexPart = substr($path, $i + 1, $closePos - $i - 1);

                if ($indexPart === '*') {
                    $segments[] = ['type' => 'wildcard_index', 'value' => '*'];
                } elseif (is_numeric($indexPart)) {
                    $segments[] = ['type' => 'index', 'value' => (int) $indexPart];
                }

                $i = $closePos + 1;
                continue;
            }

            if ($char === '*') {
                if ($current !== '') {
                    $segments[] = ['type' => 'key', 'value' => $current];
                    $current = '';
                }
                $segments[] = ['type' => 'wildcard', 'value' => '*'];
                ++$i;
                continue;
            }

            $current .= $char;
            ++$i;
        }

        if ($current !== '') {
            $segments[] = ['type' => 'key', 'value' => $current];
        }

        return $segments;
    }

    /**
     * Extract data using parsed segments.
     *
     * @param mixed $data
     * @param array<int, array{type: string, value: string|int}> $segments
     */
    private function extractFromSegments(mixed $data, array $segments): mixed
    {
        if (empty($segments)) {
            return $data;
        }

        $segment = array_shift($segments);
        $type = $segment['type'];
        $value = $segment['value'];

        switch ($type) {
            case 'key':
                if (!is_array($data)) {
                    return null;
                }
                if (!array_key_exists((string) $value, $data)) {
                    return null;
                }
                return $this->extractFromSegments($data[(string) $value], $segments);

            case 'index':
                if (!is_array($data) || !array_is_list($data)) {
                    return null;
                }
                $index = (int) $value;
                if (!isset($data[$index])) {
                    return null;
                }
                return $this->extractFromSegments($data[$index], $segments);

            case 'wildcard_index':
                if (!is_array($data) || !array_is_list($data)) {
                    return null;
                }
                $results = [];
                foreach ($data as $item) {
                    $extracted = $this->extractFromSegments($item, $segments);
                    if ($extracted !== null) {
                        $results[] = $extracted;
                    }
                }
                return empty($results) ? null : $results;

            case 'wildcard':
                if (!is_array($data)) {
                    return null;
                }
                $results = [];
                foreach ($data as $item) {
                    $extracted = $this->extractFromSegments($item, $segments);
                    if ($extracted !== null) {
                        $results[] = $extracted;
                    }
                }
                return empty($results) ? null : $results;

            default:
                return null;
        }
    }

    /**
     * Format extracted value for display.
     */
    public function formatValue(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_string($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        if (is_array($value)) {
            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '[]';
        }

        return (string) json_encode($value);
    }
}
