---
allowed-tools: all
description: **EXECUTE all unit tests** with comprehensive coverage analysis and parallel agent coordination
---

# ⚡⚡⚡ CRITICAL REQUIREMENT: UNIT TEST EXECUTION AND VALIDATION! ⚡⚡⚡

**THIS IS NOT A SIMPLE TEST RUN - THIS IS A COMPREHENSIVE UNIT TEST EXECUTION SYSTEM!**

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

### Failure Response Protocol
When ANY issue is detected:
1. **STOP** - Do not proceed to next steps
2. **REPORT** - List all issues with file:line references
3. **FIX** - Resolve ALL issues before continuing
4. **VERIFY** - Re-run tests to confirm 100% clean execution

### Exit Codes
- `0` = Perfect execution (no warnings, no deprecations, no failures)
- `1` = Any failure, warning, deprecation, or incomplete test
- `2` = Configuration or setup error

## ⚠️ SPECIFICATION-FIRST PHILOSOPHY (MANDATORY)

**Unit tests are behavioral specifications, NOT confirmations of existing code.**
- Write test specifications BEFORE reading the implementation
- Test names must describe REQUIREMENTS ("it should return X when given Y"), not implementation details ("it calls function Z")
- Tests must fail if the feature is broken in real consumer usage
- See `templates/CLAUDE.md` → "MANDATORY: Specification-First Testing" for full mandate

## 📋 TEST SCOPE DEFINITION

**This command executes UNIT TESTS ONLY - completely separate from integration tests:**

### Scope Boundaries
- ✅ **INCLUDES**: All unit tests (`*Test.php`, `*.test.ts`, `*_test.go`, etc.)
- ❌ **EXCLUDES**: Integration tests, E2E tests, API tests
- ❌ **EXCLUDES**: Tests requiring external services (database, cache, queues)

### What "ALL Tests" Means for Unit Tests
When running `/test unit` with no arguments:
- Execute ALL unit tests in the project
- Do NOT include integration tests
- Do NOT include E2E tests
- Maintain strict isolation from other test types

### Unit Test Characteristics
- No external dependencies (mocked)
- Fast execution (milliseconds per test)
- Isolated (no shared state between tests)
- Deterministic (same result every run)

### Context Detection
This command automatically detects unit tests by:
- Directory patterns: `tests/Unit/`, `test/unit/`, `__tests__/unit/`
- File patterns: `*Test.php` (not `*IntegrationTest.php`)
- Annotations: `@group unit`, `@unit`
- Configuration: `phpunit.xml` unit suite, `jest.config.js` unit projects

When you run `/test/unit`, you are REQUIRED to:

1. **EXECUTE** all unit tests with framework-specific optimizations and parallel processing
2. **ANALYZE** test coverage gaps and generate actionable improvement recommendations
3. **VALIDATE** unit test quality and identify potential improvements
4. **REPORT** comprehensive test results with detailed failure analysis
5. **USE MULTIPLE AGENTS** for parallel unit test execution using Task tool

I'll spawn 5 specialized agents using Task tool for comprehensive unit testing:

**Test Discovery Agent:**
```markdown
<function_calls>
<invoke name="Task">
<parameter name="subagent_type">test-fixer</parameter>
<parameter name="description">Discover and categorize unit tests</parameter>
<parameter name="prompt">You are the Test Discovery Agent for unit test execution.

Your responsibilities:
1. Scan the project for all unit test files across frameworks (Jest, pytest, Go test, RSpec, PHPUnit)
2. Identify test frameworks and configuration patterns
3. Identify critical behaviors requiring specification (not just analyze existing tests)
4. Categorize tests by speed and complexity (fast/slow tests)
5. Map test coverage gaps and missing test files
6. Generate comprehensive test inventory with framework optimizations
7. Validate test structure and organization
8. Report on test file distribution and patterns

MANDATORY RESULT TRACKING:
- You MUST save your analysis results to /tmp/test-unit-discovery-results.json
- Include success: true/false field in your JSON output
- Document framework detected, test count, and file paths
- Report any discovery failures or structural issues

Execute discovery commands based on detected framework:
- Jest: find . -name "*.test.js" -o -name "*.spec.js" | head -10
- pytest: find . -name "test_*.py" -o -name "*_test.py" | head -10
- PHPUnit: find . -name "*Test.php" | head -10
- Go: find . -name "*_test.go" | head -10

CRITICAL: Your result JSON must include actual test execution preparation data.</parameter>
</invoke>
</function_calls>
```

