# /test:isolated-fix Command

Fix failing tests with advanced isolation strategies to prevent cyclic breaking and dependency conflicts.

## 🚨 ZERO TOLERANCE ENFORCEMENT

**MANDATORY - ALL isolated test fixing must achieve PERFECT execution:**

### Success Criteria (ALL must be met)
- ✅ **0 Failed Tests** - Every single test must pass after fixing
- ✅ **0 Errors** - No runtime errors allowed in any test
- ✅ **0 Warnings** - Test warnings are treated as failures
- ✅ **0 Cyclic Failures** - Fixing one test must not break another
- ✅ **100% Isolation** - Unit and integration tests completely separated
- ✅ **0 Dependency Conflicts** - All test dependencies properly isolated
- ✅ **0 Rollback Required** - All fixes must be stable and validated

### Failure Response Protocol
When ANY issue is detected:
1. **STOP** - Do not proceed to next test fixes
2. **REPORT** - List all isolation breaches with dependency traces
3. **ROLLBACK** - Revert breaking changes immediately
4. **FIX** - Resolve ALL isolation issues before continuing
5. **VERIFY** - Re-run full suite to confirm 100% clean execution

### Exit Codes
- `0` = Perfect isolated fixing (no cyclic failures, complete isolation)
- `1` = Any test failure, isolation breach, or cyclic dependency
- `2` = Isolation infrastructure or configuration error

## 🔄 ISOLATED FIX VERIFICATION LOOP

**Each isolated fix MUST be verified against the full suite before proceeding:**

### Isolation-Verification Cycle
```
┌─────────────────────────────────────────────────────────┐
│  FOR EACH failing test:                                 │
│    1. Isolate the failure                               │
│    2. Apply minimal fix                                 │
│    3. Run FULL test suite (not just fixed test)         │
│    4. Verify ALL zero-tolerance conditions              │
│    5. Confirm no regressions introduced                 │
│    6. IF any new failure → ROLLBACK fix                 │
│    7. Only proceed if 100% clean                        │
└─────────────────────────────────────────────────────────┘
```

### Rollback Trigger Conditions
- Any previously passing test now fails
- New warnings introduced (even if tests pass)
- New deprecations appear
- Test execution time significantly increases (potential infinite loop)

---

## Usage

```bash
/test:isolated-fix
```

## Description

This command orchestrates test fixing with strict isolation boundaries to prevent the common problem of fixing one test breaking another. It implements dependency tracking, impact analysis, and staged validation to ensure fixes don't cause cascading failures.

## Core Features

### 🔒 Isolation Strategy
- **Separate base classes** for unit vs integration tests
- **Independent bootstrap files** per test type
- **Dependency graph tracking** before modifications
- **Impact analysis** for all changes
- **Rollback capability** for breaking changes

### 🎯 Problem Prevention
- **No cyclic breaking**: Fixes validated against related tests
- **No cross-contamination**: Unit and integration tests isolated
- **No shared resource conflicts**: Each test type uses own resources
- **No bootstrap complexity**: Simplified, separate bootstraps

## Execution Flow

### Phase 1: Analysis & Categorization
```yaml
analysis:
  - Detect all failing tests
  - Categorize as unit/integration/e2e
  - Build dependency graph
  - Identify shared resources
  - Calculate impact zones
```

### Phase 2: Isolation Setup
```yaml
isolation:
  - Create separate base classes if needed
  - Split bootstrap files by test type
  - Establish rollback points
  - Initialize modification tracker
```

### Phase 3: Parallel Fixing with Validation
```yaml
fixing:
  agents:
    - Unit Test Isolation Fixer (handles all unit tests)
    - Integration Test Isolation Fixer (handles integration tests)
    - Dependency Tracker (monitors changes)
    - Impact Validator (checks related tests)
    - Rollback Manager (handles failures)

  validation:
    - After each fix, run the fixed test
    - Check all related tests still pass
    - Rollback if any related test breaks
    - Track all modifications for conflict detection
```

