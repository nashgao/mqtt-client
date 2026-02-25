---
name: test-completion-gates
description: MANDATORY completion gates that PREVENT premature success claims in test execution
type: shared
---

# 🚫 MANDATORY TEST COMPLETION GATES

**CRITICAL: This module MUST be included in ALL test-related agents to prevent premature completion claims**

## ⛔ HARD BLOCKERS - YOU CANNOT PROCEED WITHOUT THESE

### COMPLETION GATE CHECKLIST - MANDATORY BEFORE ANY SUCCESS CLAIM

```markdown
## 🔒 COMPLETION GATE - CANNOT PROCEED WITHOUT VERIFICATION

**YOU ARE NOT DONE until ALL of these are ✅:**

□ **FULL TEST SUITE EXECUTED**: Ran complete test command (not cherry-picked tests)
□ **EXIT CODE VERIFIED**: Test command returned exit code 0
□ **POSITIVE INDICATORS FOUND**: Output contains success patterns (PASSED, ✓, OK)
□ **NO FAILURES DETECTED**: Zero instances of FAIL, ERROR, or ✗ in output
□ **NO TIMEOUTS**: All tests completed without timing out
□ **NO SKIPPED TESTS**: Zero tests skipped (for unit tests)
□ **VALIDATION REPEATED**: Ran full suite 3 times with consistent success
□ **OUTPUT CAPTURED**: Test output saved and verified non-empty

**❌ IF ANY CHECKBOX IS UNCHECKED: YOU ARE NOT DONE - RETURN TO FIXING**
```

## 🚨 CRITICAL ENFORCEMENT PATTERNS

### 1. MANDATORY FULL SUITE EXECUTION

```bash
# ❌ FORBIDDEN - Cherry-picking individual tests
npm test -- specific-test.js  # WRONG - NOT FULL VALIDATION

# ✅ REQUIRED - Full test suite execution
composer test:integration  # RIGHT - FULL SUITE
npm test                   # RIGHT - FULL SUITE
pytest                     # RIGHT - FULL SUITE
```

### 2. MANDATORY EXIT CODE VERIFICATION

```bash
# EVERY test execution MUST capture and verify exit code
$TEST_COMMAND 2>&1 | tee test_output.log
TEST_EXIT_CODE=$?

if [ $TEST_EXIT_CODE -ne 0 ]; then
    echo "❌ CRITICAL: Tests failed with exit code $TEST_EXIT_CODE"
    echo "⚠️  CANNOT CLAIM SUCCESS - MUST CONTINUE FIXING"
    # DO NOT PROCEED - RETURN TO FIXING
    exit 1
fi
```

### 3. MANDATORY POSITIVE SUCCESS VERIFICATION

```bash
# Exit code 0 is NOT sufficient - MUST verify positive indicators
if ! grep -E "(Tests:.*passed|✓|PASSED|OK \([0-9]+ test)" test_output.log > /dev/null; then
    echo "❌ CRITICAL: No positive test success indicators found"
    echo "⚠️  Exit code 0 but no proof of actual test execution"
    echo "🔄 MUST RE-RUN WITH PROPER VERIFICATION"
    # DO NOT PROCEED - TESTS MAY NOT HAVE RUN
    exit 1
fi
```

### 4. MANDATORY FAILURE DETECTION

```bash
# MUST check for ANY failure patterns
if grep -E "FAIL|FAILED|ERROR|✗|✖" test_output.log > /dev/null; then
    echo "❌ CRITICAL: Test failures detected in output"
    echo "📊 Failure patterns found - CANNOT claim success"
    echo "🔄 MUST CONTINUE FIXING ALL FAILURES"
    # DO NOT PROCEED - FAILURES EXIST
    exit 1
fi
```

### 5. MANDATORY TRIPLE VALIDATION

```bash
# SUCCESS REQUIRES 3 CONSECUTIVE FULL PASSES
VALIDATION_PASSES=0
REQUIRED_PASSES=3

for i in 1 2 3; do
    echo "🔍 Validation run $i/$REQUIRED_PASSES..."
    $TEST_COMMAND 2>&1 | tee "validation_${i}.log"

    if [ $? -eq 0 ] && \
       grep -E "(Tests:.*passed|✓|PASSED|OK)" "validation_${i}.log" && \
       ! grep -E "FAIL|ERROR|✗" "validation_${i}.log"; then
        VALIDATION_PASSES=$((VALIDATION_PASSES + 1))
        echo "✅ Validation $i: PASSED"
    else
        echo "❌ Validation $i: FAILED"
        break  # ANY failure = start over
    fi
done

if [ $VALIDATION_PASSES -ne $REQUIRED_PASSES ]; then
    echo "❌ CRITICAL: Only $VALIDATION_PASSES/$REQUIRED_PASSES validations passed"
    echo "⚠️  CANNOT CLAIM SUCCESS WITHOUT $REQUIRED_PASSES CONSECUTIVE PASSES"
    exit 1
fi
```

