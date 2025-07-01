<?php

declare(strict_types=1);

namespace Nashgao\MQTT\Config;

use Nashgao\MQTT\Utils\ConfigValidator;

class PoolConfig
{
    public string $name;
    public string $host;
    public int $port;
    public string $username = '';
    public string $password = '';
    public string $prefix = '';
    public int $keepalive = 60;
    public int $maxAttempts = 3;
    public int $protocolLevel = 5;
    public array $properties = [];
    public bool $cleanSession = true;
    public array $will = [];
    public array $swooleConfig = [];
    
    // Pool-specific settings
    public int $minConnections = 1;
    public int $maxConnections = 10;
    public int $connectTimeout = 10;
    public int $waitTimeout = 3;
    public int $heartbeat = -1;
    public int $maxIdleTime = 60;
    
    // Subscription configuration
    public TopicSubscriptionConfig $subscriptionConfig;
    
    // Publishing configuration  
    public TopicPublishConfig $publishConfig;

    public function __construct(string $name, array $config = [])
    {
        $this->name = $name;
        $this->subscriptionConfig = new TopicSubscriptionConfig();
        $this->publishConfig = new TopicPublishConfig();
        
        // Validate the configuration first
        $validatedConfig = ConfigValidator::validateConnectionConfig($config);
        
        // Set properties from validated config
        foreach ($validatedConfig as $key => $value) {
            $propertyName = match($key) {
                'keep_alive' => 'keepalive',
                'clean_session' => 'cleanSession',
                'max_attempts' => 'maxAttempts',
                'protocol_level' => 'protocolLevel',
                'swoole_config' => 'swooleConfig',
                default => $key
            };
            if (property_exists($this, $propertyName)) {
                $this->{$propertyName} = $value;
            }
        }
        
        // Handle nested configurations
        if (isset($config['pool'])) {
            $this->setPoolSettings($config['pool']);
        }
        
        if (isset($config['subscribe'])) {
            $this->subscriptionConfig = new TopicSubscriptionConfig($config['subscribe']);
        }
        
        if (isset($config['publish'])) {
            $this->publishConfig = new TopicPublishConfig($config['publish']);
        }
    }
    
    private function setPoolSettings(array $poolSettings): void
    {
        $validatedPool = ConfigValidator::validatePoolConfig($poolSettings);
        
        foreach ($validatedPool as $key => $value) {
            $camelKey = $this->toCamelCase($key);
            if (property_exists($this, $camelKey)) {
                $this->{$camelKey} = $value;
            }
        }
    }
    
    private function toCamelCase(string $string): string
    {
        return lcfirst(str_replace('_', '', ucwords($string, '_')));
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function getConnectionConfig(): array
    {
        return [
            'host' => $this->host,
            'port' => $this->port,
            'username' => $this->username,
            'password' => $this->password,
            'prefix' => $this->prefix,
            'keepalive' => $this->keepalive,
            'max_attempts' => $this->maxAttempts,
            'protocol_level' => $this->protocolLevel,
            'properties' => $this->properties,
            'clean_session' => $this->cleanSession,
            'will' => $this->will,
            'swoole_config' => $this->swooleConfig,
        ];
    }
    
    public function getPoolSettings(): array
    {
        return [
            'min_connections' => $this->minConnections,
            'max_connections' => $this->maxConnections,
            'connect_timeout' => $this->connectTimeout,
            'wait_timeout' => $this->waitTimeout,
            'heartbeat' => $this->heartbeat,
            'max_idle_time' => $this->maxIdleTime,
        ];
    }
    
    public function getSubscriptionConfig(): TopicSubscriptionConfig
    {
        return $this->subscriptionConfig;
    }
    
    public function getPublishConfig(): TopicPublishConfig
    {
        return $this->publishConfig;
    }
    
    public function hasSubscriptions(): bool
    {
        return $this->subscriptionConfig->hasAutoSubscriptions();
    }
    
    public function getAutoSubscribeTopics(): array
    {
        return $this->subscriptionConfig->getAutoSubscribeTopics();
    }
    
    public function setHost(string $host): self
    {
        $this->host = $host;
        return $this;
    }
    
    public function setPort(int $port): self
    {
        $this->port = $port;
        return $this;
    }
    
    public function setCredentials(string $username, string $password): self
    {
        $this->username = $username;
        $this->password = $password;
        return $this;
    }
    
    public function setPoolLimits(int $min, int $max): self
    {
        $this->minConnections = $min;
        $this->maxConnections = $max;
        return $this;
    }
    
    public function addSubscription(TopicSubscription $subscription): self
    {
        $this->subscriptionConfig->addTopic($subscription);
        return $this;
    }
    
    public function addPublishTopic(TopicPublish $publish): self
    {
        $this->publishConfig->addTopic($publish);
        return $this;
    }
    
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'connection' => $this->getConnectionConfig(),
            'pool' => $this->getPoolSettings(),
            'subscribe' => $this->subscriptionConfig->toArray(),
            'publish' => $this->publishConfig->toArray(),
        ];
    }
    
    public function validate(): bool
    {
        try {
            ConfigValidator::validateConnectionConfig($this->getConnectionConfig());
            ConfigValidator::validatePoolConfig($this->getPoolSettings());
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function isValid(): bool
    {
        return $this->validate();
    }
}