**Test Execution Agent:**
```markdown
<function_calls>
<invoke name="Task">
<parameter name="subagent_type">test-fixer</parameter>
<parameter name="description">Execute unit tests in parallel</parameter>
<parameter name="prompt">You are the Test Execution Agent for unit tests.

Your responsibilities:
1. Execute unit tests in parallel batches using framework-specific optimizations
2. Run Jest with --maxWorkers, pytest with -n auto, Go with -parallel
3. Capture comprehensive test results and execution metrics
4. Monitor test execution progress and performance
5. Generate detailed test execution logs
6. Validate 100% success rate requirement (MANDATORY)
7. Block execution if any tests fail

CRITICAL: 100% TEST SUCCESS RATE REQUIRED
- Any failing tests MUST block execution
- Provide detailed failure analysis
- No coverage analysis until 100% success achieved

MANDATORY TEST EXECUTION COMMANDS:
You MUST actually run test commands, not just coordinate:

Jest projects: npx jest --coverage --maxWorkers=4 --verbose --testPathPattern="test|spec"
pytest projects: python -m pytest -v --cov=. --cov-report=html --cov-report=term -n auto
Go projects: go test -v -race -coverprofile=coverage.out -parallel 4 ./...
PHPUnit projects: ./vendor/bin/phpunit --coverage-html coverage --coverage-text --process-isolation
RSpec projects: bundle exec rspec --format documentation

MANDATORY RESULT TRACKING:
- You MUST save execution results to /tmp/test-unit-execution-results.json
- Include success: true/false, exit_code, test_count, failures, and duration
- Capture stdout/stderr from actual test execution
- CRITICAL: success field must reflect actual test execution outcome

Your agent is not successful unless you actually execute test commands.</parameter>
</invoke>
</function_calls>
```

**Test Analysis Agent:**
```markdown
<function_calls>
<invoke name="Task">
<parameter name="subagent_type">test-fixer</parameter>
<parameter name="description">Analyze test failures and quality</parameter>
<parameter name="prompt">You are the Test Analysis Agent for unit tests.

Your responsibilities:
1. Analyze failing test results and identify root causes
2. Parse framework-specific error messages and stack traces
3. Identify patterns in test failures (flaky tests, environment issues)
4. Validate test quality against best practices
5. Check for proper assertions, mocking, and test structure
6. Generate actionable recommendations for test improvements
7. Prioritize fixes by impact and complexity

MANDATORY RESULT TRACKING:
- You MUST save analysis results to /tmp/test-unit-analysis-results.json
- Include success: true/false field based on analysis completion
- Document failure patterns, root causes, and recommendations
- Only execute after Test Execution Agent confirms results

Analysis includes:
- Root cause analysis of failures
- Test quality validation
- Best practices compliance
- Flaky test identification
- Performance bottlenecks
- Structural improvements

CRITICAL: Wait for execution results before analyzing.</parameter>
</invoke>
</function_calls>
```

**Coverage Agent:**
```markdown
<function_calls>
<invoke name="Task">
<parameter name="subagent_type">test-fixer</parameter>
<parameter name="description">Measure comprehensive test coverage</parameter>
<parameter name="prompt">You are the Coverage Agent for unit tests.

Your responsibilities:
1. Generate comprehensive test coverage reports using framework tools
2. Calculate line, branch, and function coverage metrics
3. Identify uncovered code paths and critical gaps
4. Parse coverage data (Jest coverage-final.json, pytest .coverage, Go coverage.out)
5. Prioritize coverage gaps by business impact
6. Generate actionable recommendations for test additions
7. Create coverage improvement roadmap

MANDATORY RESULT TRACKING:
- You MUST save coverage results to /tmp/test-unit-coverage-results.json
- Include success: true/false field based on coverage generation
- Document coverage percentages, gaps, and improvement recommendations
- Only execute after successful test execution

Coverage analysis includes:
- Line coverage percentage and gaps
- Branch coverage analysis
- Function/method coverage
- Critical path coverage validation
- Coverage trend analysis
- Gap prioritization by importance

Generate reports in HTML and JSON formats based on framework detected.</parameter>
</invoke>
</function_calls>
```

