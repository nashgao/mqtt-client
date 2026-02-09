---
allowed-tools: all
description: Run PHP tests with co-phpunit and generate coverage reports
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

# PHP Test Runner with co-phpunit

Execute PHP tests using `vendor/bin/co-phpunit` with various options, filters, and reporting capabilities.

## Basic Test Execution

### Run All Tests

Execute complete test suite with mandatory verification:
```bash
# Run PHPUnit and capture exit code
vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --testdox --colors=always 2>&1 | tee test-output.log
PHPUNIT_EXIT_CODE=${PIPESTATUS[0]}

if [ $PHPUNIT_EXIT_CODE -ne 0 ]; then
    echo "❌ CRITICAL: PHPUnit failed with exit code $PHPUNIT_EXIT_CODE"
    exit $PHPUNIT_EXIT_CODE
fi

# Verify positive success indicators
if ! grep -E "(OK \([0-9]+ test|Tests: [0-9]+, Assertions: [0-9]+, Failures: 0)" test-output.log; then
    echo "❌ CRITICAL: No PHPUnit success indicators found"
    exit 1
fi

echo "✅ All tests passed successfully"
```

### Run Tests with Memory Limit

For memory-intensive tests with verification:
```bash
# Run PHPUnit with increased memory and capture exit code
php -d memory_limit=512M vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --testdox --colors=always 2>&1 | tee test-output.log
PHPUNIT_EXIT_CODE=${PIPESTATUS[0]}

if [ $PHPUNIT_EXIT_CODE -ne 0 ]; then
    echo "❌ CRITICAL: PHPUnit failed with exit code $PHPUNIT_EXIT_CODE"
    exit $PHPUNIT_EXIT_CODE
fi

# Verify positive success indicators
if ! grep -E "(OK \([0-9]+ test|Tests: [0-9]+, Assertions: [0-9]+, Failures: 0)" test-output.log; then
    echo "❌ CRITICAL: No PHPUnit success indicators found"
    exit 1
fi

echo "✅ Memory-intensive tests passed successfully"
```

## Filtered Test Execution

### By Test Method/Class

Run specific test methods or classes with verification:
```bash
# Single test method with verification
vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --filter "testUserCreation" --testdox --colors=always 2>&1 | tee test-output.log
PHPUNIT_EXIT_CODE=${PIPESTATUS[0]}

if [ $PHPUNIT_EXIT_CODE -ne 0 ]; then
    echo "❌ CRITICAL: Filtered test failed with exit code $PHPUNIT_EXIT_CODE"
    exit $PHPUNIT_EXIT_CODE
fi

# Verify test execution
if ! grep -E "(OK \([0-9]+ test|✓|Tests: [0-9]+)" test-output.log; then
    echo "❌ CRITICAL: No test execution indicators found"
    exit 1
fi

echo "✅ Filtered tests passed successfully"

# For composer shortcuts, ensure they run actual verification:
# composer test-filter "UserServiceTest" # Only if composer.json includes verification
```

### By Group

Run tests by group annotations with verification:
```bash
# Unit tests only with verification
vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --group unit --testdox --colors=always 2>&1 | tee test-output.log
PHPUNIT_EXIT_CODE=${PIPESTATUS[0]}

if [ $PHPUNIT_EXIT_CODE -ne 0 ]; then
    echo "❌ CRITICAL: Unit tests failed with exit code $PHPUNIT_EXIT_CODE"
    exit $PHPUNIT_EXIT_CODE
fi

# Verify test execution
if ! grep -E "(OK \([0-9]+ test|✓|Tests: [0-9]+)" test-output.log; then
    echo "❌ CRITICAL: No unit test execution indicators found"
    exit 1
fi

echo "✅ Unit tests passed successfully"

# Integration tests with verification
vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --group integration --testdox --colors=always 2>&1 | tee test-output.log
PHPUNIT_EXIT_CODE=${PIPESTATUS[0]}

if [ $PHPUNIT_EXIT_CODE -ne 0 ]; then
    echo "❌ CRITICAL: Integration tests failed with exit code $PHPUNIT_EXIT_CODE"
    exit $PHPUNIT_EXIT_CODE
fi

echo "✅ Integration tests passed successfully"
```

### By Directory

Run tests from specific directories with verification:
```bash
# Unit tests directory with verification
vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml test/Cases/Unit/ --testdox --colors=always 2>&1 | tee test-output.log
PHPUNIT_EXIT_CODE=${PIPESTATUS[0]}

if [ $PHPUNIT_EXIT_CODE -ne 0 ]; then
    echo "❌ CRITICAL: Unit directory tests failed with exit code $PHPUNIT_EXIT_CODE"
    exit $PHPUNIT_EXIT_CODE
fi

# Verify test execution
if ! grep -E "(OK \([0-9]+ test|✓|Tests: [0-9]+)" test-output.log; then
    echo "❌ CRITICAL: No test execution indicators found for unit tests"
    exit 1
fi

echo "✅ Unit directory tests passed successfully"

# Integration tests with verification
vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml test/Cases/Integration/ --testdox --colors=always 2>&1 | tee test-output.log
PHPUNIT_EXIT_CODE=${PIPESTATUS[0]}

if [ $PHPUNIT_EXIT_CODE -ne 0 ]; then
    echo "❌ CRITICAL: Integration directory tests failed with exit code $PHPUNIT_EXIT_CODE"
    exit $PHPUNIT_EXIT_CODE
fi

echo "✅ Integration directory tests passed successfully"
```

