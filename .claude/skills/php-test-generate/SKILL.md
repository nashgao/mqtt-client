---
allowed-tools: all
description: Generate PHP test cases for different testing scenarios using hyperf/testing patterns
---

## 🚨 MANDATORY: Rule Enforcement for Test Commands

**This command operates under the Rule Enforcement Framework**
**Reference: `/templates/agents/../../shared/skills/rule-enforcement-framework.md`**

**CRITICAL ENFORCEMENT RULES:**
- 🔒 **Scope Containment**: Only modify files within assigned test scope
- 🔒 **Test Type Separation**: NEVER convert between UnitTestCase and BaseIntegrationTestCase
- 🔒 **Verification Mandate**: Execute actual test commands (`composer test` or `composer test:integration`)
- 🔒 **Exit Code Validation**: Confirm zero exit codes before claiming success
- 🔒 **No Architecture Changes**: No framework modifications without explicit permission

**IMMEDIATE HALT CONDITIONS:**
- Cross-test-type contamination detected
- File modifications outside assigned scope
- Success claims without command execution verification
- Architectural changes attempted without permission

---

# PHP Test Case Generator

Generate comprehensive PHP test cases following hyperf/testing patterns with proper structure, mocking, and coroutine support.

## Generate Test Cases

### Unit Tests

Generate unit tests for specific classes:

```php
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Test\Cases\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use {{NAMESPACE}}\Test\TestCase;
use {{TARGET_CLASS}};
use Mockery;

/**
 * @internal
 */
#[Group('unit')]
#[CoversClass({{TARGET_CLASS}}::class)]
class {{TEST_CLASS_NAME}} extends TestCase
{
    private {{TARGET_CLASS}} ${{TARGET_INSTANCE}};

    protected function setUp(): void
    {
        parent::setUp();
        $this->{{TARGET_INSTANCE}} = new {{TARGET_CLASS}}();
    }

    public function testConstruct(): void
    {
        $instance = new {{TARGET_CLASS}}();
        $this->assertInstanceOf({{TARGET_CLASS}}::class, $instance);
    }

    // Add specific test methods for each public method
    {{TEST_METHODS}}
}
```

### Integration Tests

Generate integration tests with dependency injection:

```php
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Test\Cases\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use {{NAMESPACE}}\Test\TestCase;
use Hyperf\Testing\Concerns\RefreshDatabase;

/**
 * @internal
 */
#[Group('integration')]
#[CoversNothing]
class {{TEST_CLASS_NAME}} extends TestCase
{
    use RefreshDatabase;

    public function testIntegration(): void
    {
        // Integration test implementation
        $this->assertTrue(true);
    }

    protected function getEnvironment(): array
    {
        return [
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
        ];
    }
}
```

### Feature Tests

Generate feature/acceptance tests:

```php
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Test\Cases\Feature;

use PHPUnit\Framework\Attributes\Group;
use {{NAMESPACE}}\Test\TestCase;
use Hyperf\Testing\Client;

/**
 * @internal
 */
#[Group('feature')]
class {{TEST_CLASS_NAME}} extends TestCase
{
    public function testFeature(): void
    {
        $response = $this->client->get('{{ENDPOINT_PATH}}');
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertIsArray($data);
    }

    public function testFeatureWithAuthentication(): void
    {
        $token = $this->getAuthToken();
        
        $response = $this->client->get('{{ENDPOINT_PATH}}', [
            'headers' => [
                'Authorization' => "Bearer {$token}"
            ]
        ]);
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    private function getAuthToken(): string
    {
        // Implementation for getting auth token
        return 'test-token';
    }
}
```

## Test Generation Patterns

### Service Class Tests

For service classes with dependencies:

```php
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Test\Cases\Unit\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use {{NAMESPACE}}\Test\TestCase;
use {{SERVICE_CLASS}};
use {{REPOSITORY_CLASS}};
use {{VALIDATOR_CLASS}};
use Mockery\MockInterface;

#[Group('unit')]
#[CoversClass({{SERVICE_CLASS}}::class)]
class {{SERVICE_CLASS}}Test extends TestCase
{
    private {{SERVICE_CLASS}} $service;
    private MockInterface $repository;
    private MockInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = $this->createMock({{REPOSITORY_CLASS}}::class);
        $this->validator = $this->createMock({{VALIDATOR_CLASS}}::class);
        
        $this->service = new {{SERVICE_CLASS}}(
            $this->repository,
            $this->validator
        );
    }

    public function testCreateSuccess(): void
    {
        $data = ['name' => 'Test', 'email' => 'test@example.com'];
        
        $this->validator
            ->shouldReceive('validate')
            ->once()
            ->with($data)
            ->andReturn(true);

        $this->repository
            ->shouldReceive('save')
            ->once()
            ->andReturn(true);

        $result = $this->service->create($data);
        
        $this->assertTrue($result);
    }

    public function testCreateValidationFailure(): void
    {
        $data = ['name' => '', 'email' => 'invalid'];
        
        $this->validator
            ->shouldReceive('validate')
            ->once()
            ->with($data)
            ->andThrow(new ValidationException('Invalid data'));

        $this->expectException(ValidationException::class);
        $this->service->create($data);
    }
}
```