**Fix Coordinator Agent:**
```markdown
<function_calls>
<invoke name="Task">
<parameter name="subagent_type">test-fixer</parameter>
<parameter name="description">Coordinate test fixes and reporting</parameter>
<parameter name="prompt">You are the Fix Coordinator Agent for unit tests.

Your responsibilities:
1. Coordinate outputs from all test agents
2. Compile comprehensive test execution report
3. Prioritize identified issues by severity and impact
4. Generate actionable fix recommendations
5. Create unified test quality dashboard
6. Monitor fix implementation progress
7. Validate that all requirements are met

MANDATORY RESULT AGGREGATION:
- Aggregate results from /tmp/test-unit-*-results.json files
- Validate all agents completed successfully
- Create unified status report
- Prioritize fixes by impact
- Generate implementation timeline
- Validate completion criteria
- Provide executive summary

MANDATORY RESULT TRACKING:
- You MUST save coordination results to /tmp/test-unit-coordinator-results.json
- Include success: true/false based on overall unit test success
- Document completion status for all phases
- Report any coordination failures or missing agent results

COMPLETION VALIDATION:
- ✅ 100% unit test success rate achieved
- ✅ All unit tests discovered and executed
- ✅ Test coverage analyzed with gap identification
- ✅ Test quality validated against best practices
- ✅ Actionable recommendations provided
- ✅ Performance metrics collected

Generate final report to /tmp/unit-test-final-report.json with executive summary.</parameter>
</invoke>
</function_calls>
```

**FORBIDDEN BEHAVIORS:**
- ❌ "Run basic npm test command" → NO! Use framework-specific optimizations!
- ❌ "Skip coverage analysis" → NO! Comprehensive coverage reporting required!
- ❌ **"Accept any test failures"** → NO! 100% SUCCESS RATE MANDATORY!
- ❌ **"Continue with failing tests"** → NO! ALL FAILURES MUST BE FIXED!
- ❌ "Ignore test failures" → NO! Detailed failure analysis and fixing required!
- ❌ "Single-threaded execution" → NO! Use parallel agent coordination!
- ❌ "Generic test output" → NO! Framework-specific parsing and reporting!
- ❌ Writing tests by reading source code and confirming what it does (confirmation testing)

**MANDATORY WORKFLOW:**
```
1. Framework detection → Identify test framework and configuration
2. IMMEDIATELY spawn 5 agents using Task tool for parallel execution
3. AGENT RESULT VERIFICATION → Validate all agents completed successfully
4. Test discovery → Find all unit test files and categorize them
5. Parallel execution → Run tests across multiple agents
6. **100% SUCCESS VALIDATION** → BLOCK EXECUTION if any test fails
7. Coverage analysis → Generate comprehensive coverage reports only after 100% success
8. FINAL SUCCESS VALIDATION → Verify all tests pass and coverage meets thresholds
```

## AGENT RESULT VERIFICATION (MANDATORY)

After spawning all 5 agents, you MUST verify their results:

```bash
# MANDATORY: Verify all agents completed successfully
AGENT_RESULTS_DIR="/tmp"
AGENT_FILES=("test-unit-discovery-results.json" "test-unit-execution-results.json" "test-unit-analysis-results.json" "test-unit-coverage-results.json" "test-unit-coordinator-results.json")

for result_file in "${AGENT_FILES[@]}"; do
    FULL_PATH="$AGENT_RESULTS_DIR/$result_file"
    if [ -f "$FULL_PATH" ]; then
        # Use jq to parse agent results
        AGENT_SUCCESS=$(jq -r '.success // false' "$FULL_PATH" 2>/dev/null || echo 'false')
        if [ "$AGENT_SUCCESS" != "true" ]; then
            echo "❌ CRITICAL: Agent failed to complete unit test execution successfully"
            echo "   Failed agent result: $result_file"
            echo "   Check agent logs for failure details"
            exit 1
        fi
    else
        echo "❌ CRITICAL: Missing agent result file: $result_file"
        echo "   Agent may have failed to complete or save results"
        exit 1
    fi
done

echo "✅ All unit test agents completed successfully"
```

