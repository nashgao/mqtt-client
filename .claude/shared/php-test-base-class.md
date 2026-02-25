# PHP Test Base Class Template

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

Base test class template for PHP projects using hyperf/testing with coroutine support, proper mocking, and comprehensive testing utilities.

## Base Test Class

```php
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Test;

use Hyperf\Testing\TestCase as HyperfTestCase;
use Hyperf\Testing\Client;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use Psr\Container\ContainerInterface;
use Swoole\Coroutine;

/**
 * Base test class with hyperf/testing integration
 * Provides common testing utilities and proper setup/teardown
 */
abstract class TestCase extends HyperfTestCase
{
    /**
     * HTTP test client
     */
    protected Client $client;

    /**
     * Application container
     */
    protected ContainerInterface $container;

    #[Before]
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->container = $this->getContainer();
        $this->client = make(Client::class);
        
        // Additional setup can be added here
        $this->setUpTestEnvironment();
    }

    #[After]
    protected function tearDown(): void
    {
        // Clean up mocks
        Mockery::close();
        
        // Additional cleanup
        $this->tearDownTestEnvironment();
        
        parent::tearDown();
    }

    /**
     * Set up test environment
     * Override in child classes for specific setup
     */
    protected function setUpTestEnvironment(): void
    {
        // Default implementation - can be overridden
    }

    /**
     * Tear down test environment
     * Override in child classes for specific cleanup
     */
    protected function tearDownTestEnvironment(): void
    {
        // Default implementation - can be overridden
    }

    /**
     * Create a mock object
     */
    protected function createMock(string $className): MockInterface
    {
        return Mockery::mock($className);
    }

    /**
     * Create a partial mock object
     */
    protected function createPartialMock(string $className, array $methods = []): MockInterface
    {
        return Mockery::mock($className)->makePartial();
    }

    /**
     * Create a spy object
     */
    protected function createSpy(string $className): MockInterface
    {
        return Mockery::spy($className);
    }

    /**
     * Get service from container
     */
    protected function get(string $id)
    {
        return $this->container->get($id);
    }

    /**
     * Execute code in coroutine context
     */
    protected function runInCoroutine(callable $callback)
    {
        $result = null;
        $exception = null;

        Coroutine::run(function () use ($callback, &$result, &$exception) {
            try {
                $result = $callback();
            } catch (\Throwable $e) {
                $exception = $e;
            }
        });

        if ($exception) {
            throw $exception;
        }

        return $result;
    }

    /**
     * Assert that a callback runs without exceptions
     */
    protected function assertNoException(callable $callback): void
    {
        $exception = null;

        try {
            $callback();
        } catch (\Throwable $e) {
            $exception = $e;
        }

        $this->assertNull($exception, $exception ? $exception->getMessage() : '');
    }

    /**
     * Assert that two arrays are equal ignoring order
     */
    protected function assertArrayEqualsIgnoringOrder(array $expected, array $actual, string $message = ''): void
    {
        sort($expected);
        sort($actual);
        $this->assertEquals($expected, $actual, $message);
    }

    /**
     * Assert that an array contains all expected keys
     */
    protected function assertArrayHasKeys(array $keys, array $array, string $message = ''): void
    {
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $array, $message);
        }
    }

    /**
     * Assert that a response has expected JSON structure
     */
    protected function assertJsonStructure(array $structure, array $data, string $message = ''): void
    {
        foreach ($structure as $key => $value) {
            if (is_array($value) && $key === '*') {
                $this->assertIsArray($data, $message);
                foreach ($data as $dataItem) {
                    $this->assertJsonStructure($value, $dataItem, $message);
                }
            } elseif (is_array($value)) {
                $this->assertArrayHasKey($key, $data, $message);
                $this->assertJsonStructure($value, $data[$key], $message);
            } else {
                $this->assertArrayHasKey($value, $data, $message);
            }
        }
    }

    /**
     * Create test data factory
     */
    protected function factory(string $class, array $attributes = []): array
    {
        $factoryClass = "{{NAMESPACE}}\\Test\\Factories\\{$class}Factory";
        
        if (!class_exists($factoryClass)) {
            throw new \InvalidArgumentException("Factory class {$factoryClass} not found");
        }

        return $factoryClass::create($attributes);
    }

    /**
     * Create multiple test data items
     */
    protected function factoryMany(string $class, int $count, array $attributes = []): array
    {
        $factoryClass = "{{NAMESPACE}}\\Test\\Factories\\{$class}Factory";
        
        if (!class_exists($factoryClass)) {
            throw new \InvalidArgumentException("Factory class {$factoryClass} not found");
        }

        return $factoryClass::createMany($count, $attributes);
    }

    /**
     * Fake time for testing
     */
    protected function travelTo(string $date): void
    {
        if (class_exists('\Carbon\Carbon')) {
            \Carbon\Carbon::setTestNow($date);
        }
    }

    /**
     * Reset fake time
     */
    protected function travelBack(): void
    {
        if (class_exists('\Carbon\Carbon')) {
            \Carbon\Carbon::setTestNow();
        }
    }

    /**
     * Get environment variable for testing
     */
    protected function env(string $key, $default = null)
    {
        return env($key, $default);
    }

    /**
     * Assert that a string matches a pattern
     */
    protected function assertMatchesPattern(string $pattern, string $string, string $message = ''): void
    {
        $this->assertMatchesRegularExpression($pattern, $string, $message);
    }

    /**
     * Assert that an exception is thrown with specific message
     */
    protected function assertExceptionMessage(string $expectedMessage, callable $callback): void
    {
        $exception = null;

        try {
            $callback();
        } catch (\Throwable $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception, 'Expected exception was not thrown');
        $this->assertStringContainsString($expectedMessage, $exception->getMessage());
    }

    /**
     * Create a temporary file for testing
     */
    protected function createTempFile(string $content = '', string $suffix = '.tmp'): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_') . $suffix;
        file_put_contents($tempFile, $content);
        
        // Register for cleanup
        register_shutdown_function(function () use ($tempFile) {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        });

        return $tempFile;
    }

    /**
     * Get random test data
     */
    protected function randomString(int $length = 10): string
    {
        return substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyz', ceil($length / 36))), 0, $length);
    }

    protected function randomInt(int $min = 1, int $max = 1000): int
    {
        return random_int($min, $max);
    }

    protected function randomEmail(): string
    {
        return $this->randomString(8) . '@' . $this->randomString(5) . '.com';
    }

    /**
     * Sleep for testing (coroutine-aware)
     */
    protected function sleep(float $seconds): void
    {
        if (Coroutine::getCid() > 0) {
            Coroutine::sleep($seconds);
        } else {
            usleep((int) ($seconds * 1000000));
        }
    }

    /**
     * Benchmark execution time
     */
    protected function benchmark(callable $callback): float
    {
        $start = microtime(true);
        $callback();
        return microtime(true) - $start;
    }

    /**
     * Assert execution time is within bounds
     */
    protected function assertExecutionTime(float $maxSeconds, callable $callback, string $message = ''): void
    {
        $time = $this->benchmark($callback);
        $this->assertLessThanOrEqual($maxSeconds, $time, $message ?: "Execution time {$time}s exceeded limit {$maxSeconds}s");
    }
}
```

