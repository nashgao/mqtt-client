---
allowed-tools: all
description: Test-Driven Development workflow with comprehensive red-green-refactor cycle automation
intensity: ⚡⚡⚡⚡⚡
pattern: 🔄🔄🔄🔄🔄
---

# 🔄🔄🔄🔄🔄 CRITICAL TDD WORKFLOW: COMPREHENSIVE RED-GREEN-REFACTOR AUTOMATION! 🔄🔄🔄🔄🔄

## ⚠️ ENFORCED DEFAULT — THIS IS NOT OPT-IN

**This is the MANDATORY default workflow for ALL test writing across all test commands.**
All other test commands (`/test:unit`, `/test:integration`, `/test:e2e`, `/test:coverage`) MUST follow this specification-first philosophy. Tests are **executable specifications** — they define what code SHOULD do, not confirm what it DOES.

**Key principle: Never read implementation code before writing a test.** Write what the code should do based on requirements, interfaces, and consumer expectations. If the test fails, the code is wrong — not the test.

When you run `/test workflows/tdd`, you are REQUIRED to:

1. **IMPLEMENT** full Test-Driven Development workflow with automated red-green-refactor cycles
2. **AUTOMATE** test creation, execution, and refactoring processes
3. **VALIDATE** each TDD cycle with comprehensive quality checks
4. **USE MULTIPLE AGENTS** for parallel TDD workflow execution:
   - Spawn one agent per TDD cycle phase (red, green, refactor)
   - Spawn agents for different code modules or features
   - Say: "I'll spawn multiple agents to execute TDD workflow across all development phases in parallel"
5. **OPTIMIZE** code quality through systematic refactoring
6. **TRACK** TDD metrics and workflow effectiveness

## 🎯 USE MULTIPLE AGENTS

**MANDATORY AGENT SPAWNING FOR TDD WORKFLOW:**
```
"I'll spawn multiple agents to handle TDD workflow comprehensively:
- Red Phase Agent: Write failing tests that define requirements
- Green Phase Agent: Implement minimal code to make tests pass
- Refactor Phase Agent: Improve code quality while maintaining functionality
- Test Quality Agent: Ensure tests are well-designed and maintainable
- Coverage Agent: Validate test coverage and identify gaps
- Metrics Agent: Track TDD cycle efficiency and quality metrics
- Integration Agent: Ensure TDD workflow integrates with existing codebase"
```

## 🚨 FORBIDDEN BEHAVIORS

**NEVER:**
- ❌ Skip writing tests first → NO! Tests must come before implementation!
- ❌ Write more code than needed to pass tests → NO! Minimal implementation only!
- ❌ Skip refactoring phase → NO! Code quality improvement is mandatory!
- ❌ "TDD takes too long" → NO! TDD improves long-term velocity!
- ❌ Write tests after implementation → NO! Tests must drive development!
- ❌ Skip running tests between phases → NO! Continuous validation required!

**MANDATORY WORKFLOW:**
```
1. Red Phase → Write failing test that defines new requirement
2. IMMEDIATELY spawn agents for parallel TDD execution
3. Green Phase → Write minimal code to make test pass
4. Refactor Phase → Improve code quality while maintaining functionality
5. Integration → Ensure changes work with existing codebase
6. VERIFY TDD cycle completion and quality metrics
```

**YOU ARE NOT DONE UNTIL:**
- ✅ ALL TDD cycles are completed with red-green-refactor phases
- ✅ Tests are well-designed and maintainable
- ✅ Code quality is improved through systematic refactoring
- ✅ Test coverage meets or exceeds targets
- ✅ TDD metrics are tracked and analyzed
- ✅ Integration with existing codebase is validated

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

## 🛡️ TDD NON-REGRESSION REQUIREMENT

**The Refactor phase MUST NOT break existing tests:**

### TDD Cycle with Non-Regression
```
┌─────────────────────────────────────────────────────────┐
│  RED → GREEN → REFACTOR (with Non-Regression Check)    │
│                                                          │
│  1. RED: Write failing test                             │
│  2. GREEN: Write minimal code to pass                   │
│  3. REFACTOR:                                           │
│     a. Make improvement                                 │
│     b. Run ALL tests (not just new one)                │
│     c. Verify no regressions                           │
│     d. If regression → UNDO refactor                   │
│     e. Only commit if ALL tests pass                   │
└─────────────────────────────────────────────────────────┘
```

