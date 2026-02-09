# CRITICAL: Rule Enforcement Framework

## 🚨 ABSOLUTE COMPLIANCE REQUIREMENTS

**MANDATORY FOR ALL AGENTS - ZERO TOLERANCE FOR VIOLATIONS**

### Core Violation Prevention Rules

#### 1. SEPARATION RULE ENFORCEMENT
```yaml
SEPARATION_CONSTRAINTS:
  unit_tests:
    - NEVER modify integration tests when fixing unit tests
    - NEVER convert integration tests to use UnitTestCase
    - NEVER cross-contaminate test types

  integration_tests:
    - NEVER modify unit tests when fixing integration tests
    - NEVER convert to UnitTestCase (MUST use BaseIntegrationTestCase)
    - NEVER optimize by merging test types

  VIOLATION_DETECTION:
    - Any file modification outside assigned scope = IMMEDIATE HALT
    - Any TestCase class conversion = IMMEDIATE HALT
    - Any cross-test-type imports = IMMEDIATE HALT
```

#### 2. VERIFICATION RULE ENFORCEMENT
```yaml
VERIFICATION_REQUIREMENTS:
  mandatory_commands:
    unit_tests: "composer test"
    integration_tests: "composer test:integration"

  SUCCESS_CRITERIA:
    - Command MUST be executed, not assumed
    - Exit code MUST be 0
    - No test failures allowed
    - NEVER claim "fixed" without actual execution

  VIOLATION_DETECTION:
    - No command execution = IMMEDIATE HALT
    - Assumption-based reporting = IMMEDIATE HALT
    - Claims without verification = IMMEDIATE HALT
```

#### 3. SCOPE CONSTRAINT ENFORCEMENT
```yaml
SCOPE_BOUNDARIES:
  assigned_task_only:
    - NEVER optimize beyond assigned scope
    - NEVER "improve" architecture without permission
    - NEVER make decisions outside explicit constraints

  VIOLATION_DETECTION:
    - Any architectural changes = IMMEDIATE HALT
    - Any "optimization" beyond scope = IMMEDIATE HALT
    - Any assumption of improvement authority = IMMEDIATE HALT
```

## 🔒 MANDATORY PRE-EXECUTION CHECKS

**ALL AGENTS MUST COMPLETE BEFORE ANY ACTION:**

### Pre-Action Compliance Checklist
```yaml
BEFORE_ANY_FILE_MODIFICATION:
  - [ ] Verify file is within assigned scope
  - [ ] Confirm no separation rule violations
  - [ ] Check no architectural assumptions made
  - [ ] Validate explicit permission for changes

BEFORE_CLAIMING_SUCCESS:
  - [ ] Execute mandatory verification command
  - [ ] Confirm zero exit code
  - [ ] Verify no test failures
  - [ ] Document actual execution results
```

## 🚨 IMMEDIATE HALT TRIGGERS

**AGENTS MUST STOP IMMEDIATELY IF:**

1. **Scope Violation Detected**: Modifying files outside assigned area
2. **Separation Violation**: Cross-contaminating unit/integration tests
3. **Verification Bypass**: Claiming success without command execution
4. **Assumption Override**: Making architectural decisions without permission
5. **Optimization Creep**: "Improving" beyond explicit constraints

## 🛡️ COMPLIANCE VALIDATION GATES

### Gate 1: Scope Validation
```bash
# MANDATORY before any file modification
validate_scope() {
  if [[ "$TARGET_FILE" != "$ASSIGNED_SCOPE"* ]]; then
    echo "VIOLATION: File outside assigned scope"
    exit 1
  fi
}
```

### Gate 2: Separation Validation
```bash
# MANDATORY for test-related tasks
validate_separation() {
  if [[ "$TASK_TYPE" == "unit" && "$TARGET_FILE" == "*integration*" ]]; then
    echo "VIOLATION: Unit task modifying integration tests"
    exit 1
  fi
}
```

### Gate 3: Verification Validation
```bash
# MANDATORY before claiming success
validate_verification() {
  if [[ -z "$VERIFICATION_COMMAND_EXECUTED" ]]; then
    echo "VIOLATION: No verification command executed"
    exit 1
  fi
}
```

## 📋 VIOLATION REPORTING PROTOCOL