### Phase 4: Final Validation
```yaml
validation:
  - Run full test suite
  - Ensure no new failures introduced
  - Verify proper isolation maintained
  - Generate fix report with dependency map
```

## Implementation

I'll fix the failing tests using isolation strategies to prevent cyclic breaking.

## TASK TOOL AGENT SPAWNING (MANDATORY)

I'll spawn 5 specialized agents using Task tool for isolated test fixing:

### Test Dependency Analyzer:
```markdown
<function_calls>
<invoke name="Task">
<parameter name="subagent_type">test-fixer</parameter>
<parameter name="description">Analyze test dependencies and build graph</parameter>
<parameter name="prompt">You are the Test Dependency Analyzer for isolated test fixing.

Your responsibilities:
1. Scan all test files to identify dependencies and shared resources
2. Build a dependency graph showing test relationships
3. Categorize tests as unit, integration, or e2e
4. Identify shared fixtures, base classes, and configuration files
5. Map potential conflict zones and cyclic dependencies

MANDATORY DEPENDENCY ANALYSIS:
You MUST actually analyze test structure and dependencies:
- Parse test files to find imports and class hierarchies
- Identify shared test utilities and fixtures
- Map database dependencies and external service connections
- Document test execution order dependencies

MANDATORY RESULT TRACKING:
- You MUST save analysis results to /tmp/test-isolated-dependency-results.json
- Include success: true/false, dependency_graph, shared_resources, conflict_zones
- Document test categorization and identified dependencies
- Report any analysis failures or inaccessible test files

CRITICAL: Isolation fixing cannot proceed without comprehensive dependency analysis.</parameter>
</invoke>
</function_calls>
```

### Test Isolation Setup Agent:
```markdown
<function_calls>
<invoke name="Task">
<parameter name="subagent_type">test-fixer</parameter>
<parameter name="description">Setup isolation infrastructure</parameter>
<parameter name="prompt">You are the Test Isolation Setup Agent for test environment preparation.

Your responsibilities:
1. Create separate base classes for unit and integration tests
2. Setup isolated test configurations and bootstrap files
3. Establish rollback points before making changes
4. Configure isolated test databases and resources
5. Implement test environment separation

MANDATORY ISOLATION SETUP:
You MUST actually create isolation infrastructure:
- Create UnitTestCase and IntegrationTestCase base classes
- Setup separate bootstrap files for different test types
- Create isolated test database configurations
- Implement proper test data isolation strategies

MANDATORY RESULT TRACKING:
- You MUST save setup results to /tmp/test-isolated-setup-results.json
- Include success: true/false, isolation_infrastructure_created, rollback_points_established
- Document infrastructure files created and configuration changes
- Only execute after Test Dependency Analyzer confirms analysis

CRITICAL: Test isolation requires proper infrastructure separation.</parameter>
</invoke>
</function_calls>
```

### Unit Test Isolation Fixer:
```markdown
<function_calls>
<invoke name="Task">
<parameter name="subagent_type">test-fixer</parameter>
<parameter name="description">Fix unit tests with strict isolation</parameter>
<parameter name="prompt">You are the Unit Test Isolation Fixer for unit test repairs.

Your responsibilities:
1. Fix failing unit tests using strict mocking and isolation
2. Ensure unit tests don't depend on external resources
3. Implement proper mocking for all dependencies
4. Validate unit test fixes don't break other unit tests
5. Maintain separation from integration test infrastructure

MANDATORY UNIT TEST FIXING:
You MUST actually fix unit test failures:
- Implement proper mocking for database and external services
- Fix test assertions and expectations
- Ensure unit tests use UnitTestCase base class
- Execute fixed unit tests to verify they pass

MANDATORY RESULT TRACKING:
- You MUST save fixing results to /tmp/test-isolated-unit-fix-results.json
- Include success: true/false, unit_tests_fixed, tests_passing, mocking_implemented
- Document specific test fixes and mock implementations
- Only execute after Test Isolation Setup Agent confirms infrastructure

CRITICAL: Unit tests must be completely isolated from external dependencies.</parameter>
</invoke>
</function_calls>
```