### Refactor Phase Requirements
During refactoring:
1. **Run full test suite** after each refactor step
2. **Compare with pre-refactor baseline** - no regressions allowed
3. **Small incremental changes** - easier to identify regression source
4. **Immediate rollback** if any test breaks

### TDD Non-Regression Contract
```
"At the end of each TDD cycle, ALL tests that were passing
before the cycle MUST still be passing."
```

---

🛑 **MANDATORY TDD WORKFLOW CHECK** 🛑
1. Re-read ~/.claude/CLAUDE.md RIGHT NOW
2. Check current codebase structure and testing setup
3. Verify you understand the TDD workflow requirements

Execute comprehensive TDD workflow for: $ARGUMENTS

**FORBIDDEN SHORTCUT PATTERNS:**
- "TDD is too slow for this project" → NO, TDD improves quality and velocity!
- "Tests are obvious, skip writing them first" → NO, tests must drive development!
- "Refactoring can wait" → NO, refactoring is essential for code quality!
- "Minimal implementation is too restrictive" → NO, resist over-engineering!
- "TDD cycles are too rigid" → NO, follow the discipline for best results!

Let me ultrathink about the comprehensive TDD workflow architecture and execution strategy.

🚨 **REMEMBER: TDD improves code quality, reduces bugs, and increases developer confidence!** 🚨

**Comprehensive TDD Workflow Protocol:**

**Step 0: TDD Environment Setup**
- Configure testing framework and tools
- Set up automated test runner and file watcher
- Establish TDD metrics tracking system
- Prepare development environment for rapid iteration
- Configure continuous integration for TDD workflow

**Step 1: Red Phase - Write Failing Test**

**Red Phase Implementation:**
```bash
# TDD Red Phase Execution
execute_red_phase() {
    local feature_name=$1
    local test_file=$2
    local project_dir=${3:-.}
    
    echo "=== TDD RED PHASE: Writing Failing Test ==="
    echo "Feature: $feature_name"
    echo "Test File: $test_file"
    echo ""
    
    # Source shared utilities
    source "$(dirname "$0")/../../../shared/test/utils.md"
    source "$(dirname "$0")/../../../shared/test/runners.md"
    
    # Detect framework
    local framework=$(detect_test_framework "$project_dir")
    echo "Detected framework: $framework"
    
    # Create test file if it doesn't exist
    if [ ! -f "$test_file" ]; then
        create_test_file "$test_file" "$framework" "$feature_name"
    fi
    
    # Write failing test
    write_failing_test "$feature_name" "$test_file" "$framework"
    
    # Run test to confirm it fails
    echo "Running test to confirm failure..."
    cd "$project_dir" || return 1

    # Execute test and capture output
    local test_output="/tmp/red-phase-test-$$.log"
    execute_test_command "$framework" "$project_dir" "$test_file" 2>&1 | tee "$test_output"
    local exit_code=${PIPESTATUS[0]}

    # CRITICAL: Test MUST fail in red phase
    if [ $exit_code -eq 0 ]; then
        echo "❌ CRITICAL ERROR: Test passed when it should fail in RED phase!"
        echo "Red phase requires failing test that defines requirements"
        echo "Review test implementation - it must fail initially"
        return 1
    fi

    # Verify we have actual test failure output
    if ! grep -E "(FAIL|FAILED|ERROR|fail|failed|error)" "$test_output" >/dev/null 2>&1; then
        echo "❌ CRITICAL: No failure indicators found in test output"
        echo "Test must produce clear failure output in red phase"
        return 1
    fi

    echo "✅ Test correctly fails - Red phase complete"
    echo "Exit code: $exit_code (non-zero as expected)"
    return 0
}

# Create test file with framework-specific structure
create_test_file() {
    local test_file=$1
    local framework=$2
    local feature_name=$3
    
    local test_dir=$(dirname "$test_file")
    mkdir -p "$test_dir"
    
    case "$framework" in
        "jest")
            cat > "$test_file" <<EOF
describe('$feature_name', () => {
    // Red phase: Write failing test here
    test('should implement $feature_name functionality', () => {
        // TODO: Implement test that defines requirements
        expect(true).toBe(false); // Placeholder to ensure test fails
    });
});
EOF
            ;;
        "pytest")
            cat > "$test_file" <<EOF
import pytest

class Test${feature_name//_/}:
    def test_${feature_name}_functionality(self):
        """Red phase: Write failing test here"""
        # TODO: Implement test that defines requirements
        assert False, "Placeholder to ensure test fails"
EOF
            ;;
        "go-test")
            cat > "$test_file" <<EOF
package main

import "testing"

func Test${feature_name//_/}(t *testing.T) {
    // Red phase: Write failing test here
    // TODO: Implement test that defines requirements
    t.Fatal("Placeholder to ensure test fails")
}
EOF
            ;;
        "rspec")
            cat > "$test_file" <<EOF
RSpec.describe '$feature_name' do
  # Red phase: Write failing test here
  it 'should implement $feature_name functionality' do
    # TODO: Implement test that defines requirements
    expect(true).to be false # Placeholder to ensure test fails
  end
end
EOF
            ;;
    esac
    
    echo "Created test file: $test_file"
}

# Write failing test based on requirements
write_failing_test() {
    local feature_name=$1
    local test_file=$2
    local framework=$3
    
    echo "Writing failing test for: $feature_name"
    echo "This test IS the specification — it defines what the code MUST do"
    echo "CRITICAL: Do NOT read the implementation before writing this test"

    # Specification-first test writing process
    echo "Define the test as a behavioral specification:"
    echo "1. What should the CONSUMER expect when using this feature?"
    echo "2. What are the expected inputs and outputs from the consumer's perspective?"
    echo "3. What edge cases would a consumer encounter?"
    echo "4. What error conditions should the consumer be protected from?"
    echo ""
    echo "The failing test must read like a requirement — a non-programmer"
    echo "should understand what the code should do just from reading the test."
    
    # Generate test based on requirements
    generate_requirement_based_test "$feature_name" "$test_file" "$framework"
}

# Generate test based on defined requirements
generate_requirement_based_test() {
    local feature_name=$1
    local test_file=$2
    local framework=$3
    
    # This would be customized based on actual requirements
    echo "Generating requirement-based test for $feature_name..."
    echo "Test file: $test_file"
    echo "Framework: $framework"
    
    # Generate actual failing test based on requirements
    echo "Writing failing test that defines requirements..."

    # This is where the actual test content would be written
    # The test should fail initially and define the expected behavior
    echo "Test generated with failing assertion to drive implementation"
}
```

