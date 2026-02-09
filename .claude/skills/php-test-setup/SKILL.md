---
allowed-tools: all
description: Set up hyperf/testing for PHP unit testing with co-phpunit
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

# PHP Unit Testing Setup with Hyperf/Testing

Set up complete PHP unit testing environment using hyperf/testing framework with `vendor/bin/co-phpunit` for optimal coroutine support.

## Overview

This command configures a PHP project with:
- hyperf/testing framework installation
- PHPUnit configuration with co-phpunit
- Test bootstrap and directory structure
- Composer scripts for testing workflow
- Base test classes and utilities

## Setup Process

### 1. Install Testing Dependencies

```bash
# Install hyperf/testing and related packages
composer require --dev hyperf/testing mockery/mockery

# Install PHPUnit if not already present
composer require --dev phpunit/phpunit
```

### 2. Create PHPUnit Configuration

Generate `phpunit.xml`:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit backupGlobals="false"
         colors="true"
         processIsolation="false"
         stopOnFailure="false"
         stderr="false">
    <testsuites>
        <testsuite name="Tests">
            <directory suffix="Test.php">test/Cases</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory suffix=".php">./src</directory>
        </include>
    </source>
    <extensions>
        <bootstrap class="YourNamespace\Test\Extension\AfterLastExtension" />
    </extensions>
</phpunit>
```

### 3. Create Test Bootstrap

Generate `test/bootstrap.php`:
```php
<?php

declare(strict_types=1);

use Swoole\Runtime;

ini_set('display_errors', 'on');
ini_set('display_startup_errors', 'on');

error_reporting(E_ALL);
date_default_timezone_set('UTC');

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__));

if (extension_loaded('swoole')) {
    ! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', SWOOLE_HOOK_ALL);
    Runtime::enableCoroutine();
}

require BASE_PATH . '/vendor/autoload.php';
```

### 4. Configure Composer Scripts

Add testing scripts to `composer.json`:
```json
{
    "scripts": {
        "test": "php -d memory_limit=512M vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --colors=always",
        "test-coverage": "php -d memory_limit=512M vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --colors=always --coverage-html coverage/",
        "test-filter": "co-phpunit --prepend test/bootstrap.php -c phpunit.xml --colors=always --filter",
        "test-group": "co-phpunit --prepend test/bootstrap.php -c phpunit.xml --colors=always --group"
    }
}
```

### 5. Create Directory Structure

```
test/
├── bootstrap.php
├── Cases/
│   ├── Unit/
│   ├── Integration/
│   └── Feature/
├── Stubs/
├── Fixtures/
└── Extension/
    └── AfterLastExtension.php
```

### 6. Base Test Class

Create base test class with hyperf/testing integration:
```php
<?php

declare(strict_types=1);

namespace YourNamespace\Test;

use Hyperf\Testing\TestCase as HyperfTestCase;
use Mockery;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;

abstract class TestCase extends HyperfTestCase
{
    #[Before]
    protected function setUp(): void
    {
        parent::setUp();
        // Add custom setup logic here
    }

    #[After]
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    
    protected function createMock(string $className): \Mockery\MockInterface
    {
        return Mockery::mock($className);
    }
    
    protected function createPartialMock(string $className, array $methods = []): \Mockery\MockInterface
    {
        return Mockery::mock($className)->makePartial();
    }
}
```

## Usage Examples

### Running Tests with Verification

```bash
# Run all tests with mandatory verification
vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --testdox --colors=always 2>&1 | tee test-output.log
PHPUNIT_EXIT_CODE=${PIPESTATUS[0]}

if [ $PHPUNIT_EXIT_CODE -ne 0 ]; then
    echo "❌ CRITICAL: Test setup verification failed with exit code $PHPUNIT_EXIT_CODE"
    exit $PHPUNIT_EXIT_CODE
fi

# Verify positive success indicators
if ! grep -E "(OK \([0-9]+ test|Tests: [0-9]+, Assertions: [0-9]+, Failures: 0)" test-output.log; then
    echo "❌ CRITICAL: No test execution indicators found"
    exit 1
fi

echo "✅ Test setup verified successfully"

# Run specific test file with verification
vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --filter "ExampleTest" --testdox --colors=always 2>&1 | tee test-output.log
PHPUNIT_EXIT_CODE=${PIPESTATUS[0]}

if [ $PHPUNIT_EXIT_CODE -ne 0 ]; then
    echo "❌ CRITICAL: Specific test failed with exit code $PHPUNIT_EXIT_CODE"
    exit $PHPUNIT_EXIT_CODE
fi

echo "✅ Specific test executed successfully"

# Run tests with coverage and verification
vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --coverage-html coverage/ --testdox --colors=always 2>&1 | tee test-output.log
PHPUNIT_EXIT_CODE=${PIPESTATUS[0]}

if [ $PHPUNIT_EXIT_CODE -ne 0 ]; then
    echo "❌ CRITICAL: Coverage test failed with exit code $PHPUNIT_EXIT_CODE"
    exit $PHPUNIT_EXIT_CODE
fi

# Verify coverage was generated
if [ ! -d "coverage" ] || [ ! -f "coverage/index.html" ]; then
    echo "❌ CRITICAL: Coverage report not generated"
    exit 1
