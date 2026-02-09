# Hyperf Testing Common Pitfalls and Solutions

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

### Hyperf-Specific Strict Mode
- **co-phpunit strict mode** - Run with `--strict-global-state`
- **No test interdependencies** - Each test must be independent
- **Environment-aware config** - CI vs local auto-detection required
- **Docker service validation** - Verify connectivity before running tests
- **Database cleanup mandatory** - RefreshDatabase or transactions enforced

---

## 🚨 Essential Guide to Avoiding Hyperf Testing Issues

This document covers the most common problems developers encounter when testing Hyperf applications and provides practical solutions.

---

## 1. Coroutine Context Issues

### ❌ Problem: "Coroutine context not found" Errors

**Symptoms:**
- Tests fail with "Context not found" errors
- Random failures in async operations
- Container services not accessible
- Database connections failing

**Common Causes:**
```php
// ❌ WRONG: Using regular phpunit
"scripts": {
    "test": "phpunit"
}

// ❌ WRONG: Bootstrap doesn't initialize coroutine context
// test/bootstrap.php
require_once dirname(__DIR__) . '/vendor/autoload.php';
```

**✅ Solutions:**

```php
// ✅ CORRECT: Use co-phpunit
"scripts": {
    "test": "vendor/bin/co-phpunit",
    "test:coverage": "vendor/bin/co-phpunit --coverage-html coverage"
}

// ✅ CORRECT: Bootstrap with coroutine context
// test/bootstrap.php
<?php
declare(strict_types=1);

use Hyperf\Testing\Bootstrap;

require_once dirname(__DIR__) . '/vendor/autoload.php';

Bootstrap::init();
```

**Additional Fixes:**
- Ensure Swoole extension is properly loaded
- Check that `co-phpunit` is installed via hyperf/testing
- Verify bootstrap file is referenced in phpunit.xml

---

## 2. Container Configuration Problems

### ❌ Problem: Services Not Injectable in Tests

**Symptoms:**
- Dependency injection fails in test classes
- "Class not found" errors for services
- Configuration values not loading
- Aspect-oriented programming not working

**Common Causes:**
```php
// ❌ WRONG: Trying to use container before initialization
class MyTest extends TestCase
{
    private MyService $service;
    
    public function __construct() // Called before setUp
    {
        $this->service = $this->container->get(MyService::class); // FAILS
    }
}

// ❌ WRONG: Missing container configuration
// Missing proper bootstrap or container setup
```

**✅ Solutions:**

```php
// ✅ CORRECT: Use setUp method
class MyTest extends TestCase
{
    private MyService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->container->get(MyService::class);
    }
}

// ✅ CORRECT: Proper bootstrap configuration
// test/bootstrap.php
<?php
declare(strict_types=1);

use Hyperf\Testing\Bootstrap;
use Hyperf\Utils\ApplicationContext;

require_once dirname(__DIR__) . '/vendor/autoload.php';

Bootstrap::init();

// Ensure container is properly initialized
$container = ApplicationContext::getContainer();
```

**Environment-Aware Configuration:**
```php
// config/autoload/testing.php
<?php
declare(strict_types=1);

return [
    'enable' => true,
    'environment' => 'testing',
    'database' => [
        'default' => [
            // Environment-aware host detection for CI vs Local
            'host' => env('TEST_DB_HOST', env('CI') ? 'postgres' : '127.0.0.1'),
            'port' => env('TEST_DB_PORT', env('CI') ? 5432 : 3306),
            'database' => env('TEST_DB_DATABASE', 'test_db'),
            'username' => env('TEST_DB_USERNAME', env('CI') ? 'postgres' : 'root'),
            'password' => env('TEST_DB_PASSWORD', env('CI') ? 'postgres' : ''),
            'driver' => env('TEST_DB_DRIVER', env('CI') ? 'pgsql' : 'mysql'),
            'pool' => [
                'min_connections' => 1,
                'max_connections' => env('CI') ? 5 : 2, // More connections in CI
                'connect_timeout' => 60.0,
                'wait_timeout' => 3.0,
                'heartbeat' => -1,
                'max_idle_time' => 60.0,
            ],
        ]
    ],
    'redis' => [
        'default' => [
            'host' => env('REDIS_HOST', env('CI') ? 'redis' : '127.0.0.1'),
            'port' => env('REDIS_PORT', 6379),
            'database' => env('REDIS_DB', 15), // Use high-numbered DB for tests
        ]
    ]
];
```

