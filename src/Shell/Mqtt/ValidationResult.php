<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Shell\Mqtt;

/**
 * Result of MQTT topic pattern validation.
 */
final readonly class ValidationResult
{
    private function __construct(
        public bool $valid,
        public ?string $error = null,
    ) {}

    public static function valid(): self
    {
        return new self(true);
    }

    public static function invalid(string $error): self
    {
        return new self(false, $error);
    }

    public function isValid(): bool
    {
        return $this->valid;
    }
}
