<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Utils;

use Nashgao\MQTT\Exception\InvalidConfigException;
use Nashgao\MQTT\Metrics\ValidationMetrics;

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
class EnhancedConfigValidator extends ConfigValidator
{
    private static ?object $externalValidator = null;
    private static string $validatorType = 'built-in';
    
    /**
     * Set an external validation library for enhanced validation.
     * 
     * @param object $validator Instance of external validator (Respect\Validation, Valitron, etc.)
     * @param string $type Type identifier: 'respect', 'valitron', 'rakit', etc.
     */
    public static function setExternalValidator(object $validator, string $type): void
    {
        self::$externalValidator = $validator;
        self::$validatorType = $type;
    }
    
    /**
     * Enhanced connection config validation with external library support.
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
        parent::getMetrics()?->recordValidation('connection_config_enhanced', $isValid, $errorMessage);
        
        if (!$isValid) {
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
        parent::getMetrics()?->recordValidation('topic_config_enhanced', $isValid, $errorMessage);
        
        if (!$isValid) {
            throw new InvalidConfigException($errorMessage);
        }
        
        return $config;
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
            if (!isset($config[$field]) || empty($config[$field])) {
                $errors[] = "Required field '{$field}' is missing or empty";
            }
        }
        
        // Host validation
        if (isset($config['host'])) {
            if (!self::isValidHost($config['host'])) {
                $errors[] = "Invalid host: {$config['host']}";
            }
        }
        
        // Port validation
        if (isset($config['port'])) {
            if (!self::isValidPort($config['port'])) {
                $errors[] = "Invalid port: {$config['port']}. Must be between " . self::MIN_PORT . ' and ' . self::MAX_PORT;
            }
        }
        
        // Client ID validation
        if (isset($config['client_id'])) {
            if (!self::isValidClientId($config['client_id'])) {
                $errors[] = 'Invalid client_id: length must be <= ' . self::MAX_CLIENT_ID_LENGTH . ' characters';
            }
        }
        
        // Keep alive validation
        if (isset($config['keep_alive'])) {
            if (!self::isValidKeepAlive($config['keep_alive'])) {
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
            if (!self::isValidQos($config['qos'])) {
                $errors[] = "Invalid QoS level: {$config['qos']}. Must be 0, 1, or 2";
            }
        }
        
        // Retain handling validation
        if (isset($config['retain_handling'])) {
            if (!self::isValidRetainHandling($config['retain_handling'])) {
                $errors[] = "Invalid retain_handling: {$config['retain_handling']}. Must be 0, 1, or 2";
            }
        }
        
        // Topic name validation
        if (isset($config['topic'])) {
            if (!self::isValidTopicName($config['topic'])) {
                $errors[] = 'Invalid topic name: length exceeds ' . self::MAX_TOPIC_LENGTH . ' bytes';
            }
        }
        
        // Multisub number validation
        if (isset($config['multisub_num'])) {
            if (!is_int($config['multisub_num']) || $config['multisub_num'] < 1) {
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
            switch (self::$validatorType) {
                case 'respect':
                    return self::validateWithRespect($config, $configType);
                case 'valitron':
                    return self::validateWithValitron($config, $configType);
                case 'rakit':
                    return self::validateWithRakit($config, $configType);
                default:
                    return []; // Unknown validator type, skip external validation
            }
        } catch (\Exception $e) {
            // If external validation fails, log it but don't break our validation
            return ["External validation error: {$e->getMessage()}"];
        }
    }
    
    /**
     * Validate using Respect/Validation library.
     * 
     * Example usage:
     * ```php
     * use Respect\Validation\Validator as v;
     * EnhancedConfigValidator::setExternalValidator(v::class, 'respect');
     * ```
     */
    private static function validateWithRespect(array $config, string $configType): array
    {
        if (!class_exists('Respect\\Validation\\Validator')) {
            return ['Respect/Validation library not installed'];
        }
        
        $errors = [];
        $v = self::$externalValidator;
        
        if ($configType === 'connection') {
            // Enhanced host validation using Respect/Validation
            if (isset($config['host'])) {
                if (!$v::oneOf($v::ip(), $v::domain())->validate($config['host'])) {
                    $errors[] = 'Host must be a valid IP address or domain name (Respect validation)';
                }
            }
            
            // Enhanced port validation
            if (isset($config['port'])) {
                if (!$v::intVal()->between(1, 65535)->validate($config['port'])) {
                    $errors[] = 'Port must be an integer between 1 and 65535 (Respect validation)';
                }
            }
            
            // Enhanced client ID validation
            if (isset($config['client_id'])) {
                if (!$v::stringType()->length(1, 23)->alnum('-_')->validate($config['client_id'])) {
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
        if (!class_exists('Valitron\\Validator')) {
            return ['Valitron library not installed'];
        }
        
        $errors = [];
        
        // Create Valitron validator instance
        $validator = new \Valitron\Validator($config);
        
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
        
        if (!$validator->validate()) {
            foreach ($validator->errors() as $field => $fieldErrors) {
                $errors = array_merge($errors, array_map(fn($err) => "$field: $err (Valitron)", $fieldErrors));
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate using Rakit/Validation library.
     */
    private static function validateWithRakit(array $config, string $configType): array
    {
        if (!class_exists('Rakit\\Validation\\Validator')) {
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
                    $errors[] = "$error (Rakit)";
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Get validation statistics including external validator performance.
     */
    public static function getValidationStats(): array
    {
        $baseStats = parent::getMetrics()?->toArray() ?? [];
        
        return array_merge($baseStats, [
            'external_validator' => [
                'type' => self::$validatorType,
                'enabled' => self::$externalValidator !== null,
                'class' => self::$externalValidator ? get_class(self::$externalValidator) : null,
            ],
        ]);
    }
    
    /**
     * Factory method to create enhanced validator with popular libraries.
     */
    public static function withRespectValidation(): self
    {
        if (class_exists('Respect\\Validation\\Validator')) {
            self::setExternalValidator(\Respect\Validation\Validator::class, 'respect');
        }
        return new self();
    }
    
    public static function withValitronValidation(): self
    {
        if (class_exists('Valitron\\Validator')) {
            self::setExternalValidator(new \Valitron\Validator([]), 'valitron');
        }
        return new self();
    }
    
    public static function withRakitValidation(): self
    {
        if (class_exists('Rakit\\Validation\\Validator')) {
            self::setExternalValidator(new \Rakit\Validation\Validator(), 'rakit');
        }
        return new self();
    }
}