---

## 3. Database Testing Issues

### ❌ Problem: Database State Persists Between Tests

**Symptoms:**
- Tests pass individually but fail when run together
- Unexpected data in tests
- Foreign key constraint errors
- Database connection pool issues

**Common Causes:**
```php
// ❌ WRONG: No transaction rollback
class UserTest extends TestCase
{
    public function testCreateUser(): void
    {
        User::create(['name' => 'John', 'email' => 'john@test.com']);
        // Data persists to next test
    }
}

// ❌ WRONG: Using production database
// .env.testing missing or incorrect
DB_DATABASE=production_db  // DANGEROUS!
```

**✅ Solutions:**

```php
// ✅ CORRECT: Use RefreshDatabase trait
use Hyperf\Testing\Traits\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->refreshDatabase();
    }
    
    public function testCreateUser(): void
    {
        User::create(['name' => 'John', 'email' => 'john@test.com']);
        // Database automatically cleaned after test
    }
}

// ✅ CORRECT: Database transactions
class UserServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Db::beginTransaction();
    }
    
    protected function tearDown(): void
    {
        Db::rollback();
        parent::tearDown();
    }
}
```

**Environment-Aware Database Configuration:**

**Local Environment (.env.testing):**
```env
# Local development testing configuration
APP_ENV=testing
CI=false

# Database - Local MySQL/PostgreSQL
DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=test_database
DB_USERNAME=test_user
DB_PASSWORD=test_password

# Alternative for PostgreSQL locally
# DB_DRIVER=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_USERNAME=postgres
# DB_PASSWORD=postgres

# Redis - Local
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_DB=15
```

**CI Environment (.env.ci):**
```env
# Continuous Integration configuration
APP_ENV=testing
CI=true

# Database - Docker service names
DB_DRIVER=pgsql
DB_HOST=postgres  # Docker service name
DB_PORT=5432
DB_DATABASE=test_database
DB_USERNAME=postgres
DB_PASSWORD=postgres

# Alternative MySQL in CI
# DB_DRIVER=mysql
# DB_HOST=mysql  # Docker service name
# DB_PORT=3306
# DB_USERNAME=test_user
# DB_PASSWORD=test_password

# Redis - Docker service name
REDIS_HOST=redis  # Docker service name
REDIS_PORT=6379
REDIS_DB=15
```

**Smart Database Configuration (config/autoload/databases.php):**
```php
<?php
declare(strict_types=1);

use function Hyperf\Support\env;

$isCI = env('CI', false);
$isTesting = env('APP_ENV') === 'testing';

return [
    'default' => [
        'driver' => env('DB_DRIVER', $isCI ? 'pgsql' : 'mysql'),

        // Environment-aware host resolution
        'host' => env('DB_HOST', function() use ($isCI) {
            if ($isCI) {
                return env('DB_DRIVER', 'pgsql') === 'pgsql' ? 'postgres' : 'mysql';
            }
            return '127.0.0.1';
        }),

        // Environment-aware port selection
        'port' => env('DB_PORT', function() use ($isCI) {
            $driver = env('DB_DRIVER', $isCI ? 'pgsql' : 'mysql');
            return $driver === 'pgsql' ? 5432 : 3306;
        }),

        'database' => env('DB_DATABASE', 'test_database'),

        // Environment-aware credentials
        'username' => env('DB_USERNAME', function() use ($isCI) {
            $driver = env('DB_DRIVER', $isCI ? 'pgsql' : 'mysql');
            return $driver === 'pgsql' ? 'postgres' : 'root';
        }),

        'password' => env('DB_PASSWORD', function() use ($isCI) {
            $driver = env('DB_DRIVER', $isCI ? 'pgsql' : 'mysql');
            return $driver === 'pgsql' ? 'postgres' : '';
        }),

        'charset' => env('DB_CHARSET', 'utf8mb4'),
        'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
        'prefix' => env('DB_PREFIX', ''),

        'pool' => [
            'min_connections' => 1,
            'max_connections' => $isTesting ? ($isCI ? 5 : 2) : 10,
            'connect_timeout' => 60.0,
            'wait_timeout' => 3.0,
            'heartbeat' => -1,
            'max_idle_time' => 60.0,
        ],

        'commands' => [
            'gen:model' => [
                'path' => 'app/Model',
                'force_casts' => true,
                'inheritance' => 'Model',
            ],
        ],
    ],
];
```

