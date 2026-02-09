# Shared Test Command Detection Component

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

This component provides unified test command detection across all test-related agents.

## Language-Specific Pattern Components
- **Go Testing**: See `test-golang-patterns.md` for comprehensive Go test patterns
- **Rust Testing**: See `test-rust-patterns.md` for comprehensive Rust test patterns

## Core Detection Function

```bash
#!/bin/bash

# Universal test command detection function
# Returns the appropriate test command for the project
detect_test_command() {
    local verbose="${1:-false}"
    
    [ "$verbose" = "true" ] && echo "🔍 Detecting test command for project..." >&2
    
    # PHP Projects (Composer/PHPUnit)
    if [ -f "composer.json" ]; then
        if grep -q '"test"' composer.json 2>/dev/null; then
            [ "$verbose" = "true" ] && echo "✅ Found composer test script" >&2
            echo "composer test"
            return 0
        elif [ -f "vendor/bin/phpunit" ]; then
            [ "$verbose" = "true" ] && echo "✅ Found PHPUnit binary" >&2
            echo "./vendor/bin/phpunit"
            return 0
        elif grep -q 'phpunit' composer.json 2>/dev/null; then
            [ "$verbose" = "true" ] && echo "✅ Found PHPUnit in composer.json" >&2
            echo "composer run-script test"
            return 0
        fi
    fi
    
    # Node.js Projects
    if [ -f "package.json" ]; then
        if grep -q '"test"' package.json 2>/dev/null; then
            # Check for specific test runners
            if grep -q 'jest' package.json 2>/dev/null; then
                [ "$verbose" = "true" ] && echo "✅ Found Jest test runner" >&2
                echo "npm test"
                return 0
            elif grep -q 'mocha' package.json 2>/dev/null; then
                [ "$verbose" = "true" ] && echo "✅ Found Mocha test runner" >&2
                echo "npm test"
                return 0
            elif grep -q 'vitest' package.json 2>/dev/null; then
                [ "$verbose" = "true" ] && echo "✅ Found Vitest test runner" >&2
                echo "npm test"
                return 0
            else
                [ "$verbose" = "true" ] && echo "✅ Found npm test script" >&2
                echo "npm test"
                return 0
            fi
        fi
        
        # Check for yarn
        if [ -f "yarn.lock" ] && command -v yarn &>/dev/null; then
            [ "$verbose" = "true" ] && echo "✅ Using Yarn for testing" >&2
            echo "yarn test"
            return 0
        fi
        
        # Check for pnpm
        if [ -f "pnpm-lock.yaml" ] && command -v pnpm &>/dev/null; then
            [ "$verbose" = "true" ] && echo "✅ Using pnpm for testing" >&2
            echo "pnpm test"
            return 0
        fi
    fi
    
    # Python Projects
    if [ -f "setup.py" ] || [ -f "pyproject.toml" ] || [ -f "requirements.txt" ]; then
        # Check for pytest
        if [ -f "pytest.ini" ] || [ -f "pyproject.toml" ] && grep -q 'pytest' pyproject.toml 2>/dev/null; then
            [ "$verbose" = "true" ] && echo "✅ Found pytest configuration" >&2
            echo "pytest"
            return 0
        elif command -v pytest &>/dev/null; then
            [ "$verbose" = "true" ] && echo "✅ Found pytest command" >&2
            echo "pytest"
            return 0
        fi
        
        # Check for Django
        if [ -f "manage.py" ]; then
            [ "$verbose" = "true" ] && echo "✅ Found Django project" >&2
            echo "python manage.py test"
            return 0
        fi
        
        # Check for tox
        if [ -f "tox.ini" ]; then
            [ "$verbose" = "true" ] && echo "✅ Found tox configuration" >&2
            echo "tox"
            return 0
        fi
        
        # Default Python unittest
        [ "$verbose" = "true" ] && echo "✅ Using Python unittest" >&2
        echo "python -m unittest discover"
        return 0
    fi
    
    # Go Projects (with enhanced patterns)
    if [ -f "go.mod" ]; then
        [ "$verbose" = "true" ] && echo "✅ Found Go module" >&2
        # Check for enhanced Go patterns if available
        if [ -f "${CLAUDE_AGENT_PATH:-}/test-golang-patterns.md" ]; then
            source "${CLAUDE_AGENT_PATH:-}/test-golang-patterns.md"
            generate_go_test_command "all" "true" "auto" "false" "true"
        else
            echo "go test -v -race -cover ./..."
        fi
        return 0
    fi
    
    # Java Projects (Maven)
    if [ -f "pom.xml" ]; then
        [ "$verbose" = "true" ] && echo "✅ Found Maven project" >&2
        echo "mvn test"
        return 0
    fi
    
    # Java Projects (Gradle)
    if [ -f "build.gradle" ] || [ -f "build.gradle.kts" ]; then
        [ "$verbose" = "true" ] && echo "✅ Found Gradle project" >&2
        if [ -f "gradlew" ]; then
            echo "./gradlew test"
        else
            echo "gradle test"
        fi
        return 0
    fi
    
    # Ruby Projects
    if [ -f "Gemfile" ]; then
        if grep -q 'rspec' Gemfile 2>/dev/null; then
            [ "$verbose" = "true" ] && echo "✅ Found RSpec" >&2
            echo "bundle exec rspec"
            return 0
        elif [ -f "Rakefile" ] && grep -q 'test' Rakefile 2>/dev/null; then
            [ "$verbose" = "true" ] && echo "✅ Found Rake test task" >&2
            echo "bundle exec rake test"
            return 0
        fi
    fi
    
    # Rust Projects (with enhanced patterns)
    if [ -f "Cargo.toml" ]; then
        [ "$verbose" = "true" ] && echo "✅ Found Cargo project" >&2
        # Check for enhanced Rust patterns if available
        if [ -f "${CLAUDE_AGENT_PATH:-}/test-rust-patterns.md" ]; then
            source "${CLAUDE_AGENT_PATH:-}/test-rust-patterns.md"
            generate_rust_test_command "all" "false" "auto" "false" "true"
        else
            echo "cargo test --all-targets -- --show-output"
        fi
        return 0
    fi
    
    # .NET Projects
    if ls *.csproj &>/dev/null || ls *.sln &>/dev/null; then
        [ "$verbose" = "true" ] && echo "✅ Found .NET project" >&2
        echo "dotnet test"
        return 0
    fi
    
    # Makefile with test target
    if [ -f "Makefile" ] && grep -q '^test:' Makefile 2>/dev/null; then
        [ "$verbose" = "true" ] && echo "✅ Found Makefile with test target" >&2
        echo "make test"
        return 0
    fi
    
    # Unknown - must ask user
    [ "$verbose" = "true" ] && echo "❌ Could not detect test command automatically" >&2
    echo "UNKNOWN"
    return 1
}

# Validate test results based on framework
validate_test_success() {
    local log_file="$1"
    local test_command="$2"
    
    if [ ! -f "$log_file" ]; then
        echo "ERROR: Log file not found: $log_file" >&2
        return 1
    fi
    
    # PHP/PHPUnit validation (ENHANCED)
    if [[ "$test_command" == *"composer"* ]] || [[ "$test_command" == *"phpunit"* ]]; then
        # MANDATORY: Check for positive success indicators first
        if grep -qE "(OK \([0-9]+ tests?|Tests: [0-9]+.*Assertions: [0-9]+.*OK)" "$log_file"; then
            # Now verify no failures
            if ! grep -qE "(FAILURES!|ERRORS!|Tests: [0-9]+.*Failures: [1-9]|Tests: [0-9]+.*Errors: [1-9])" "$log_file"; then
                return 0  # Success with positive verification
            fi
        fi
        return 1  # No success indicators or has failures
    fi
    
    # JavaScript/Jest/Mocha validation (ENHANCED)
    if [[ "$test_command" == *"npm"* ]] || [[ "$test_command" == *"yarn"* ]] || [[ "$test_command" == *"pnpm"* ]]; then
        # MANDATORY: Check for positive success indicators first
        if grep -qE "(Tests:[[:space:]]+[0-9]+.*passed|Test Suites:[[:space:]]+[0-9]+.*passed|✓.*passed|All tests passed)" "$log_file"; then
            # Now verify no failures
            if ! grep -qE "([0-9]+.*failed|FAIL|✕|Tests:[[:space:]]+[0-9]+.*failed)" "$log_file"; then
                return 0  # Success with positive verification
            fi
        fi
        return 1  # No success indicators or has failures
    fi
    
    # Python/pytest validation (ENHANCED)
    if [[ "$test_command" == *"pytest"* ]]; then
        # MANDATORY: Check for positive success indicators first
        if grep -qE "([0-9]+.*passed|test session starts|collected [0-9]+ items.*passed)" "$log_file"; then
            # Now verify no failures
            if ! grep -qE "([0-9]+.*failed|[0-9]+.*error|FAILED|ERROR)" "$log_file"; then
                return 0  # Success with positive verification
            fi
        fi
        return 1  # No success indicators or has failures
    fi
    
    # Go test validation (ENHANCED)
    if [[ "$test_command" == *"go test"* ]]; then
        # Use enhanced validation if available
        if [ -f "${CLAUDE_AGENT_PATH:-}/test-golang-patterns.md" ]; then
            source "${CLAUDE_AGENT_PATH:-}/test-golang-patterns.md"
            validate_go_test_success "$log_file"
            return $?
        fi
        # MANDATORY: Enhanced fallback validation
        if grep -qE "(PASS|ok[[:space:]]+.*[0-9]+\.[0-9]+s)" "$log_file"; then
            # Now verify no failures
            if ! grep -qE "(FAIL|--- FAIL:|panic:)" "$log_file"; then
                return 0  # Success with positive verification
            fi
        fi
        return 1  # No success indicators or has failures
    fi
    
    # Maven test validation (ENHANCED)
    if [[ "$test_command" == *"mvn"* ]]; then
        # MANDATORY: Check for positive success indicators first
        if grep -qE "(BUILD SUCCESS|Tests run: [0-9]+.*Failures: 0.*Errors: 0)" "$log_file"; then
            # Now verify no failures
            if ! grep -qE "(BUILD FAILURE|Tests run: [0-9]+.*Failures: [1-9]|Tests run: [0-9]+.*Errors: [1-9])" "$log_file"; then
                return 0  # Success with positive verification
            fi
        fi
        return 1  # No success indicators or has failures
    fi

    # Gradle test validation (ENHANCED)
    if [[ "$test_command" == *"gradle"* ]]; then
        # MANDATORY: Check for positive success indicators first
        if grep -qE "(BUILD SUCCESSFUL|Test.*PASSED)" "$log_file"; then
            # Now verify no failures
            if ! grep -qE "(BUILD FAILED|Test.*FAILED)" "$log_file"; then
                return 0  # Success with positive verification
            fi
        fi
        return 1  # No success indicators or has failures
    fi
    
    # Cargo test validation (ENHANCED)
    if [[ "$test_command" == *"cargo"* ]]; then
        # Use enhanced validation if available
        if [ -f "${CLAUDE_AGENT_PATH:-}/test-rust-patterns.md" ]; then
            source "${CLAUDE_AGENT_PATH:-}/test-rust-patterns.md"
            validate_rust_test_success "$log_file"
            return $?
        fi
        # MANDATORY: Enhanced fallback validation
        if grep -qE "(test result: ok|running [0-9]+ tests.*ok)" "$log_file"; then
            # Now verify no failures
            if ! grep -qE "(test result: FAILED|[0-9]+ failed)" "$log_file"; then
                return 0  # Success with positive verification
            fi
        fi
        return 1  # No success indicators or has failures
    fi

    # .NET test validation (ENHANCED)
    if [[ "$test_command" == *"dotnet"* ]]; then
        # MANDATORY: Check for positive success indicators first
        if grep -qE "(Passed![[:space:]]*[0-9]+|Test Run Successful)" "$log_file"; then
            # Now verify no failures
            if ! grep -qE "(Failed![[:space:]]*[1-9]|Test Run Failed)" "$log_file"; then
                return 0  # Success with positive verification
            fi
        fi
        return 1  # No success indicators or has failures
    fi
    
    # MANDATORY: Verify positive success indicators first
    if ! [ -f "$log_file" ] || ! [ -s "$log_file" ]; then
        echo "❌ CRITICAL: Test log file missing or empty" >&2
        return 1
    fi

    # Check for positive success indicators FIRST (ENHANCED PATTERNS)
    if grep -qE "(Tests: [0-9]+.*Passed|✓ [0-9]+ test|All tests passed|PASSED|OK \([0-9]+ test|[0-9]+ tests? passed|Test execution: SUCCESS|✓.*SUCCESS)" "$log_file"; then
        # Now verify no failures
        if grep -qiE "(FAIL|ERROR|FAILED|✕|✖|[0-9]+.*failed|Test execution: FAILED)" "$log_file"; then
            return 1  # Has failures despite success indicators
        fi
        return 0  # Has success indicators and no failures
    else
        echo "❌ CRITICAL: No positive test success indicators found" >&2
        return 1  # No success indicators = failure
    fi
}

# Count test failures based on framework
count_test_failures() {
    local log_file="$1"
    local test_command="$2"
    local count=0
    
    if [ ! -f "$log_file" ]; then
        echo "999"  # Return high failure count when log missing
        return 1
    fi
    
    # PHP/PHPUnit
    if [[ "$test_command" == *"composer"* ]] || [[ "$test_command" == *"phpunit"* ]]; then
        # Look for summary line like "Tests: 10, Assertions: 20, Failures: 3"
        count=$(grep -oE 'Failures: [0-9]+' "$log_file" | grep -oE '[0-9]+' | head -1)
        if [ -z "$count" ]; then
            # Fallback: count F or E markers
            count=$(grep -c '^[FE]$' "$log_file")
        fi
    
    # JavaScript
    elif [[ "$test_command" == *"npm"* ]] || [[ "$test_command" == *"yarn"* ]]; then
        # Look for "X failed" pattern
        count=$(grep -oE '[0-9]+ failed' "$log_file" | grep -oE '[0-9]+' | head -1)
        if [ -z "$count" ]; then
            # Fallback: count FAIL patterns
            count=$(grep -c 'FAIL' "$log_file")
        fi
    
    # Python/pytest
    elif [[ "$test_command" == *"pytest"* ]]; then
        count=$(grep -oE '[0-9]+ failed' "$log_file" | grep -oE '[0-9]+' | head -1)
        if [ -z "$count" ]; then
            # Fallback: count FAILED patterns
            count=$(grep -c 'FAILED' "$log_file")
        fi
    
    # Go (enhanced)
    elif [[ "$test_command" == *"go test"* ]]; then
        # Use enhanced counting if available
        if [ -f "${CLAUDE_AGENT_PATH:-}/test-golang-patterns.md" ]; then
            source "${CLAUDE_AGENT_PATH:-}/test-golang-patterns.md"
            count=$(count_go_test_failures "$log_file")
        else
            count=$(grep -c '--- FAIL:' "$log_file")
        fi
    
    # Generic fallback
    else
        count=$(grep -ciE '(FAIL|ERROR|FAILED)' "$log_file")
    fi
    
    # Ensure we return a number
    if [ -z "$count" ] || [ "$count" = "" ]; then
        echo "0"
    else
        echo "$count"
    fi
}

# MANDATORY: Enhanced test execution verification
verify_test_execution() {
    local log_file="$1"
    local test_command="$2"
    local expected_test_count="${3:-1}"

    echo "🔍 MANDATORY TEST EXECUTION VERIFICATION"
    echo "======================================="

    # Check log file exists and has content
    if ! [ -f "$log_file" ] || ! [ -s "$log_file" ]; then
        echo "❌ CRITICAL FAILURE: Test log file missing or empty: $log_file"
        return 1
    fi

    # Check for test execution evidence
    if ! grep -qiE "(test|spec)" "$log_file"; then
        echo "❌ CRITICAL FAILURE: No test execution evidence found in log"
        return 1
    fi

    # Validate using framework-specific patterns
    if validate_test_success "$log_file" "$test_command"; then
        echo "✅ TEST EXECUTION VERIFIED: All tests passed with positive confirmation"
        return 0
    else
        echo "❌ CRITICAL FAILURE: Test execution failed validation"
        echo "   Log file: $log_file"
        echo "   Command: $test_command"
        echo "   Expected: 100% success rate"
        return 1
    fi
}

# Interactive test command confirmation
confirm_test_command() {
    local detected_command="$1"
    
    if [ "$detected_command" = "UNKNOWN" ]; then
        echo "❌ Could not automatically detect the test command for this project." >&2
        echo "" >&2
        echo "Common test commands:" >&2
        echo "  - PHP: composer test, ./vendor/bin/phpunit" >&2
        echo "  - Node.js: npm test, yarn test, pnpm test" >&2
        echo "  - Python: pytest, python -m unittest discover" >&2
        echo "  - Go: go test ./..." >&2
        echo "  - Java: mvn test, gradle test" >&2
        echo "  - Ruby: bundle exec rspec, rake test" >&2
        echo "  - Rust: cargo test" >&2
        echo "  - .NET: dotnet test" >&2
        echo "" >&2
        echo "⚠️  You must specify the correct test command to proceed." >&2
        return 1
    else
        echo "✅ Detected test command: $detected_command" >&2
        return 0
    fi
}
```