**Step 2: Green Phase - Minimal Implementation**

**Green Phase Implementation:**
```bash
# TDD Green Phase Execution
execute_green_phase() {
    local feature_name=$1
    local source_file=$2
    local test_file=$3
    local project_dir=${4:-.}
    
    echo "=== TDD GREEN PHASE: Minimal Implementation ==="
    echo "Feature: $feature_name"
    echo "Source File: $source_file"
    echo ""
    
    # Create source file if it doesn't exist
    if [ ! -f "$source_file" ]; then
        create_source_file "$source_file" "$framework" "$feature_name"
    fi
    
    # Implement minimal code to make test pass
    implement_minimal_code "$feature_name" "$source_file" "$framework"
    
    # Run test to confirm it passes
    echo "Running test to confirm implementation passes..."
    cd "$project_dir" || return 1

    # Execute test and capture output
    local test_output="/tmp/green-phase-test-$$.log"
    execute_test_command "$framework" "$project_dir" "$test_file" 2>&1 | tee "$test_output"
    local exit_code=${PIPESTATUS[0]}

    # CRITICAL: Test MUST pass in green phase
    if [ $exit_code -ne 0 ]; then
        echo "❌ CRITICAL: Test failed in GREEN phase - implementation incomplete"
        echo "Green phase requires minimal implementation that makes test pass"
        echo "Exit code: $exit_code"
        return 1
    fi

    # Verify we have positive success indicators
    if ! grep -E "(PASS|PASSED|OK|✓|passed|ok)" "$test_output" >/dev/null 2>&1; then
        echo "❌ CRITICAL: No positive success indicators found"
        echo "Test must show clear success in green phase"
        return 1
    fi

    echo "✅ Test passes - Green phase complete"
    echo "Exit code: $exit_code (zero = success)"
    return 0
}

# Create source file with minimal structure
create_source_file() {
    local source_file=$1
    local framework=$2
    local feature_name=$3
    
    local source_dir=$(dirname "$source_file")
    mkdir -p "$source_dir"
    
    case "$framework" in
        "jest")
            cat > "$source_file" <<EOF
// Green phase: Minimal implementation for $feature_name
function ${feature_name}() {
    // TODO: Implement minimal code to make test pass
    throw new Error('Not implemented');
}

module.exports = { ${feature_name} };
EOF
            ;;
        "pytest")
            cat > "$source_file" <<EOF
# Green phase: Minimal implementation for $feature_name
def ${feature_name}():
    """TODO: Implement minimal code to make test pass"""
    raise NotImplementedError("Not implemented")
EOF
            ;;
        "go-test")
            cat > "$source_file" <<EOF
package main

// Green phase: Minimal implementation for $feature_name
func ${feature_name//_/}() error {
    // TODO: Implement minimal code to make test pass
    return fmt.Errorf("not implemented")
}
EOF
            ;;
        "rspec")
            cat > "$source_file" <<EOF
# Green phase: Minimal implementation for $feature_name
def ${feature_name}
  # TODO: Implement minimal code to make test pass
  raise NotImplementedError, "Not implemented"
end
EOF
            ;;
    esac
    
    echo "Created source file: $source_file"
}

# Implement minimal code to make test pass
implement_minimal_code() {
    local feature_name=$1
    local source_file=$2
    local framework=$3
    
    echo "Implementing minimal code for: $feature_name"
    echo "Principle: Write only enough code to make the test pass"
    
    # Interactive implementation process
    echo "Implementation guidelines:"
    echo "1. Write the simplest code that makes the test pass"
    echo "2. Don't add functionality not covered by tests"
    echo "3. Don't optimize prematurely"
    echo "4. Focus on making the test pass, not on perfect design"
    
    # Generate minimal implementation
    generate_minimal_implementation "$feature_name" "$source_file" "$framework"
}

# Generate minimal implementation
generate_minimal_implementation() {
    local feature_name=$1
    local source_file=$2
    local framework=$3
    
    echo "Generating minimal implementation for $feature_name..."
    echo "Source file: $source_file"
    echo "Framework: $framework"
    
    # Generate minimal implementation to make test pass
    echo "Writing minimal implementation to satisfy failing test..."

    # This is where the actual minimal code would be written
    # Just enough to make the test pass, no more
    echo "Minimal implementation generated to make test pass"
}
```