## FRAMEWORK-SPECIFIC ACTUAL TEST EXECUTION (MANDATORY)

After agent coordination, you MUST execute actual unit tests:

```bash
# Detect framework and run appropriate tests
if [ -f "package.json" ] && grep -q "jest\|mocha\|vitest" package.json; then
    echo "🧪 Executing Jest/Node.js unit tests..."
    npx jest --coverage --maxWorkers=4 --verbose --testPathPattern="test|spec"
    TEST_EXIT_CODE=$?
elif [ -f "requirements.txt" ] || [ -f "setup.py" ] || [ -f "pyproject.toml" ]; then
    echo "🧪 Executing pytest unit tests..."
    python -m pytest -v --cov=. --cov-report=html --cov-report=term -n auto
    TEST_EXIT_CODE=$?
elif ls *.go 1> /dev/null 2>&1; then
    echo "🧪 Executing Go unit tests..."
    go test -v -race -coverprofile=coverage.out -parallel 4 ./...
    TEST_EXIT_CODE=$?
elif [ -f "composer.json" ] && [ -d "vendor/phpunit" ]; then
    echo "🧪 Executing PHPUnit tests..."
    ./vendor/bin/phpunit --coverage-html coverage --coverage-text --process-isolation
    TEST_EXIT_CODE=$?
elif [ -f "Gemfile" ] && grep -q "rspec" Gemfile; then
    echo "🧪 Executing RSpec tests..."
    bundle exec rspec --format documentation
    TEST_EXIT_CODE=$?
else
    echo "❌ No supported test framework detected"
    exit 1
fi

# MANDATORY: Validate test execution success
if [ $TEST_EXIT_CODE -ne 0 ]; then
    echo "❌ CRITICAL: Unit tests failed with exit code $TEST_EXIT_CODE"
    echo "   All unit tests must pass before proceeding"
    echo "   Check test output above for failure details"
    exit $TEST_EXIT_CODE
fi

echo "✅ All unit tests executed successfully"
```

**YOU ARE NOT DONE UNTIL:**
- ✅ **100% UNIT TEST SUCCESS RATE ACHIEVED** - NO FAILURES ALLOWED
- ✅ All unit tests discovered and executed successfully
- ✅ Test coverage analyzed with gap identification
- ✅ **ZERO FAILED TESTS** - Any failure must be fixed before proceeding
- ✅ Performance metrics collected and reported
- ✅ Actionable recommendations provided for improvements
- ✅ Test quality validated and documented

---

🛑 **MANDATORY UNIT TEST EXECUTION PROTOCOL** 🛑
1. Re-read ~/.claude/CLAUDE.md RIGHT NOW
2. Check current test framework configuration
3. Verify unit test structure and organization

Execute comprehensive unit test execution with ZERO tolerance for incomplete coverage analysis.

**FORBIDDEN SHORTCUT PATTERNS:**
- "Basic test run is sufficient" → NO, comprehensive execution required
- "Skip slow tests for speed" → NO, all tests must be executed
- "Coverage reports are optional" → NO, mandatory coverage analysis
- "Manual failure analysis is fine" → NO, automated analysis required
- "Single agent execution is faster" → NO, parallel execution mandatory

You are executing unit tests for: $ARGUMENTS

**Command-line PHP Control Flags:**
Parse and respect these flags in $ARGUMENTS:
- `--no-php`: Skip all PHP-specific behaviors and structure validation
- `--skip-php-structure-check`: Skip PHP structure validation only

Let me ultrathink about comprehensive unit test execution with parallel agent coordination.

🚨 **REMEMBER: Unit tests are the foundation of code quality and reliability!** 🚨

**Test Output Best Practices:**