## Usage Examples

### Basic Detection
```bash
# Detect and use test command
TEST_CMD=$(detect_test_command)
if [ "$TEST_CMD" = "UNKNOWN" ]; then
    echo "Please specify test command"
    exit 1
fi

echo "Running tests with: $TEST_CMD"
$TEST_CMD
```

### With Validation
```bash
# Run tests and validate results
TEST_CMD=$(detect_test_command true)  # verbose mode
$TEST_CMD 2>&1 | tee test_output.log

if validate_test_success "test_output.log" "$TEST_CMD"; then
    echo "✅ All tests passed!"
else
    FAILURES=$(count_test_failures "test_output.log" "$TEST_CMD")
    echo "❌ Tests failed: $FAILURES failures detected"
fi
```

### Integration in Agents
```bash
# Source this component in agent scripts
source /path/to/test-command-detection.md

# Use in test-fixer agent
TEST_CMD=$(detect_test_command)
confirm_test_command "$TEST_CMD" || exit 1

# Run tests
$TEST_CMD 2>&1 | tee initial_test_run.log

# Count failures
TOTAL_FAILURES=$(count_test_failures "initial_test_run.log" "$TEST_CMD")
echo "Found $TOTAL_FAILURES test failures to fix"

# ... fix tests ...

# Validate success
$TEST_CMD 2>&1 | tee final_test_run.log
if validate_test_success "final_test_run.log" "$TEST_CMD"; then
    echo "✅ Successfully fixed all tests!"
fi
```

