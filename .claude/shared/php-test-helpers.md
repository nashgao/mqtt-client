# PHP Test Helpers and Utilities

## 🚨 MANDATORY: Rule Enforcement Integration

**This shared resource operates under the Rule Enforcement Framework**
**Reference: `/templates/agents/_shared/rule-enforcement-framework.md`**

**ALL USERS OF THIS RESOURCE MUST:**
- ✅ Validate scope before any file modifications
- ✅ Respect unit/integration test separation
- ✅ Execute verification commands before claiming success
- ✅ Never make architectural decisions beyond assigned scope

**VIOLATION CONSEQUENCES:** Immediate halt and escalation to user

---

## Overview

Collection of helper classes and utilities for PHP testing with hyperf/testing, including mock factories, data generators, and testing utilities.

## Mock Factory Helper

```php
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Test\Helpers;

use Mockery;
use Mockery\MockInterface;

/**
 * Mock factory for creating various mock objects
 */
class MockFactory
{
    /**
     * Create HTTP client mock
     */
    public static function httpClient(): MockInterface
    {
        return Mockery::mock(\Hyperf\Guzzle\ClientInterface::class);
    }

    /**
     * Create database connection mock
     */
    public static function databaseConnection(): MockInterface
    {
        return Mockery::mock(\Hyperf\Database\ConnectionInterface::class);
    }

    /**
     * Create logger mock
     */
    public static function logger(): MockInterface
    {
        return Mockery::mock(\Psr\Log\LoggerInterface::class);
    }

    /**
     * Create cache mock
     */
    public static function cache(): MockInterface
    {
        return Mockery::mock(\Psr\SimpleCache\CacheInterface::class);
    }

    /**
     * Create event dispatcher mock
     */
    public static function eventDispatcher(): MockInterface
    {
        return Mockery::mock(\Psr\EventDispatcher\EventDispatcherInterface::class);
    }

    /**
     * Create container mock
     */
    public static function container(): MockInterface
    {
        return Mockery::mock(\Psr\Container\ContainerInterface::class);
    }

    /**
     * Create Redis mock
     */
    public static function redis(): MockInterface
    {
        return Mockery::mock(\Hyperf\Redis\RedisInterface::class);
    }

    /**
     * Create validator mock
     */
    public static function validator(): MockInterface
    {
        return Mockery::mock(\Hyperf\Validation\ValidatorFactoryInterface::class);
    }

    /**
     * Create request mock
     */
    public static function request(): MockInterface
    {
        return Mockery::mock(\Psr\Http\Message\ServerRequestInterface::class);
    }

    /**
     * Create response mock
     */
    public static function response(): MockInterface
    {
        return Mockery::mock(\Psr\Http\Message\ResponseInterface::class);
    }
}
```

## Test Data Generator

