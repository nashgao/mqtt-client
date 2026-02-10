<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Utils;

use Nashgao\MQTT\Exception\InvalidConfigException;
use Nashgao\MQTT\Metrics\ValidationMetrics;
use Valitron\Validator;

/**
 * Enhanced ConfigValidator that can optionally integrate with popular validation libraries
 * while maintaining compatibility with the existing system.
 *
 * This implementation provides a bridge pattern that allows integration with:
 * - Respect/Validation (recommended)
 * - Vlucas/Valitron
 * - Rakit/Validation
 *
 * While maintaining our custom MQTT-specific validation logic.
 */
class ConfigValidator
{
    protected const array VALID_QOS_LEVELS = [0, 1, 2];

    protected const array VALID_RETAIN_HANDLING = [0, 1, 2];

    protected const int MAX_TOPIC_LENGTH = 65535; // MQTT spec limit

    protected const int MAX_CLIENT_ID_LENGTH = 23; // MQTT 3.1 spec limit

    protected const int MIN_KEEP_ALIVE = 0;

    protected const int MAX_KEEP_ALIVE = 65535;

    protected const int MIN_PORT = 1;

    protected const int MAX_PORT = 65535;

    private static ?ValidationMetrics $metrics = null;

    private static ?object $externalValidator = null;

    private static string $validatorType = 'built-in';

    public static function setMetrics(ValidationMetrics $metrics): void
    {
        self::$metrics = $metrics;
    }

    public static function getMetrics(): ?ValidationMetrics
    {
        return self::$metrics;
    }

    /**
     * Set an external validation library for enhanced validation.
     *
     * @param object $validator Instance of external validator (Respect\Validation, Valitron, etc.)
     * @param string $type type identifier: 'respect', 'valitron', 'rakit', etc
     */
    public static function setExternalValidator(object $validator, string $type): void
    {
        self::$externalValidator = $validator;
        self::$validatorType = $type;
    }

    /**
     * Enhanced connection config validation with external library support.
     *
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     * @throws InvalidConfigException
     */
    public static function validateConnectionConfig(array $config): array
    {
        $startTime = microtime(true);

        // First run our custom MQTT-specific validation
        $errors = self::validateMQTTConnectionConfig($config);

        // Then run external validation if available
        if (self::$externalValidator !== null) {
            $externalErrors = self::runExternalValidation($config, 'connection');
            $errors = array_merge($errors, $externalErrors);
        }

        $isValid = empty($errors);
        $errorMessage = $isValid ? '' : 'Enhanced validation failed: ' . implode(', ', $errors);

        // Record metrics with performance timing
        $duration = microtime(true) - $startTime;
        self::$metrics?->recordValidation('connection_config', $isValid, $errorMessage);

        if (! $isValid) {
            throw new InvalidConfigException($errorMessage);
        }

        return $config;
    }

    /**
     * Enhanced topic config validation.
     */
    public static function validateTopicConfig(array $config): array
    {
        $startTime = microtime(true);

        // Custom MQTT validation
        $errors = self::validateMQTTTopicConfig($config);

        // External validation
        if (self::$externalValidator !== null) {
            $externalErrors = self::runExternalValidation($config, 'topic');
            $errors = array_merge($errors, $externalErrors);
        }

        $isValid = empty($errors);
        $errorMessage = $isValid ? '' : 'Enhanced topic validation failed: ' . implode(', ', $errors);

        $duration = microtime(true) - $startTime;
        self::$metrics?->recordValidation('topic_config', $isValid, $errorMessage);

        if (! $isValid) {
            throw new InvalidConfigException($errorMessage);
        }

        return $config;
    }

    /**
     * Enhanced pool config validation.
     */
    public static function validatePoolConfig(array $config): array
    {
        $startTime = microtime(true);

        $errors = [];

        // Validate min_connections
        if (isset($config['min_connections'])) {
            if (! is_int($config['min_connections']) || $config['min_connections'] < 0) {
                $errors[] = 'Invalid min_connections: must be a non-negative integer';
            }
        }

        // Validate max_connections
        if (isset($config['max_connections'])) {
            if (! is_int($config['max_connections']) || $config['max_connections'] < 1) {
                $errors[] = 'Invalid max_connections: must be a positive integer';
            }
        }

        // Validate that max >= min
        if (isset($config['min_connections'], $config['max_connections'])) {
            if ($config['max_connections'] < $config['min_connections']) {
                $errors[] = 'max_connections must be >= min_connections';
            }
        }

        // External validation
        if (self::$externalValidator !== null) {
            $externalErrors = self::runExternalValidation($config, 'pool');
            $errors = array_merge($errors, $externalErrors);
        }

        $isValid = empty($errors);
        $errorMessage = $isValid ? '' : 'Pool configuration validation failed: ' . implode(', ', $errors);

        $duration = microtime(true) - $startTime;
        self::$metrics?->recordValidation('pool_config', $isValid, $errorMessage);

        if (! $isValid) {
            throw new InvalidConfigException($errorMessage);
        }

        return $config;
    }

