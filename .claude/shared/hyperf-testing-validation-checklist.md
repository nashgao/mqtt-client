# Hyperf Testing Setup Validation Checklist

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

### Pre-Test Validation Requirements
- **All dependency checks pass** - Composer, PHPUnit, co-phpunit installed
- **Configuration validated** - phpunit.xml, bootstrap.php verified
- **Environment detected** - CI vs local configuration correct
- **Services reachable** - Database, Redis connectivity confirmed
- **No existing test failures** - Clean slate before modifications

---

## 🔍 Pre-Test Validation Requirements

This comprehensive checklist ensures your Hyperf project is properly configured for testing with hyperf/testing framework.

### 1. Composer Dependencies Validation

#### Required Dependencies
- [ ] `hyperf/testing` installed as dev dependency
- [ ] `phpunit/phpunit` (version ^9.0|^10.0) installed
- [ ] `mockery/mockery` installed for mocking
- [ ] `hyperf/database` if using database features
- [ ] `hyperf/redis` if using Redis features
- [ ] `hyperf/cache` if using cache features

#### Test Script Configuration
- [ ] `composer.json` contains correct test script: `"test": "vendor/bin/co-phpunit"`
- [ ] Script uses `co-phpunit` (coroutine-enabled PHPUnit) not regular `phpunit`
- [ ] Optional scripts for coverage: `"test:coverage": "vendor/bin/co-phpunit --coverage-html coverage"`

### 2. PHPUnit Configuration (phpunit.xml)

#### Basic Configuration
- [ ] `phpunit.xml` or `phpunit.xml.dist` exists in project root
- [ ] Bootstrap file points to `test/bootstrap.php` or similar
- [ ] Test suites configured for `test/` directory
- [ ] Colors enabled for better output
- [ ] Process isolation disabled for Hyperf compatibility

#### Hyperf-Specific Settings
```xml
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/9.3/phpunit.xsd"
         bootstrap="test/bootstrap.php"
         colors="true"
         processIsolation="false"
         stopOnFailure="false">
```

- [ ] `processIsolation="false"` - Critical for Hyperf
- [ ] Bootstrap correctly initializes Hyperf container
- [ ] Memory limit adequate for coroutine testing

### 3. Bootstrap File Validation

#### Container Initialization
- [ ] Bootstrap file exists (typically `test/bootstrap.php`)
- [ ] Hyperf application container properly initialized
- [ ] Environment configuration loaded
- [ ] Coroutine environment set up correctly

#### Required Bootstrap Elements
```php
// Required in bootstrap.php
- [ ] Composer autoloader included
- [ ] Hyperf\Testing\Bootstrap class used
- [ ] Container configured for testing environment
- [ ] Database connections configured if needed
- [ ] Cache cleared for clean test state
```

### 4. Directory Structure Validation

#### Standard Test Structure
```
test/
├── bootstrap.php          # Bootstrap file
├── Cases/                # Test cases directory
│   ├── Unit/             # Unit tests
│   ├── Feature/          # Feature tests
│   └── Http/             # HTTP tests
├── Fixtures/             # Test fixtures
└── Stubs/               # Test stubs and mocks
```

- [ ] Test directory structure follows Hyperf conventions
- [ ] Unit tests in `test/Cases/Unit/`
- [ ] Feature tests in `test/Cases/Feature/`
- [ ] HTTP tests in `test/Cases/Http/`

### 5. Base Test Classes Validation

#### Required Base Classes
- [ ] `Hyperf\Testing\TestCase` extended by all test classes
- [ ] Custom base test class created if needed
- [ ] Proper use of `setUp()` and `tearDown()` methods
- [ ] Container access methods available

#### Base Test Class Example
```php
<?php

namespace HyperfTest\Cases;

use Hyperf\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Custom setup logic
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
        // Custom cleanup logic
    }
}
```

## 🧪 Test Implementation Validation

### 1. Basic Unit Test Structure

#### Required Elements
- [ ] Test classes extend appropriate base class
- [ ] Test methods start with `test` prefix or use `@test` annotation
- [ ] Proper assertions used (`$this->assertEquals`, etc.)
- [ ] Dependency injection working in tests
- [ ] Container services accessible

#### Example Unit Test Validation
```php
<?php

namespace HyperfTest\Cases\Unit;

use HyperfTest\Cases\TestCase;
use App\Service\ExampleService;

class ExampleServiceTest extends TestCase
{
    public function testServiceMethod(): void
    {
        $service = $this->container->get(ExampleService::class);
        $result = $service->doSomething();
        
        $this->assertEquals('expected', $result);
    }
}
```

### 2. Mocking Hyperf Dependencies

#### Container Mocking
- [ ] Services can be mocked and replaced in container
- [ ] `Mockery` integration working correctly
- [ ] Hyperf components mockable (Logger, Config, etc.)
- [ ] Database connections mockable if needed

#### Mocking Examples Validation
```php
// Service mocking
- [ ] Mock services injected into container
- [ ] Hyperf\Contract interfaces mockable
- [ ] Event dispatcher mocking available
- [ ] HTTP client mocking functional
```

### 3. Async/Coroutine Testing

