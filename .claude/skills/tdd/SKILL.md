---
allowed-tools: all
description: Test-Driven Development orchestrator with red-green-refactor cycle
---

# TDD: Test-Driven Development

## Usage

```
/tdd <feature-description>
/tdd <file-path>
```

Examples:
```
/tdd UserService validates emails and rejects duplicates
/tdd src/services/rate-limiter.ts
```

## Step 0: Environment Detection

Detect the project's test framework before proceeding:

| Indicator | Framework | Test Command | Coverage Flag |
|-----------|-----------|-------------|---------------|
| `package.json` with jest | Jest | `npx jest` | `--coverage` |
| `package.json` with vitest | Vitest | `npx vitest run` | `--coverage` |
| `pytest.ini` or `pyproject.toml` | pytest | `python -m pytest` | `--cov` |
| `go.mod` | go test | `go test ./...` | `-cover` |
| `composer.json` + `phpunit.xml` | PHPUnit | `./vendor/bin/co-phpunit` | `--coverage-text` |
| `Cargo.toml` | cargo test | `cargo test` | via `cargo-tarpaulin` |
| `Gemfile` with rspec | RSpec | `bundle exec rspec` | `--format doc` |

If no framework is detected, ask the user which test framework to use.

## Step 0.5: Mode Detection

After parsing `$ARGUMENTS`, determine if you're working on **new code** or **existing code**:

### Detection Logic

1. If `$ARGUMENTS` is a file path:
   - Check if the implementation file exists
   - Check if it contains non-trivial code (>10 lines of logic, not just class scaffolding)
   - Check if tests already exist for this file
2. If `$ARGUMENTS` is a feature description:
   - Search for existing implementations matching the description
   - Check if related test files exist

### Mode Classification

| Implementation Exists | Tests Exist | Mode | Recommendation |
|----------------------|-------------|------|----------------|
| No | No | **NEW CODE TDD** | Standard `/tdd` workflow (this skill) |
| Yes | No | **EXISTING CODE, NO TESTS** | Standard `/tdd` workflow — tests should fail initially if bugs exist |
| Yes | Yes | **EXISTING CODE + TESTS** | Consider `/tdd retrofit` for test quality audit |

### User Notification

**If existing code detected with existing tests:**

```
⚠️ EXISTING CODE DETECTED

You're applying TDD to existing code that already has tests.

Current state:
- Implementation: <file> (X lines of logic)
- Existing tests: <test-file> (Y test methods)

Two options:

A) **Continue with `/tdd`**: Write specification tests. If they pass immediately,
   this confirms the code works but provides limited bug discovery value.

B) **Switch to `/tdd retrofit`**: Audits existing tests for confirmation-test
   anti-patterns and systematically converts them to specifications.

For existing code with existing tests → `/tdd retrofit` is recommended.
Reply 'continue' to proceed with standard TDD, or 'retrofit' to switch.
```

**If existing code detected WITHOUT tests:**

```
📋 EXISTING CODE DETECTED (No Tests)

Implementation exists at <file> but no tests found.
Standard TDD applies: specification tests may reveal bugs in existing code.

Proceeding with TDD workflow...
```

**Gate: If existing code + tests detected, wait for user choice before proceeding.**

## Step 1: Specification

**Do NOT read implementation code.** Write the test specification from the consumer's perspective.

1. Parse `$ARGUMENTS` to understand the feature
2. If `$ARGUMENTS` is a file path, read only the public interface (function signatures, class API) — not the implementation body
3. Produce a test specification outline:

```
Feature: <name>

Happy paths:
  - <scenario>: given <input> expect <output>

Edge cases:
  - <scenario>: given <boundary input> expect <behavior>

Error conditions:
  - <scenario>: given <invalid input> expect <error type>
```

4. Present the outline to the user: "Here is the test specification. Should I add, remove, or modify any cases before proceeding?"
5. Proceed ONLY after user confirms