### When Violation Detected:
1. **IMMEDIATE HALT**: Stop all operations
2. **CLEAR REPORTING**: State exactly what rule was violated
3. **NO ASSUMPTIONS**: Do not attempt to "fix" the violation
4. **REQUEST GUIDANCE**: Ask for explicit instructions

### Violation Report Template:
```
RULE VIOLATION DETECTED:
- Rule: [Separation/Verification/Scope]
- Violation: [Specific action that violated rule]
- File/Scope: [What was being modified illegally]
- Required Action: [Awaiting explicit guidance]
- Status: HALTED - No further action until guidance received
```

## ⚡ EMERGENCY COMPLIANCE MEASURES

### If Agent Recognizes Past Violation:
1. **IMMEDIATE CONFESSION**: Report the violation clearly
2. **NO JUSTIFICATION**: Do not explain why violation seemed logical
3. **ROLLBACK OFFER**: Offer to undo any problematic changes
4. **AWAIT INSTRUCTIONS**: Do not proceed until explicit guidance

### Violation Recovery Protocol:
```yaml
RECOVERY_STEPS:
  1. identify_violation: "Acknowledge specific rule broken"
  2. assess_damage: "List all files/changes affected"
  3. propose_rollback: "Offer specific rollback actions"
  4. await_permission: "No action until explicit approval"
  5. execute_fix: "Only perform explicitly approved actions"
```

## 🔧 AGENT TEMPLATE INTEGRATION

**THIS FRAMEWORK MUST BE INCLUDED IN ALL AGENT TEMPLATES:**

### Required Header Section:
```markdown
## 🚨 CRITICAL: Rule Enforcement Active

BEFORE ANY ACTION - VALIDATE:
- [ ] Action within assigned scope only
- [ ] No separation rule violations
- [ ] No verification bypasses
- [ ] No architectural assumptions

IMMEDIATE HALT TRIGGERS:
- File modification outside scope
- Cross-test-type contamination
- Success claims without verification
- Optimization beyond constraints
```

### Required Footer Section:
```markdown
## ⚠️ COMPLIANCE VERIFICATION REQUIRED

BEFORE CLAIMING SUCCESS:
1. Execute verification command: [SPECIFIC_COMMAND]
2. Confirm zero exit code
3. Report actual execution results
4. No assumptions or optimizations

VIOLATION = IMMEDIATE HALT + REPORT
```

## 🎯 SPECIFIC ANTI-VIOLATION MEASURES

### For Test-Fixing Agents:
```yaml
FORBIDDEN_ACTIONS:
  - Converting integration tests to UnitTestCase
  - Modifying tests outside assigned type
  - Claiming fix without command execution
  - Optimizing test architecture

REQUIRED_ACTIONS:
  - Respect BaseIntegrationTestCase for integration tests
  - Execute composer test OR composer test:integration
  - Report actual command results
  - Stay within assigned test type scope
```

### For All Agents:
```yaml
MINDSET_REQUIREMENTS:
  - Follow explicit constraints over optimization instincts
  - Verify through execution, never assumption
  - Stay within assigned scope boundaries
  - Report violations immediately when detected
  - Request permission for any architectural changes
```

## 🔐 ABSOLUTE COMPLIANCE COMMITMENT

**EVERY AGENT MUST ACKNOWLEDGE:**

"I commit to absolute compliance with user constraints over optimization instincts. I will halt immediately upon detecting any rule violation and request explicit guidance rather than making assumptions or architectural decisions beyond my assigned scope."

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

### Rule Enforcement Integration
**Enhanced verification requirements for rule enforcement:**
- **Test execution** MUST report warnings as failures
- **Verification commands** MUST fail on deprecation notices
- **Quality checks** MUST enforce zero tolerance for incomplete tests
- **Compliance validation** MUST block on risky test detection

### Violation Detection Enhancement
```yaml
ENHANCED_VIOLATION_DETECTION:
  test_failures:
    - Failed tests = IMMEDIATE HALT
    - Errors = IMMEDIATE HALT
    - Warnings = IMMEDIATE HALT (warnings ARE failures)
    - Deprecations = IMMEDIATE HALT
    - Incomplete tests = IMMEDIATE HALT
    - Risky tests = IMMEDIATE HALT
    - Skipped tests = IMMEDIATE HALT (unless justified)
```

---

**COMPLIANCE STATUS: MANDATORY - NO EXCEPTIONS - IMMEDIATE ENFORCEMENT**