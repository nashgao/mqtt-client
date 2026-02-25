# PHP Test Mock Factories

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

## 🚨 ZERO TOLERANCE ENFORCEMENT

**MANDATORY - ALL tests must achieve PERFECT execution:**

### Success Criteria (ALL must be met)
- ✅ **0 Failed Tests** - Every single test must pass
- ✅ **0 Errors** - No runtime errors allowed
- ✅ **0 Warnings** - Warnings are treated as failures
- ✅ **0 Deprecations** - Deprecation notices block success
- ✅ **0 Incomplete Tests** - Incomplete tests are failures
- ✅ **0 Risky Tests** - Risky test detection must pass
- ✅ **0 Skipped Tests** - Unless explicitly allowed with justification

### Validation Gate
No test execution is complete until ALL criteria above are met.
Partial success is NOT success - it is failure.

### Mock Factory Testing Requirements
- **All mocks must be verified** - shouldReceive() expectations enforced
- **Mock isolation required** - Each test creates fresh mocks
- **No real external calls** - All external services must be mocked
- **Cleanup mandatory** - Mockery::close() in tearDown()

---

## Overview

Comprehensive mock factory system for PHP testing with hyperf/testing, providing pre-configured mocks for common services and components.

## Core Mock Factory

```php
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Test\Mocks;

use Mockery;
use Mockery\MockInterface;

/**
 * Core mock factory for common services
 */
class MockFactory
{
    /**
     * Create HTTP client mock
     */
    public static function httpClient(): HttpClientMock
    {
        return new HttpClientMock();
    }

    /**
     * Create database connection mock
     */
    public static function database(): DatabaseMock
    {
        return new DatabaseMock();
    }

    /**
     * Create Redis mock
     */
    public static function redis(): RedisMock
    {
        return new RedisMock();
    }

    /**
     * Create logger mock
     */
    public static function logger(): LoggerMock
    {
        return new LoggerMock();
    }

    /**
     * Create cache mock
     */
    public static function cache(): CacheMock
    {
        return new CacheMock();
    }

    /**
     * Create event dispatcher mock
     */
    public static function eventDispatcher(): EventDispatcherMock
    {
        return new EventDispatcherMock();
    }

    /**
     * Create validator mock
     */
    public static function validator(): ValidatorMock
    {
        return new ValidatorMock();
    }

    /**
     * Create container mock
     */
    public static function container(): ContainerMock
    {
        return new ContainerMock();
    }

    /**
     * Create request mock
     */
    public static function request(): RequestMock
    {
        return new RequestMock();
    }

    /**
     * Create response mock
     */
    public static function response(): ResponseMock
    {
        return new ResponseMock();
    }

    /**
     * Create queue mock
     */
    public static function queue(): QueueMock
    {
        return new QueueMock();
    }

    /**
     * Create file system mock
     */
    public static function filesystem(): FilesystemMock
    {
        return new FilesystemMock();
    }
}
```

## HTTP Client Mock

```php
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Test\Mocks;

use Mockery;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\ClientInterface;

/**
 * HTTP client mock wrapper
 */
class HttpClientMock
{
    private MockInterface $mock;
    private array $responses = [];

    public function __construct()
    {
        $this->mock = Mockery::mock(ClientInterface::class);
    }

    /**
     * Get the mock instance
     */
    public function getMock(): MockInterface
    {
        return $this->mock;
    }

    /**
     * Add response for specific request
     */
    public function shouldReturnResponse(string $method, string $uri, array $response): self
    {
        $this->responses[strtoupper($method)][$uri] = $response;
        
        $responseMock = Mockery::mock(ResponseInterface::class);
        $responseMock->shouldReceive('getStatusCode')->andReturn($response['status'] ?? 200);
        $responseMock->shouldReceive('getBody->getContents')->andReturn(json_encode($response['body'] ?? []));
        $responseMock->shouldReceive('getHeaders')->andReturn($response['headers'] ?? []);
        
        $this->mock->shouldReceive(strtolower($method))
            ->with($uri, Mockery::any())
            ->andReturn($responseMock);

        return $this;
    }

    /**
     * Mock GET request
     */
    public function shouldGet(string $uri, array $response = []): self
    {
        return $this->shouldReturnResponse('GET', $uri, $response);
    }

    /**
     * Mock POST request
     */
    public function shouldPost(string $uri, array $response = []): self
    {
        return $this->shouldReturnResponse('POST', $uri, $response);
    }

    /**
     * Mock PUT request
     */
    public function shouldPut(string $uri, array $response = []): self
    {
        return $this->shouldReturnResponse('PUT', $uri, $response);
    }

    /**
     * Mock DELETE request
     */
    public function shouldDelete(string $uri, array $response = []): self
    {
        return $this->shouldReturnResponse('DELETE', $uri, $response);
    }

    /**
     * Mock failed request
     */
    public function shouldFail(string $method, string $uri, \Exception $exception): self
    {
        $this->mock->shouldReceive(strtolower($method))
            ->with($uri, Mockery::any())
            ->andThrow($exception);

        return $this;
    }

    /**
     * Verify request was made
     */
    public function shouldHaveReceived(string $method, string $uri, array $options = []): void
    {
        $this->mock->shouldHaveReceived(strtolower($method))
            ->with($uri, $options ? Mockery::subset($options) : Mockery::any());
    }

    /**
     * Mock request with specific data
     */
    public function shouldReceiveRequestWith(string $method, string $uri, array $expectedData): self
    {
        $responseMock = Mockery::mock(ResponseInterface::class);
        $responseMock->shouldReceive('getStatusCode')->andReturn(200);
        $responseMock->shouldReceive('getBody->getContents')->andReturn('{}');
        
        $this->mock->shouldReceive(strtolower($method))
            ->with($uri, Mockery::on(function($options) use ($expectedData) {
                if (!isset($options['json'])) {
                    return false;
                }
                return array_intersect_assoc($options['json'], $expectedData) === $expectedData;
            }))
            ->andReturn($responseMock);

        return $this;
    }
}
```