### Repository Tests

For repository/DAO classes:

```php
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Test\Cases\Unit\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use {{NAMESPACE}}\Test\TestCase;
use {{REPOSITORY_CLASS}};
use Hyperf\Database\ConnectionInterface;
use Hyperf\Testing\Concerns\RefreshDatabase;
use Mockery\MockInterface;

#[Group('unit')]
#[CoversClass({{REPOSITORY_CLASS}}::class)]
class {{REPOSITORY_CLASS}}Test extends TestCase
{
    use RefreshDatabase;

    private {{REPOSITORY_CLASS}} $repository;
    private MockInterface $connection;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->connection = $this->createMock(ConnectionInterface::class);
        $this->repository = new {{REPOSITORY_CLASS}}($this->connection);
    }

    public function testFindById(): void
    {
        $id = 1;
        $expected = ['id' => $id, 'name' => 'Test'];
        
        $this->connection
            ->shouldReceive('selectOne')
            ->once()
            ->with('SELECT * FROM {{TABLE}} WHERE id = ?', [$id])
            ->andReturn($expected);

        $result = $this->repository->findById($id);
        
        $this->assertEquals($expected, $result);
    }

    public function testSave(): void
    {
        $data = ['name' => 'Test', 'email' => 'test@example.com'];
        
        $this->connection
            ->shouldReceive('insert')
            ->once()
            ->with(Mockery::type('string'), Mockery::type('array'))
            ->andReturn(true);

        $result = $this->repository->save($data);
        
        $this->assertTrue($result);
    }
}
```

### Controller Tests

For HTTP controllers:

```php
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Test\Cases\Feature\Controller;

use PHPUnit\Framework\Attributes\Group;
use {{NAMESPACE}}\Test\TestCase;
use {{CONTROLLER_CLASS}};

#[Group('feature')]
class {{CONTROLLER_CLASS}}Test extends TestCase
{
    public function testIndex(): void
    {
        $response = $this->client->get('{{ROUTE_PATH}}');
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('data', $data);
    }

    public function testShow(): void
    {
        $id = 1;
        $response = $this->client->get("{{ROUTE_PATH}}/{$id}");
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertEquals($id, $data['id']);
    }

    public function testStore(): void
    {
        $data = [
            'name' => 'Test Name',
            'email' => 'test@example.com'
        ];
        
        $response = $this->client->post('{{ROUTE_PATH}}', $data);
        
        $this->assertEquals(201, $response->getStatusCode());
        
        $responseData = json_decode($response->getBody()->getContents(), true);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertEquals($data['name'], $responseData['name']);
    }

    public function testUpdate(): void
    {
        $id = 1;
        $data = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com'
        ];
        
        $response = $this->client->put("{{ROUTE_PATH}}/{$id}", $data);
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals($data['name'], $responseData['name']);
    }

    public function testDestroy(): void
    {
        $id = 1;
        $response = $this->client->delete("{{ROUTE_PATH}}/{$id}");
        
        $this->assertEquals(204, $response->getStatusCode());
    }
}
```

## Specialized Test Patterns

### Coroutine Tests

For testing coroutine-based functionality (following space-actor patterns):

```php
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Test\Cases\Unit;

use PHPUnit\Framework\Attributes\Group;
use {{NAMESPACE}}\Test\TestCase;
use Swoole\Coroutine;

#[Group('coroutine')]
class CoroutineTest extends TestCase
{
    public function testCoroutineExecution(): void
    {
        $test = function () {
            // Test coroutine functionality
            $result = $this->asyncOperation();
            $this->assertEquals('async result', $result);
            
            // Test async operations with proper waiting
            $source = TestSource::fromElements(['a', 'b', 'c']);
            $sink = TestSink::auto();
            
            $source->subscribe($sink);
            $this->assertTrue($sink->waitForCompletion(2.0));
            
            $this->assertEquals(['a', 'b', 'c'], $sink->getReceivedElements());
        };

        if (Coroutine::getCid()) {
            $test();
        } else {
            Coroutine\run($test);
        }
    }

    public function testConcurrentExecution(): void
    {
        $test = function () {
            $results = [];
            
            // Create multiple concurrent operations
            for ($i = 0; $i < 3; ++$i) {
                Coroutine::create(function () use ($i, &$results) {
                    $result = $this->asyncOperation("task-{$i}");
                    $results[$i] = $result;
                });
            }
            
            // Wait for completion
            Coroutine::sleep(1.0);
            
            $this->assertCount(3, $results);
            $this->assertEquals('async result: task-0', $results[0]);
            $this->assertEquals('async result: task-1', $results[1]);
            $this->assertEquals('async result: task-2', $results[2]);
        };

        if (Coroutine::getCid()) {
            $test();
        } else {
            Coroutine\run($test);
        }
    }

    private function asyncOperation(string $input = 'default'): string
    {
        Coroutine::sleep(0.1);
        return "async result: {$input}";
    }
}
```

### Event Testing

For testing event-driven code:

