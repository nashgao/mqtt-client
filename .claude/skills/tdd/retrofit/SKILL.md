---
allowed-tools: all
description: Retrofit existing test suites to specification-first TDD quality
---

# TDD Retrofit: Improve Existing Test Suites

Converts confirmation-style tests into specification-style tests and fills coverage gaps. Use this when tests and implementation already exist but test quality is low.

## Usage

```
/tdd retrofit <test-file-or-directory>
```

Examples:
```
/tdd retrofit test/Unit/Services/UserServiceTest.php
/tdd retrofit tests/
/tdd retrofit src/services/__tests__/
```

## Step 0: Baseline

1. Detect test framework (use the parent `/tdd` skill's detection table)
2. Resolve `$ARGUMENTS` to test files
3. Run the full suite, record:
   - Test count
   - Pass count
   - Coverage % (line + branch)
4. Run tests in **randomized order** to detect hidden coupling:
   - PHPUnit: `--order-by=random`
   - Jest: `--randomize`
   - pytest: `--random-order` (requires pytest-random-order)
   - go test: `-shuffle=on`
5. If any test fails only in random order, it has hidden state dependencies

**Gate: All tests must pass in both normal AND random order. If order-dependent tests exist, fix them first with `/test fix`. Do not proceed until the suite is stable.**

## Step 1: Audit Existing Tests

Read each test file and classify every test method using the **4-checkpoint semantic classification system**.

### Classification Criteria (Semantic Checkpoints)

For EACH test method, apply these 4 checkpoints:

#### Checkpoint 1: Counterfactual Test (MANDATORY)
Ask: "If I deleted or broke the production implementation, would this test fail?"

- **PASS** → Test depends on real behavior (continue checking)
- **FAIL** → Mock determines outcome → CONFIRMATION TEST

#### Checkpoint 2: Independence Test
Ask: "Is the expected outcome defined BEFORE looking at the implementation?"

- **PASS** → Test specifies requirements
- **FAIL** → Test confirms what code already does

#### Checkpoint 3: Mock Coupling Analysis
Ask: "Does the mock setup mirror the implementation logic?"

| Coupling Level | Description | Classification Signal |
|---------------|-------------|----------------------|
| **DECOUPLED** | Mocks provide inputs, assertions verify outputs | Specification |
| **COUPLED** | Mock return + impl logic = predetermined result | Confirmation |

Example COUPLED (bad):
- Implementation: `if tokens > 0, return allowed=true`
- Mock: `returns tokens=100`
- Assertion: `allowed=true`
- Result: Mock + impl = predetermined outcome

Example DECOUPLED (good):
- Test: "After 100 requests, 101st should be rejected"
- Uses real rate limiter or stateful test double
- Outcome determined by actual behavior

#### Checkpoint 4: Regression Detection
Ask: "If a developer introduced a bug, would this test catch it?"

- **CATCHES** → Fails on real behavior change
- **MISSES** → Only fails on mock setup change

### Classification Output Format

Present per-test classification table:

| Test Name | Counterfactual | Independence | Coupling | Regression | Classification |
|-----------|---------------|--------------|----------|------------|----------------|
| `testUserCanLogin` | PASS | PASS | DECOUPLED | CATCHES | **SPEC** |
| `testValidateEmailCalled` | FAIL | FAIL | COUPLED | MISSES | **CONFIRM** |
| `testGetterReturnsValue` | PASS | N/A | N/A | N/A | **STRUCTURAL** |

**Scoring rule:**
- 4/4 PASS → SPECIFICATION (keep)
- Any FAIL → CONFIRMATION (rewrite)
- No behavior tested → STRUCTURAL (evaluate for removal)

### Confirmation Test Anti-Patterns (Detection Library)

These code patterns are strong confirmation-test signals:

#### Pattern A: Mock Echo
Mock returns X, test asserts X:
```php
$mock->shouldReceive('get')->andReturn(['key' => 'value']);
$result = $service->fetch();
$this->assertEquals(['key' => 'value'], $result['data']); // Echo!
```
**Detection**: Mock return value appears directly in assertion.

#### Pattern B: Logic Mirror
Test mirrors implementation branching:
```php
// If implementation: if (count > threshold) return 'high'
$this->mockConfig->shouldReceive('get')->with('threshold')->andReturn(10);
$result = $service->classify(15);  // 15 > 10
$this->assertEquals('high', $result);  // Mirrors impl logic
```
**Detection**: Test sets up conditions for predictable code path.

#### Pattern C: State Puppetry
Single mock controls entire outcome:
```php
$this->mockRedis->shouldReceive('get')->with('circuit:state')->andReturn('open');
$this->assertTrue($service->isOpen());  // Mock said so
```
**Detection**: One mock return determines entire test outcome.

#### Pattern D: Invisible Coverage
Testing the mock itself:
```php
$this->vaultService->shouldReceive('read')->andReturn(['secret' => 'xxx']);
$result = $this->vaultService->read('path');  // Testing the mock!
$this->assertArrayHasKey('secret', $result);
```
**Detection**: "Service under test" IS the mock, not a real service using it.

#### Pattern E: Implementation Leakage
Test knows internal formats:
```php
$expectedKey = "rate_limit:{$clientId}:{$endpoint}";  // Internal detail!
$this->mockRedis->shouldReceive('get')->with($expectedKey)->andReturn(100);
```
**Detection**: Test constructs keys/formats only implementation should know.

### Framework-Specific Pattern Detection

| Pattern | PHP (Mockery) | Jest | pytest |
|---------|---------------|------|--------|
| Mock Echo | `->andReturn(X)` + `assertEquals(X)` | `mockReturnValue(X)` + `expect(X)` | `return_value=X` + `assert X` |
| Verify-only | `->shouldHaveReceived()` without output assert | `toHaveBeenCalled()` alone | `assert_called()` alone |
| Partial mock | `makePartial()`, `spy()` | `jest.spyOn()` | `patch.object()` |

### Mock Smell Detection (Additional Signals)

These patterns are additional confirmation-test signals:
- **>3 mocks per test** — testing wiring, not behavior
- **`verify()` / `shouldHaveReceived()` without behavior assertion** — pure interaction testing
- **Mock chaining** (`mock->method()->returns(anotherMock)`) — Law of Demeter violation
- **Partial mocking** (`spy()` / `makePartial()`) — unclear test boundaries

### Prioritization

Prioritize rewriting by risk:
1. Tests covering payment, auth, data-integrity code — rewrite first
2. Tests covering core business logic — rewrite second
3. Tests covering utilities/helpers — rewrite last

### Output

Present audit summary to user:
```
Audit Results for tests/Unit/Services/

Specification (keep):     12 tests
Confirmation (rewrite):    8 tests  [4 mock-heavy, 2 verify-only, 2 implementation-mirroring]
Structural (evaluate):     3 tests

Priority rewrites: PaymentServiceTest (3 confirmation), AuthServiceTest (2 confirmation)
```

**Gate: User approves classification before any changes are made.**

### Agent routing
- Spawn `quality-code-analyzer` for automated classification
- Include directive: "Apply 4-checkpoint semantic classification. Output structured table with Counterfactual/Independence/Coupling/Regression columns. Detect anti-patterns A-E from the pattern library."

## Step 2: Identify Coverage Gaps

NOW read implementation code. Compare the public API surface against existing test coverage:

1. List all public methods/functions in the implementation
2. For each, check: is there a specification test that exercises this from the consumer perspective?
3. Check for untested:
   - Error conditions and edge cases
   - Consumer lifecycle paths (boot, configure, shutdown)
   - Boundary values
   - Integration points with other modules

Produce a gap list prioritized by module criticality.

### Agent routing
- Spawn `php-test-coverage-analyzer` (or framework equivalent) for coverage analysis

## Step 2.5: Bug Discovery Protocol

**When ANY of these conditions occur, STOP and enter this protocol:**
- A specification test fails for existing implementation
- Audit reveals non-functional code (stub implementations, hardcoded values)
- Coverage analysis finds untested critical paths that are broken

### 2.5.1: Document the Bug

For each bug discovered, create a structured report:

| Field | Description |
|-------|-------------|
| **Location** | File:line where bug exists |
| **Type** | Stub/Hardcoded/Logic Error/Race Condition/etc. |
| **Impact** | What feature is broken? |
| **Discovery Method** | Which test/audit step found it? |
| **Severity** | CRITICAL/HIGH/MEDIUM/LOW |

Example:
```
BUG #1: ThreatProtectionService DDoS Detection Non-Functional
Location: src/Services/ThreatProtectionService.php:374-392
Type: Stub Implementation
Impact: DDoS attacks of ANY type can NEVER be detected
Discovery: Specification test for "should detect high request frequency"
Severity: CRITICAL
```

### 2.5.2: Present Bugs to User (MANDATORY GATE)

Present ALL discovered bugs before ANY fixing:

```
🚨 BUG DISCOVERY REPORT

Found X bugs in production code that confirmation tests were masking:

1. [CRITICAL] ThreatProtectionService - DDoS detection returns hardcoded 0
2. [CRITICAL] ThreatProtectionService - Timing always shows 0.001ms
3. [HIGH] VaultService - Inconsistent error handling
...

These are PRODUCTION CODE bugs, not test problems.

Options:
A) Fix all bugs now (recommended for CRITICAL)
B) Fix critical bugs, defer others
C) Document bugs, continue retrofit (create issues for later)
```

**Gate: User must explicitly choose before continuing.**

### 2.5.3: Fix Production Bugs (Mini-TDD Cycle)

For each bug the user chooses to fix:

1. **Specification Test First** (Red)
   - Write a test that specifies correct behavior
   - This test WILL fail because the bug exists
   - Example: "DDoS detector should identify >100 requests/second as attack"

2. **Fix the Production Code** (Green)
   - Implement the actual functionality
   - Replace stubs with real implementations
   - Fix logic errors

3. **Verify**
   - Run the specification test — must pass
   - Run full suite — no regressions

4. **Document the Fix**
   ```
   FIXED: ThreatProtectionService DDoS Detection
   - Replaced hardcoded return 0 with actual Redis request counting
   - Added Redis keys: rate_limit:{client}:{endpoint}
   - Test: testDdosDetection_HighRequestFrequency_DetectsAttack
   ```

### Agent Routing for Bug Fixes
- Spawn the appropriate language-specific agent (see CLAUDE.md routing table)
- For PHP: spawn `php-transformer` for implementation fixes
- Spawn `testing-unit-master` to verify the fix

**Gate: All chosen bugs must be fixed before resuming Step 3.**

## Step 3: Rewrite Confirmation Tests (Incremental)

For each confirmation test identified in Step 1:

1. Write a specification-style replacement:
   - Consumer perspective
   - Requirement-describing name
   - Asserts behavior/outputs, not internal calls
2. **Keep the old test running alongside the new one** — do not delete yet
3. Run full suite — both old and new must pass
4. After completing a batch of rewrites:
   - Compare coverage: do new tests cover the same paths as old tests?
   - If new tests cover same or more → mark old tests for removal
   - If new tests cover less → investigate what the old test was uniquely catching
5. Remove old confirmation tests only after confirming equivalence

If a rewritten test **fails** — this is a genuine bug discovery. **RETURN TO Step 2.5** (Bug Discovery Protocol). The old confirmation test was passing by construction but the feature is actually broken. You must fix the production code, not adjust the test.

**Gate: All rewritten tests pass. Test count not decreased. Full suite green.**

### Agent routing
- Spawn `php-test-generator` (or framework equivalent) for test writing
- Spawn `test-quick-fixer` if rewritten tests fail unexpectedly

## Step 4: Add Gap Tests

For each gap from Step 2:

1. Write a specification test from consumer perspective
2. Test should pass (implementation exists)
3. If it fails — genuine discovery. **RETURN TO Step 2.5** (Bug Discovery Protocol):
   - Implementation bug → fix the production code using the mini-TDD cycle
   - Missing feature → document as known gap, discuss with user

**NEVER modify the specification test to make it pass. Always fix the code.**

**Gate: All gap tests pass or are explicitly acknowledged. Coverage improved over baseline.**

### Agent routing
- Spawn `php-test-generator` (or framework equivalent) for test writing

## Step 5: Quality Verification

1. Run full suite — zero failures
2. Run tests in randomized order again — still zero failures
3. Compare against Step 0 baseline:

```
Retrofit Summary:

Tests:        45 → 52  (+7)
Coverage:     62% → 78% (+16%)
Branch:       48% → 71% (+23%)

Conversions:  8 confirmation → 8 specification
Removed:      2 structural (trivial getter tests)
Gap tests:    7 new specification tests added
```

4. Optionally: run mutation testing to verify rewritten tests catch real bugs
   - PHP: `vendor/bin/infection`
   - JS/TS: `npx stryker run`
   - Python: `mutmut run`

Present summary to user.

## Step 5.5: Adversarial Self-Review

Before declaring the retrofit complete, run this self-verification:

### 5.5.1: Assume Something Was Missed
Ask: "What types of confirmation tests might I have overlooked?"
- Tests that passed all checkpoints but still test wiring?
- Tests where mocks are subtle (e.g., dependency injection setup)?

### 5.5.2: Verify Semantic Change (Not Just Syntax)
For each rewritten test, verify:
- Did I change the SEMANTICS or just the SYNTAX?
- Does the rewrite still use mocks to determine outcomes?
- Would the rewrite fail if the real code broke?

### 5.5.3: Deletion Thought Experiment
Pick 3 random "specification" tests from the rewrite batch:
- Mentally delete the production method being tested
- Would the test fail, or would mocks keep it passing?
- If mocks would keep it passing → re-classify as CONFIRMATION

### 5.5.4: Integration Test Companion Check
For each unit test, ask:
- Is there an integration test validating this same behavior with real dependencies?
- If NO → document as "unit test without integration companion"

### 5.5.5: Honest Classification Report

Output:
| Category | Count | Tests |
|----------|-------|-------|
| Confident specifications | X | list... |
| Uncertain (may still be confirmation) | Y | list... |
| Needs integration companion | Z | list... |

**Gate: If uncertain count > 0, revisit those tests before completion.**

## Completion Criteria

All must be true:
- [ ] All tests pass in normal and randomized order
- [ ] No confirmation tests remain (all converted or justified)
- [ ] Coverage improved over baseline
- [ ] No skipped or incomplete tests
- [ ] User approved audit classification
- [ ] Old confirmation tests removed after equivalence confirmed
- [ ] All discovered production bugs either fixed OR explicitly deferred with user approval
- [ ] Bug discovery report created if any bugs found

## Key Difference from Standard `/tdd`

| Aspect | Standard `/tdd` | Retrofit `/tdd retrofit` |
|--------|----------------|--------------------------|
| Starting point | Feature description | Existing tests + implementation |
| Red phase | Write failing tests | N/A — audit instead |
| Green phase | Write implementation | N/A — implementation exists |
| Refactor phase | Refactor implementation | Refactor tests |
| Test expectation | New tests must FAIL | New/rewritten tests must PASS |
| Read implementation? | Never before writing tests | Yes, during gap analysis |
| Primary output | Working code + tests | Higher-quality test suite |

## Delegation Map

| Phase | Agent |
|-------|-------|
| Baseline run | Run tests via Bash |
| Audit / classification | `quality-code-analyzer` |
| Coverage gap analysis | `php-test-coverage-analyzer` (or framework equivalent) |
| Test writing / rewriting | `php-test-generator` (or framework equivalent) |
| Unexpected failures | `test-quick-fixer` |
| PHP compliance | `php-transformer` |

---

Execute TDD retrofit for: $ARGUMENTS

Begin with Step 0 (Baseline). Do not modify any test until the user approves the audit classification in Step 1.
