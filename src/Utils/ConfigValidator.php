<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Utils;

use Nashgao\MQTT\Exception\InvalidConfigException;
use Nashgao\MQTT\Metrics\ValidationMetrics;

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
    
    public static function setMetrics(ValidationMetrics $metrics): void
    {
        self::$metrics = $metrics;
    }
    
    public static function getMetrics(): ?ValidationMetrics
    {
        return self::$metrics;
    }

    /**
     * Validate MQTT connection configuration.
     *
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     * @throws InvalidConfigException
     */
    public static function validateConnectionConfig(array $config): array
    {
        $errors = [];

        // Validate required fields
        $requiredFields = ['host', 'port'];
        foreach ($requiredFields as $field) {
            if (! isset($config[$field]) || empty($config[$field])) {
                $errors[] = "Required field '{$field}' is missing or empty";
            }
        }

        // Validate host
        if (isset($config['host'])) {
            if (! self::isValidHost($config['host'])) {
                $errors[] = "Invalid host: {$config['host']}";
            }
        }

        // Validate port
        if (isset($config['port'])) {
            if (! self::isValidPort($config['port'])) {
                $errors[] = "Invalid port: {$config['port']}. Must be between " . self::MIN_PORT . ' and ' . self::MAX_PORT;
            }
        }

        // Validate client_id if provided
        if (isset($config['client_id'])) {
            if (! self::isValidClientId($config['client_id'])) {
                $errors[] = 'Invalid client_id: length must be <= ' . self::MAX_CLIENT_ID_LENGTH . ' characters';
            }
        }

        // Validate keep_alive if provided
        if (isset($config['keep_alive'])) {
            if (! self::isValidKeepAlive($config['keep_alive'])) {
                $errors[] = 'Invalid keep_alive: must be between ' . self::MIN_KEEP_ALIVE . ' and ' . self::MAX_KEEP_ALIVE;
            }
        }

        $isValid = empty($errors);
        $errorMessage = $isValid ? '' : 'Configuration validation failed: ' . implode(', ', $errors);
        
        // Record validation metrics
        self::$metrics?->recordValidation('connection_config', $isValid, $errorMessage);

        if (!$isValid) {
            throw new InvalidConfigException($errorMessage);
        }

        return $config;
    }

    /**
     * Validate topic configuration.
     */
    public static function validateTopicConfig(array $config): array
    {
        $errors = [];

        // Validate QoS
        if (isset($config['qos'])) {
            if (! self::isValidQos($config['qos'])) {
                $errors[] = "Invalid QoS level: {$config['qos']}. Must be 0, 1, or 2";
            }
        }

        // Validate retain_handling
        if (isset($config['retain_handling'])) {
            if (! self::isValidRetainHandling($config['retain_handling'])) {
                $errors[] = "Invalid retain_handling: {$config['retain_handling']}. Must be 0, 1, or 2";
            }
        }

        // Validate topic name if provided
        if (isset($config['topic'])) {
            if (! self::isValidTopicName($config['topic'])) {
                $errors[] = 'Invalid topic name: length exceeds ' . self::MAX_TOPIC_LENGTH . ' bytes';
            }
        }

        // Validate multisub_num
        if (isset($config['multisub_num'])) {
            if (! is_int($config['multisub_num']) || $config['multisub_num'] < 1) {
                $errors[] = 'Invalid multisub_num: must be a positive integer';
            }
        }

        $isValid = empty($errors);
        $errorMessage = $isValid ? '' : 'Topic configuration validation failed: ' . implode(', ', $errors);
        
        // Record validation metrics
        self::$metrics?->recordValidation('topic_config', $isValid, $errorMessage);

        if (!$isValid) {
            throw new InvalidConfigException($errorMessage);
        }

        return $config;
    }

    /**
     * Validate pool configuration.
     */
    public static function validatePoolConfig(array $config): array
    {
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

        $isValid = empty($errors);
        $errorMessage = $isValid ? '' : 'Pool configuration validation failed: ' . implode(', ', $errors);
        
        // Record validation metrics
        self::$metrics?->recordValidation('pool_config', $isValid, $errorMessage);

        if (!$isValid) {
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
        if (strpos($topicFilter, '+') !== false) {
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
        if ($isValid && strpos($topicFilter, '#') !== false) {
            $hashPos = strpos($topicFilter, '#');
            if ($hashPos !== strlen($topicFilter) - 1) {
                $isValid = false;
                $errorMessage = '# wildcard must be at the end of topic filter';
            } else {
                $beforeHash = substr($topicFilter, 0, $hashPos);
                if ($beforeHash !== '' && substr($beforeHash, -1) !== '/') {
                    $isValid = false;
                    $errorMessage = '# wildcard must be alone in topic level';
                }
            }
        }
        
        // Record validation metrics
        self::$metrics?->recordValidation('topic_filter', $isValid, $errorMessage);

        return $isValid;
    }

    protected static function isValidQos($qos): bool
    {
        return is_int($qos) && in_array($qos, self::VALID_QOS_LEVELS, true);
    }

    protected static function isValidRetainHandling($retainHandling): bool
    {
        return is_int($retainHandling) && in_array($retainHandling, self::VALID_RETAIN_HANDLING, true);
    }

    protected static function isValidTopicName(string $topic): bool
    {
        return strlen($topic) <= self::MAX_TOPIC_LENGTH;
    }

    protected static function isValidClientId(string $clientId): bool
    {
        return strlen($clientId) <= self::MAX_CLIENT_ID_LENGTH;
    }

    protected static function isValidKeepAlive($keepAlive): bool
    {
        return is_int($keepAlive) && $keepAlive >= self::MIN_KEEP_ALIVE && $keepAlive <= self::MAX_KEEP_ALIVE;
    }

    protected static function isValidHost(string $host): bool
    {
        // Check if it's a valid IP address or hostname
        return filter_var($host, FILTER_VALIDATE_IP) !== false
               || (filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false);
    }

    protected static function isValidPort($port): bool
    {
        return is_int($port) && $port >= self::MIN_PORT && $port <= self::MAX_PORT;
    }
}