**Docker Compose for Testing (docker-compose.test.yml):**
```yaml
version: '3.8'
services:
  app:
    build: .
    environment:
      - APP_ENV=testing
      - CI=true
      - DB_HOST=postgres
      - REDIS_HOST=redis
    depends_on:
      - postgres
      - redis

  postgres:
    image: postgres:15-alpine
    environment:
      POSTGRES_DB: test_database
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: postgres
    ports:
      - "5432:5432"
    tmpfs:
      - /var/lib/postgresql/data  # In-memory for faster tests

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: test_database
      MYSQL_USER: test_user
      MYSQL_PASSWORD: test_password
      MYSQL_ROOT_PASSWORD: root
    ports:
      - "3306:3306"
    tmpfs:
      - /var/lib/mysql  # In-memory for faster tests

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"
    tmpfs:
      - /data  # In-memory for faster tests
```

**GitHub Actions Configuration (.github/workflows/test.yml):**
```yaml
name: Tests
on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    services:
      postgres:
        image: postgres:15
        env:
          POSTGRES_DB: test_database
          POSTGRES_USER: postgres
          POSTGRES_PASSWORD: postgres
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5
        ports:
          - 5432:5432

      redis:
        image: redis:7
        options: >-
          --health-cmd "redis-cli ping"
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5
        ports:
          - 6379:6379

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: swoole, pdo, pdo_pgsql, redis

      - name: Install dependencies
        run: composer install --no-progress --no-suggest

      - name: Copy environment file
        run: cp .env.ci .env.testing

      - name: Run tests
        env:
          CI: true
          DB_HOST: localhost  # GitHub Actions uses localhost
          REDIS_HOST: localhost
        run: vendor/bin/co-phpunit
```

**Environment Detection Utilities:**
```php
// app/Utils/EnvironmentDetector.php
<?php
declare(strict_types=1);

namespace App\Utils;

use function Hyperf\Support\env;

class EnvironmentDetector
{
    public static function isCI(): bool
    {
        return env('CI', false) || env('GITHUB_ACTIONS', false) || env('GITLAB_CI', false);
    }

    public static function isTesting(): bool
    {
        return env('APP_ENV') === 'testing';
    }

    public static function isLocal(): bool
    {
        return !self::isCI() && self::isTesting();
    }

    public static function getDbDefaults(): array
    {
        if (self::isCI()) {
            return [
                'driver' => 'pgsql',
                'host' => 'postgres',
                'port' => 5432,
                'username' => 'postgres',
                'password' => 'postgres',
            ];
        }

        return [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => 3306,
            'username' => 'root',
            'password' => '',
        ];
    }

    public static function getRedisDefaults(): array
    {
        return [
            'host' => self::isCI() ? 'redis' : '127.0.0.1',
            'port' => 6379,
            'database' => 15, // Always use high number for tests
        ];
    }
}
```

**Advanced Environment Configuration Helper:**
```php
// config/autoload/environment.php
<?php
declare(strict_types=1);

use App\Utils\EnvironmentDetector;

return [
    'testing' => [
        'database' => [
            'default' => array_merge(EnvironmentDetector::getDbDefaults(), [
                'database' => env('DB_DATABASE', 'test_database'),
                'pool' => [
                    'min_connections' => 1,
                    'max_connections' => EnvironmentDetector::isCI() ? 5 : 2,
                    'connect_timeout' => 60.0,
                    'wait_timeout' => 3.0,
                ],
            ]),
        ],
        'redis' => [
            'default' => EnvironmentDetector::getRedisDefaults(),
        ],
        'cache' => [
            'default' => [
                'driver' => 'memory', // Use in-memory cache for tests
            ],
        ],
        'logger' => [
            'default' => [
                'level' => EnvironmentDetector::isCI() ? 'error' : 'debug',
                'handlers' => [
                    [
                        'class' => 'Monolog\Handler\StreamHandler',
                        'constructor' => [
                            'stream' => 'php://stderr',
                            'level' => 'debug',
                        ],
                    ],
                ],
            ],
        ],
    ],
];
```