## Framework-Specific Patterns

### PHPUnit Success/Failure Patterns
- **Success**: "OK (X tests, Y assertions)"
- **Failure**: "FAILURES!", "Tests: X, Assertions: Y, Failures: Z"
- **Error marker**: Lines with just "F" or "E"

### Jest/Mocha Success/Failure Patterns
- **Success**: "Test Suites: X passed", "✓", "PASS"
- **Failure**: "X failed", "✕", "FAIL"

### Pytest Success/Failure Patterns
- **Success**: "X passed"
- **Failure**: "X failed", "X error"

### Go Test Success/Failure Patterns
- **Success**: "PASS", "ok"
- **Failure**: "FAIL", "--- FAIL:"

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

## 🔍 UNIT VS INTEGRATION TEST DETECTION

**Patterns for detecting and separating test types:**

### Detection Patterns by Language

#### PHP (PHPUnit)
```yaml
unit_tests:
  directories: ["tests/Unit", "test/unit"]
  file_patterns: ["*Test.php", "!*IntegrationTest.php"]
  annotations: ["@group unit"]
  config_suite: "unit"

integration_tests:
  directories: ["tests/Integration", "tests/Feature"]
  file_patterns: ["*IntegrationTest.php", "*FeatureTest.php"]
  annotations: ["@group integration", "@group feature"]
  config_suite: "integration"
```

