<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Rule\Action;

use NashGao\InteractiveShell\Message\Message;

/**
 * Highlight action that marks messages for special display formatting.
 *
 * When a rule with this action matches, the message is registered in the
 * HighlightRegistry. The formatter checks this registry and applies
 * visual highlighting (background color) when displaying the message.
 */
final class HighlightAction implements ActionInterface
{
    /**
     * @param string $color Highlight color (yellow, green, red, blue, cyan, magenta, white)
     * @param string|null $reason Optional reason for highlighting (shown in verbose mode)
     */
    public function __construct(
        private readonly string $color = 'yellow',
        private readonly ?string $reason = null,
    ) {}

    /**
     * Register the message for highlighting.
     */
    public function execute(array $data, Message $originalMessage): void
    {
        HighlightRegistry::register($originalMessage, $this->color, $this->reason);
    }

    /**
     * Get the highlight color.
     */
    public function getColor(): string
    {
        return $this->color;
    }

    /**
     * Get the highlight reason.
     */
    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function getName(): string
    {
        $name = "highlight:{$this->color}";
        if ($this->reason !== null) {
            $name .= ":{$this->reason}";
        }
        return $name;
    }
}