```php
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Test\Helpers;

/**
 * Generate test data for various scenarios
 */
class TestDataGenerator
{
    /**
     * Generate random string
     */
    public static function randomString(int $length = 10): string
    {
        return substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyz', ceil($length / 36))), 0, $length);
    }

    /**
     * Generate random email
     */
    public static function randomEmail(): string
    {
        return self::randomString(8) . '@' . self::randomString(5) . '.com';
    }

    /**
     * Generate random phone number
     */
    public static function randomPhone(): string
    {
        return '+1' . rand(1000000000, 9999999999);
    }

    /**
     * Generate random UUID
     */
    public static function randomUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    /**
     * Generate random date
     */
    public static function randomDate(string $format = 'Y-m-d'): string
    {
        $timestamp = mt_rand(strtotime('-1 year'), strtotime('+1 year'));
        return date($format, $timestamp);
    }

    /**
     * Generate random datetime
     */
    public static function randomDateTime(string $format = 'Y-m-d H:i:s'): string
    {
        $timestamp = mt_rand(strtotime('-1 year'), strtotime('+1 year'));
        return date($format, $timestamp);
    }

    /**
     * Generate random URL
     */
    public static function randomUrl(): string
    {
        return 'https://' . self::randomString(8) . '.com/' . self::randomString(6);
    }

    /**
     * Generate random IP address
     */
    public static function randomIp(): string
    {
        return rand(1, 255) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(1, 254);
    }

    /**
     * Generate random MAC address
     */
    public static function randomMac(): string
    {
        return sprintf(
            '%02x:%02x:%02x:%02x:%02x:%02x',
            mt_rand(0, 255),
            mt_rand(0, 255),
            mt_rand(0, 255),
            mt_rand(0, 255),
            mt_rand(0, 255),
            mt_rand(0, 255)
        );
    }

    /**
     * Generate random JSON data
     */
    public static function randomJsonData(): array
    {
        return [
            'id' => self::randomUuid(),
            'name' => self::randomString(10),
            'email' => self::randomEmail(),
            'created_at' => self::randomDateTime(),
            'metadata' => [
                'source' => self::randomString(5),
                'tags' => [self::randomString(4), self::randomString(6)],
            ],
        ];
    }

    /**
     * Generate random array of data
     */
    public static function randomArrayData(int $count = 5): array
    {
        return array_map(fn() => self::randomJsonData(), range(1, $count));
    }

    /**
     * Pick random element from array
     */
    public static function randomChoice(array $choices)
    {
        return $choices[array_rand($choices)];
    }

    /**
     * Generate random boolean
     */
    public static function randomBool(): bool
    {
        return (bool) mt_rand(0, 1);
    }

    /**
     * Generate random number in range
     */
    public static function randomNumber(int $min = 1, int $max = 1000): int
    {
        return mt_rand($min, $max);
    }

    /**
     * Generate random float
     */
    public static function randomFloat(float $min = 0.0, float $max = 100.0, int $decimals = 2): float
    {
        return round(mt_rand() / mt_getrandmax() * ($max - $min) + $min, $decimals);
    }
}
```

## Database Test Helper

```php
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Test\Helpers;

use Hyperf\Database\ConnectionInterface;

/**
 * Database testing helper
 */
class DatabaseHelper
{
    private ConnectionInterface $connection;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Truncate table
     */
    public function truncate(string $table): void
    {
        $this->connection->table($table)->truncate();
    }

    /**
     * Truncate multiple tables
     */
    public function truncateAll(array $tables): void
    {
        foreach ($tables as $table) {
            $this->truncate($table);
        }
    }

    /**
     * Seed table with data
     */
    public function seed(string $table, array $data): void
    {
        $this->connection->table($table)->insert($data);
    }

    /**
     * Get record count
     */
    public function count(string $table, array $conditions = []): int
    {
        $query = $this->connection->table($table);
        
        if (!empty($conditions)) {
            $query->where($conditions);
        }
        
        return $query->count();
    }

    /**
     * Check if record exists
     */
    public function exists(string $table, array $conditions): bool
    {
        return $this->connection->table($table)->where($conditions)->exists();
    }

    /**
     * Get last inserted ID
     */
    public function lastInsertId(): int
    {
        return $this->connection->getPdo()->lastInsertId();
    }

    /**
     * Execute raw SQL
     */
    public function raw(string $sql, array $bindings = []): mixed
    {
        return $this->connection->select($sql, $bindings);
    }

    /**
     * Start transaction
     */
    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    /**
     * Rollback transaction
     */
    public function rollback(): void
    {
        $this->connection->rollBack();
    }

    /**
     * Commit transaction
     */
    public function commit(): void
    {
        $this->connection->commit();
    }

    /**
     * Execute in transaction
     */
    public function transaction(callable $callback): mixed
    {
        return $this->connection->transaction($callback);
    }

    /**
     * Get table schema
     */
    public function getColumns(string $table): array
    {
        $sql = "SHOW COLUMNS FROM {$table}";
        return $this->connection->select($sql);
    }

    /**
     * Drop table if exists
     */
    public function dropIfExists(string $table): void
    {
        $this->connection->statement("DROP TABLE IF EXISTS {$table}");
    }

    /**
     * Create temporary table
     */
    public function createTempTable(string $name, array $columns): void
    {
        $columnDefs = [];
        foreach ($columns as $column => $type) {
            $columnDefs[] = "{$column} {$type}";
        }
        
        $sql = "CREATE TEMPORARY TABLE {$name} (" . implode(', ', $columnDefs) . ")";
        $this->connection->statement($sql);
    }
}
```