---

## 4. Mocking and Stubbing Issues

### ❌ Problem: Hyperf Services Not Mockable

**Symptoms:**
- Mockery expectations not working
- Services still calling real methods
- Cannot isolate units under test
- Mock objects not registered in container

**Common Causes:**
```php
// ❌ WRONG: Mock not registered in container
class ServiceTest extends TestCase
{
    public function testMethod(): void
    {
        $mock = Mockery::mock(ExternalService::class);
        $mock->shouldReceive('getData')->andReturn('test');
        
        // Mock not in container - real service will be used
        $service = $this->container->get(MyService::class);
        $result = $service->process(); // Calls real ExternalService
    }
}

// ❌ WRONG: Mocking final classes or static methods
$mock = Mockery::mock(FinalClass::class); // Won't work
$mock = Mockery::mock(StaticMethodClass::class); // Limited functionality
```

**✅ Solutions:**

```php
// ✅ CORRECT: Register mock in container
class ServiceTest extends TestCase
{
    public function testMethod(): void
    {
        $mock = Mockery::mock(ExternalService::class);
        $mock->shouldReceive('getData')->andReturn('test');
        
        // Register mock in container
        $this->container->set(ExternalService::class, $mock);
        
        $service = $this->container->get(MyService::class);
        $result = $service->process(); // Uses mock
        
        $this->assertEquals('expected', $result);
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

// ✅ CORRECT: Mock using interfaces
interface ExternalServiceInterface
{
    public function getData(): array;
}

class ExternalService implements ExternalServiceInterface
{
    public function getData(): array { /* real implementation */ }
}

// In test:
$mock = Mockery::mock(ExternalServiceInterface::class);
$this->container->set(ExternalServiceInterface::class, $mock);
```

**Complex Mocking Scenarios:**
```php
// ✅ CORRECT: Mock HTTP client
class ApiServiceTest extends TestCase
{
    public function testApiCall(): void
    {
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode(['status' => 'success']))
        ]);
        
        $client = new Client(['handler' => HandlerStack::create($mockHandler)]);
        
        $mockClientFactory = Mockery::mock(ClientFactory::class);
        $mockClientFactory->shouldReceive('create')->andReturn($client);
        
        $this->container->set(ClientFactory::class, $mockClientFactory);
        
        $service = $this->container->get(ApiService::class);
        $result = $service->fetchData();
        
        $this->assertEquals(['status' => 'success'], $result);
    }
}
```

---

## 5. Async/Coroutine Testing Pitfalls

### ❌ Problem: Async Operations Not Testable

**Symptoms:**
- Async methods hang in tests
- Coroutine context lost in async operations
- Race conditions in concurrent tests
- Timeout issues in async operations

**Common Causes:**
```php
// ❌ WRONG: Not handling async properly
class AsyncTest extends TestCase
{
    public function testAsync(): void
    {
        $service = $this->container->get(AsyncService::class);
        
        // This may hang or fail
        $result = $service->performAsyncOperation();
        
        $this->assertEquals('expected', $result);
    }
}

// ❌ WRONG: Race conditions in parallel tests
public function testParallel(): void
{
    $results = [];
    
    // This can cause race conditions
    Coroutine::create(function () use (&$results) {
        $results[] = $this->service->process(1);
    });
    
    Coroutine::create(function () use (&$results) {
        $results[] = $this->service->process(2);
    });
    
    // Results may not be ready
    $this->assertCount(2, $results); // May fail
}
```

**✅ Solutions:**