⚠️ **CRITICAL: Clean Test Output Requirements**
- NO console output (var_dump, print_r, echo) in test code
- Use assertions for validation, not debug output
- For PHP tests, use the TestDebugHelper trait from templates/shared/test-debug-helper.md
- Enable debug output ONLY via environment variables (TEST_DEBUG=1)
- Output to STDERR for debugging to avoid interfering with test parsers

**Agent Coordination Protocol:**

The 5 agents work in coordinated phases:

**Phase 1: Discovery (Test Discovery Agent)**
- Framework detection and configuration
- Test file discovery and categorization
- Structure validation
- Clean output verification (no debug statements)
- Results: /tmp/test-discovery-results.json

**Phase 2: Execution (Test Execution Agent)**
- Parallel test execution with framework optimizations
- Real-time progress monitoring
- Performance metrics collection
- Mandatory 100% success validation
- Results: /tmp/test-execution-results.json

**Phase 3: Analysis (Test Analysis Agent + Coverage Agent)**
- Failure analysis and root cause identification
- Test quality validation
- Coverage gap analysis and prioritization
- Results: /tmp/test-analysis-results.json, /tmp/coverage-results.json

**Phase 4: Coordination (Fix Coordinator Agent)**
- Result aggregation and prioritization
- Comprehensive reporting
- Action plan generation
- Results: /tmp/unit-test-final-report.json

**Framework-Specific Optimizations:**

**Jest:**
```bash
npx jest --coverage --maxWorkers=4 --verbose --testPathPattern="test|spec"
```

**pytest:**
```bash
python -m pytest -v --cov=. --cov-report=html --cov-report=term -n auto
```

**Go test:**
```bash
go test -v -race -coverprofile=coverage.out -parallel 4 ./...
```

**RSpec:**
```bash
bundle exec rspec --format documentation --format html --out rspec_results.html
```

**PHPUnit:**
```bash
./vendor/bin/phpunit --coverage-html coverage --coverage-text --process-isolation
```

**Performance Monitoring:**

Each agent tracks:
- Execution time per test suite
- Memory usage during execution
- Parallel execution efficiency
- Framework-specific optimizations applied
- Coverage generation time

**Quality Validation Criteria:**

- Test structure follows framework best practices
- Proper use of assertions and expectations
- Appropriate mocking and stubbing
- Test isolation and independence
- Descriptive test names and organization
- Adequate test coverage (>80% recommended)

**Failure Analysis Framework:**

1. **Syntax Errors**: Code compilation/parsing failures
2. **Logic Errors**: Incorrect test expectations or implementations
3. **Environment Issues**: Missing dependencies, configuration problems
4. **Flaky Tests**: Inconsistent results due to timing or state issues
5. **Performance Issues**: Tests exceeding reasonable execution time

**Coverage Gap Prioritization:**

1. **Critical Paths**: Core business logic and error handling
2. **Security Functions**: Authentication, authorization, input validation
3. **Data Processing**: Database operations, API integrations
4. **Edge Cases**: Boundary conditions, error scenarios
5. **Utility Functions**: Helper methods and common operations

**Final Verification Checklist:**
- [ ] All 5 agents spawned successfully using Task tool
- [ ] Framework detection completed correctly
- [ ] All unit tests discovered and categorized
- [ ] 100% test success rate achieved (MANDATORY)
- [ ] Comprehensive coverage analysis completed
- [ ] Test quality validated against best practices
- [ ] Performance metrics collected and analyzed
- [ ] Actionable recommendations generated
- [ ] Final coordination report produced
- [ ] All temporary coordination files cleaned up

**Anti-Patterns to Avoid:**
- ❌ Using bash functions with & instead of Task tool
- ❌ Running tests without coverage analysis
- ❌ Accepting any test failures without fixing
- ❌ Single-threaded execution without parallelization
- ❌ Generic reporting without framework-specific insights
- ❌ Skipping test quality validation
- ❌ Missing coordination between agents

**REMEMBER:**
This is UNIT TEST EXECUTION mode with true parallel agent coordination using Task tool. The goal is comprehensive testing with coverage analysis, quality validation, and performance optimization through specialized agent collaboration.

Executing comprehensive unit test execution protocol with parallel Task tool agent coordination...