## HTTP Test Helper

```php
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Test\Helpers;

use Hyperf\Testing\Client;
use Psr\Http\Message\ResponseInterface;

/**
 * HTTP testing helper
 */
class HttpHelper
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Make authenticated request
     */
    public function authenticatedRequest(string $method, string $uri, string $token, array $data = []): ResponseInterface
    {
        $headers = [
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        return match (strtoupper($method)) {
            'GET' => $this->client->get($uri, compact('headers')),
            'POST' => $this->client->post($uri, ['headers' => $headers, 'json' => $data]),
            'PUT' => $this->client->put($uri, ['headers' => $headers, 'json' => $data]),
            'PATCH' => $this->client->patch($uri, ['headers' => $headers, 'json' => $data]),
            'DELETE' => $this->client->delete($uri, compact('headers')),
            default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}")
        };
    }

    /**
     * Parse JSON response
     */
    public function parseJson(ResponseInterface $response): array
    {
        $content = $response->getBody()->getContents();
        return json_decode($content, true);
    }

    /**
     * Get response status
     */
    public function getStatus(ResponseInterface $response): int
    {
        return $response->getStatusCode();
    }

    /**
     * Get response headers
     */
    public function getHeaders(ResponseInterface $response): array
    {
        return $response->getHeaders();
    }

    /**
     * Get response header
     */
    public function getHeader(ResponseInterface $response, string $name): string
    {
        return $response->getHeaderLine($name);
    }

    /**
     * Assert response structure
     */
    public function assertStructure(array $structure, array $data): bool
    {
        foreach ($structure as $key => $value) {
            if (is_array($value) && $key === '*') {
                if (!is_array($data)) {
                    return false;
                }
                foreach ($data as $item) {
                    if (!$this->assertStructure($value, $item)) {
                        return false;
                    }
                }
            } elseif (is_array($value)) {
                if (!array_key_exists($key, $data) || !is_array($data[$key])) {
                    return false;
                }
                if (!$this->assertStructure($value, $data[$key])) {
                    return false;
                }
            } else {
                if (!array_key_exists($value, $data)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Create form data
     */
    public function createFormData(array $data): array
    {
        return ['form_params' => $data];
    }

    /**
     * Create multipart data
     */
    public function createMultipart(array $data): array
    {
        $multipart = [];
        foreach ($data as $name => $contents) {
            $multipart[] = compact('name', 'contents');
        }
        return ['multipart' => $multipart];
    }

    /**
     * Upload file
     */
    public function uploadFile(string $uri, string $fieldName, string $filePath, array $data = [], array $headers = []): ResponseInterface
    {
        $multipart = [
            [
                'name' => $fieldName,
                'contents' => fopen($filePath, 'r'),
                'filename' => basename($filePath),
            ]
        ];

        foreach ($data as $name => $contents) {
            $multipart[] = compact('name', 'contents');
        }

        return $this->client->post($uri, [
            'headers' => $headers,
            'multipart' => $multipart,
        ]);
    }
}
```

## Assertion Helper

