# Hyperf Testing Examples

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

### Hyperf Testing Requirements (MANDATORY for co-phpunit)
- **Use co-phpunit exclusively** - NEVER use standard phpunit
- **Coroutine context required** - All tests must preserve coroutine context
- **Proper bootstrap essential** - Hyperf\Testing\Bootstrap::init() mandatory
- **Container isolation enforced** - Fresh container per test when needed
- **Database transactions required** - Use RefreshDatabase or manual transactions

---

## 🧪 Comprehensive Testing Examples for Hyperf Framework

This guide provides practical examples for testing Hyperf applications using hyperf/testing framework.

---

## 1. Basic Unit Tests with hyperf/testing

### Simple Service Test

```php
<?php

declare(strict_types=1);

namespace HyperfTest\Cases\Unit\Service;

use App\Service\UserService;
use HyperfTest\Cases\TestCase;
use Hyperf\Utils\ApplicationContext;

class UserServiceTest extends TestCase
{
    protected UserService $userService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userService = ApplicationContext::getContainer()
            ->get(UserService::class);
    }

    public function testCreateUser(): void
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 30
        ];

        $user = $this->userService->create($userData);

        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertEquals(30, $user->age);
        $this->assertIsInt($user->id);
    }

    public function testValidateUserData(): void
    {
        $validData = ['name' => 'Jane', 'email' => 'jane@test.com', 'age' => 25];
        $this->assertTrue($this->userService->validate($validData));

        $invalidData = ['name' => '', 'email' => 'invalid', 'age' => -5];
        $this->assertFalse($this->userService->validate($invalidData));
    }

    public function testUserExists(): void
    {
        $this->assertFalse($this->userService->exists('nonexistent@test.com'));
    }
}
```

### Configuration Service Test

```php
<?php

declare(strict_types=1);

namespace HyperfTest\Cases\Unit\Service;

use App\Service\ConfigService;
use HyperfTest\Cases\TestCase;
use Hyperf\Config\Config;
use Mockery as M;

class ConfigServiceTest extends TestCase
{
    private ConfigService $configService;
    private Config $mockConfig;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock the config dependency
        $this->mockConfig = M::mock(Config::class);
        $this->container->set(Config::class, $this->mockConfig);
        
        $this->configService = $this->container->get(ConfigService::class);
    }

    public function testGetDatabaseConfig(): void
    {
        $expectedConfig = [
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'test_db'
        ];

        $this->mockConfig->shouldReceive('get')
            ->with('database.default')
            ->andReturn($expectedConfig);

        $result = $this->configService->getDatabaseConfig();

        $this->assertEquals($expectedConfig, $result);
    }

    public function testGetConfigWithDefault(): void
    {
        $this->mockConfig->shouldReceive('get')
            ->with('app.nonexistent', 'default_value')
            ->andReturn('default_value');

        $result = $this->configService->getAppConfig('nonexistent', 'default_value');

        $this->assertEquals('default_value', $result);
    }

    protected function tearDown(): void
    {
        M::close();
        parent::tearDown();
    }
}
```

---

## 2. Mocking Hyperf Dependencies

### Mocking Logger

```php
<?php

declare(strict_types=1);

namespace HyperfTest\Cases\Unit\Service;

use App\Service\LoggingService;
use HyperfTest\Cases\TestCase;
use Psr\Log\LoggerInterface;
use Mockery as M;

class LoggingServiceTest extends TestCase
{
    private LoggingService $loggingService;
    private LoggerInterface $mockLogger;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockLogger = M::mock(LoggerInterface::class);
        $this->container->set(LoggerInterface::class, $this->mockLogger);
        
        $this->loggingService = $this->container->get(LoggingService::class);
    }

    public function testLogError(): void
    {
        $errorMessage = 'Test error message';
        $context = ['user_id' => 123];

        $this->mockLogger->shouldReceive('error')
            ->once()
            ->with($errorMessage, $context);

        $this->loggingService->logError($errorMessage, $context);
    }

    public function testLogWithLevel(): void
    {
        $this->mockLogger->shouldReceive('info')
            ->once()
            ->with('Info message', []);

        $this->mockLogger->shouldReceive('warning')
            ->once()
            ->with('Warning message', ['type' => 'validation']);

        $this->loggingService->log('info', 'Info message');
        $this->loggingService->log('warning', 'Warning message', ['type' => 'validation']);
    }

    protected function tearDown(): void
    {
        M::close();
        parent::tearDown();
    }
}
```

### Mocking HTTP Client