#### Coroutine Support
- [ ] `co-phpunit` used instead of regular `phpunit`
- [ ] Async operations testable
- [ ] Coroutine context preserved in tests
- [ ] Parallel testing working if enabled

#### Async Testing Examples
```php
// Coroutine testing validation
- [ ] Async service calls work in tests
- [ ] Coroutine::create() functional in tests
- [ ] Concurrent operations testable
- [ ] Proper async assertions available
```

### 4. Database Transaction Testing

#### Transaction Support
- [ ] Database testing traits available
- [ ] Automatic transaction rollback working
- [ ] Test database configuration separate from production
- [ ] Migration and seeding support in tests

#### Database Test Validation
```php
// Database testing checklist
- [ ] Hyperf\Testing\DatabaseTestCase available
- [ ] RefreshDatabase trait functional
- [ ] Factory and seeder integration working
- [ ] Multiple database connection testing
```

## ⚠️ Common Pitfalls and Solutions

### 1. Coroutine Context Issues

#### Problem Indicators
- [ ] Tests fail with "Coroutine context not found" errors
- [ ] Random test failures in async operations
- [ ] Container services not accessible in tests

#### Solutions
- [ ] Use `co-phpunit` instead of `phpunit`
- [ ] Ensure bootstrap properly initializes coroutine context
- [ ] Check Swoole extension is loaded and configured

### 2. Container Configuration Problems

#### Problem Indicators
- [ ] Services not injectable in tests
- [ ] Configuration values not loading
- [ ] Aspect-oriented programming not working

#### Solutions
- [ ] Verify bootstrap file initializes container correctly
- [ ] Check test environment configuration files
- [ ] Ensure proper service provider registration

### 3. Database Testing Issues

#### Problem Indicators
- [ ] Database state persists between tests
- [ ] Transaction rollback not working
- [ ] Connection pool issues in tests

#### Solutions
- [ ] Use separate test database
- [ ] Implement proper transaction rollback
- [ ] Configure connection pool for testing

### 4. HTTP Testing Problems

#### Problem Indicators
- [ ] HTTP client not mockable
- [ ] Route testing failing
- [ ] Middleware not executing in tests

#### Solutions
- [ ] Use Hyperf HTTP testing tools
- [ ] Mock HTTP client properly
- [ ] Ensure middleware registration in test environment

## 🎯 Performance Optimization for Tests

### 1. Test Execution Speed

#### Optimization Checklist
- [ ] Unnecessary database operations minimized
- [ ] Heavy fixtures loading optimized
- [ ] Parallel test execution considered
- [ ] Test data factories used efficiently

### 2. Memory Management

#### Memory Optimization
- [ ] Large objects properly cleaned up
- [ ] Container services recycled appropriately
- [ ] Test isolation maintained without memory leaks

## 🔧 Debugging Test Issues

### 1. Debug Configuration

#### Debug Tools Setup
- [ ] Xdebug or similar debugger configured for tests
- [ ] Test-specific logging configuration
- [ ] Error reporting maximized for tests
- [ ] Debug output properly routed

### 2. Test Output Management

#### Clean Output Requirements
- [ ] No `var_dump()` or `echo` statements in test code
- [ ] Debug output controlled via environment variables
- [ ] Test results parseable by CI/CD systems
- [ ] Proper assertion messages for failures

## ✅ Final Validation Commands

### Run These Commands to Validate Setup

```bash
# 1. Verify composer dependencies
composer show | grep hyperf/testing

# 2. Validate PHPUnit configuration
vendor/bin/co-phpunit --configuration phpunit.xml --dry-run

# 3. Test bootstrap file
php test/bootstrap.php

# 4. Run a simple test
vendor/bin/co-phpunit --filter ExampleTest

# 5. Verify coroutine support
vendor/bin/co-phpunit --testdox --verbose
```

### Expected Results
- [ ] All dependencies present and correct versions
- [ ] PHPUnit configuration valid
- [ ] Bootstrap executes without errors
- [ ] Sample tests run successfully
- [ ] Coroutine context properly maintained

## 📚 Additional Resources

### Documentation Links
- [ ] Hyperf Testing Documentation reviewed
- [ ] PHPUnit documentation for version compatibility
- [ ] Swoole coroutine testing best practices
- [ ] Hyperf database testing patterns

### Example Projects
- [ ] Reference implementation available
- [ ] Test suite examples reviewed
- [ ] CI/CD integration patterns studied

---

## 🚨 CRITICAL SUCCESS CRITERIA

**ALL items must be ✅ before proceeding with test development:**

1. **Dependencies**: All required packages installed with correct versions
2. **Configuration**: PHPUnit and bootstrap properly configured
3. **Structure**: Directory structure follows Hyperf conventions
4. **Base Classes**: Proper test base classes available
5. **Container**: Hyperf container accessible in tests
6. **Coroutines**: Async/coroutine support functional
7. **Database**: Database testing properly configured
8. **Mocking**: Service and dependency mocking working
9. **Performance**: Tests run efficiently without memory issues
10. **Debugging**: Debug tools and output properly configured

**Remember**: Hyperf testing requires specific setup due to its coroutine-based architecture. Standard PHPUnit setup will not work correctly.