## Database Mock

```php
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Test\Mocks;

use Mockery;
use Mockery\MockInterface;
use Hyperf\Database\ConnectionInterface;

/**
 * Database connection mock wrapper
 */
class DatabaseMock
{
    private MockInterface $mock;

    public function __construct()
    {
        $this->mock = Mockery::mock(ConnectionInterface::class);
    }

    /**
     * Get the mock instance
     */
    public function getMock(): MockInterface
    {
        return $this->mock;
    }

    /**
     * Mock select query
     */
    public function shouldSelect(string $query, array $bindings = [], array $result = []): self
    {
        $this->mock->shouldReceive('select')
            ->with($query, $bindings)
            ->andReturn($result);

        return $this;
    }

    /**
     * Mock select one query
     */
    public function shouldSelectOne(string $query, array $bindings = [], array $result = null): self
    {
        $this->mock->shouldReceive('selectOne')
            ->with($query, $bindings)
            ->andReturn($result);

        return $this;
    }

    /**
     * Mock insert query
     */
    public function shouldInsert(string $query, array $bindings = [], bool $result = true): self
    {
        $this->mock->shouldReceive('insert')
            ->with($query, $bindings)
            ->andReturn($result);

        return $this;
    }

    /**
     * Mock update query
     */
    public function shouldUpdate(string $query, array $bindings = [], int $affected = 1): self
    {
        $this->mock->shouldReceive('update')
            ->with($query, $bindings)
            ->andReturn($affected);

        return $this;
    }

    /**
     * Mock delete query
     */
    public function shouldDelete(string $query, array $bindings = [], int $affected = 1): self
    {
        $this->mock->shouldReceive('delete')
            ->with($query, $bindings)
            ->andReturn($affected);

        return $this;
    }

    /**
     * Mock table method
     */
    public function shouldTable(string $table): QueryBuilderMock
    {
        $queryBuilderMock = new QueryBuilderMock();
        
        $this->mock->shouldReceive('table')
            ->with($table)
            ->andReturn($queryBuilderMock->getMock());

        return $queryBuilderMock;
    }

    /**
     * Mock transaction
     */
    public function shouldTransaction(callable $callback, $result = null): self
    {
        $this->mock->shouldReceive('transaction')
            ->with(Mockery::type('callable'))
            ->andReturnUsing($callback);

        return $this;
    }

    /**
     * Mock begin transaction
     */
    public function shouldBeginTransaction(): self
    {
        $this->mock->shouldReceive('beginTransaction')->andReturnNull();
        return $this;
    }

    /**
     * Mock commit
     */
    public function shouldCommit(): self
    {
        $this->mock->shouldReceive('commit')->andReturnNull();
        return $this;
    }

    /**
     * Mock rollback
     */
    public function shouldRollback(): self
    {
        $this->mock->shouldReceive('rollBack')->andReturnNull();
        return $this;
    }
}
```

## Redis Mock