```php
// ✅ CORRECT: Use WaitGroup for coordination
use Swoole\Coroutine\WaitGroup;

class AsyncTest extends TestCase
{
    public function testParallelOperations(): void
    {
        $wg = new WaitGroup();
        $results = [];
        
        $tasks = ['task1', 'task2', 'task3'];
        
        foreach ($tasks as $task) {
            $wg->add();
            Coroutine::create(function () use ($task, &$results, $wg) {
                $results[$task] = $this->service->processAsync($task);
                $wg->done();
            });
        }
        
        $wg->wait(); // Wait for all tasks to complete
        
        $this->assertCount(3, $results);
        foreach ($tasks as $task) {
            $this->assertArrayHasKey($task, $results);
        }
    }
}

// ✅ CORRECT: Use Hyperf Parallel for better control
use Hyperf\Utils\Parallel;

class AsyncTest extends TestCase
{
    public function testHyperfParallel(): void
    {
        $parallel = new Parallel();
        
        $parallel->add(function () {
            return $this->service->processAsync('item1');
        }, 'task1');
        
        $parallel->add(function () {
            return $this->service->processAsync('item2');
        }, 'task2');
        
        $results = $parallel->wait();
        
        $this->assertArrayHasKey('task1', $results);
        $this->assertArrayHasKey('task2', $results);
    }
}
```

**Timeout Handling:**
```php
// ✅ CORRECT: Handle timeouts properly
class AsyncTest extends TestCase
{
    public function testWithTimeout(): void
    {
        $startTime = microtime(true);
        
        try {
            $result = $this->service->asyncOperationWithTimeout(5.0); // 5 second timeout
            $this->assertNotNull($result);
        } catch (TimeoutException $e) {
            $this->fail('Operation should not timeout');
        }
        
        $executionTime = microtime(true) - $startTime;
        $this->assertLessThan(5.0, $executionTime);
    }
}
```

---

## 6. HTTP Testing Problems

### ❌ Problem: HTTP Client Mocking Failures

**Symptoms:**
- HTTP requests still hit real endpoints
- Cannot control HTTP responses in tests
- Network timeouts in test environment
- External API dependencies break tests

**Common Causes:**
```php
// ❌ WRONG: Real HTTP calls in tests
class ExternalApiTest extends TestCase
{
    public function testApiCall(): void
    {
        $service = $this->container->get(ExternalApiService::class);
        
        // This makes real HTTP call - BAD!
        $result = $service->fetchUserData(123);
        
        $this->assertNotNull($result);
    }
}
```

**✅ Solutions:**

```php
// ✅ CORRECT: Mock HTTP client
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class ExternalApiTest extends TestCase
{
    public function testApiCall(): void
    {
        // Create mock response
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode([
                'id' => 123,
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ]))
        ]);
        
        $client = new Client(['handler' => HandlerStack::create($mockHandler)]);
        
        // Inject mocked client
        $mockClientFactory = Mockery::mock(ClientFactory::class);
        $mockClientFactory->shouldReceive('create')->andReturn($client);
        $this->container->set(ClientFactory::class, $mockClientFactory);
        
        $service = $this->container->get(ExternalApiService::class);
        $result = $service->fetchUserData(123);
        
        $this->assertEquals(123, $result['id']);
        $this->assertEquals('John Doe', $result['name']);
    }
}
```

---

## 7. Memory and Performance Issues

### ❌ Problem: Tests Consume Too Much Memory

**Symptoms:**
- Out of memory errors during test execution
- Tests become progressively slower
- Memory leaks between tests
- Container services not garbage collected

**Common Causes:**
```php
// ❌ WRONG: Not cleaning up large objects
class PerformanceTest extends TestCase
{
    private $largeDataSet;
    
    public function testLargeDataProcessing(): void
    {
        $this->largeDataSet = $this->generateLargeDataSet(100000);
        
        $result = $this->service->processLargeData($this->largeDataSet);
        
        // largeDataSet not cleaned up - memory leak
    }
}

// ❌ WRONG: Container services accumulating
class ServiceTest extends TestCase
{
    public function testMultipleServices(): void
    {
        for ($i = 0; $i < 1000; $i++) {
            $service = $this->container->get(SomeService::class);
            $service->doSomething();
            // Services accumulate in container
        }
    }
}
```

