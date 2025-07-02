<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Metrics;

class ValidationMetrics
{
    private array $validationCounts = [];

    private array $validationErrors = [];

    private int $totalValidations = 0;

    private int $totalErrors = 0;

    public function recordValidation(string $type, bool $isValid, string $errorMessage = ''): self
    {
        if (! isset($this->validationCounts[$type])) {
            $this->validationCounts[$type] = [
                'total' => 0,
                'successful' => 0,
                'failed' => 0,
            ];
        }

        ++$this->validationCounts[$type]['total'];
        ++$this->totalValidations;

        if ($isValid) {
            ++$this->validationCounts[$type]['successful'];
        } else {
            ++$this->validationCounts[$type]['failed'];
            ++$this->totalErrors;

            if (! isset($this->validationErrors[$type])) {
                $this->validationErrors[$type] = [];
            }

            $this->validationErrors[$type][] = [
                'message' => $errorMessage,
                'timestamp' => microtime(true),
            ];

            if (count($this->validationErrors[$type]) > 50) {
                array_shift($this->validationErrors[$type]);
            }
        }
        return $this;
    }

    public function getValidationCount(string $type): array
    {
        return $this->validationCounts[$type] ?? [
            'total' => 0,
            'successful' => 0,
            'failed' => 0,
        ];
    }

    public function getValidationSuccessRate(string $type): float
    {
        $counts = $this->getValidationCount($type);
        if ($counts['total'] === 0) {
            return 1.0;
        }
        return $counts['successful'] / $counts['total'];
    }

    public function getOverallSuccessRate(): float
    {
        if ($this->totalValidations === 0) {
            return 1.0;
        }
        return ($this->totalValidations - $this->totalErrors) / $this->totalValidations;
    }

    public function getRecentErrors(string $type, int $limit = 10): array
    {
        if (! isset($this->validationErrors[$type])) {
            return [];
        }

        return array_slice($this->validationErrors[$type], -$limit);
    }

    public function getMostFailedValidationType(): ?string
    {
        if (empty($this->validationCounts)) {
            return null;
        }

        $maxFailures = 0;
        $mostFailedType = null;

        foreach ($this->validationCounts as $type => $counts) {
            if ($counts['failed'] > $maxFailures) {
                $maxFailures = $counts['failed'];
                $mostFailedType = $type;
            }
        }

        return $mostFailedType;
    }

    public function getTotalValidations(): int
    {
        return $this->totalValidations;
    }

    public function getTotalErrors(): int
    {
        return $this->totalErrors;
    }

    public function toArray(): array
    {
        return [
            'total_validations' => $this->totalValidations,
            'total_errors' => $this->totalErrors,
            'overall_success_rate' => $this->getOverallSuccessRate(),
            'validation_counts' => $this->validationCounts,
            'most_failed_type' => $this->getMostFailedValidationType(),
            'validation_success_rates' => array_map(
                fn ($type) => $this->getValidationSuccessRate($type),
                array_keys($this->validationCounts)
            ),
        ];
    }

    public function reset(): self
    {
        $this->validationCounts = [];
        $this->validationErrors = [];
        $this->totalValidations = 0;
        $this->totalErrors = 0;
        return $this;
    }
}