```php
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Test\Mocks;

use Mockery;
use Mockery\MockInterface;
use Hyperf\Redis\RedisInterface;

/**
 * Redis mock wrapper
 */
class RedisMock
{
    private MockInterface $mock;
    private array $storage = [];

    public function __construct()
    {
        $this->mock = Mockery::mock(RedisInterface::class);
    }

    /**
     * Get the mock instance
     */
    public function getMock(): MockInterface
    {
        return $this->mock;
    }

    /**
     * Mock get operation
     */
    public function shouldGet(string $key, $value = null): self
    {
        $this->storage[$key] = $value;
        
        $this->mock->shouldReceive('get')
            ->with($key)
            ->andReturn($value);

        return $this;
    }

    /**
     * Mock set operation
     */
    public function shouldSet(string $key, $value, bool $result = true): self
    {
        $this->storage[$key] = $value;
        
        $this->mock->shouldReceive('set')
            ->with($key, $value)
            ->andReturn($result);

        return $this;
    }

    /**
     * Mock exists operation
     */
    public function shouldExists(string $key, bool $exists = true): self
    {
        $this->mock->shouldReceive('exists')
            ->with($key)
            ->andReturn($exists ? 1 : 0);

        return $this;
    }

    /**
     * Mock delete operation
     */
    public function shouldDel(string $key, int $result = 1): self
    {
        unset($this->storage[$key]);
        
        $this->mock->shouldReceive('del')
            ->with($key)
            ->andReturn($result);

        return $this;
    }

    /**
     * Mock expire operation
     */
    public function shouldExpire(string $key, int $seconds, bool $result = true): self
    {
        $this->mock->shouldReceive('expire')
            ->with($key, $seconds)
            ->andReturn($result ? 1 : 0);

        return $this;
    }

    /**
     * Mock increment operation
     */
    public function shouldIncr(string $key, int $result = 1): self
    {
        $this->mock->shouldReceive('incr')
            ->with($key)
            ->andReturn($result);

        return $this;
    }

    /**
     * Mock hash operations
     */
    public function shouldHget(string $key, string $field, $value = null): self
    {
        $this->mock->shouldReceive('hget')
            ->with($key, $field)
            ->andReturn($value);

        return $this;
    }

    public function shouldHset(string $key, string $field, $value, int $result = 1): self
    {
        $this->mock->shouldReceive('hset')
            ->with($key, $field, $value)
            ->andReturn($result);

        return $this;
    }

    /**
     * Mock list operations
     */
    public function shouldLpush(string $key, $value, int $result = 1): self
    {
        $this->mock->shouldReceive('lpush')
            ->with($key, $value)
            ->andReturn($result);

        return $this;
    }

    public function shouldRpop(string $key, $value = null): self
    {
        $this->mock->shouldReceive('rpop')
            ->with($key)
            ->andReturn($value);

        return $this;
    }

    /**
     * Get stored value
     */
    public function getStoredValue(string $key)
    {
        return $this->storage[$key] ?? null;
    }

    /**
     * Clear storage
     */
    public function clearStorage(): void
    {
        $this->storage = [];
    }
}
```

## Logger Mock

```php
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Test\Mocks;

use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;

/**
 * Logger mock wrapper
 */
class LoggerMock
{
    private MockInterface $mock;
    private array $logs = [];

    public function __construct()
    {
        $this->mock = Mockery::mock(LoggerInterface::class);
        $this->setupLogCapture();
    }

    /**
     * Get the mock instance
     */
    public function getMock(): MockInterface
    {
        return $this->mock;
    }

    /**
     * Setup log capture
     */
    private function setupLogCapture(): void
    {
        $levels = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];
        
        foreach ($levels as $level) {
            $this->mock->shouldReceive($level)
                ->withArgs(function($message, $context = []) use ($level) {
                    $this->logs[] = [
                        'level' => $level,
                        'message' => $message,
                        'context' => $context,
                        'timestamp' => microtime(true),
                    ];
                    return true;
                })
                ->andReturnNull();
        }

        $this->mock->shouldReceive('log')
            ->withArgs(function($level, $message, $context = []) {
                $this->logs[] = [
                    'level' => $level,
                    'message' => $message,
                    'context' => $context,
                    'timestamp' => microtime(true),
                ];
                return true;
            })
            ->andReturnNull();
    }

    /**
     * Should log specific message
     */
    public function shouldLog(string $level, string $message, array $context = []): self
    {
        $this->mock->shouldReceive($level)
            ->with($message, $context)
            ->once();

        return $this;
    }

    /**
     * Should receive any log
     */
    public function shouldReceiveAnyLog(): self
    {
        $this->mock->shouldReceive(Mockery::anyOf(
            'emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'
        ))->atLeast()->once();

        return $this;
    }

    /**
     * Get captured logs
     */
    public function getLogs(): array
    {
        return $this->logs;
    }

    /**
     * Get logs by level
     */
    public function getLogsByLevel(string $level): array
    {
        return array_filter($this->logs, fn($log) => $log['level'] === $level);
    }

    /**
     * Assert log was written
     */
    public function assertLogged(string $level, string $message = null, array $context = []): void
    {
        $logs = $this->getLogsByLevel($level);
        
        if ($message === null) {
            assert(!empty($logs), "No logs found for level: {$level}");
            return;
        }

        $found = false;
        foreach ($logs as $log) {
            if (strpos($log['message'], $message) !== false) {
                if (empty($context) || array_intersect_assoc($log['context'], $context) === $context) {
                    $found = true;
                    break;
                }
            }
        }

        assert($found, "Log not found: {$level} - {$message}");
    }

    /**
     * Assert no logs
     */
    public function assertNoLogs(): void
    {
        assert(empty($this->logs), "Expected no logs, but found " . count($this->logs));
    }

    /**
     * Clear captured logs
     */
    public function clearLogs(): void
    {
        $this->logs = [];
    }
}
```