```php
<?php

declare(strict_types=1);

namespace HyperfTest\Cases\Unit\Service;

use App\Service\ApiService;
use HyperfTest\Cases\TestCase;
use Hyperf\Guzzle\ClientFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;

class ApiServiceTest extends TestCase
{
    private ApiService $apiService;
    private MockHandler $mockHandler;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);
        $client = new Client(['handler' => $handlerStack]);
        
        $mockClientFactory = \Mockery::mock(ClientFactory::class);
        $mockClientFactory->shouldReceive('create')->andReturn($client);
        
        $this->container->set(ClientFactory::class, $mockClientFactory);
        $this->apiService = $this->container->get(ApiService::class);
    }

    public function testSuccessfulApiCall(): void
    {
        $responseData = ['status' => 'success', 'data' => ['id' => 1]];
        
        $this->mockHandler->append(
            new Response(200, [], json_encode($responseData))
        );

        $result = $this->apiService->fetchUserData(1);

        $this->assertEquals($responseData, $result);
    }

    public function testApiCallWithError(): void
    {
        $this->mockHandler->append(
            new RequestException('Network error', 
                \Mockery::mock(RequestInterface::class)
            )
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('API call failed');

        $this->apiService->fetchUserData(1);
    }

    public function testApiCallWith404(): void
    {
        $this->mockHandler->append(
            new Response(404, [], 'Not Found')
        );

        $result = $this->apiService->fetchUserData(999);

        $this->assertNull($result);
    }
}
```

### Mocking Redis

```php
<?php

declare(strict_types=1);

namespace HyperfTest\Cases\Unit\Service;

use App\Service\CacheService;
use HyperfTest\Cases\TestCase;
use Hyperf\Redis\RedisFactory;
use Hyperf\Redis\Redis;
use Mockery as M;

class CacheServiceTest extends TestCase
{
    private CacheService $cacheService;
    private Redis $mockRedis;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockRedis = M::mock(Redis::class);
        $mockRedisFactory = M::mock(RedisFactory::class);
        
        $mockRedisFactory->shouldReceive('get')
            ->with('default')
            ->andReturn($this->mockRedis);
        
        $this->container->set(RedisFactory::class, $mockRedisFactory);
        $this->cacheService = $this->container->get(CacheService::class);
    }

    public function testSetCache(): void
    {
        $key = 'test:key';
        $value = 'test value';
        $ttl = 3600;

        $this->mockRedis->shouldReceive('setex')
            ->once()
            ->with($key, $ttl, $value)
            ->andReturn(true);

        $result = $this->cacheService->set($key, $value, $ttl);

        $this->assertTrue($result);
    }

    public function testGetCache(): void
    {
        $key = 'test:key';
        $expectedValue = 'cached value';

        $this->mockRedis->shouldReceive('get')
            ->once()
            ->with($key)
            ->andReturn($expectedValue);

        $result = $this->cacheService->get($key);

        $this->assertEquals($expectedValue, $result);
    }

    public function testCacheExists(): void
    {
        $key = 'test:key';

        $this->mockRedis->shouldReceive('exists')
            ->once()
            ->with($key)
            ->andReturn(1);

        $this->assertTrue($this->cacheService->exists($key));
    }

    protected function tearDown(): void
    {
        M::close();
        parent::tearDown();
    }
}
```

---

## 3. Testing Async/Coroutine Code

### Basic Coroutine Test (Space-Actor Pattern)

