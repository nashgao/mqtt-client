# CRITICAL: Compliance Validation Gates

## 🚨 MANDATORY PRE-EXECUTION VALIDATION

**ALL AGENTS MUST COMPLETE BEFORE ANY FILE MODIFICATION:**

### Pre-Action Compliance Checklist

```bash
#!/bin/bash

# MANDATORY: Run before ANY agent action
validate_agent_compliance() {
    local agent_task="$1"
    local target_scope="$2"
    local assigned_type="$3"  # unit | integration | mixed

    echo "🔒 COMPLIANCE VALIDATION: Starting pre-action checks"

    # Gate 1: Scope Validation
    if [[ "$target_scope" != "$ASSIGNED_SCOPE"* ]]; then
        echo "❌ VIOLATION: Action outside assigned scope"
        echo "   Assigned: $ASSIGNED_SCOPE"
        echo "   Target: $target_scope"
        return 1
    fi
    echo "✅ Scope validation passed"

    # Gate 2: Separation Rule Validation
    if [[ "$assigned_type" == "unit" && "$target_scope" == *"integration"* ]]; then
        echo "❌ VIOLATION: Unit task attempting to modify integration tests"
        echo "   Task type: $assigned_type"
        echo "   Target: $target_scope"
        return 1
    fi

    if [[ "$assigned_type" == "integration" && "$target_scope" == *"unit"* ]]; then
        echo "❌ VIOLATION: Integration task attempting to modify unit tests"
        echo "   Task type: $assigned_type"
        echo "   Target: $target_scope"
        return 1
    fi
    echo "✅ Separation rule validation passed"

    # Gate 3: TestCase Conversion Validation
    if grep -q "BaseIntegrationTestCase.*UnitTestCase" "$target_scope" 2>/dev/null; then
        echo "❌ VIOLATION: Attempted conversion of BaseIntegrationTestCase to UnitTestCase"
        echo "   File: $target_scope"
        return 1
    fi
    echo "✅ TestCase conversion validation passed"

    echo "✅ ALL PRE-ACTION COMPLIANCE GATES PASSED"
    return 0
}
```

### Architecture Decision Validation

```bash
# MANDATORY: Validate architectural changes
validate_architectural_changes() {
    local change_type="$1"
    local justification="$2"

    echo "🔒 ARCHITECTURAL VALIDATION: Checking change authority"

    # Forbidden architectural changes without explicit permission
    case "$change_type" in
        "test_class_conversion")
            echo "❌ VIOLATION: Test class conversions require explicit permission"
            return 1
            ;;
        "test_framework_change")
            echo "❌ VIOLATION: Framework changes require explicit permission"
            return 1
            ;;
        "dependency_injection")
            echo "❌ VIOLATION: Dependency injection changes require explicit permission"
            return 1
            ;;
        *)
            echo "✅ Architectural change validation passed"
            return 0
            ;;
    esac
}
```

## 🔒 MANDATORY POST-EXECUTION VERIFICATION

**ALL AGENTS MUST COMPLETE BEFORE CLAIMING SUCCESS:**

### Verification Command Execution

```bash
# MANDATORY: Execute and verify test commands
execute_and_verify_tests() {
    local test_type="$1"  # unit | integration | both
    local project_root="$2"

    echo "🔒 VERIFICATION EXECUTION: Starting mandatory test verification"

    # Determine correct test command
    local test_command=""
    case "$test_type" in
        "unit")
            test_command=$(detect_unit_test_command "$project_root")
            ;;
        "integration")
            test_command=$(detect_integration_test_command "$project_root")
            ;;
        "both")
            test_command=$(detect_full_test_command "$project_root")
            ;;
        *)
            echo "❌ VIOLATION: Invalid test type specified"
            return 1
            ;;
    esac

    if [[ -z "$test_command" ]]; then
        echo "❌ VIOLATION: Could not determine test command"
        return 1
    fi

    echo "📋 Executing: $test_command"

    # Execute test command and capture output
    local output_file="verification_output_$(date +%s).log"
    $test_command 2>&1 | tee "$output_file"
    local exit_code=$?

    # Verification Gate 1: Exit Code
    if [[ $exit_code -ne 0 ]]; then
        echo "❌ VERIFICATION FAILED: Test command returned exit code $exit_code"
        echo "❌ Tests are NOT fixed - violation of verification requirement"
        return 1
    fi
    echo "✅ Verification Gate 1 passed: Exit code 0"

    # Verification Gate 2: Output Validation
    if [[ ! -f "$output_file" ]] || [[ ! -s "$output_file" ]]; then
        echo "❌ VERIFICATION FAILED: No test output captured"
        return 1
    fi
    echo "✅ Verification Gate 2 passed: Output captured"

    # Verification Gate 3: Positive Indicators
    if ! grep -E "(Tests: [0-9]+|test.*passed|✓|PASSED|OK)" "$output_file" > /dev/null; then
        echo "❌ VERIFICATION FAILED: No positive test success indicators found"
        return 1
    fi
    echo "✅ Verification Gate 3 passed: Positive indicators found"

    # Verification Gate 4: No Failures
    if grep -E "FAIL|FAILED|ERROR|✗|✖" "$output_file" > /dev/null; then
        echo "❌ VERIFICATION FAILED: Failure patterns detected in output"
        return 1
    fi
    echo "✅ Verification Gate 4 passed: No failures detected"

    echo "✅ ALL VERIFICATION GATES PASSED"
    echo "📁 Output saved to: $output_file"

    return 0
}
```

