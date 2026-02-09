<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Formatter;

use NashGao\InteractiveShell\Message\Message;
use Nashgao\MQTT\Shell\Config\ShellConfig;
use Nashgao\MQTT\Shell\Rule\Action\HighlightRegistry;

/**
 * MQTT-specific message formatter for debug shell output.
 *
 * Standalone formatter with MQTT-specific field extraction and formatting.
 */
final class MqttMessageFormatter
{
    // Format constants
    public const FORMAT_COMPACT = 'compact';
    public const FORMAT_TABLE = 'table';
    public const FORMAT_VERTICAL = 'vertical';
    public const FORMAT_JSON = 'json';
    public const FORMAT_HEXDUMP = 'hex';

    // Configuration properties
    private int $payloadTruncation = 100;
    private int $keyDisplayLength = 40;
    private bool $colorEnabled = true;
    private string $format = self::FORMAT_COMPACT;

    /** @var array<string, bool> Field filters for selective display */
    private array $fieldFilters = [];

    private ?int $displayMessageId = null;

    public function __construct(?ShellConfig $config = null)
    {
        $config ??= ShellConfig::default();
        $this->payloadTruncation = $config->payloadTruncation;
        $this->keyDisplayLength = $config->topicDisplayLength;
    }

    /**
     * Set a message ID to display as prefix in compact format.
     * null = no prefix.
     */
    public function setDisplayMessageId(?int $id): void
    {
        $this->displayMessageId = $id;
    }

    /**
     * Set the output format with alias normalization.
     */
    public function setFormat(string $format): void
    {
        $this->format = $this->normalizeFormat($format);
    }

    /**
     * Get the current output format.
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * Normalize format string, handling aliases and invalid values.
     */
    private function normalizeFormat(string $format): string
    {
        return match (strtolower($format)) {
            'c', 'compact' => self::FORMAT_COMPACT,
            't', 'table' => self::FORMAT_TABLE,
            'v', 'vertical' => self::FORMAT_VERTICAL,
            'j', 'json' => self::FORMAT_JSON,
            'h', 'hex', 'hexdump' => 'hex', // Tests expect 'hex' string
            default => self::FORMAT_COMPACT,
        };
    }

    /**
     * Enable or disable color output.
     */
    public function setColorEnabled(bool $enabled): void
    {
        $this->colorEnabled = $enabled;
    }

    /**
     * Colorize text with ANSI color codes.
     */
    private function colorize(string $text, string $color): string
    {
        if (!$this->colorEnabled) {
            return $text;
        }

        $colorCode = match ($color) {
            'black' => '30',
            'red' => '31',
            'green' => '32',
            'yellow' => '33',
            'blue' => '34',
            'magenta' => '35',
            'cyan' => '36',
            'white' => '37',
            'gray', 'grey' => '90',
            default => '0',
        };

        return "\033[{$colorCode}m{$text}\033[0m";
    }

    /**
     * Truncate text with ellipsis if it exceeds max length.
     */
    private function truncate(string $text, int $maxLength): string
    {
        if (mb_strlen($text) <= $maxLength) {
            return $text;
        }

        return mb_substr($text, 0, $maxLength - 3) . '...';
    }

    /**
     * Format bytes as hexadecimal dump.
     */
    private function formatHexBytes(string $data): string
    {
        $lines = [];
        $length = strlen($data);
        $bytesPerLine = 16;

        for ($offset = 0; $offset < $length; $offset += $bytesPerLine) {
            $chunk = substr($data, $offset, $bytesPerLine);
            $hexPart = '';
            $asciiPart = '';

            for ($i = 0; $i < $bytesPerLine; $i++) {
                if ($i < strlen($chunk)) {
                    $byte = ord($chunk[$i]);
                    $hexPart .= sprintf('%02x ', $byte);
                    $asciiPart .= ($byte >= 32 && $byte <= 126) ? $chunk[$i] : '.';
                } else {
                    $hexPart .= '   ';
                    $asciiPart .= ' ';
                }

                // Add spacing between 8-byte groups
                if ($i === 7) {
                    $hexPart .= ' ';
                }
            }

            $lines[] = sprintf('%08x  %s |%s|', $offset, $hexPart, $asciiPart);
        }

        return implode("\n", $lines);
    }