## Database Test Case

For database-related tests:

```php
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Test;

use Hyperf\Testing\Concerns\RefreshDatabase;
use Hyperf\Database\ConnectionInterface;
use Hyperf\Database\Schema\Schema;

/**
 * Base test case for database operations
 */
abstract class DatabaseTestCase extends TestCase
{
    use RefreshDatabase;

    protected ConnectionInterface $db;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->db = $this->get(ConnectionInterface::class);
    }

    /**
     * Get database connection
     */
    protected function getConnection(): ConnectionInterface
    {
        return $this->db;
    }

    /**
     * Execute raw SQL query
     */
    protected function sql(string $query, array $bindings = []): mixed
    {
        return $this->db->select($query, $bindings);
    }

    /**
     * Insert test data
     */
    protected function insertTestData(string $table, array $data): bool
    {
        return $this->db->table($table)->insert($data);
    }

    /**
     * Get table schema
     */
    protected function getTableSchema(string $table): array
    {
        return Schema::getColumnListing($table);
    }

    /**
     * Assert table exists
     */
    protected function assertTableExists(string $table): void
    {
        $this->assertTrue(Schema::hasTable($table), "Table {$table} does not exist");
    }

    /**
     * Assert table has column
     */
    protected function assertTableHasColumn(string $table, string $column): void
    {
        $this->assertTrue(
            Schema::hasColumn($table, $column),
            "Table {$table} does not have column {$column}"
        );
    }

    /**
     * Assert database has record
     */
    protected function assertDatabaseHas(string $table, array $data): void
    {
        $count = $this->db->table($table)->where($data)->count();
        $this->assertGreaterThan(0, $count, "Record not found in table {$table}");
    }

    /**
     * Assert database missing record
     */
    protected function assertDatabaseMissing(string $table, array $data): void
    {
        $count = $this->db->table($table)->where($data)->count();
        $this->assertEquals(0, $count, "Unexpected record found in table {$table}");
    }
}
```