## 🛑 FORBIDDEN PATTERNS - IMMEDIATE FAILURE

### ❌ ABSOLUTELY FORBIDDEN BEHAVIORS

```markdown
❌ **NEVER DO THESE - IMMEDIATE TASK FAILURE:**
- Claiming "all tests fixed" without running full suite
- Saying "should work now" without verification
- Running only subset of tests for final validation
- Accepting exit code 0 without positive indicators
- Declaring success after single test run
- Ignoring timeouts or skipped tests
- Assuming fixes work without proof
```

### ❌ FORBIDDEN SUCCESS CLAIMS

```markdown
❌ **THESE STATEMENTS ARE FORBIDDEN WITHOUT FULL VERIFICATION:**
- "All tests have been fixed" (without 3x validation)
- "Tests are passing now" (without full suite run)
- "100% pass rate achieved" (without positive indicators)
- "All issues resolved" (without failure pattern check)
- "Task complete" (without completion gate checklist)
```

## 📋 VERIFICATION WORKFLOW - MANDATORY SEQUENCE

### REQUIRED VERIFICATION STEPS

```bash
# STEP 1: Initial full suite run
echo "=== STEP 1: INITIAL FULL SUITE EXECUTION ==="
$TEST_COMMAND 2>&1 | tee initial_test.log
INITIAL_EXIT=$?

# STEP 2: Verify execution occurred
echo "=== STEP 2: VERIFY TEST EXECUTION ==="
if [ ! -s initial_test.log ]; then
    echo "❌ No test output - tests did not execute"
    exit 1
fi

# STEP 3: Check exit code
echo "=== STEP 3: EXIT CODE VERIFICATION ==="
if [ $INITIAL_EXIT -ne 0 ]; then
    echo "❌ Exit code $INITIAL_EXIT - tests failed"
    # MUST CONTINUE FIXING
fi

# STEP 4: Verify positive indicators
echo "=== STEP 4: POSITIVE INDICATOR CHECK ==="
if ! grep -E "Tests:.*passed|✓|PASSED|OK" initial_test.log; then
    echo "❌ No positive success indicators"
    # MUST INVESTIGATE WHY
fi

# STEP 5: Check for failures
echo "=== STEP 5: FAILURE PATTERN DETECTION ==="
if grep -E "FAIL|ERROR|✗" initial_test.log; then
    echo "❌ Failure patterns detected"
    # MUST FIX ALL FAILURES
fi

# STEP 6: Triple validation (only if steps 1-5 pass)
echo "=== STEP 6: TRIPLE VALIDATION REQUIRED ==="
# Run 3x validation loop (see above)

# STEP 7: Final completion gate
echo "=== STEP 7: COMPLETION GATE VERIFICATION ==="
# Verify ALL checkboxes checked before success claim
```

## 🔐 ENFORCEMENT MECHANISMS

### AUTO-REJECTION TRIGGERS

```bash
# Automatic rejection if these patterns detected in agent output:
AUTO_REJECT_PATTERNS=(
    "fixed.*should.*work"     # Assumption without verification
    "all.*tests.*fixed"       # Claim without proof
    "probably.*passing"        # Uncertainty
    "most.*tests.*pass"        # Partial completion
    "sample.*of.*tests"        # Subset testing
    "representative.*tests"    # Cherry-picking
)
```

### VALIDATION ENFORCEMENT

```bash
# Function that MUST be called before ANY success claim
enforce_completion_gates() {
    local gates_passed=true

    # Gate 1: Full suite executed
    if [ -z "$FULL_SUITE_RUN" ]; then
        echo "❌ Gate Failed: Full test suite not executed"
        gates_passed=false
    fi

    # Gate 2: Exit code 0
    if [ "$TEST_EXIT_CODE" -ne 0 ]; then
        echo "❌ Gate Failed: Test exit code is $TEST_EXIT_CODE"
        gates_passed=false
    fi

    # Gate 3: Positive indicators
    if [ -z "$POSITIVE_INDICATORS" ]; then
        echo "❌ Gate Failed: No positive test indicators found"
        gates_passed=false
    fi

    # Gate 4: No failures
    if [ -n "$FAILURE_PATTERNS" ]; then
        echo "❌ Gate Failed: Failure patterns detected"
        gates_passed=false
    fi

    # Gate 5: Triple validation
    if [ "$VALIDATION_COUNT" -lt 3 ]; then
        echo "❌ Gate Failed: Only $VALIDATION_COUNT/3 validations completed"
        gates_passed=false
    fi

    if [ "$gates_passed" = false ]; then
        echo "🚫 COMPLETION GATES FAILED - CANNOT CLAIM SUCCESS"
        echo "📋 Review checklist and complete ALL requirements"
        exit 1
    fi

    echo "✅ ALL COMPLETION GATES PASSED - Success verified!"
}
```