**Step 3: Refactor Phase - Code Quality Improvement**

**Refactor Phase Implementation:**
```bash
# TDD Refactor Phase Execution
execute_refactor_phase() {
    local feature_name=$1
    local source_file=$2
    local test_file=$3
    local project_dir=${4:-.}
    
    echo "=== TDD REFACTOR PHASE: Code Quality Improvement ==="
    echo "Feature: $feature_name"
    echo "Source File: $source_file"
    echo ""
    
    # Backup current implementation
    backup_current_implementation "$source_file"
    
    # Analyze code for refactoring opportunities
    analyze_refactoring_opportunities "$source_file" "$framework"
    
    # Apply refactoring improvements
    apply_refactoring_improvements "$source_file" "$framework"
    
    # Run all tests to ensure refactoring doesn't break functionality
    echo "Running all tests to verify refactoring..."
    cd "$project_dir" || return 1

    # Execute all tests and capture output
    local test_output="/tmp/refactor-phase-test-$$.log"
    execute_test_command "$framework" "$project_dir" "" 2>&1 | tee "$test_output"
    local exit_code=${PIPESTATUS[0]}

    # CRITICAL: ALL tests MUST pass after refactoring
    if [ $exit_code -ne 0 ]; then
        echo "❌ CRITICAL: Tests failed after refactoring - breaking changes detected"
        echo "Refactoring must maintain functionality - reverting changes"
        echo "Exit code: $exit_code"
        restore_backup "$source_file"
        return 1
    fi

    # Verify positive success indicators for all tests
    if ! grep -E "(PASS|PASSED|OK|✓|All tests passed|passed)" "$test_output" >/dev/null 2>&1; then
        echo "❌ CRITICAL: No positive success indicators found after refactoring"
        echo "All tests must show clear success after refactoring"
        restore_backup "$source_file"
        return 1
    fi

    echo "✅ All tests pass - Refactor phase complete"
    echo "Exit code: $exit_code (zero = success)"
    cleanup_backup "$source_file"
    return 0
}

# Backup current implementation before refactoring
backup_current_implementation() {
    local source_file=$1
    local backup_file="${source_file}.tdd-backup"
    
    cp "$source_file" "$backup_file"
    echo "Backed up current implementation to: $backup_file"
}

# Analyze code for refactoring opportunities
analyze_refactoring_opportunities() {
    local source_file=$1
    local framework=$2
    
    echo "Analyzing refactoring opportunities in: $source_file"
    
    # Check for common refactoring patterns
    check_code_duplication "$source_file"
    check_function_complexity "$source_file"
    check_naming_conventions "$source_file"
    check_code_structure "$source_file"
    
    echo "Refactoring opportunities identified"
}

# Apply refactoring improvements
apply_refactoring_improvements() {
    local source_file=$1
    local framework=$2
    
    echo "Applying refactoring improvements to: $source_file"
    
    # Apply common refactoring patterns
    extract_methods "$source_file"
    improve_variable_names "$source_file"
    eliminate_code_duplication "$source_file"
    improve_code_structure "$source_file"
    
    echo "Refactoring improvements applied"
}

# Check for code duplication
check_code_duplication() {
    local source_file=$1
    
    echo "Checking for code duplication in: $source_file"
    # Implementation would analyze for duplicate code patterns
}

# Check function complexity
check_function_complexity() {
    local source_file=$1
    
    echo "Checking function complexity in: $source_file"
    # Implementation would analyze cyclomatic complexity
}

# Extract methods to improve readability
extract_methods() {
    local source_file=$1
    
    echo "Extracting methods to improve readability"
    # Implementation would identify and extract reusable methods
}

# Improve variable names for clarity
improve_variable_names() {
    local source_file=$1
    
    echo "Improving variable names for clarity"
    # Implementation would suggest better variable names
}

# Run all tests to verify refactoring with proper exit code checking
run_all_tests() {
    local project_dir=$1
    local framework=$2

    echo "Running all tests to verify refactoring..."
    cd "$project_dir" || return 1

    # Execute all tests and capture exit code
    execute_test_command "$framework" "$project_dir" ""
    local exit_code=$?

    # Return the actual exit code for proper error handling
    return $exit_code
}

# Restore backup if refactoring fails
restore_backup() {
    local source_file=$1
    local backup_file="${source_file}.tdd-backup"
    
    if [ -f "$backup_file" ]; then
        mv "$backup_file" "$source_file"
        echo "Restored backup implementation"
    fi
}

# Cleanup backup after successful refactoring
cleanup_backup() {
    local source_file=$1
    local backup_file="${source_file}.tdd-backup"
    
    if [ -f "$backup_file" ]; then
        rm "$backup_file"
        echo "Cleaned up backup file"
    fi
}
```