```php
<?php

declare(strict_types=1);

namespace HyperfTest\Cases\Unit\Service;

use App\Service\AsyncService;
use HyperfTest\Cases\TestCase;
use Swoole\Coroutine;

class AsyncServiceTest extends TestCase
{
    private AsyncService $asyncService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->asyncService = $this->container->get(AsyncService::class);
    }

    public function testAsyncOperation(): void
    {
        $test = function () {
            $result = $this->asyncService->performAsyncTask('test-data');
            
            $this->assertIsString($result);
            $this->assertStringContains('processed', $result);
        };

        if (Coroutine::getCid()) {
            $test();
        } else {
            Coroutine\run($test);
        }
    }

    public function testConcurrentOperations(): void
    {
        $test = function () {
            $tasks = ['task1', 'task2', 'task3'];
            $results = [];

            // Test concurrent execution
            for ($i = 0; $i < count($tasks); ++$i) {
                Coroutine::create(function () use ($tasks, $i, &$results) {
                    $task = $tasks[$i];
                    $results[$task] = $this->asyncService->performAsyncTask($task);
                });
            }
            
            // Wait for completion
            Coroutine::sleep(1.0);

            $this->assertCount(3, $results);
            foreach ($tasks as $task) {
                $this->assertArrayHasKey($task, $results);
                $this->assertStringContains($task, $results[$task]);
            }
        };

        if (Coroutine::getCid()) {
            $test();
        } else {
            Coroutine\run($test);
        }
    }

    public function testStreamProcessing(): void
    {
        $test = function () {
            // Test stream-like processing with test utilities
            $elements = ['data1', 'data2', 'data3'];
            $source = TestSource::fromElements($elements);
            $sink = TestSink::auto();
            
            $source->subscribe($sink);
            
            $this->assertTrue($sink->waitForCompletion(2.0));
            $this->assertEquals($elements, $sink->getReceivedElements());
            
            // Verify processing behavior
            StreamAsserts::assertSinkCompleted($sink);
            StreamAsserts::assertSinkReceivedElements($elements, $sink);
        };

        if (Coroutine::getCid()) {
            $test();
        } else {
            Coroutine\run($test);
        }
    }

    public function testErrorHandling(): void
    {
        $test = function () {
            // Test error handling in async operations
            $error = new \RuntimeException('Test async error');
            $source = TestSource::failed($error);
            $sink = TestSink::auto();

            $source->subscribe($sink);

            // Wait for error
            $this->assertTrue($sink->waitForCompletion(1.0));

            // Verify error handling
            StreamAsserts::assertSinkFailed($sink, \RuntimeException::class);
            $this->assertEquals('Test async error', $sink->getError()->getMessage());
        };

        if (Coroutine::getCid()) {
            $test();
        } else {
            Coroutine\run($test);
        }
    }
}
```

### Parallel Processing Test

```php
<?php

declare(strict_types=1);

namespace HyperfTest\Cases\Unit\Service;

use App\Service\ParallelProcessingService;
use HyperfTest\Cases\TestCase;
use Hyperf\Utils\Parallel;

class ParallelProcessingServiceTest extends TestCase
{
    private ParallelProcessingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->container->get(ParallelProcessingService::class);
    }

    public function testParallelExecution(): void
    {
        $parallel = new Parallel();

        $parallel->add(function () {
            return $this->service->processItem('item1');
        }, 'task1');

        $parallel->add(function () {
            return $this->service->processItem('item2');
        }, 'task2');

        $parallel->add(function () {
            return $this->service->processItem('item3');
        }, 'task3');

        $results = $parallel->wait();

        $this->assertArrayHasKey('task1', $results);
        $this->assertArrayHasKey('task2', $results);
        $this->assertArrayHasKey('task3', $results);

        foreach ($results as $key => $result) {
            $this->assertStringContains('processed', $result);
        }
    }

    public function testParallelWithException(): void
    {
        $parallel = new Parallel();

        $parallel->add(function () {
            return $this->service->processItem('valid-item');
        }, 'success');

        $parallel->add(function () {
            return $this->service->processItem('invalid-item');
        }, 'failure');

        $results = $parallel->wait();

        $this->assertArrayHasKey('success', $results);
        $this->assertArrayHasKey('failure', $results);
        
        // Check that successful task completed
        $this->assertStringContains('processed', $results['success']);
        
        // Check that failed task returned error or exception
        $this->assertInstanceOf(\Throwable::class, $results['failure']);
    }
}
```

---

## 4. Database Transaction Testing

### Basic Database Test

```php
<?php

declare(strict_types=1);

namespace HyperfTest\Cases\Feature;

use App\Model\User;
use HyperfTest\Cases\TestCase;
use Hyperf\Testing\Traits\RefreshDatabase;
use Hyperf\DbConnection\Db;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->refreshDatabase();
    }

    public function testCreateUser(): void
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => password_hash('secret', PASSWORD_BCRYPT),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $user = User::create($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        
        // Verify in database
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);
    }

    public function testUserRelationships(): void
    {
        // Create user and related data
        $user = User::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => password_hash('secret', PASSWORD_BCRYPT),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Test relationships (assuming posts relationship exists)
        $this->assertInstanceOf(\Hyperf\Database\Model\Collection::class, $user->posts);
        $this->assertEquals(0, $user->posts->count());
    }

    public function testUserValidation(): void
    {
        // Test unique email constraint
        User::create([
            'name' => 'First User',
            'email' => 'test@example.com',
            'password' => password_hash('secret', PASSWORD_BCRYPT),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->expectException(\Hyperf\Database\Exception\QueryException::class);

        User::create([
            'name' => 'Second User',
            'email' => 'test@example.com', // Duplicate email
            'password' => password_hash('secret', PASSWORD_BCRYPT),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
```