```php
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Test\Helpers;

use PHPUnit\Framework\Assert;

/**
 * Custom assertion helper
 */
class AssertionHelper
{
    /**
     * Assert array contains subset
     */
    public static function assertArraySubset(array $subset, array $array, string $message = ''): void
    {
        foreach ($subset as $key => $value) {
            Assert::assertArrayHasKey($key, $array, $message);
            if (is_array($value)) {
                Assert::assertIsArray($array[$key], $message);
                self::assertArraySubset($value, $array[$key], $message);
            } else {
                Assert::assertEquals($value, $array[$key], $message);
            }
        }
    }

    /**
     * Assert string contains all substrings
     */
    public static function assertStringContainsAll(array $needles, string $haystack, string $message = ''): void
    {
        foreach ($needles as $needle) {
            Assert::assertStringContainsString($needle, $haystack, $message);
        }
    }

    /**
     * Assert array has exact count
     */
    public static function assertArrayHasCount(int $expectedCount, array $array, string $message = ''): void
    {
        Assert::assertCount($expectedCount, $array, $message);
    }

    /**
     * Assert execution time
     */
    public static function assertExecutionTime(float $maxSeconds, callable $callback, string $message = ''): void
    {
        $start = microtime(true);
        $callback();
        $duration = microtime(true) - $start;
        
        Assert::assertLessThanOrEqual(
            $maxSeconds, 
            $duration, 
            $message ?: "Execution time {$duration}s exceeded limit {$maxSeconds}s"
        );
    }

    /**
     * Assert memory usage
     */
    public static function assertMemoryUsage(int $maxBytes, callable $callback, string $message = ''): void
    {
        $startMemory = memory_get_usage(true);
        $callback();
        $memoryUsed = memory_get_usage(true) - $startMemory;
        
        Assert::assertLessThanOrEqual(
            $maxBytes, 
            $memoryUsed, 
            $message ?: "Memory usage {$memoryUsed} bytes exceeded limit {$maxBytes} bytes"
        );
    }

    /**
     * Assert no exceptions thrown
     */
    public static function assertNoException(callable $callback, string $message = ''): void
    {
        $exception = null;
        
        try {
            $callback();
        } catch (\Throwable $e) {
            $exception = $e;
        }
        
        Assert::assertNull($exception, $message ?: ($exception ? $exception->getMessage() : ''));
    }

    /**
     * Assert valid UUID
     */
    public static function assertValidUuid(string $uuid, string $message = ''): void
    {
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
        Assert::assertMatchesRegularExpression($pattern, $uuid, $message);
    }

    /**
     * Assert valid email
     */
    public static function assertValidEmail(string $email, string $message = ''): void
    {
        Assert::assertTrue(filter_var($email, FILTER_VALIDATE_EMAIL) !== false, $message);
    }

    /**
     * Assert valid URL
     */
    public static function assertValidUrl(string $url, string $message = ''): void
    {
        Assert::assertTrue(filter_var($url, FILTER_VALIDATE_URL) !== false, $message);
    }
}
```

## 🚨 ZERO TOLERANCE ENFORCEMENT

**MANDATORY - ALL PHP tests must achieve PERFECT execution:**

### Success Criteria (ALL must be met)
- ✅ **0 Failed Tests** - Every single test must pass
- ✅ **0 Errors** - No runtime errors allowed
- ✅ **0 Warnings** - Warnings are treated as failures
- ✅ **0 Deprecations** - Deprecation notices block success
- ✅ **0 Incomplete Tests** - Incomplete tests are failures
- ✅ **0 Risky Tests** - Risky test detection must pass
- ✅ **0 Skipped Tests** - Unless explicitly allowed with justification

### PHPUnit Strict Mode Flags (MANDATORY)
```bash
phpunit --fail-on-warning --fail-on-risky --fail-on-incomplete --fail-on-skipped --stop-on-failure
```

### PHP Error Handling
```php
error_reporting(E_ALL);
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
```

## Usage Examples

```php
use {{NAMESPACE}}\Test\Helpers\MockFactory;
use {{NAMESPACE}}\Test\Helpers\TestDataGenerator;
use {{NAMESPACE}}\Test\Helpers\AssertionHelper;

// Create mocks
$httpClient = MockFactory::httpClient();
$logger = MockFactory::logger();

// Generate test data
$email = TestDataGenerator::randomEmail();
$data = TestDataGenerator::randomJsonData();

// Custom assertions
AssertionHelper::assertValidEmail($email);
AssertionHelper::assertExecutionTime(0.1, function() {
    // Some operation
});
```

These helpers provide comprehensive utilities for PHP testing with hyperf/testing framework, making tests more maintainable and expressive.