### Test Command Detection Functions

```bash
# Detect appropriate test commands based on project structure
detect_unit_test_command() {
    local project_root="$1"

    if [[ -f "$project_root/composer.json" ]]; then
        if grep -q '"test"' "$project_root/composer.json"; then
            echo "composer test"
        elif grep -q '"phpunit"' "$project_root/composer.json"; then
            echo "composer run phpunit"
        else
            echo "vendor/bin/phpunit"
        fi
    elif [[ -f "$project_root/package.json" ]]; then
        if grep -q '"test"' "$project_root/package.json"; then
            echo "npm test"
        elif grep -q '"jest"' "$project_root/package.json"; then
            echo "npx jest"
        else
            echo "npm run test"
        fi
    elif [[ -f "$project_root/pytest.ini" ]] || [[ -f "$project_root/setup.cfg" ]]; then
        echo "pytest"
    else
        echo ""
    fi
}

detect_integration_test_command() {
    local project_root="$1"

    if [[ -f "$project_root/composer.json" ]]; then
        if grep -q '"test:integration"' "$project_root/composer.json"; then
            echo "composer test:integration"
        elif grep -q '"integration"' "$project_root/composer.json"; then
            echo "composer run integration"
        else
            # Fallback to unit command for projects without separate integration
            detect_unit_test_command "$project_root"
        fi
    elif [[ -f "$project_root/package.json" ]]; then
        if grep -q '"test:integration"' "$project_root/package.json"; then
            echo "npm run test:integration"
        else
            detect_unit_test_command "$project_root"
        fi
    else
        detect_unit_test_command "$project_root"
    fi
}

detect_full_test_command() {
    local project_root="$1"

    # For full test suite, prefer the most comprehensive command
    if [[ -f "$project_root/composer.json" ]]; then
        if grep -q '"test:all"' "$project_root/composer.json"; then
            echo "composer test:all"
        else
            detect_unit_test_command "$project_root"
        fi
    else
        detect_unit_test_command "$project_root"
    fi
}
```

## 🚨 VIOLATION DETECTION AND REPORTING

### Real-Time Violation Monitoring

```bash
# Monitor for violations during agent execution
monitor_compliance_violations() {
    local agent_pid="$1"
    local monitoring_log="compliance_monitor_$(date +%s).log"

    echo "🔒 COMPLIANCE MONITOR: Watching agent $agent_pid for violations"

    while kill -0 "$agent_pid" 2>/dev/null; do
        # Check for file modifications outside scope
        if find . -newer /tmp/agent_start_time -name "*.php" -o -name "*.js" -o -name "*.py" | \
           grep -v "$ASSIGNED_SCOPE" > /dev/null 2>&1; then
            echo "❌ VIOLATION DETECTED: File modification outside assigned scope" | tee -a "$monitoring_log"
            kill -TERM "$agent_pid"
            return 1
        fi

        # Check for forbidden class conversions
        if grep -r "UnitTestCase" --include="*integration*" . > /dev/null 2>&1; then
            echo "❌ VIOLATION DETECTED: Integration test converted to UnitTestCase" | tee -a "$monitoring_log"
            kill -TERM "$agent_pid"
            return 1
        fi

        sleep 1
    done

    echo "✅ COMPLIANCE MONITOR: No violations detected during execution"
    return 0
}
```

### Violation Report Generation

```bash
# Generate comprehensive violation report
generate_violation_report() {
    local violation_type="$1"
    local violation_details="$2"
    local agent_name="$3"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')

    cat << EOF > "violation_report_$(date +%s).md"
# COMPLIANCE VIOLATION REPORT

**Timestamp**: $timestamp
**Agent**: $agent_name
**Violation Type**: $violation_type

## Violation Details
$violation_details

## Remediation Required
- [ ] Stop all agent operations immediately
- [ ] Rollback any changes made outside assigned scope
- [ ] Re-run with corrected scope constraints
- [ ] Verify compliance before resuming

## Prevention Measures
- [ ] Review agent constraints
- [ ] Update scope validation
- [ ] Enhance monitoring systems
- [ ] Document lessons learned

**Status**: BLOCKING - No further operations until resolved
EOF

    echo "📋 Violation report generated: violation_report_$(date +%s).md"
}
```