**Step 4: Complete TDD Cycle Execution**

**Full TDD Cycle Implementation:**
```bash
# Execute complete TDD cycle
execute_tdd_cycle() {
    local feature_name=$1
    local test_file=$2
    local source_file=$3
    local project_dir=${4:-.}
    
    echo "=== EXECUTING COMPLETE TDD CYCLE ==="
    echo "Feature: $feature_name"
    echo "Test File: $test_file"
    echo "Source File: $source_file"
    echo ""
    
    # Source shared utilities
    source "$(dirname "$0")/../../../shared/test/utils.md"
    source "$(dirname "$0")/../../../shared/test/runners.md"
    
    # Detect framework
    local framework=$(detect_test_framework "$project_dir")
    
    # Initialize TDD cycle tracking
    local cycle_start_time=$(date +%s)
    local cycle_id="tdd-cycle-$(date +%Y%m%d-%H%M%S)"
    
    # Phase 1: Red - Write failing test
    echo "Starting Red Phase..."
    if execute_red_phase "$feature_name" "$test_file" "$project_dir"; then
        echo "✅ Red Phase Complete"
    else
        echo "❌ Red Phase Failed"
        return 1
    fi
    
    # Phase 2: Green - Minimal implementation
    echo "Starting Green Phase..."
    if execute_green_phase "$feature_name" "$source_file" "$test_file" "$project_dir"; then
        echo "✅ Green Phase Complete"
    else
        echo "❌ Green Phase Failed"
        return 1
    fi
    
    # Phase 3: Refactor - Code quality improvement
    echo "Starting Refactor Phase..."
    if execute_refactor_phase "$feature_name" "$source_file" "$test_file" "$project_dir"; then
        echo "✅ Refactor Phase Complete"
    else
        echo "❌ Refactor Phase Failed"
        return 1
    fi
    
    # Calculate cycle metrics
    local cycle_end_time=$(date +%s)
    local cycle_duration=$((cycle_end_time - cycle_start_time))
    
    # Record TDD cycle completion
    record_tdd_cycle_completion "$cycle_id" "$feature_name" "$cycle_duration"
    
    echo "🎉 TDD Cycle Complete!"
    echo "Feature: $feature_name"
    echo "Duration: ${cycle_duration}s"
    echo "Cycle ID: $cycle_id"
}

# Record TDD cycle completion metrics
record_tdd_cycle_completion() {
    local cycle_id=$1
    local feature_name=$2
    local duration=$3
    
    local metrics_file="/tmp/tdd-metrics-$$.json"
    
    if [ ! -f "$metrics_file" ]; then
        echo '{"cycles": []}' > "$metrics_file"
    fi
    
    # Add cycle data
    if command -v jq >/dev/null 2>&1; then
        local cycle_data=$(cat <<EOF
{
    "cycle_id": "$cycle_id",
    "feature_name": "$feature_name",
    "duration": $duration,
    "timestamp": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
    "phases": {
        "red": "completed",
        "green": "completed",
        "refactor": "completed"
    }
}
EOF
        )
        
        local temp_file=$(mktemp)
        jq ".cycles += [$cycle_data]" "$metrics_file" > "$temp_file"
        mv "$temp_file" "$metrics_file"
    fi
    
    echo "Recorded TDD cycle metrics: $cycle_id"
}

# Generate TDD workflow summary
generate_tdd_summary() {
    local project_dir=${1:-.}
    local metrics_file="/tmp/tdd-metrics-$$.json"
    
    echo "=== TDD Workflow Summary ==="
    echo ""
    
    if [ -f "$metrics_file" ] && command -v jq >/dev/null 2>&1; then
        local total_cycles=$(jq '.cycles | length' "$metrics_file")
        local avg_duration=$(jq '.cycles | map(.duration) | add / length' "$metrics_file")
        
        echo "Total TDD Cycles: $total_cycles"
        echo "Average Cycle Duration: ${avg_duration}s"
        echo ""
        
        echo "Recent Cycles:"
        jq -r '.cycles | sort_by(.timestamp) | reverse | limit(5; .[]) | "- \(.feature_name): \(.duration)s (\(.timestamp))"' "$metrics_file"
    fi
    
    echo ""
    echo "TDD Workflow Benefits:"
    echo "- Improved code quality through systematic testing"
    echo "- Better requirement understanding through test-first approach"
    echo "- Reduced bugs through comprehensive test coverage"
    echo "- Enhanced code maintainability through refactoring"
    echo "- Increased developer confidence in code changes"
}
```