    /**
     * Pretty print JSON with proper formatting.
     */
    private function formatJsonWithDepthAndSchema(mixed $data): string
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return $json ?: '{}';
    }

    /**
     * Apply field filters to data array.
     * If filters are set, only show filtered fields.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function applyFieldFilters(array $data): array
    {
        if (empty($this->fieldFilters)) {
            return $data;
        }

        $filtered = [];
        foreach ($data as $key => $value) {
            if (isset($this->fieldFilters[$key])) {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    /**
     * Format a message for display with MQTT-specific highlighting.
     * Dispatches to appropriate format method based on current format setting.
     */
    public function format(Message $message): string
    {
        // Dispatch to appropriate format method
        $output = match ($this->format) {
            self::FORMAT_TABLE => $this->formatTable($message),
            self::FORMAT_VERTICAL => $this->formatVertical($message),
            self::FORMAT_JSON => $this->formatJson($message),
            self::FORMAT_HEXDUMP => $this->formatHexDump($message),
            default => $this->formatCompact($message),
        };

        // Apply highlighting if message is in the highlight registry
        $highlightInfo = HighlightRegistry::getHighlightInfo($message);
        if ($highlightInfo !== null && $this->colorEnabled) {
            $output = $this->applyHighlight($output, $highlightInfo->color);
        }

        return $output;
    }

    /**
     * Format a table header.
     */
    public function formatTableHeader(): string
    {
        $topicLen = $this->keyDisplayLength;
        $header = sprintf(
            '| %-8s | %-3s | %s | %-' . $topicLen . 's | %s',
            'TIME',
            'DIR',
            'Q',
            'TOPIC',
            'PAYLOAD'
        );
        $separator = '+' . str_repeat('-', 10) . '+' . str_repeat('-', 5) . '+---+'
            . str_repeat('-', $topicLen + 2) . '+' . str_repeat('-', 50);

        return $separator . "\n" . $header . "\n" . $separator;
    }

    // ─── Protocol-specific Format Implementations ────────────────────────

    /**
     * Compact single-line format.
     *
     * Example: [12:34:56.789] IN  sensors/room1/temp {"value": 23.5}
     * With ID: [#42] [12:34:56.789] IN  sensors/room1/temp {"value": 23.5}
     */
    private function formatCompact(Message $message): string
    {
        $time = $message->timestamp->format('H:i:s.v');
        $direction = $this->extractDirection($message);
        $dirLabel = $this->formatDirection($direction);
        $topic = $this->extractTopic($message);
        $payload = $this->formatMqttPayloadCompact($message->payload);
        $type = $message->type;

        // ID prefix if set
        $idPrefix = '';
        if ($this->displayMessageId !== null) {
            $idPrefix = $this->colorize("[#{$this->displayMessageId}] ", 'gray');
        }

        // System messages
        if ($type === 'system') {
            $text = is_string($message->payload) ? $message->payload : json_encode($message->payload);
            return $idPrefix
                . $this->colorize("[{$time}] ", 'gray')
                . $this->colorize('SYS ', 'cyan')
                . $text;
        }

        // Subscribe events
        if ($type === 'subscribe') {
            $topics = $this->extractSubscribeTopics($message);
            return $idPrefix
                . $this->colorize("[{$time}] ", 'gray')
                . $this->colorize('SUB ', 'magenta')
                . implode(', ', $topics);
        }

        // Disconnect events
        if ($type === 'disconnect') {
            $code = is_array($message->payload) ? (string) ($message->payload['code'] ?? 'unknown') : 'unknown';
            return $idPrefix
                . $this->colorize("[{$time}] ", 'gray')
                . $this->colorize('DIS ', 'red')
                . "code={$code}";
        }

        // Publish messages
        return $idPrefix
            . $this->colorize("[{$time}] ", 'gray')
            . $dirLabel
            . $this->colorize($topic, 'yellow')
            . ' '
            . $payload;
    }

    /**
     * Table row format.
     */
    private function formatTable(Message $message): string
    {
        $time = $message->timestamp->format('H:i:s');
        $direction = $this->extractDirection($message);
        $topic = $this->extractTopic($message);
        $qos = $this->extractQos($message);
        $payload = $this->formatMqttPayloadCompact($message->payload, 50);
        $topicLen = $this->keyDisplayLength;

        return sprintf(
            '| %-8s | %-3s | %d | %-' . $topicLen . 's | %s',
            $time,
            strtoupper(substr($direction, 0, 3)),
            $qos,
            $this->truncate($topic, $topicLen),
            $payload
        );
    }

    /**
     * Vertical detailed format (like MySQL \G).
     */
    private function formatVertical(Message $message): string
    {
        $lines = [];
        $lines[] = str_repeat('*', 27) . ' Message ' . str_repeat('*', 27);

        $direction = $this->extractDirection($message);
        $topic = $this->extractTopic($message);
        $qos = $this->extractQos($message);
        $pool = $this->extractPool($message);

        $lines[] = sprintf('%15s: %s', 'type', $message->type);
        $lines[] = sprintf('%15s: %s', 'direction', $direction);
        $lines[] = sprintf('%15s: %s', 'topic', $topic);
        $lines[] = sprintf('%15s: %d', 'qos', $qos);
        $lines[] = sprintf('%15s: %s', 'pool', $pool);
        $lines[] = sprintf('%15s: %s', 'timestamp', $message->timestamp->format(\DateTimeInterface::ATOM));

        // Metadata
        if (!empty($message->metadata)) {
            $retain = (bool) ($message->metadata['retain'] ?? false);
            $dup = (bool) ($message->metadata['dup'] ?? false);
            $messageId = $message->metadata['message_id'] ?? null;

            $lines[] = sprintf('%15s: %s', 'retain', $retain ? 'true' : 'false');
            $lines[] = sprintf('%15s: %s', 'dup', $dup ? 'true' : 'false');
            if ($messageId !== null && is_scalar($messageId)) {
                $lines[] = sprintf('%15s: %s', 'message_id', (string) $messageId);
            }
        }

        // Payload
        $lines[] = sprintf('%15s:', 'payload');
        $payloadStr = $this->formatMqttPayloadPretty($message->payload);
        foreach (explode("\n", $payloadStr) as $payloadLine) {
            $lines[] = '                  ' . $payloadLine;
        }

        $lines[] = str_repeat('*', 63);

        return implode("\n", $lines);
    }

    /**
     * JSON format for export.
     */
    private function formatJson(Message $message): string
    {
        $data = [
            'type' => $message->type,
            'direction' => $this->extractDirection($message),
            'topic' => $this->extractTopic($message),
            'qos' => $this->extractQos($message),
            'pool' => $this->extractPool($message),
            'timestamp' => $message->timestamp->format(\DateTimeInterface::ATOM),
            'payload' => $message->payload,
            'metadata' => $message->metadata,
        ];

        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
    }

    /**
     * Override hex dump to include MQTT-specific fields.
     */
    private function formatHexDump(Message $message): string
    {
        $lines = [];
        $lines[] = str_repeat('=', 80);

        $time = $message->timestamp->format(\DateTimeInterface::ATOM);
        $direction = $this->extractDirection($message);
        $topic = $this->extractTopic($message);

        $lines[] = sprintf('Timestamp: %s', $time);
        $lines[] = sprintf('Direction: %s', $direction);
        $lines[] = sprintf('Topic:     %s', $topic);
        $lines[] = str_repeat('-', 80);
        $lines[] = '';

        $payload = $this->extractRawPayload($message);
        $lines[] = $this->formatHexBytes($payload);

        $lines[] = str_repeat('=', 80);

        return implode("\n", $lines);
    }

    /**
     * Extract raw payload as string from Message.
     */
    private function extractRawPayload(Message $message): string
    {
        if (is_string($message->payload)) {
            return $message->payload;
        }

        if (is_array($message->payload)) {
            // Extract the actual message content from MQTT payload structure
            $content = $message->payload['message'] ?? $message->payload;

            if (is_string($content)) {
                return $content;
            }

            // Return JSON representation for non-string content
            return json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
        }

        return (string) json_encode($message->payload);
    }

    // ─── MQTT-specific Field Extraction ──────────────────────────────────

    private function extractDirection(Message $message): string
    {
        $direction = $message->metadata['direction'] ?? 'unknown';
        return is_string($direction) ? $direction : 'unknown';
    }

    private function extractTopic(Message $message): string
    {
        if (is_array($message->payload) && isset($message->payload['topic'])) {
            return is_string($message->payload['topic']) ? $message->payload['topic'] : '';
        }
        return '';
    }

    private function extractQos(Message $message): int
    {
        if (is_array($message->payload) && isset($message->payload['qos'])) {
            $qos = $message->payload['qos'];
            return is_numeric($qos) ? (int) $qos : 0;
        }
        if (isset($message->metadata['qos'])) {
            $qos = $message->metadata['qos'];
            return is_numeric($qos) ? (int) $qos : 0;
        }
        return 0;
    }

    private function extractPool(Message $message): string
    {
        if (is_array($message->payload) && isset($message->payload['pool'])) {
            return is_string($message->payload['pool']) ? $message->payload['pool'] : 'default';
        }
        return 'default';
    }

    /**
     * Extract topics from subscribe event.
     *
     * @return array<string>
     */
    private function extractSubscribeTopics(Message $message): array
    {
        if (is_array($message->payload) && isset($message->payload['topics'])) {
            $topics = $message->payload['topics'];
            return is_array($topics) ? array_map('strval', $topics) : [];
        }
        return [];
    }

    private function formatDirection(string $direction): string
    {
        $normalized = match ($direction) {
            'incoming', 'in' => 'IN',
            'outgoing', 'out' => 'OUT',
            default => '???',
        };

        $color = $normalized === 'IN' ? 'green' : 'blue';
        return $this->colorize(sprintf('%-3s ', $normalized), $color);
    }

    /**
     * Apply highlight styling to output.
     */
    private function applyHighlight(string $output, string $color): string
    {
        // For vertical format, add a highlight marker
        if ($this->format === self::FORMAT_VERTICAL) {
            $marker = $this->colorize("▶ HIGHLIGHTED ({$color})", $color);
            return $marker . "\n" . $output;
        }

        // For other formats, wrap with background color
        $colorCode = match ($color) {
            'red' => '41',
            'green' => '42',
            'yellow' => '43',
            'blue' => '44',
            'magenta' => '45',
            'cyan' => '46',
            'white' => '47',
            default => '43',
        };

        return "\033[{$colorCode}m{$output}\033[0m";
    }

    // ─── MQTT-specific Payload Formatting ────────────────────────────────

    /**
     * Format MQTT payload for compact display.
     * Extracts 'message' field from MQTT payload structure.
     */
    private function formatMqttPayloadCompact(mixed $payload, ?int $maxLength = null): string
    {
        $maxLength ??= $this->payloadTruncation;

        if (is_array($payload)) {
            // Extract the actual message content from MQTT payload structure
            $message = $payload['message'] ?? $payload;
            if (is_string($message)) {
                return $this->truncate($message, $maxLength);
            }
            $json = json_encode($message, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            return $this->truncate($json ?: '{}', $maxLength);
        }

        if (is_string($payload)) {
            return $this->truncate($payload, $maxLength);
        }

        return $this->truncate((string) json_encode($payload), $maxLength);
    }

    /**
     * Format MQTT payload for pretty display.
     * Extracts 'message' field from MQTT payload structure.
     */
    private function formatMqttPayloadPretty(mixed $payload): string
    {
        if (is_array($payload)) {
            $message = $payload['message'] ?? $payload;

            // Try to parse JSON string
            if (is_string($message)) {
                $decoded = json_decode($message, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $decoded = $this->applyFieldFilters($decoded);
                    return $this->formatJsonWithDepthAndSchema($decoded);
                }
                return $message;
            }

            if (is_array($message)) {
                $message = $this->applyFieldFilters($message);
            }
            return $this->formatJsonWithDepthAndSchema($message);
        }

        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $decoded = $this->applyFieldFilters($decoded);
                return $this->formatJsonWithDepthAndSchema($decoded);
            }
            return $payload;
        }

        return $this->formatJsonWithDepthAndSchema($payload);
    }
}