**✅ Solutions:**

```php
// ✅ CORRECT: Proper cleanup in tearDown
class PerformanceTest extends TestCase
{
    private $largeDataSet;
    
    public function testLargeDataProcessing(): void
    {
        $this->largeDataSet = $this->generateLargeDataSet(100000);
        
        $result = $this->service->processLargeData($this->largeDataSet);
        
        $this->assertNotEmpty($result);
    }
    
    protected function tearDown(): void
    {
        $this->largeDataSet = null; // Explicit cleanup
        gc_collect_cycles(); // Force garbage collection if needed
        parent::tearDown();
    }
}

// ✅ CORRECT: Memory-efficient data generation
class DataTest extends TestCase
{
    public function testDataProcessing(): void
    {
        // Use generators for large datasets
        $data = $this->generateDataIterator(1000);
        
        $count = 0;
        foreach ($data as $item) {
            $this->service->processItem($item);
            $count++;
        }
        
        $this->assertEquals(1000, $count);
    }
    
    private function generateDataIterator(int $count): \Generator
    {
        for ($i = 0; $i < $count; $i++) {
            yield ['id' => $i, 'data' => "item_{$i}"];
        }
    }
}
```

---

## 8. Configuration and Environment Issues

### ❌ Problem: Test Environment Configuration

**Symptoms:**
- Tests using production configuration
- Environment variables not loading correctly
- Cache not cleared between tests
- Wrong database connections

**Common Causes:**
```php
// ❌ WRONG: Missing test environment files
// No .env.testing file
// Wrong environment detection in config files

// ❌ WRONG: Cache not cleared
class ConfigTest extends TestCase
{
    public function testConfig(): void
    {
        // Old cached config may be used
        $config = $this->container->get(ConfigInterface::class);
        $value = $config->get('app.name');
        
        $this->assertEquals('Test App', $value);
    }
}
```

**✅ Solutions:**

```env
# ✅ CORRECT: .env.testing file
APP_ENV=testing
APP_NAME="Test Application"
APP_DEBUG=true

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=test_database
DB_USERNAME=test_user
DB_PASSWORD=test_password

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_DB=1  # Use different Redis database for tests
```

```php
// ✅ CORRECT: Environment-specific configuration
// config/autoload/server.php
<?php
declare(strict_types=1);

return [
    'mode' => SWOOLE_PROCESS,
    'servers' => [
        [
            'name' => 'http',
            'type' => Server::SERVER_HTTP,
            'host' => '0.0.0.0',
            'port' => env('APP_ENV') === 'testing' ? 9502 : 9501, // Different port for tests
            'sock_type' => SWOOLE_SOCK_TCP,
        ],
    ],
];
```

```php
// ✅ CORRECT: Clear cache in tests
class ConfigTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear configuration cache
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        
        // Clear application cache if needed
        $this->clearApplicationCache();
    }
    
    private function clearApplicationCache(): void
    {
        $cacheManager = $this->container->get(CacheManager::class);
        $cacheManager->getDriver()->flush();
    }
}
```

---

## 9. Event and Listener Testing

### ❌ Problem: Events Not Firing in Tests

**Symptoms:**
- Event listeners not executing
- Events fired but not captured
- Side effects not occurring as expected

**✅ Solutions:**

```php
// ✅ CORRECT: Test events explicitly
use Hyperf\Event\EventDispatcher;

class EventTest extends TestCase
{
    public function testEventFiring(): void
    {
        $dispatcher = $this->container->get(EventDispatcher::class);
        
        $eventFired = false;
        $dispatcher->addListener(UserCreated::class, function ($event) use (&$eventFired) {
            $eventFired = true;
            $this->assertEquals('john@example.com', $event->user->email);
        });
        
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);
        
        $this->assertTrue($eventFired);
    }
}
```

---

## 10. Debugging Test Failures

### ✅ Debugging Strategies

