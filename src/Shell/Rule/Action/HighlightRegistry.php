<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Rule\Action;

use NashGao\InteractiveShell\Message\Message;
use WeakMap;

/**
 * Registry for tracking highlighted messages.
 *
 * Uses WeakMap to automatically clean up when messages are garbage collected.
 * The formatter checks this registry to apply highlight styling.
 */
final class HighlightRegistry
{
    /** @var WeakMap<Message, HighlightInfo> */
    private static ?WeakMap $highlights = null;

    /**
     * Get the singleton WeakMap instance.
     *
     * @return WeakMap<Message, HighlightInfo>
     */
    private static function getHighlights(): WeakMap
    {
        if (self::$highlights === null) {
            /** @var WeakMap<Message, HighlightInfo> $map */
            $map = new WeakMap();
            self::$highlights = $map;
        }
        return self::$highlights;
    }

    /**
     * Register a message for highlighting.
     *
     * @param Message $message The message to highlight
     * @param string $color The highlight color (e.g., 'yellow', 'red', 'green')
     * @param string|null $reason Optional reason for the highlight
     */
    public static function register(Message $message, string $color = 'yellow', ?string $reason = null): void
    {
        self::getHighlights()[$message] = new HighlightInfo($color, $reason);
    }

    /**
     * Check if a message is highlighted.
     *
     * @param Message $message The message to check
     * @return bool True if the message is highlighted
     */
    public static function isHighlighted(Message $message): bool
    {
        return isset(self::getHighlights()[$message]);
    }

    /**
     * Get highlight info for a message.
     *
     * @param Message $message The message to get info for
     * @return HighlightInfo|null Highlight info or null if not highlighted
     */
    public static function getHighlightInfo(Message $message): ?HighlightInfo
    {
        return self::getHighlights()[$message] ?? null;
    }

    /**
     * Clear a specific highlight.
     *
     * @param Message $message The message to clear highlight from
     */
    public static function clear(Message $message): void
    {
        unset(self::getHighlights()[$message]);
    }

    /**
     * Clear all highlights.
     */
    public static function clearAll(): void
    {
        self::$highlights = null;
    }

    /**
     * Get the count of currently highlighted messages.
     */
    public static function count(): int
    {
        return count(self::getHighlights());
    }
}