```php
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Test\Cases\Unit\Event;

use PHPUnit\Framework\Attributes\Group;
use {{NAMESPACE}}\Test\TestCase;
use {{EVENT_CLASS}};
use {{LISTENER_CLASS}};
use Psr\EventDispatcher\EventDispatcherInterface;
use Mockery\MockInterface;

#[Group('event')]
class EventTest extends TestCase
{
    private MockInterface $dispatcher;
    private MockInterface $listener;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->listener = $this->createMock({{LISTENER_CLASS}}::class);
    }

    public function testEventDispatch(): void
    {
        $event = new {{EVENT_CLASS}}(['data' => 'test']);
        
        $this->dispatcher
            ->shouldReceive('dispatch')
            ->once()
            ->with($event)
            ->andReturn($event);

        $result = $this->dispatcher->dispatch($event);
        
        $this->assertSame($event, $result);
    }

    public function testEventListener(): void
    {
        $event = new {{EVENT_CLASS}}(['data' => 'test']);
        
        $this->listener
            ->shouldReceive('handle')
            ->once()
            ->with($event);

        $this->listener->handle($event);
    }
}
```

## Test Data and Fixtures

### Factory Pattern

Create test data factories:

```php
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Test\Fixtures;

class {{MODEL_NAME}}Factory
{
    public static function create(array $attributes = []): array
    {
        return array_merge([
            'id' => rand(1, 1000),
            'name' => 'Test Name',
            'email' => 'test@example.com',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $attributes);
    }

    public static function createMany(int $count, array $attributes = []): array
    {
        return array_map(fn() => self::create($attributes), range(1, $count));
    }
}
```

## Command Usage

Generate tests using this command with MANDATORY execution verification:

```bash
# Generate and verify unit test for a specific class
claude run php-test-generate --type=unit --class=App\\Service\\UserService

# After generation, ALWAYS run the generated tests to verify they work:
vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --filter "UserServiceTest" --testdox --colors=always 2>&1 | tee test-output.log
PHPUNIT_EXIT_CODE=${PIPESTATUS[0]}

if [ $PHPUNIT_EXIT_CODE -ne 0 ]; then
    echo "❌ CRITICAL: Generated test failed with exit code $PHPUNIT_EXIT_CODE"
    echo "Generated test has issues - fix before proceeding"
    exit $PHPUNIT_EXIT_CODE
fi

# Verify test execution indicators
if ! grep -E "(✓|OK \\([0-9]+ test|Tests: [0-9]+)" test-output.log; then
    echo "❌ CRITICAL: No test execution indicators found"
    exit 1
fi

echo "✅ Generated unit test passes successfully"

# Generate integration test with verification
claude run php-test-generate --type=integration --class=App\\Repository\\UserRepository

# MANDATORY: Run generated integration test
vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --filter "UserRepositoryTest" --testdox --colors=always 2>&1 | tee test-output.log
PHPUNIT_EXIT_CODE=${PIPESTATUS[0]}

if [ $PHPUNIT_EXIT_CODE -ne 0 ]; then
    echo "❌ CRITICAL: Generated integration test failed with exit code $PHPUNIT_EXIT_CODE"
    exit $PHPUNIT_EXIT_CODE
fi

echo "✅ Generated integration test passes successfully"

# Generate feature test with verification
claude run php-test-generate --type=feature --controller=App\\Controller\\UserController --route=/api/users

# MANDATORY: Run generated feature test
vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --filter "UserControllerTest" --testdox --colors=always 2>&1 | tee test-output.log
PHPUNIT_EXIT_CODE=${PIPESTATUS[0]}

if [ $PHPUNIT_EXIT_CODE -ne 0 ]; then
    echo "❌ CRITICAL: Generated feature test failed with exit code $PHPUNIT_EXIT_CODE"
    exit $PHPUNIT_EXIT_CODE
fi

echo "✅ Generated feature test passes successfully"

# Generate all test types with comprehensive verification
claude run php-test-generate --type=all --class=App\\Service\\UserService

# MANDATORY: Run all generated tests
vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --filter "UserService" --testdox --colors=always 2>&1 | tee test-output.log
PHPUNIT_EXIT_CODE=${PIPESTATUS[0]}

if [ $PHPUNIT_EXIT_CODE -ne 0 ]; then
    echo "❌ CRITICAL: Generated test suite failed with exit code $PHPUNIT_EXIT_CODE"
    exit $PHPUNIT_EXIT_CODE
fi

# Verify comprehensive test execution
if ! grep -E "(OK \\([0-9]+ test|Tests: [0-9]+, Assertions: [0-9]+, Failures: 0)" test-output.log; then
    echo "❌ CRITICAL: Generated test suite has execution issues"
    exit 1
fi

echo "✅ Complete generated test suite passes successfully"
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

## ⚠️ CRITICAL REQUIREMENT

**EVERY generated test MUST be executed with co-phpunit to verify it works correctly. Generated tests that don't pass are considered FAILED implementations.**

This generator creates comprehensive test suites following hyperf/testing patterns with proper mocking, coroutine support, and best practices - but ONLY accepts working, verified test implementations.