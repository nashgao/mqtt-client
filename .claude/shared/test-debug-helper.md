---
name: test-debug-helper
description: Conditional debug helper trait for PHP unit tests following best practices
tags: [testing, debugging, php, best-practices]
---

# Test Debug Helper - Clean Test Output Best Practices

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

### Debug Output Requirements
- **NO debug output in production test runs** - All debug calls behind TEST_DEBUG flag
- **Clean CI/CD output mandatory** - Only test results, no var_dump/echo
- **Conditional debugging only** - Use TestDebugHelper trait with environment checks
- **STDERR for debug output** - Never pollute STDOUT which parsers rely on

---

## Core Principle: NO Console Output in Production Tests

**Industry Standard:** Tests should produce clean, parseable output for CI/CD pipelines. Debug output should only appear when explicitly requested during development.

## PHP Test Debug Helper Trait

```php
<?php

namespace Tests\Support;

/**
 * Provides conditional debugging capabilities for tests
 * Following PHPUnit best practices for clean test output
 */
trait TestDebugHelper
{
    /**
     * Output debug information only when TEST_DEBUG environment variable is set
     * 
     * @param mixed $data Data to debug
     * @param string $label Optional label for the debug output
     * @return void
     */
    protected function debug($data, string $label = ''): void
    {
        if (!$this->isDebugEnabled()) {
            return;
        }
        
        $output = "\n=== DEBUG";
        if ($label) {
            $output .= ": {$label}";
        }
        $output .= " ===\n";
        
        // Use appropriate format based on data type
        if (is_object($data) || is_array($data)) {
            $output .= json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } else {
            $output .= var_export($data, true);
        }
        
        $output .= "\n" . str_repeat('=', strlen($output)) . "\n";
        
        // Output to STDERR to not interfere with test output parsing
        fwrite(STDERR, $output);
    }
    
    /**
     * Log debug information with structured format
     * 
     * @param string $context Test context or method name
     * @param mixed $data Data to log
     * @param string $level Debug level (info, warning, error)
     * @return void
     */
    protected function debugLog(string $context, $data, string $level = 'info'): void
    {
        if (!$this->isDebugEnabled()) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $testName = $this->getName() ?? 'unknown';
        
        $logEntry = [
            'timestamp' => $timestamp,
            'test' => $testName,
            'context' => $context,
            'level' => $level,
            'data' => $data
        ];
        
        fwrite(STDERR, json_encode($logEntry) . "\n");
    }
    
    /**
     * Assert with debug context on failure
     * 
     * @param bool $condition Condition to assert
     * @param string $message Assertion message
     * @param mixed $debugData Data to output on failure
     * @return void
     */
    protected function assertWithDebug(bool $condition, string $message, $debugData = null): void
    {
        if (!$condition && $this->isDebugEnabled() && $debugData !== null) {
            $this->debug($debugData, "Assertion Failed: {$message}");
        }
        
        $this->assertTrue($condition, $message);
    }
    
    /**
     * Check if debug mode is enabled
     * 
     * @return bool
     */
    protected function isDebugEnabled(): bool
    {
        return getenv('TEST_DEBUG') === '1' 
            || getenv('PHPUNIT_DEBUG') === '1'
            || $this->isVerboseMode()
            || $this->isDebugMode();
    }
    
    /**
     * Check if PHPUnit is running in verbose mode
     * 
     * @return bool
     */
    private function isVerboseMode(): bool
    {
        return in_array('--verbose', $_SERVER['argv'] ?? [])
            || in_array('-v', $_SERVER['argv'] ?? []);
    }
    
    /**
     * Check if PHPUnit is running in debug mode
     * 
     * @return bool
     */
    private function isDebugMode(): bool
    {
        return in_array('--debug', $_SERVER['argv'] ?? []);
    }
    
    /**
     * Measure and optionally output execution time
     * 
     * @param callable $callback Code to measure
     * @param string $label Label for the measurement
     * @return mixed Result of the callback
     */
    protected function measureExecution(callable $callback, string $label = '')
    {
        $start = microtime(true);
        
        $result = $callback();
        
        $duration = microtime(true) - $start;
        
        if ($this->isDebugEnabled()) {
            $this->debugLog($label ?: 'execution_time', [
                'duration_seconds' => $duration,
                'duration_ms' => $duration * 1000,
                'memory_peak' => memory_get_peak_usage(true)
            ]);
        }
        
        return $result;
    }
}
```