## Test Coverage

### Generate HTML Coverage Report

```bash
# Generate coverage report with verification
vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --coverage-html coverage/ --testdox --colors=always 2>&1 | tee test-output.log
PHPUNIT_EXIT_CODE=${PIPESTATUS[0]}

if [ $PHPUNIT_EXIT_CODE -ne 0 ]; then
    echo "❌ CRITICAL: Coverage generation failed with exit code $PHPUNIT_EXIT_CODE"
    exit $PHPUNIT_EXIT_CODE
fi

# Verify coverage was generated
if [ ! -d "coverage" ] || [ ! -f "coverage/index.html" ]; then
    echo "❌ CRITICAL: Coverage HTML report not generated"
    exit 1
fi

# Verify positive success indicators
if ! grep -E "(OK \([0-9]+ test|Tests: [0-9]+, Assertions: [0-9]+, Failures: 0)" test-output.log; then
    echo "❌ CRITICAL: Tests failed during coverage generation"
    exit 1
fi

echo "✅ Coverage report generated successfully at coverage/index.html"
```

### Generate Multiple Coverage Formats

```bash
# XML format for CI/CD with verification
vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --coverage-clover coverage.xml --testdox --colors=always 2>&1 | tee test-output.log
PHPUNIT_EXIT_CODE=${PIPESTATUS[0]}

if [ $PHPUNIT_EXIT_CODE -ne 0 ]; then
    echo "❌ CRITICAL: XML coverage generation failed with exit code $PHPUNIT_EXIT_CODE"
    exit $PHPUNIT_EXIT_CODE
fi

# Verify XML coverage file exists
if [ ! -f "coverage.xml" ]; then
    echo "❌ CRITICAL: Coverage XML file not generated"
    exit 1
fi

echo "✅ XML coverage report generated successfully"

# Text format with verification
vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --coverage-text --testdox --colors=always 2>&1 | tee test-output.log
PHPUNIT_EXIT_CODE=${PIPESTATUS[0]}

if [ $PHPUNIT_EXIT_CODE -ne 0 ]; then
    echo "❌ CRITICAL: Text coverage generation failed with exit code $PHPUNIT_EXIT_CODE"
    exit $PHPUNIT_EXIT_CODE
fi

echo "✅ Text coverage report displayed successfully"

# JSON format with verification
vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --coverage-json coverage.json --testdox --colors=always 2>&1 | tee test-output.log
PHPUNIT_EXIT_CODE=${PIPESTATUS[0]}

if [ $PHPUNIT_EXIT_CODE -ne 0 ]; then
    echo "❌ CRITICAL: JSON coverage generation failed with exit code $PHPUNIT_EXIT_CODE"
    exit $PHPUNIT_EXIT_CODE
fi

# Verify JSON coverage file exists
if [ ! -f "coverage.json" ]; then
    echo "❌ CRITICAL: Coverage JSON file not generated"
    exit 1
fi

echo "✅ JSON coverage report generated successfully"
```

### Coverage with Minimum Threshold

Set minimum coverage requirements with verification:
```bash
# Generate coverage with threshold verification
vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --coverage-html coverage/ --coverage-filter src/ --coverage-text --testdox --colors=always 2>&1 | tee test-output.log
PHPUNIT_EXIT_CODE=${PIPESTATUS[0]}

if [ $PHPUNIT_EXIT_CODE -ne 0 ]; then
    echo "❌ CRITICAL: Coverage with threshold failed with exit code $PHPUNIT_EXIT_CODE"
    exit $PHPUNIT_EXIT_CODE
fi

# Extract coverage percentage from output
COVERAGE_PERCENT=$(grep -o 'Lines:[[:space:]]*[0-9]*\.[0-9]*%' test-output.log | grep -o '[0-9]*\.[0-9]*' | head -1)

if [ -z "$COVERAGE_PERCENT" ]; then
    echo "❌ CRITICAL: Could not extract coverage percentage"
    exit 1
fi

# Check minimum threshold (80%)
MINIMUM_COVERAGE=80
if (( $(echo "$COVERAGE_PERCENT < $MINIMUM_COVERAGE" | bc -l) )); then
    echo "❌ CRITICAL: Coverage $COVERAGE_PERCENT% is below minimum $MINIMUM_COVERAGE%"
    exit 1
fi

echo "✅ Coverage $COVERAGE_PERCENT% meets minimum threshold of $MINIMUM_COVERAGE%"
```