### Transaction Service Test

```php
<?php

declare(strict_types=1);

namespace HyperfTest\Cases\Feature\Service;

use App\Service\TransactionService;
use App\Model\User;
use App\Model\Account;
use HyperfTest\Cases\TestCase;
use Hyperf\Testing\Traits\RefreshDatabase;
use Hyperf\DbConnection\Db;

class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    private TransactionService $transactionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->refreshDatabase();
        $this->transactionService = $this->container->get(TransactionService::class);
    }

    public function testSuccessfulTransaction(): void
    {
        // Create test users and accounts
        $user1 = User::create([
            'name' => 'User 1',
            'email' => 'user1@test.com',
            'password' => password_hash('secret', PASSWORD_BCRYPT),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $user2 = User::create([
            'name' => 'User 2',
            'email' => 'user2@test.com',
            'password' => password_hash('secret', PASSWORD_BCRYPT),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $account1 = Account::create([
            'user_id' => $user1->id,
            'balance' => 1000.00,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $account2 = Account::create([
            'user_id' => $user2->id,
            'balance' => 500.00,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Test transaction
        $result = $this->transactionService->transfer($account1->id, $account2->id, 200.00);

        $this->assertTrue($result);

        // Verify balances changed correctly
        $account1->refresh();
        $account2->refresh();

        $this->assertEquals(800.00, $account1->balance);
        $this->assertEquals(700.00, $account2->balance);
    }

    public function testFailedTransaction(): void
    {
        // Create test account with insufficient funds
        $user = User::create([
            'name' => 'User',
            'email' => 'user@test.com',
            'password' => password_hash('secret', PASSWORD_BCRYPT),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $account1 = Account::create([
            'user_id' => $user->id,
            'balance' => 100.00,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $account2 = Account::create([
            'user_id' => $user->id,
            'balance' => 0.00,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Attempt transaction with insufficient funds
        $result = $this->transactionService->transfer($account1->id, $account2->id, 200.00);

        $this->assertFalse($result);

        // Verify balances unchanged
        $account1->refresh();
        $account2->refresh();

        $this->assertEquals(100.00, $account1->balance);
        $this->assertEquals(0.00, $account2->balance);
    }

    public function testTransactionRollback(): void
    {
        $initialUserCount = User::count();
        $initialAccountCount = Account::count();

        try {
            Db::transaction(function () {
                // Create user (this should succeed)
                $user = User::create([
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                    'password' => password_hash('secret', PASSWORD_BCRYPT),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                // This should cause an exception and rollback
                throw new \Exception('Force rollback');
            });
        } catch (\Exception $e) {
            // Expected exception
        }

        // Verify rollback occurred
        $this->assertEquals($initialUserCount, User::count());
        $this->assertEquals($initialAccountCount, Account::count());
    }
}
```

### Database Factory Test

```php
<?php

declare(strict_types=1);

namespace HyperfTest\Cases\Feature;

use App\Model\User;
use HyperfTest\Cases\TestCase;
use Hyperf\Testing\Traits\RefreshDatabase;

class UserFactoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->refreshDatabase();
    }

    public function testUserFactory(): void
    {
        // Using model factory (if implemented)
        $users = User::factory()->count(5)->create();

        $this->assertCount(5, $users);
        $this->assertEquals(5, User::count());

        foreach ($users as $user) {
            $this->assertInstanceOf(User::class, $user);
            $this->assertNotEmpty($user->name);
            $this->assertNotEmpty($user->email);
            $this->assertTrue(filter_var($user->email, FILTER_VALIDATE_EMAIL) !== false);
        }
    }

    public function testUserFactoryWithAttributes(): void
    {
        $user = User::factory()->create([
            'name' => 'Custom Name',
            'email' => 'custom@example.com'
        ]);

        $this->assertEquals('Custom Name', $user->name);
        $this->assertEquals('custom@example.com', $user->email);
    }
}
```

---

## 5. Base Test Classes for Reuse

### Custom TestCase Base

