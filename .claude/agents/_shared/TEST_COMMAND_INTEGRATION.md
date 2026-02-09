# Test Command Detection Integration Guide

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
This document ensures ALL test-related agents, commands, and services use the correct test command detection component instead of hardcoding or assuming `npm test`.

## 🚨 CRITICAL REQUIREMENT
**NEVER ASSUME `npm test`** - Always detect the correct test command for the project:
- PHP: `composer test` or `./vendor/bin/phpunit`
- Python: `pytest` or project-specific command
- Go: `go test ./...`
- Java: `mvn test` or `gradle test`
- Ruby: `bundle exec rspec`
- Rust: `cargo test`
- .NET: `dotnet test`

## Components Updated with Test Detection

### Core Detection Component
- **`_shared/test-command-detection.md`** - Master detection logic for all frameworks

### Test-Related Agents (ALL UPDATED)
1. **`cicd-test-fixer.md`** ✅
   - Detects test command BEFORE fixing
   - Validates using framework-specific patterns
   - Ensures 100% pass rate with correct command

2. **`testing-orchestrator.md`** ✅
   - Test command detection as first priority
   - Framework-aware validation patterns
   - Reports test command used

3. **`testing-unit-master.md`** ✅
   - Uses test-command-detection component
   - Never assumes npm test

4. **`testing-integration-master.md`** ✅
   - Uses test-command-detection component
   - Handles integration test commands properly

5. **`testing-api-integration.md`** ✅
   - Detects test command for API test suites
   - Framework-aware execution

6. **`cicd-regression-tester.md`** ✅
   - Uses proper test detection for regression testing
   - Framework-specific validation

### Test Commands (UPDATED)
- **`commands/test/fix.md`** ✅
  - Detects test command as FIRST step
  - Uses test-fixer agent with proper detection

### Shared Components (INTEGRATED)
- **`_shared/test-intelligence.md`** ✅
  - References test-command-detection component
  - Fallback uses proper detection instead of npm test

## Usage Pattern

### For Agents
```bash
# At the beginning of any test-related agent
source _shared/test-command-detection.md

# Detect the test command
TEST_CMD=$(detect_test_command true)  # verbose mode
if [ "$TEST_CMD" = "UNKNOWN" ]; then
    echo "❌ Cannot proceed without test command"
    echo "Please specify: composer test, pytest, go test, etc."
    exit 1
fi

# Use the detected command
echo "✅ Running tests with: $TEST_CMD"
$TEST_CMD 2>&1 | tee test_output.log

# Validate results using framework-specific patterns
if validate_test_success "test_output.log" "$TEST_CMD"; then
    echo "✅ All tests passed!"
else
    FAILURES=$(count_test_failures "test_output.log" "$TEST_CMD")
    echo "❌ $FAILURES tests failed"
fi
```

### For Commands
```markdown
When running `/test fix` or any test command:
1. **DETECT** test command first using detection component
2. **VALIDATE** detection is correct for the project
3. **EXECUTE** using the detected command
4. **VERIFY** using framework-specific success patterns
```

## Framework-Specific Success Patterns

### PHPUnit/Composer
- **Success**: "OK (X tests, Y assertions)"
- **Failure**: "FAILURES!" or "ERRORS!"
- **Command**: `composer test` or `./vendor/bin/phpunit`

### Jest/Mocha/Vitest
- **Success**: "Test Suites: X passed", "✓"
- **Failure**: "X failed", "✕", "FAIL"
- **Command**: `npm test`, `yarn test`, `pnpm test`

### Pytest
- **Success**: "X passed"
- **Failure**: "X failed", "X error"
- **Command**: `pytest`, `python -m pytest`

### Go
- **Success**: "PASS", "ok"
- **Failure**: "FAIL", "--- FAIL:"
- **Command**: `go test ./...`

## Verification Checklist

Before any test-related agent or command executes:
- [ ] ✅ Test command detection component is sourced/available
- [ ] ✅ Test command is detected, not assumed
- [ ] ✅ Detection result is validated (not UNKNOWN)
- [ ] ✅ Framework-specific patterns are used for validation
- [ ] ✅ Failure counting uses framework-specific methods
- [ ] ✅ Success validation uses correct patterns

## Common Mistakes to Avoid

❌ **NEVER DO THIS:**
```bash
# Wrong - assumes npm test
npm test 2>&1 | tee output.log
```

✅ **ALWAYS DO THIS:**
```bash
# Correct - detects proper command
TEST_CMD=$(detect_test_command)
$TEST_CMD 2>&1 | tee output.log
```

## Impact Summary

This integration ensures:
1. **PHP projects** use `composer test`, not npm test
2. **Python projects** use `pytest` or configured runner
3. **Go projects** use `go test ./...`
4. **All frameworks** are properly detected and validated
5. **100% test pass rate** is verified with correct commands
6. **No assumptions** about test commands are made

## Maintenance Notes

When adding new test-related agents or commands:
1. Import/source the test-command-detection component
2. Use `detect_test_command` function before any test execution
3. Validate detection results before proceeding
4. Use framework-specific validation patterns
5. Document the test command used in reports/logs

## 🚨 ZERO TOLERANCE ENFORCEMENT

**ALL shared test utilities MUST enforce PERFECT execution:**

### Success Criteria (ALL must be met)
- ✅ **0 Failed Tests** - Every single test must pass
- ✅ **0 Errors** - No runtime errors allowed
- ✅ **0 Warnings** - Warnings are treated as failures
- ✅ **0 Deprecations** - Deprecation notices block success
- ✅ **0 Incomplete Tests** - Incomplete tests are failures
- ✅ **0 Risky Tests** - Risky test detection must pass
- ✅ **0 Skipped Tests** - Unless explicitly allowed with justification

### Integration Requirements
- All test detection must flag warnings as errors
- All completion gates must reject warnings/deprecations
- All coordination must enforce zero tolerance across agents