fi

echo "✅ Coverage tests executed successfully"

# Run tests by group with verification
vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --group unit --testdox --colors=always 2>&1 | tee test-output.log
PHPUNIT_EXIT_CODE=${PIPESTATUS[0]}

if [ $PHPUNIT_EXIT_CODE -ne 0 ]; then
    echo "❌ CRITICAL: Unit group test failed with exit code $PHPUNIT_EXIT_CODE"
    exit $PHPUNIT_EXIT_CODE
fi

echo "✅ Unit group tests executed successfully"
```

### Writing Tests

```php
<?php

declare(strict_types=1);

namespace YourNamespace\Test\Cases\Unit;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use YourNamespace\Test\TestCase;

#[Group('unit')]
#[CoversNothing]
class ExampleTest extends TestCase
{
    public function testExample(): void
    {
        $this->assertTrue(true);
    }
    
    public function testWithMock(): void
    {
        $mock = $this->createMock(SomeClass::class);
        $mock->shouldReceive('method')->once()->andReturn('result');
        
        $result = $mock->method();
        $this->assertEquals('result', $result);
    }
}
```

## Advanced Configuration

### Database Testing

For database tests with transactions:
```php
use Hyperf\Testing\Concerns\RefreshDatabase;

class DatabaseTest extends TestCase
{
    use RefreshDatabase;
    
    public function testDatabaseOperation(): void
    {
        // Test database operations here
        // Database will be refreshed after each test
    }
}
```

### HTTP Testing

For HTTP endpoint testing:
```php
use Hyperf\Testing\Client;

class ApiTest extends TestCase
{
    public function testApiEndpoint(): void
    {
        $response = $this->client->get('/api/users');
        
        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertIsArray($data);
    }
}
```

### Coroutine Testing

For testing coroutine-based code (following space-actor patterns):
```php
use Swoole\Coroutine;

class CoroutineTest extends TestCase
{
    public function testCoroutineCode(): void
    {
        $test = function () {
            // Your test logic here
            $source = TestSource::fromElements(['hello', 'world']);
            $sink = TestSink::auto();
            
            $source->subscribe($sink);
            
            $this->assertTrue($sink->waitForCompletion(2.0));
            $this->assertEquals(['hello', 'world'], $sink->getReceivedElements());
        };

        if (Coroutine::getCid()) {
            $test();
        } else {
            Coroutine\run($test);
        }
    }
}
```

## Best Practices

1. **Use co-phpunit**: Always use `vendor/bin/co-phpunit` for coroutine support
2. **Group tests**: Use `#[Group()]` attributes to categorize tests
3. **Memory limit**: Set appropriate memory limits for complex tests
4. **Bootstrap**: Always use `--prepend test/bootstrap.php` for proper initialization
5. **Mocking**: Use Mockery for advanced mocking capabilities
6. **Coverage**: Generate coverage reports to ensure adequate test coverage

## Troubleshooting

### Common Issues

1. **Swoole hook issues**: Ensure `SWOOLE_HOOK_FLAGS` is properly defined
2. **Memory issues**: Increase memory limit in test scripts
3. **Coroutine conflicts**: Use co-phpunit instead of regular phpunit
4. **Database connections**: Ensure proper cleanup in tearDown methods

### Debug Mode with Verification

Enable debug output with verification:
```bash
# Debug mode with verification
vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --debug --verbose --testdox --colors=always 2>&1 | tee test-output.log
PHPUNIT_EXIT_CODE=${PIPESTATUS[0]}

if [ $PHPUNIT_EXIT_CODE -ne 0 ]; then
    echo "❌ CRITICAL: Debug test execution failed with exit code $PHPUNIT_EXIT_CODE"
    exit $PHPUNIT_EXIT_CODE
fi

echo "✅ Debug test execution successful"
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

## ⚠️ CRITICAL SETUP VERIFICATION

**After completing the PHP test setup, you MUST verify the entire configuration works correctly:**

```bash
# Final setup verification
echo "Verifying PHP test setup..."

# 1. Check co-phpunit installation
if [ ! -f "vendor/bin/co-phpunit" ]; then
    echo "❌ CRITICAL: co-phpunit not installed"
    exit 1
fi

# 2. Check configuration files
if [ ! -f "phpunit.xml" ]; then
    echo "❌ CRITICAL: phpunit.xml not found"
    exit 1
fi

if [ ! -f "test/bootstrap.php" ]; then
    echo "❌ CRITICAL: test/bootstrap.php not found"
    exit 1
fi

# 3. Run example test to verify setup
vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --testdox --colors=always 2>&1 | tee test-output.log
PHPUNIT_EXIT_CODE=${PIPESTATUS[0]}

if [ $PHPUNIT_EXIT_CODE -ne 0 ]; then
    echo "❌ CRITICAL: Setup verification failed - tests cannot execute"
    exit $PHPUNIT_EXIT_CODE
fi

echo "✅ PHP test setup verification completed successfully"
echo "Setup is ready for test development and execution"
```

This setup provides a robust foundation for PHP unit testing with hyperf/testing framework, ensuring proper coroutine support and comprehensive testing capabilities. **All test executions are verified to prevent broken test implementations.**