```php
<?php

declare(strict_types=1);

namespace HyperfTest\Cases;

use Hyperf\Testing\TestCase as BaseTestCase;
use Hyperf\Testing\Traits\RefreshDatabase;
use Psr\Container\ContainerInterface;

abstract class TestCase extends BaseTestCase
{
    protected ContainerInterface $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = $this->getContainer();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        \Mockery::close();
    }

    /**
     * Helper method to assert database has record
     */
    protected function assertDatabaseHas(string $table, array $data): void
    {
        $query = \Hyperf\DbConnection\Db::table($table);
        
        foreach ($data as $key => $value) {
            $query->where($key, $value);
        }
        
        $this->assertTrue($query->exists(), 
            "Failed to find matching record in table [{$table}]"
        );
    }

    /**
     * Helper method to assert database missing record
     */
    protected function assertDatabaseMissing(string $table, array $data): void
    {
        $query = \Hyperf\DbConnection\Db::table($table);
        
        foreach ($data as $key => $value) {
            $query->where($key, $value);
        }
        
        $this->assertFalse($query->exists(), 
            "Found unexpected record in table [{$table}]"
        );
    }

    /**
     * Get a service from the container
     */
    protected function get(string $abstract)
    {
        return $this->container->get($abstract);
    }

    /**
     * Mock a service in the container
     */
    protected function mock(string $abstract, \Closure $mock = null)
    {
        $mockedInstance = \Mockery::mock($abstract);
        
        if ($mock) {
            $mock($mockedInstance);
        }
        
        $this->container->set($abstract, $mockedInstance);
        
        return $mockedInstance;
    }
}
```

### HTTP Test Base

```php
<?php

declare(strict_types=1);

namespace HyperfTest\Cases\Feature;

use HyperfTest\Cases\TestCase;
use Hyperf\Testing\Traits\Http\TestResponse;

abstract class HttpTestCase extends TestCase
{
    /**
     * Make a GET request
     */
    protected function get(string $uri, array $headers = []): TestResponse
    {
        return $this->json('GET', $uri, [], $headers);
    }

    /**
     * Make a POST request
     */
    protected function post(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->json('POST', $uri, $data, $headers);
    }

    /**
     * Make a PUT request
     */
    protected function put(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->json('PUT', $uri, $data, $headers);
    }

    /**
     * Make a DELETE request
     */
    protected function delete(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->json('DELETE', $uri, $data, $headers);
    }

    /**
     * Assert JSON response structure
     */
    protected function assertJsonStructure(TestResponse $response, array $structure): void
    {
        $responseData = $response->json();
        $this->assertArrayStructure($structure, $responseData);
    }

    private function assertArrayStructure(array $structure, array $data, string $path = ''): void
    {
        foreach ($structure as $key => $value) {
            if (is_array($value)) {
                $this->assertArrayHasKey($key, $data, 
                    "Missing key [{$key}] in response" . ($path ? " at path [{$path}]" : "")
                );
                $this->assertArrayStructure($value, $data[$key], $path . '.' . $key);
            } else {
                $this->assertArrayHasKey($value, $data, 
                    "Missing key [{$value}] in response" . ($path ? " at path [{$path}]" : "")
                );
            }
        }
    }
}
```

---

## 6. Integration Test Examples

### API Controller Test

```php
<?php

declare(strict_types=1);

namespace HyperfTest\Cases\Feature\Controller;

use App\Model\User;
use HyperfTest\Cases\Feature\HttpTestCase;
use Hyperf\Testing\Traits\RefreshDatabase;

class UserControllerTest extends HttpTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->refreshDatabase();
    }

    public function testGetUsers(): void
    {
        // Create test users
        User::factory()->count(3)->create();

        $response = $this->get('/api/users');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'name', 
                             'email',
                             'created_at',
                             'updated_at'
                         ]
                     ]
                 ]);

        $this->assertCount(3, $response->json('data'));
    }

    public function testCreateUser(): void
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123'
        ];

        $response = $this->post('/api/users', $userData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'name',
                         'email',
                         'created_at',
                         'updated_at'
                     ]
                 ]);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);
    }

    public function testUpdateUser(): void
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com'
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com'
        ];

        $response = $this->put("/api/users/{$user->id}", $updateData);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'name' => 'Updated Name',
                     'email' => 'updated@example.com'
                 ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com'
        ]);
    }

    public function testDeleteUser(): void
    {
        $user = User::factory()->create();

        $response = $this->delete("/api/users/{$user->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id
        ]);
    }

    public function testValidationErrors(): void
    {
        $invalidData = [
            'name' => '',  // Required
            'email' => 'invalid-email',  // Invalid format
            'password' => '123'  // Too short
        ];

        $response = $this->post('/api/users', $invalidData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name', 'email', 'password']);
    }
}
```

---

This comprehensive guide covers all major aspects of testing Hyperf applications. The examples demonstrate proper setup, mocking, async testing, database transactions, and integration testing patterns specific to the Hyperf framework.