## Debug and Verbose Output

### Debug Mode

Enable debug output for troubleshooting:
```bash
vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --debug --colors=always
```

### Verbose Output

Show detailed test execution information:
```bash
vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --verbose --colors=always
```

### Show Test Progress

Display test progress with dots or detailed output:
```bash
# Progress dots
vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --colors=always

# Detailed progress
vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --testdox --colors=always
```

## Failure Handling

### Stop on First Failure

Stop execution on first test failure:
```bash
vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --stop-on-failure --colors=always
```

### Stop on Error

Stop execution on first error:
```bash
vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --stop-on-error --colors=always
```

### Show Only Failures

Show only failing tests:
```bash
vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --stop-on-failure --verbose --colors=always
```

## Parallel Test Execution

### Run Tests in Parallel

For faster execution with multiple processes:
```bash
# Using parallel extension (if available)
vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --process-isolation --colors=always
```

### Coroutine-Optimized Execution

Leverage Swoole coroutines for async tests:
```bash
# Ensure proper coroutine hooks
SWOOLE_HOOK_FLAGS=SWOOLE_HOOK_ALL vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --colors=always
```

## Custom Configuration

### Environment-Specific Tests

Run tests with different environments:
```bash
# Development environment
APP_ENV=dev vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --colors=always

# Testing environment
APP_ENV=test vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --colors=always

# CI environment
APP_ENV=ci vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --colors=always
```

### Database-Specific Tests

Run tests with different database configurations:
```bash
# SQLite memory database
DB_CONNECTION=sqlite DB_DATABASE=:memory: vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --group=database --colors=always

# MySQL test database
DB_CONNECTION=mysql DB_DATABASE=test_db vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --group=database --colors=always
```

## Continuous Integration

### CI/CD Pipeline Script

```bash
#!/bin/bash
set -e

echo "Installing dependencies..."
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

echo "Running tests..."
php -d memory_limit=512M vendor/bin/co-phpunit \
    --prepend test/bootstrap.php \
    -c phpunit.xml \
    --coverage-clover coverage.xml \
    --log-junit junit.xml \
    --colors=never

echo "Checking coverage threshold..."
php -r "
\$xml = simplexml_load_file('coverage.xml');
\$coverage = (float) \$xml->project->metrics['coveredstatements'] / (float) \$xml->project->metrics['statements'] * 100;
if (\$coverage < 80) {
    echo \"Coverage {\$coverage}% is below minimum 80%\n\";
    exit(1);
}
echo \"Coverage: {\$coverage}%\n\";
"
```

### GitHub Actions Configuration

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    strategy:
      matrix:
        php-versions: ['8.3']
        
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: ${{ matrix.php-versions }}
        extensions: swoole, pcntl, posix
        
    - name: Install dependencies
      run: composer install --prefer-dist --no-progress
      
    - name: Run tests
      run: |
        php -d memory_limit=512M vendor/bin/co-phpunit \
          --prepend test/bootstrap.php \
          -c phpunit.xml \
          --coverage-clover coverage.xml \
          --colors=never
          
    - name: Upload coverage
      uses: codecov/codecov-action@v1
      with:
        file: ./coverage.xml
```

## Performance Optimization

### Memory Optimization

```bash
# Increase memory limit for large test suites
php -d memory_limit=1G vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --colors=always

# Garbage collection optimization
php -d zend.enable_gc=1 -d memory_limit=512M vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --colors=always
```

### Swoole Optimization

```bash
# Optimize Swoole settings
php -d swoole.use_shortname=Off \
    -d swoole.enable_coroutine=On \
    -d memory_limit=512M \
    vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --colors=always
```

## Test Result Analysis

### Generate Test Reports

```bash
# TAP format
vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --tap --colors=always > test-results.tap

# JSON format
vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --log-json test-results.json --colors=always

# JUnit XML format
vendor/bin/co-phpunit --prepend test/bootstrap.php -c phpunit.xml --log-junit junit.xml --colors=always
```

### Custom Test Listeners

Add custom test listeners in phpunit.xml:
```xml
<extensions>
    <bootstrap class="YourNamespace\Test\Extension\TestResultListener" />
</extensions>
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

## Troubleshooting

### Common Issues

1. **Memory exhaustion**: Increase memory limit or optimize test data
2. **Timeout issues**: Increase timeout limits for async operations
3. **Database locks**: Use transactions and proper cleanup
4. **Swoole conflicts**: Ensure proper coroutine hook configuration

### Debug Commands

```bash
# Check Swoole installation
php --ri swoole

# Verify coroutine support
php -r "var_dump(Co::getCid());"

# Check memory usage
php -d memory_limit=512M -r "echo ini_get('memory_limit');"
```

This command provides comprehensive test execution capabilities with co-phpunit, ensuring optimal performance and detailed reporting for PHP testing workflows.