## Step 2: Red Phase — Write Failing Tests

For each scenario in the confirmed specification:

1. Write the test file using the detected framework's conventions
2. Test names must describe requirements, not implementation details
   - Good: `rejects duplicate email with descriptive error`
   - Bad: `calls validateEmail function`
3. Do NOT read implementation code before writing tests — the test IS the specification
4. Run the test suite to confirm all new tests FAIL
   - If any new test passes immediately, it is not testing new behavior — rewrite it

### Immediate Pass Analysis

**If tests pass immediately, diagnose WHY before proceeding:**

| Immediate Pass Rate | Diagnosis | Action |
|---------------------|-----------|--------|
| 100% pass | Existing code is correct OR tests aren't testing failure paths | Investigate edge cases |
| 50-99% pass | Partial implementation exists | Continue with failing tests |
| <50% pass | Expected TDD behavior | Proceed normally |

**When all new tests pass immediately:**

```
⚠️ IMMEDIATE PASS DETECTED

All N new tests passed on first run. This means:

a) The existing code already satisfies these specifications (no bugs to catch)
b) The tests may not be exercising edge cases or failure paths
c) Tests may be confirmation-style (mocks predetermine outcomes)

Analysis:
- Red phase failures: 0/N (expected >0 for TDD)
- Code mode: [New Code | Existing Code]
- Recommendation: [Review edge cases | Tests are confirming, not specifying]

Proceeding with Green phase, but tracking this for final summary...
```

**Track for findings summary:**
- Record the immediate pass rate
- Note which scenarios passed without implementation changes
- Flag for review in final summary

### Agent routing
- Spawn `testing-unit-master` for unit-level test writing
- Spawn `php-test-generator` if PHP project detected
- Spawn `testing-integration-master` if the feature requires integration tests

**Gate: All new tests must fail. Do not proceed to Green until confirmed.**

## Step 3: Green Phase — Minimal Implementation

Write the MINIMUM code to make all failing tests pass.

Rules:
- Implement only what the tests require — nothing more
- No optimizations, no "nice to have" code
- Only generalize when multiple tests force generalization

After implementation:
1. Run the FULL test suite (not just new tests)
2. ALL tests must pass — both new and pre-existing

### Non-regression check
If any pre-existing test fails after Green phase changes:
- The implementation broke something — fix the implementation, do NOT modify the pre-existing test
- Delegate to `/test fix` if failures are complex

### Agent routing
- Spawn the appropriate language-specific coder agent (see CLAUDE.md routing table)
- Spawn `test-quick-fixer` if new tests still fail after initial implementation

**Gate: Full test suite passes with zero failures. Do not proceed to Refactor until confirmed.**

## Step 4: Refactor Phase — Improve Without Breaking

Improve the implementation while keeping all tests green.

Refactoring targets (check in order):
1. Eliminate duplication introduced during Green phase
2. Improve naming (variables, functions, classes)
3. Extract methods/functions if any function exceeds ~20 lines
4. Apply project-specific coding standards (check CLAUDE.md)

After EACH refactoring change:
1. Run the FULL test suite
2. If any test fails — UNDO the last change immediately
3. Only keep refactoring changes that maintain a green suite

### Agent routing
- Spawn `quality-code-analyzer` to identify refactoring opportunities
- For PHP: spawn `php-transformer` for Space-Utils compliance
- Delegate to `/test coverage` to check if refactoring exposed coverage gaps

**Gate: Full test suite passes. Code quality improved over pre-refactor state.**

## Findings Tracking (Throughout Execution)

**Track discoveries as you work through each cycle. Update these tables after each phase.**

### Bugs Discovered

When a specification test fails on existing code, document:

| Bug | Location | Type | Severity | Discovery Method |
|-----|----------|------|----------|------------------|
| (example) Missing null check | UserService.php:42 | Logic Error | HIGH | Spec test: "handles null input" |

**Types**: Stub Implementation, Logic Error, Race Condition, Missing Validation, Hardcoded Value, Integration Bug