## Usage Examples

### Basic Usage in Tests

```php
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\Support\TestDebugHelper;

class UserServiceTest extends TestCase
{
    use TestDebugHelper;
    
    public function testUserCreation(): void
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ];
        
        // Debug input data (only outputs when TEST_DEBUG=1)
        $this->debug($userData, 'User creation input');
        
        $user = $this->userService->create($userData);
        
        // Debug result
        $this->debug($user->toArray(), 'Created user');
        
        // Use assertions instead of var_dump
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->getName());
    }
    
    public function testComplexOperation(): void
    {
        $result = $this->measureExecution(function () {
            return $this->service->performComplexOperation();
        }, 'complex_operation');
        
        $this->assertWithDebug(
            $result->isSuccess(),
            'Complex operation should succeed',
            $result->getErrors() // Only shown if test fails and debug is enabled
        );
    }
}
```

### Environment-Based Debugging

```bash
# Enable debug output for specific test run
TEST_DEBUG=1 ./vendor/bin/phpunit tests/UserServiceTest.php

# Use PHPUnit's built-in verbose mode
./vendor/bin/phpunit --verbose tests/

# Use debug mode for detailed output
./vendor/bin/phpunit --debug tests/
```

## Best Practices

### ✅ DO:
- Use PHPUnit assertions for validation
- Enable debug output only via environment variables
- Output to STDERR to avoid interfering with test parsers
- Use structured logging for complex debugging
- Leverage PHPUnit's --verbose and --debug flags

### ❌ DON'T:
- Use `var_dump()` or `print_r()` directly in tests
- Leave debug statements in committed code
- Output to STDOUT which interferes with test results
- Use `die()` or `dd()` which stops test execution
- Rely on console output for test validation

## CI/CD Integration

### GitHub Actions Example

```yaml
- name: Run Tests (Production)
  run: ./vendor/bin/phpunit
  
- name: Run Tests with Debug (Only on failure)
  if: failure()
  run: TEST_DEBUG=1 ./vendor/bin/phpunit --verbose
```

### Local Development

```bash
# Normal test run (clean output)
composer test

# Debug specific test
TEST_DEBUG=1 ./vendor/bin/phpunit tests/Feature/ApiTest.php

# Debug with verbose output
./vendor/bin/phpunit --verbose --filter testSpecificMethod
```

## Alternative Debug Strategies

### 1. Xdebug Integration (Preferred)

```php
// Set breakpoint here in IDE
$result = $this->service->process($data);
// Step through execution with debugger
```

### 2. Test-Specific Logging

```php
class TestLogger
{
    private static bool $enabled = false;
    private static array $logs = [];
    
    public static function enable(): void
    {
        self::$enabled = getenv('TEST_DEBUG') === '1';
    }
    
    public static function log(string $message, array $context = []): void
    {
        if (!self::$enabled) {
            return;
        }
        
        self::$logs[] = [
            'message' => $message,
            'context' => $context,
            'time' => microtime(true)
        ];
    }
    
    public static function dump(): void
    {
        if (self::$enabled && !empty(self::$logs)) {
            fwrite(STDERR, json_encode(self::$logs, JSON_PRETTY_PRINT) . "\n");
        }
    }
}
```

### 3. PHPUnit's Built-in Output Testing

```php
public function testCommandOutput(): void
{
    // When testing actual output, use PHPUnit's methods
    $this->expectOutputString("Expected output\n");
    
    $command = new MyCommand();
    $command->execute();
}
```

## Compliance with Space-Utils Standards

When using this debug helper with Space-Utils projects:

1. **Strong Typing**: All methods have proper type declarations
2. **Simplicity**: Simple, focused methods without over-engineering
3. **No Abstractions**: Direct implementation without unnecessary layers
4. **Functional Approach**: Methods are pure where possible
5. **Native PHP**: Uses native functions, no external dependencies

## Summary

This debug helper trait provides a clean, professional approach to debugging tests while maintaining CI/CD compatibility. It ensures tests produce clean output by default while providing powerful debugging capabilities when explicitly enabled during development.