    /**
     * Sanitize topic name for security.
     */
    public static function sanitizeTopicName(string $topic): string
    {
        // Remove null bytes and control characters except for valid MQTT wildcards
        $sanitized = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $topic);

        // Trim whitespace
        return trim($sanitized);
    }

    /**
     * Validate that a topic filter is properly formatted for MQTT.
     */
    public static function validateTopicFilter(string $topicFilter): bool
    {
        $isValid = true;
        $errorMessage = '';

        // Basic MQTT topic filter validation
        // + wildcard must be complete level
        if (str_contains($topicFilter, '+')) {
            $parts = explode('/', $topicFilter);
            foreach ($parts as $part) {
                if ($part !== '+' && strpos($part, '+') !== false) {
                    $isValid = false;
                    $errorMessage = '+ wildcard must be alone in topic level';
                    break;
                }
            }
        }

        // # wildcard must be at end and alone in level
        if ($isValid && str_contains($topicFilter, '#')) {
            $hashPos = strpos($topicFilter, '#');
            if ($hashPos !== strlen($topicFilter) - 1) {
                $isValid = false;
                $errorMessage = '# wildcard must be at the end of topic filter';
            } else {
                $beforeHash = substr($topicFilter, 0, $hashPos);
                if ($beforeHash !== '' && ! str_ends_with($beforeHash, '/')) {
                    $isValid = false;
                    $errorMessage = '# wildcard must be alone in topic level';
                }
            }
        }

        // Record validation metrics
        self::$metrics?->recordValidation('topic_filter', $isValid, $errorMessage);

        return $isValid;
    }

    /**
     * Get validation statistics including external validator performance.
     */
    public static function getValidationStats(): array
    {
        $baseStats = self::$metrics?->toArray() ?? [];

        return array_merge($baseStats, [
            'external_validator' => [
                'type' => self::$validatorType,
                'enabled' => self::$externalValidator !== null,
                'class' => self::$externalValidator ? get_class(self::$externalValidator) : null,
            ],
        ]);
    }

    /**
     * Factory methods to create enhanced validator with popular libraries.
     */
    public static function withRespectValidation(): self
    {
        if (class_exists('Respect\Validation\Validator')) {
            // Respect\Validation uses static methods, so we create a marker object
            self::setExternalValidator(new \stdClass(), 'respect');
        }
        return new self();
    }

    public static function withValitronValidation(): self
    {
        if (class_exists('Valitron\Validator')) {
            self::setExternalValidator(new Validator([]), 'valitron');
        }
        return new self();
    }

    public static function withRakitValidation(): self
    {
        if (class_exists('Rakit\Validation\Validator')) {
            self::setExternalValidator(new \Rakit\Validation\Validator(), 'rakit');
        }
        return new self();
    }

    public static function isValidQos($qos): bool
    {
        return is_int($qos) && in_array($qos, self::VALID_QOS_LEVELS, true);
    }

    public static function isValidRetainHandling($retainHandling): bool
    {
        return is_int($retainHandling) && in_array($retainHandling, self::VALID_RETAIN_HANDLING, true);
    }

    public static function isValidTopicName(string $topic): bool
    {
        return strlen($topic) <= self::MAX_TOPIC_LENGTH;
    }

    public static function isValidClientId(string $clientId): bool
    {
        return strlen($clientId) <= self::MAX_CLIENT_ID_LENGTH;
    }

    public static function isValidKeepAlive($keepAlive): bool
    {
        return is_int($keepAlive) && $keepAlive >= self::MIN_KEEP_ALIVE && $keepAlive <= self::MAX_KEEP_ALIVE;
    }

    public static function isValidHost(string $host): bool
    {
        // Check if it's a valid IP address or hostname
        return filter_var($host, FILTER_VALIDATE_IP) !== false
               || (filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false);
    }

    public static function isValidPort($port): bool
    {
        return is_int($port) && $port >= self::MIN_PORT && $port <= self::MAX_PORT;
    }

    /**
     * MQTT-specific connection validation (our existing logic).
     */
    private static function validateMQTTConnectionConfig(array $config): array
    {
        $errors = [];

        // Required fields
        $requiredFields = ['host', 'port'];
        foreach ($requiredFields as $field) {
            if (empty($config[$field])) {
                $errors[] = "Required field '{$field}' is missing or empty";
            }
        }

        // Host validation
        if (isset($config['host'])) {
            if (! self::isValidHost($config['host'])) {
                $errors[] = "Invalid host: {$config['host']}";
            }
        }

        // Port validation
        if (isset($config['port'])) {
            if (! self::isValidPort($config['port'])) {
                $errors[] = "Invalid port: {$config['port']}. Must be between " . self::MIN_PORT . ' and ' . self::MAX_PORT;
            }
        }

        // Client ID validation
        if (isset($config['client_id'])) {
            if (! self::isValidClientId($config['client_id'])) {
                $errors[] = 'Invalid client_id: length must be <= ' . self::MAX_CLIENT_ID_LENGTH . ' characters';
            }
        }

        // Keep alive validation
        if (isset($config['keep_alive'])) {
            if (! self::isValidKeepAlive($config['keep_alive'])) {
                $errors[] = 'Invalid keep_alive: must be between ' . self::MIN_KEEP_ALIVE . ' and ' . self::MAX_KEEP_ALIVE;
            }
        }

        return $errors;
    }

    /**
     * MQTT-specific topic validation.
     */
    private static function validateMQTTTopicConfig(array $config): array
    {
        $errors = [];

        // QoS validation
        if (isset($config['qos'])) {
            if (! self::isValidQos($config['qos'])) {
                $errors[] = "Invalid QoS level: {$config['qos']}. Must be 0, 1, or 2";
            }
        }

        // Retain handling validation
        if (isset($config['retain_handling'])) {
            if (! self::isValidRetainHandling($config['retain_handling'])) {
                $errors[] = "Invalid retain_handling: {$config['retain_handling']}. Must be 0, 1, or 2";
            }
        }

        // Topic name validation
        if (isset($config['topic'])) {
            if (! self::isValidTopicName($config['topic'])) {
                $errors[] = 'Invalid topic name: length exceeds ' . self::MAX_TOPIC_LENGTH . ' bytes';
            }
        }

        // Multisub number validation
        if (isset($config['multisub_num'])) {
            if (! is_int($config['multisub_num']) || $config['multisub_num'] < 1) {
                $errors[] = 'Invalid multisub_num: must be a positive integer';
            }
        }

        return $errors;
    }

    /**
     * Run external validation based on the configured validator type.
     */
    private static function runExternalValidation(array $config, string $configType): array
    {
        try {
            return match (self::$validatorType) {
                'respect' => self::validateWithRespect($config, $configType),
                'valitron' => self::validateWithValitron($config, $configType),
                'rakit' => self::validateWithRakit($config, $configType),
                default => [],
            };
        } catch (\Exception $e) {
            // If external validation fails, log it but don't break our validation
            return ["External validation error: {$e->getMessage()}"];
        }
    }

    /**
     * Validate using Respect/Validation library.
     */
    private static function validateWithRespect(array $config, string $configType): array
    {
        if (! class_exists('Respect\Validation\Validator')) {
            return ['Respect/Validation library not installed'];
        }

        $errors = [];
        $v = self::$externalValidator;

        if ($configType === 'connection') {
            // Enhanced host validation using Respect/Validation
            if (isset($config['host'])) {
                if (! $v::oneOf($v::ip(), $v::domain())->validate($config['host'])) {
                    $errors[] = 'Host must be a valid IP address or domain name (Respect validation)';
                }
            }

            // Enhanced port validation
            if (isset($config['port'])) {
                if (! $v::intVal()->between(1, 65535)->validate($config['port'])) {
                    $errors[] = 'Port must be an integer between 1 and 65535 (Respect validation)';
                }
            }

            // Enhanced client ID validation
            if (isset($config['client_id'])) {
                if (! $v::stringType()->length(1, 23)->alnum('-_')->validate($config['client_id'])) {
                    $errors[] = 'Client ID must be alphanumeric with dashes/underscores, 1-23 characters (Respect validation)';
                }
            }
        }

        return $errors;
    }

    /**
     * Validate using Valitron library.
     */
    private static function validateWithValitron(array $config, string $configType): array
    {
        if (! class_exists('Valitron\Validator')) {
            return ['Valitron library not installed'];
        }

        $errors = [];

        // Create Valitron validator instance
        $validator = new Validator($config);

        if ($configType === 'connection') {
            $validator->rule('required', ['host', 'port']);
            $validator->rule('ip', 'host')->message('Host must be a valid IP address');
            $validator->rule('integer', 'port');
            $validator->rule('min', 'port', 1);
            $validator->rule('max', 'port', 65535);

            if (isset($config['client_id'])) {
                $validator->rule('lengthMax', 'client_id', 23);
                $validator->rule('regex', 'client_id', '/^[a-zA-Z0-9_-]+$/');
            }
        }

        if (! $validator->validate()) {
            foreach ($validator->errors() as $field => $fieldErrors) {
                $errors = array_merge($errors, array_map(fn ($err) => "{$field}: {$err} (Valitron)", $fieldErrors));
            }
        }

        return $errors;
    }

    /**
     * Validate using Rakit/Validation library.
     */
    private static function validateWithRakit(array $config, string $configType): array
    {
        if (! class_exists('Rakit\Validation\Validator')) {
            return ['Rakit/Validation library not installed'];
        }

        $errors = [];
        $validator = new \Rakit\Validation\Validator();

        if ($configType === 'connection') {
            $rules = [
                'host' => 'required|ip',
                'port' => 'required|integer|min:1|max:65535',
            ];

            if (isset($config['client_id'])) {
                $rules['client_id'] = 'max:23|regex:/^[a-zA-Z0-9_-]+$/';
            }

            $validation = $validator->validate($config, $rules);

            if ($validation->fails()) {
                foreach ($validation->errors()->all() as $error) {
                    $errors[] = "{$error} (Rakit)";
                }
            }
        }

        return $errors;
    }
}