```php
// ✅ CORRECT: Debug output for tests only
class DebuggingTest extends TestCase
{
    private function debug($message): void
    {
        if (env('TEST_DEBUG', false)) {
            fwrite(STDERR, print_r($message, true) . "\n");
        }
    }
    
    public function testWithDebugging(): void
    {
        $data = $this->service->processData();
        
        $this->debug("Processed data: " . json_encode($data));
        
        $this->assertNotEmpty($data);
    }
}
```

```bash
# Run tests with debugging enabled
TEST_DEBUG=1 vendor/bin/co-phpunit
```

---

## 11. Quick Fixes Checklist

### When Tests Fail, Check These First:

1. **✅ Use co-phpunit instead of phpunit**
2. **✅ Bootstrap properly initializes Hyperf container**
3. **✅ Environment-aware database configuration (CI vs local)**
4. **✅ Docker service names configured for CI environments**
5. **✅ Separate test database and Redis DB configured**
6. **✅ Environment variables loaded for tests (.env.testing exists)**
7. **✅ Mocks registered in container**
8. **✅ Database transactions or RefreshDatabase trait used**
9. **✅ Async operations use proper coordination (WaitGroup/Parallel)**
10. **✅ Memory cleaned up in tearDown methods**
11. **✅ No debug output (var_dump, echo) in test code**
12. **✅ Configuration cache cleared if needed**
13. **✅ CI environment detection working (env('CI') returns correct value)**
14. **✅ Database pool connections appropriate for environment**

### Emergency Debug Commands:

```bash
# Check if co-phpunit is available
ls -la vendor/bin/co-phpunit

# Verify Swoole extension
php -m | grep swoole

# Test bootstrap file directly
php test/bootstrap.php

# Verify environment configuration
php -r "require 'vendor/autoload.php'; echo 'CI: ' . (env('CI') ? 'true' : 'false') . PHP_EOL;"
php -r "require 'vendor/autoload.php'; echo 'APP_ENV: ' . env('APP_ENV') . PHP_EOL;"

# Test database connection in current environment
php -r "
require 'vendor/autoload.php';
use Hyperf\Utils\ApplicationContext;
try {
    \$container = ApplicationContext::getContainer();
    \$db = \$container->get(Hyperf\Database\ConnectionInterface::class);
    echo 'Database connection: OK' . PHP_EOL;
    echo 'Driver: ' . \$db->getDriverName() . PHP_EOL;
} catch (Exception \$e) {
    echo 'Database connection failed: ' . \$e->getMessage() . PHP_EOL;
}
"

# Test Redis connection
php -r "
require 'vendor/autoload.php';
use Hyperf\Utils\ApplicationContext;
try {
    \$container = ApplicationContext::getContainer();
    \$redis = \$container->get(Hyperf\Redis\RedisFactory::class)->get('default');
    \$redis->ping();
    echo 'Redis connection: OK' . PHP_EOL;
} catch (Exception \$e) {
    echo 'Redis connection failed: ' . \$e->getMessage() . PHP_EOL;
}
"

# Run single test with verbose output
vendor/bin/co-phpunit --testdox --verbose tests/Unit/ExampleTest.php

# Check container configuration
vendor/bin/co-phpunit --filter testContainerConfiguration

# Test environment detection
php -r "
require 'vendor/autoload.php';
require 'app/Utils/EnvironmentDetector.php';
use App\Utils\EnvironmentDetector;
echo 'Is CI: ' . (EnvironmentDetector::isCI() ? 'true' : 'false') . PHP_EOL;
echo 'Is Testing: ' . (EnvironmentDetector::isTesting() ? 'true' : 'false') . PHP_EOL;
echo 'Is Local: ' . (EnvironmentDetector::isLocal() ? 'true' : 'false') . PHP_EOL;
print_r(EnvironmentDetector::getDbDefaults());
"

# Check Docker service connectivity (in CI)
if [ "$CI" = "true" ]; then
  ping -c 1 postgres || echo "Cannot reach postgres service"
  ping -c 1 redis || echo "Cannot reach redis service"
fi
```

---

By following these solutions and avoiding these common pitfalls, you'll have a robust and reliable Hyperf testing setup that properly handles the framework's coroutine-based architecture.