**TDD Quality Checklist:**
- [ ] Red phase: Failing test written that defines requirements
- [ ] Green phase: Minimal implementation makes test pass
- [ ] Refactor phase: Code quality improved while maintaining functionality
- [ ] All tests pass after each phase
- [ ] TDD cycle metrics tracked and analyzed
- [ ] Code quality improvements documented

**Anti-Patterns to Avoid:**
- ❌ Writing implementation before tests (violates TDD principle)
- ❌ Over-engineering in green phase (should be minimal)
- ❌ Skipping refactoring phase (code quality suffers)
- ❌ Writing multiple tests before implementation (one test at a time)
- ❌ Not running tests between phases (breaks feedback loop)
- ❌ Refactoring without test safety net (risk of breaking functionality)

**Final Verification:**
Before completing TDD workflow:
- Have I followed the red-green-refactor cycle correctly?
- Are all tests passing and providing good coverage?
- Has code quality been improved through systematic refactoring?
- Are TDD metrics being tracked for continuous improvement?
- Do I have actionable insights for future TDD cycles?

**Final Commitment:**
- **I will**: Follow the red-green-refactor cycle discipline
- **I will**: Write tests first that define requirements clearly
- **I will**: Implement minimal code to make tests pass
- **I will**: Refactor systematically to improve code quality
- **I will**: Track TDD metrics for continuous improvement
- **I will NOT**: Skip phases or take shortcuts in the TDD process
- **I will NOT**: Write implementation before tests
- **I will NOT**: Over-engineer in the green phase
- **I will NOT**: Skip refactoring due to time pressure

**REMEMBER:**
This is TDD WORKFLOW mode - disciplined test-driven development with systematic red-green-refactor cycles. The goal is to improve code quality, reduce bugs, and increase developer confidence through comprehensive testing and continuous refactoring.

Executing comprehensive TDD workflow with red-green-refactor automation...