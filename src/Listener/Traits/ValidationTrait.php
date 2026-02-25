<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Listener\Traits;

trait ValidationTrait
{
    protected function getValidatedPoolName(mixed $event): string
    {
        if (! is_object($event)) {
            throw new \InvalidArgumentException('Event must be an object');
        }

        $poolName = null;

        // Check for different property names
        if (property_exists($event, 'poolName')) {
            $poolName = $event->poolName;
        } elseif (property_exists($event, 'pool_name')) {
            $poolName = $event->pool_name;
        }

        if (empty($poolName)) {
            if (property_exists($this, 'logger')) {
                /** @var mixed $logger */
                $logger = $this->logger;
                if ($logger !== null) {
                    $logger->warning('Invalid pool name, using default', [
                        'provided_pool' => $poolName,
                    ]);
                }
            }
            return 'default';
        }

        return $poolName;
    }

    protected function recordValidationMetrics(string $operation, bool $success, string $details = ''): void
    {
        if (property_exists($this, 'validationMetrics')) {
            /** @var mixed $metrics */
            $metrics = $this->validationMetrics;
            if ($metrics !== null) {
                $metrics->recordValidation($operation, $success, $details);
            }
        }
    }

    protected function handleValidationError(string $operation, \Exception $e): void
    {
        $this->recordValidationMetrics($operation, false, "Failed validation: {$e->getMessage()}");

        if (property_exists($this, 'logger')) {
            /** @var mixed $logger */
            $logger = $this->logger;
            if ($logger !== null) {
                $logger->error("Validation error in {$operation}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        throw $e;
    }
}
