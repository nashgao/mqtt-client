<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Rule\Action;

/**
 * Information about a message highlight.
 */
final readonly class HighlightInfo
{
    public function __construct(
        public string $color,
        public ?string $reason = null,
    ) {}
}