## 💀 FAILURE CONSEQUENCES

### WHAT HAPPENS IF YOU VIOLATE THESE GATES

1. **Premature Success Claim** → Task marked as FAILED
2. **Incomplete Verification** → Must restart from beginning
3. **Partial Testing** → All work invalidated
4. **Assumed Success** → Immediate rejection
5. **Single Validation** → Insufficient proof, must redo

## 🎯 SUCCESS CRITERIA - FINAL CHECKLIST

```markdown
### ✅ YOU MAY ONLY CLAIM SUCCESS WHEN:

1. ✅ Ran COMPLETE test suite (not subset)
2. ✅ Captured exit code = 0
3. ✅ Found positive success indicators in output
4. ✅ Detected ZERO failure patterns
5. ✅ NO timeouts occurred
6. ✅ NO tests were skipped (for unit tests)
7. ✅ Validated 3 times consecutively
8. ✅ All output captured and verified
9. ✅ Completion gates function returned success
10. ✅ Can provide exact test counts and results

**⚠️ MISSING ANY ITEM = NOT DONE = MUST CONTINUE**
```

## 🔄 INTEGRATION INSTRUCTIONS

### For Test Agent Authors

```markdown
# MANDATORY: Include this at the top of every test agent

## 🔒 COMPLETION GATES
**This agent implements MANDATORY completion gates from test-completion-gates.md**
**NO SUCCESS CLAIMS allowed without passing ALL gates**

# Include the enforcement function
source ./templates/agents/_shared/test-completion-gates.md

# Call before ANY success claim
enforce_completion_gates
```

### For Test Commands

```markdown
# EVERY test-related command MUST:
1. Include completion gate checklist
2. Call enforcement function before success
3. Provide gate status in output
4. Block on gate failures
5. Document gate violations
```

## 📊 METRICS & MONITORING

Track these to ensure gates are working:

- **Premature Success Rate**: Should be 0%
- **Gate Violation Count**: Track and investigate each
- **Average Validations Before Success**: Should be ≥3
- **False Success Reports**: Must be 0%
- **Verification Completeness**: Must be 100%

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

## 🛡️ NON-REGRESSION GATE

**MANDATORY: Every fix must guarantee no regression in existing passing tests:**

### Baseline Comparison Requirement
Before any fix is considered complete:
1. **Capture Pre-Fix Baseline** - Record all passing tests before fix
2. **Apply Fix** - Make the minimal change
3. **Run Full Suite** - Execute ALL tests (not just fixed one)
4. **Compare Results** - Check against baseline
5. **Detect Regressions** - Any previously passing test now failing = REGRESSION

### Regression Detection
```bash
regression_check() {
    local baseline="$1"
    local current="$2"

    # Find tests that were PASS in baseline but FAIL in current
    comm -23 <(sort "$baseline") <(sort "$current") > regressions.txt

    if [[ -s regressions.txt ]]; then
        echo "❌ REGRESSION DETECTED - Previously passing tests now fail:"
        cat regressions.txt
        return 1  # BLOCK completion
    fi
    return 0
}
```

### Regression Gate Enforcement
```yaml
completion_gates:
  - gate: zero_tolerance
    checks: [failures, warnings, deprecations, incomplete]

  - gate: non_regression  # NEW MANDATORY GATE
    checks:
      - baseline_captured: true
      - full_suite_executed: true
      - regression_comparison: passed
      - previously_passing_still_pass: true
    block_on_failure: true
    rollback_required: true
```

### Regression Response Protocol
When regression detected:
1. **STOP** - Do not proceed
2. **ROLLBACK** - Revert the fix that caused regression
3. **ANALYZE** - Understand why fix broke other test
4. **REDESIGN** - Create fix that doesn't cause regression
5. **RETRY** - Apply new fix with full verification

---

**REMEMBER: These gates exist because agents were claiming success without verification. Every gate violation undermines user trust. ENFORCE RUTHLESSLY.**