## HTTP Test Case

For HTTP/API tests:

```php
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Test;

use Hyperf\Testing\Client;
use Psr\Http\Message\ResponseInterface;

/**
 * Base test case for HTTP/API testing
 */
abstract class HttpTestCase extends TestCase
{
    /**
     * Make GET request
     */
    protected function get(string $uri, array $headers = []): ResponseInterface
    {
        return $this->client->get($uri, compact('headers'));
    }

    /**
     * Make POST request
     */
    protected function post(string $uri, array $data = [], array $headers = []): ResponseInterface
    {
        return $this->client->post($uri, compact('headers', 'json') + ['json' => $data]);
    }

    /**
     * Make PUT request
     */
    protected function put(string $uri, array $data = [], array $headers = []): ResponseInterface
    {
        return $this->client->put($uri, compact('headers', 'json') + ['json' => $data]);
    }

    /**
     * Make DELETE request
     */
    protected function delete(string $uri, array $headers = []): ResponseInterface
    {
        return $this->client->delete($uri, compact('headers'));
    }

    /**
     * Make PATCH request
     */
    protected function patch(string $uri, array $data = [], array $headers = []): ResponseInterface
    {
        return $this->client->patch($uri, compact('headers', 'json') + ['json' => $data]);
    }

    /**
     * Assert response status code
     */
    protected function assertStatus(int $status, ResponseInterface $response): void
    {
        $this->assertEquals($status, $response->getStatusCode());
    }

    /**
     * Assert response is JSON
     */
    protected function assertJson(ResponseInterface $response): array
    {
        $content = $response->getBody()->getContents();
        $data = json_decode($content, true);
        
        $this->assertIsArray($data, 'Response is not valid JSON');
        
        return $data;
    }

    /**
     * Assert JSON response structure
     */
    protected function assertJsonStructure(array $structure, ResponseInterface $response): array
    {
        $data = $this->assertJson($response);
        parent::assertJsonStructure($structure, $data);
        
        return $data;
    }

    /**
     * Assert response has header
     */
    protected function assertHeader(string $header, ResponseInterface $response, string $value = null): void
    {
        $this->assertTrue($response->hasHeader($header), "Header {$header} not found");
        
        if ($value !== null) {
            $this->assertEquals($value, $response->getHeaderLine($header));
        }
    }

    /**
     * Get authenticated headers
     */
    protected function getAuthHeaders(string $token): array
    {
        return [
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
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

## Usage Example

```php
<?php

declare(strict_types=1);

namespace App\Test\Cases\Unit;

use PHPUnit\Framework\Attributes\Group;
use App\Test\TestCase;

#[Group('unit')]
class ExampleTest extends TestCase
{
    public function testExample(): void
    {
        // Use base class utilities
        $mock = $this->createMock(SomeClass::class);
        $data = $this->factory('User', ['name' => 'Test User']);

        // Test in coroutine
        $result = $this->runInCoroutine(function () {
            return 'async result';
        });

        $this->assertEquals('async result', $result);
    }
}
```

This base test class template provides comprehensive testing utilities while maintaining compatibility with hyperf/testing and coroutine environments.