### Test Insights

Track patterns discovered during TDD:

| Insight | Impact | Phase Discovered |
|---------|--------|------------------|
| (example) Rate limiter lacks Redis integration | CRITICAL - Feature non-functional | Red (test failed unexpectedly) |

### Refactoring Improvements

Document changes made during refactor phases:

| Change | File | Improvement Type |
|--------|------|------------------|
| (example) Extracted validation logic | UserService.php | Readability |

**Improvement Types**: Readability, Performance, Maintainability, Code Reuse, Error Handling

### Mode Metrics

Track for the final summary:

```
Mode: [NEW CODE TDD | EXISTING CODE TDD]
Immediate pass rate: X% (N/M tests passed without implementation)
Red phase failures: N tests failed as expected
Bugs discovered: N production bugs found
```

## Step 5: Iterate or Complete

After completing one Red-Green-Refactor cycle:

1. Check the specification outline from Step 1
2. If scenarios remain unimplemented, return to Step 2 (Red) for the next scenario
3. If all scenarios are implemented:
   a. Run full test suite one final time
   b. Run `/test coverage` on the affected files
   c. Present comprehensive summary to user (see below)

### Final Report Format

Present the following summary when all cycles are complete:

```
## TDD Execution Summary

### Metrics
- Tests written: N
- Tests passing: N/N
- Coverage delta: +X% (before → after)
- Red phase failures: N (tests that failed before implementation)

### Mode Assessment
- **Mode**: [NEW CODE TDD | EXISTING CODE TDD]
- **Immediate Pass Rate**: X% (N tests passed on first run)
- **TDD Effectiveness**: [High | Medium | Low]
  - High: >70% of tests failed in Red phase (proper TDD)
  - Medium: 30-70% failed (partial specification coverage)
  - Low: <30% failed (confirmation testing risk)

### Discoveries

**Bugs Found**:
[List from Findings Tracking, or "None - code was correct / new implementation"]

**Refactoring Improvements**:
[List from Findings Tracking]

**Test Insights**:
[Notable edge cases, boundary conditions, patterns discovered]

### Files Modified
- Implementation: [list of files]
- Tests: [list of test files]

### Recommendations
[Based on immediate pass rate and mode:]
- If immediate pass rate was high on existing code:
  "Consider running `/tdd retrofit` to audit for confirmation tests"
- If bugs were found:
  "Specification testing revealed N production bugs"
- If coverage is still low:
  "Consider additional edge case tests for X, Y, Z"
```

## Completion Criteria

All must be true before declaring done:
- [ ] User-approved specification fully implemented
- [ ] All new tests pass
- [ ] All pre-existing tests pass (non-regression)
- [ ] Code refactored at least once per cycle
- [ ] Coverage report generated for affected files
- [ ] **Findings summary presented to user** (including mode assessment, discoveries, recommendations)
- [ ] No skipped or incomplete tests
- [ ] If existing code detected: immediate pass rate documented and analyzed

## Delegation Map

| Phase | Delegates To | Agent |
|-------|-------------|-------|
| Test writing (unit) | `/test unit` | `testing-unit-master` |
| Test writing (integration) | `/test integration` | `testing-integration-master` |
| Test writing (PHP) | — | `php-test-generator` |
| Test failure fixing | `/test fix` | `test-quick-fixer`, `test-progressive-fixer` |
| Coverage check | `/test coverage` | `php-test-coverage-analyzer` or framework equivalent |
| Code quality | — | `quality-code-analyzer` |
| PHP compliance | — | `php-transformer` |
| Debug failures | `/test debug` | `test-deep-analyzer` |

---

Execute TDD workflow for: $ARGUMENTS

Begin with Step 0 (Environment Detection), then Step 0.5 (Mode Detection), then Step 1 (Specification).
If existing code with tests is detected, wait for user choice before proceeding.
Do not write any test code until the user approves the specification outline.