### Integration Test Isolation Fixer:
```markdown
<function_calls>
<invoke name="Task">
<parameter name="subagent_type">test-fixer</parameter>
<parameter name="description">Fix integration tests with data isolation</parameter>
<parameter name="prompt">You are the Integration Test Isolation Fixer for integration test repairs.

Your responsibilities:
1. Fix failing integration tests with proper data isolation
2. Implement database transaction rollbacks for test isolation
3. Setup test-specific database schemas and data
4. Validate integration test fixes don't break other integration tests
5. Maintain separation from unit test infrastructure

MANDATORY INTEGRATION TEST FIXING:
You MUST actually fix integration test failures:
- Implement database transactions and rollbacks
- Setup isolated test data for each integration test
- Ensure integration tests use IntegrationTestCase base class
- Execute fixed integration tests to verify they pass

MANDATORY RESULT TRACKING:
- You MUST save fixing results to /tmp/test-isolated-integration-fix-results.json
- Include success: true/false, integration_tests_fixed, tests_passing, data_isolation_implemented
- Document specific test fixes and data isolation strategies
- Only execute after Test Isolation Setup Agent confirms infrastructure

CRITICAL: Integration tests must have proper data isolation and transaction management.</parameter>
</invoke>
</function_calls>
```

### Test Isolation Validator:
```markdown
<function_calls>
<invoke name="Task">
<parameter name="subagent_type">test-fixer</parameter>
<parameter name="description">Validate isolation and prevent cyclic failures</parameter>
<parameter name="prompt">You are the Test Isolation Validator for comprehensive validation.

Your responsibilities:
1. Validate all test fixes maintain proper isolation
2. Run complete test suite to ensure no cyclic failures
3. Check that unit and integration tests remain separated
4. Monitor for any new test dependencies or conflicts
5. Generate comprehensive validation report

MANDATORY ISOLATION VALIDATION:
You MUST actually validate test isolation:
- Execute complete test suite after all fixes
- Verify no cyclic test failures or dependencies
- Validate proper separation of unit and integration tests
- Check rollback points are still available if needed

MANDATORY RESULT TRACKING:
- You MUST save validation results to /tmp/test-isolated-validation-results.json
- Include success: true/false, full_suite_passing, isolation_maintained, cyclic_failures_prevented
- Document any remaining test failures or isolation breaches
- Only execute after all fixer agents complete their work

CRITICAL: All tests must pass without cyclic dependencies or isolation breaches.</parameter>
</invoke>
</function_calls>
```

## AGENT RESULT VERIFICATION (MANDATORY)

After spawning all 5 isolation agents, you MUST verify their results:

```bash
# MANDATORY: Verify all agents completed successfully
AGENT_RESULTS_DIR="/tmp"
AGENT_FILES=("test-isolated-dependency-results.json" "test-isolated-setup-results.json" "test-isolated-unit-fix-results.json" "test-isolated-integration-fix-results.json" "test-isolated-validation-results.json")

for result_file in "${AGENT_FILES[@]}"; do
    FULL_PATH="$AGENT_RESULTS_DIR/$result_file"
    if [ -f "$FULL_PATH" ]; then
        # Use jq to parse agent results
        AGENT_SUCCESS=$(jq -r '.success // false' "$FULL_PATH" 2>/dev/null || echo 'false')
        if [ "$AGENT_SUCCESS" != "true" ]; then
            echo "❌ CRITICAL: Isolated test fixing agent failed to complete successfully"
            echo "   Failed agent result: $result_file"
            echo "   Check agent logs for failure details"
            exit 1
        fi
    else
        echo "❌ CRITICAL: Missing isolated test fixing agent result file: $result_file"
        echo "   Agent may have failed to complete or save results"
        exit 1
    fi
done

echo "✅ All isolated test fixing agents completed successfully"
```

## COMPREHENSIVE TEST EXECUTION (MANDATORY)

After agent coordination, you MUST execute complete test suite to validate isolation:

```bash
# Detect framework and run comprehensive test suite
if [ -f "package.json" ] && grep -q "jest\|mocha\|vitest" package.json; then
    echo "🔒 Executing complete Jest test suite with isolation validation..."
    npx jest --verbose --runInBand --detectOpenHandles
    ISOLATION_TEST_EXIT_CODE=$?
elif [ -f "requirements.txt" ] || [ -f "setup.py" ] || [ -f "pyproject.toml" ]; then
    echo "🔒 Executing complete pytest suite with isolation validation..."
    python -m pytest -v --tb=short --maxfail=1
    ISOLATION_TEST_EXIT_CODE=$?
elif ls *.go 1> /dev/null 2>&1; then
    echo "🔒 Executing complete Go test suite with isolation validation..."
    go test -v ./... -count=1
    ISOLATION_TEST_EXIT_CODE=$?
elif [ -f "composer.json" ] && [ -d "vendor/phpunit" ]; then
    echo "🔒 Executing complete PHPUnit suite with isolation validation..."
    ./vendor/bin/phpunit --verbose --process-isolation
    ISOLATION_TEST_EXIT_CODE=$?
elif [ -f "Gemfile" ] && grep -q "rspec" Gemfile; then
    echo "🔒 Executing complete RSpec suite with isolation validation..."
    bundle exec rspec --format documentation
    ISOLATION_TEST_EXIT_CODE=$?
else
    echo "❌ No supported test framework detected for isolated test fixing"
    exit 1
fi

# MANDATORY: Validate isolated test execution success
if [ $ISOLATION_TEST_EXIT_CODE -ne 0 ]; then
    echo "❌ CRITICAL: Isolated test fixes failed with exit code $ISOLATION_TEST_EXIT_CODE"
    echo "   Test isolation was not successful - some tests are still failing"
    echo "   Check test output above for remaining failure details"
    echo "   Consider using rollback points to revert changes"
    exit $ISOLATION_TEST_EXIT_CODE
fi

echo "✅ All tests pass with proper isolation - no cyclic failures detected"
```

## Example Scenarios

### Scenario 1: Unit Test Breaking Integration Test
```php
// BEFORE: Shared base class causes conflict
class TestCase extends PHPUnit\Framework\TestCase {
    protected $db; // Sometimes mock, sometimes real
}

// AFTER: Separate base classes
class UnitTestCase extends PHPUnit\Framework\TestCase {
    protected $mockDb; // Always mock
}

class IntegrationTestCase extends PHPUnit\Framework\TestCase {
    protected $db; // Always real, with transactions
}
```

### Scenario 2: Fixture Conflict Resolution
```php
// BEFORE: Shared fixture modified by both test types
class UserFixture {
    public static $user = ['name' => 'Test'];
}

// AFTER: Isolated fixtures
class UnitUserFixture {
    public static function getMockUser() {
        return ['name' => 'MockUser_' . uniqid()];
    }
}

class IntegrationUserFixture {
    public static function createRealUser() {
        return DB::table('users')->insert([
            'name' => 'TestUser_' . uniqid()
        ]);
    }
}
```

## Success Metrics

- 🎯 **Zero cyclic failures**: No ping-pong fixing
- 🎯 **100% test isolation**: Unit and integration completely separated
- 🎯 **Full dependency tracking**: All relationships mapped
- 🎯 **Validated fixes**: Every change tested against related tests
- 🎯 **Clean rollback**: Any breaking change immediately reverted

## Agent Coordination

The command spawns these specialized agents:

1. **test-isolation-fixer**: Main fixer with isolation strategies
2. **test-dependency-tracker**: Tracks all test relationships
3. **test-impact-validator**: Validates changes don't break others
4. **test-rollback-manager**: Manages rollback points
5. **test-categorizer**: Ensures proper test type separation

## Fallback Strategy

If isolation fixing fails:
1. Create completely new test file with no shared dependencies
2. Gradually migrate working tests to isolated structure
3. Quarantine problematic tests for manual review
4. Generate detailed conflict report for debugging