## Cache Mock

```php
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Test\Mocks;

use Mockery;
use Mockery\MockInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Cache mock wrapper
 */
class CacheMock
{
    private MockInterface $mock;
    private array $cache = [];

    public function __construct()
    {
        $this->mock = Mockery::mock(CacheInterface::class);
        $this->setupCache();
    }

    /**
     * Get the mock instance
     */
    public function getMock(): MockInterface
    {
        return $this->mock;
    }

    /**
     * Setup cache behavior
     */
    private function setupCache(): void
    {
        $this->mock->shouldReceive('get')
            ->withArgs(function($key, $default = null) {
                return array_key_exists($key, $this->cache) ? $this->cache[$key] : $default;
            })
            ->andReturnUsing(function($key, $default = null) {
                return array_key_exists($key, $this->cache) ? $this->cache[$key] : $default;
            });

        $this->mock->shouldReceive('set')
            ->withArgs(function($key, $value, $ttl = null) {
                $this->cache[$key] = $value;
                return true;
            })
            ->andReturn(true);

        $this->mock->shouldReceive('has')
            ->withArgs(function($key) {
                return array_key_exists($key, $this->cache);
            })
            ->andReturnUsing(function($key) {
                return array_key_exists($key, $this->cache);
            });

        $this->mock->shouldReceive('delete')
            ->withArgs(function($key) {
                unset($this->cache[$key]);
                return true;
            })
            ->andReturn(true);

        $this->mock->shouldReceive('clear')
            ->withNoArgs()
            ->andReturnUsing(function() {
                $this->cache = [];
                return true;
            });
    }

    /**
     * Pre-populate cache
     */
    public function shouldHave(string $key, $value): self
    {
        $this->cache[$key] = $value;
        return $this;
    }

    /**
     * Should remember value
     */
    public function shouldRemember(string $key, $value, callable $callback = null): self
    {
        if (!array_key_exists($key, $this->cache)) {
            $this->cache[$key] = $callback ? $callback() : $value;
        }
        
        return $this;
    }

    /**
     * Get cached values
     */
    public function getCachedValues(): array
    {
        return $this->cache;
    }

    /**
     * Clear cache
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }

    /**
     * Assert value is cached
     */
    public function assertCached(string $key, $expectedValue = null): void
    {
        assert(array_key_exists($key, $this->cache), "Key '{$key}' is not cached");
        
        if ($expectedValue !== null) {
            assert($this->cache[$key] === $expectedValue, "Cached value does not match expected value");
        }
    }

    /**
     * Assert value is not cached
     */
    public function assertNotCached(string $key): void
    {
        assert(!array_key_exists($key, $this->cache), "Key '{$key}' should not be cached");
    }
}
```

## Usage Examples

```php
<?php

declare(strict_types=1);

namespace App\Test\Cases\Unit;

use App\Test\TestCase;
use App\Test\Mocks\MockFactory;
use PHPUnit\Framework\Attributes\Group;

#[Group('unit')]
class ServiceTest extends TestCase
{
    public function testHttpClientMock(): void
    {
        $httpMock = MockFactory::httpClient();
        
        $httpMock->shouldGet('https://api.example.com/users', [
            'status' => 200,
            'body' => ['users' => [['id' => 1, 'name' => 'John']]]
        ]);

        $client = $httpMock->getMock();
        $response = $client->get('https://api.example.com/users');
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testDatabaseMock(): void
    {
        $dbMock = MockFactory::database();
        
        $dbMock->shouldSelect(
            'SELECT * FROM users WHERE id = ?',
            [1],
            [['id' => 1, 'name' => 'John']]
        );

        $db = $dbMock->getMock();
        $result = $db->select('SELECT * FROM users WHERE id = ?', [1]);
        
        $this->assertEquals(1, $result[0]['id']);
    }

    public function testLoggerMock(): void
    {
        $loggerMock = MockFactory::logger();
        $logger = $loggerMock->getMock();
        
        $logger->info('Test message', ['context' => 'value']);
        
        $loggerMock->assertLogged('info', 'Test message', ['context' => 'value']);
    }
}
```

These mock factories provide comprehensive mocking capabilities for PHP testing with hyperf/testing, making tests more maintainable and reliable.