#### JavaScript/TypeScript (Jest)
```yaml
unit_tests:
  directories: ["__tests__/unit", "src/**/__tests__"]
  file_patterns: ["*.test.ts", "*.test.js", "!*.integration.*"]
  config_project: "unit"

integration_tests:
  directories: ["__tests__/integration", "test/integration"]
  file_patterns: ["*.integration.test.ts", "*.integration.test.js"]
  config_project: "integration"
```

#### Go
```yaml
unit_tests:
  file_patterns: ["*_test.go"]
  build_tags: ["!integration"]

integration_tests:
  file_patterns: ["*_test.go"]
  build_tags: ["integration"]
```

### Detection Priority
1. Explicit configuration (phpunit.xml suite, jest.config.js project)
2. Directory structure (tests/Unit vs tests/Integration)
3. File naming patterns (*IntegrationTest.php)
4. Annotations/tags (@group integration)
5. Content analysis (mocked vs real dependencies)

## Best Practices

1. **Always detect before running**: Never assume `npm test`
2. **Validate detection**: Confirm with user if uncertain
3. **Use framework-specific validation**: Different frameworks have different output patterns
4. **Count accurately**: Use framework-specific counting methods
5. **Handle edge cases**: Some projects may have custom test commands
6. **Leverage language patterns**: Use Go and Rust pattern components for advanced features
7. **Enable optimizations**: Use race detection, coverage, parallel execution where appropriate