## 📊 COMPLIANCE METRICS AND TRACKING

### Success/Failure Tracking

```bash
# Track compliance success rates
track_compliance_metrics() {
    local agent_name="$1"
    local compliance_result="$2"  # success | violation
    local metrics_file="compliance_metrics.json"

    if [[ ! -f "$metrics_file" ]]; then
        echo '{"agents": {}}' > "$metrics_file"
    fi

    # Update metrics (simplified version)
    local current_date=$(date +%Y-%m-%d)
    echo "📊 Updating compliance metrics for $agent_name: $compliance_result"

    # Log the result for analysis
    echo "[$current_date] $agent_name: $compliance_result" >> compliance_history.log
}
```

## 🎯 INTEGRATION WITH AGENT TEMPLATES

### Template Integration Requirements

**Every agent template MUST include:**

```markdown
## 🔒 MANDATORY COMPLIANCE VALIDATION

**BEFORE ANY ACTION:**
```bash
# Source compliance validation functions
source /path/to/compliance-validation-gates.md

# Validate compliance before proceeding
if ! validate_agent_compliance "$TASK" "$TARGET_SCOPE" "$TEST_TYPE"; then
    echo "🚫 COMPLIANCE VIOLATION - HALTING EXECUTION"
    exit 1
fi
```

**BEFORE CLAIMING SUCCESS:**
```bash
# Execute mandatory verification
if ! execute_and_verify_tests "$TEST_TYPE" "$PROJECT_ROOT"; then
    echo "🚫 VERIFICATION FAILED - NO SUCCESS CLAIM ALLOWED"
    exit 1
fi
```
```

### Mandatory Footer for All Test Agents

```markdown
## ⚠️ FINAL COMPLIANCE CHECKPOINT

**YOU CANNOT COMPLETE THIS TASK WITHOUT:**
- ✅ Scope validation passed
- ✅ Separation rules followed
- ✅ Verification command executed successfully
- ✅ Exit code 0 confirmed
- ✅ No failure patterns in output

**VIOLATION = IMMEDIATE TASK FAILURE**
```

## 🚨 ZERO TOLERANCE ENFORCEMENT

**MANDATORY - ALL validation must achieve PERFECT execution:**

### Success Criteria (ALL must be met)
- ✅ **0 Failed Tests** - Every single test must pass
- ✅ **0 Errors** - No runtime errors allowed
- ✅ **0 Warnings** - Warnings are treated as failures
- ✅ **0 Deprecations** - Deprecation notices block success
- ✅ **0 Incomplete Tests** - Incomplete tests are failures
- ✅ **0 Risky Tests** - Risky test detection must pass
- ✅ **0 Skipped Tests** - Unless explicitly allowed with justification

### Quality Gate Integration
- All compliance gates MUST reject warnings
- All validation MUST fail on deprecations
- All quality checks MUST enforce 100% pass rate
- Partial compliance is NOT compliance - it is violation

### Verification Gate Enhancement
**Verification Gate 4 ENHANCED: No Failures, Warnings, or Deprecations**
```bash
# ORIGINAL Gate 4 - Now DEPRECATED
if grep -E "FAIL|FAILED|ERROR|✗|✖" "$output_file" > /dev/null; then
    echo "❌ VERIFICATION FAILED: Failure patterns detected in output"
    return 1
fi

# ENHANCED Gate 4 - MANDATORY ZERO TOLERANCE
if grep -E "FAIL|FAILED|ERROR|WARNING|WARN|DEPRECATED|INCOMPLETE|RISKY|SKIPPED|✗|✖|⚠" "$output_file" > /dev/null; then
    echo "❌ VERIFICATION FAILED: Quality violations detected in output"
    echo "   - Failed tests, errors, warnings, deprecations, incomplete tests are ALL blocking"
    echo "   - Zero tolerance enforcement: EVERYTHING must be GREEN"
    return 1
fi
```

### Enhanced Verification Requirements
**ALL test execution verification MUST check for:**
- **Failed Tests**: Any test failure blocks success
- **Errors**: Any runtime error blocks success
- **Warnings**: Any warning blocks success (warnings = failures)
- **Deprecations**: Any deprecation notice blocks success
- **Incomplete Tests**: Any incomplete/skipped test blocks success
- **Risky Tests**: Any risky test detection blocks success

---

**COMPLIANCE STATUS: MANDATORY - ZERO TOLERANCE ENFORCEMENT**