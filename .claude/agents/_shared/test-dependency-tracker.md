# Test Dependency Tracking System

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

**MANDATORY**: All test agents MUST use this dependency tracker before modifying any test files.

## 🔒 Core Dependency Tracking

```bash
#!/bin/bash

# Initialize dependency tracking session
init_dependency_tracking() {
    local session_id="dep-track-$(date +%s)"
    local tracking_dir="/tmp/test-dependencies/${session_id}"

    mkdir -p "${tracking_dir}"/{graph,modifications,impacts,rollback}

    # Initialize dependency graph
    cat > "${tracking_dir}/graph.json" << 'EOF'
{
    "tests": {},
    "shared_resources": {
        "base_classes": {},
        "fixtures": {},
        "helpers": {},
        "bootstrap": {},
        "mocks": {}
    },
    "modification_history": [],
    "conflict_matrix": {}
}
EOF

    echo "$session_id"
}

# Analyze test dependencies before modification
analyze_test_dependencies() {
    local test_file="$1"
    local session_id="$2"
    local graph_file="/tmp/test-dependencies/${session_id}/graph.json"

    # Extract base class
    local base_class=$(grep -oP 'extends\s+\K[^\s{]+' "$test_file" | head -1)

    # Extract used traits
    local traits=$(grep -oP 'use\s+\K[^;]+' "$test_file" | grep -v '^App\\' | jq -R . | jq -s .)

    # Extract fixtures
    local fixtures=$(grep -oP '(fixture|setUp|tearDown|beforeEach|afterEach)' "$test_file" | sort -u | jq -R . | jq -s .)

    # Extract mock dependencies
    local mocks=$(grep -oP '(createMock|getMock|shouldReceive|Mockery|prophesize)' "$test_file" | wc -l)

    # Find related tests (same base class or fixtures)
    local related_tests=$(find . -name "*Test.php" -o -name "*.test.js" -o -name "test_*.py" | \
        xargs grep -l "$base_class" 2>/dev/null | grep -v "$test_file" | jq -R . | jq -s .)

    # Update dependency graph
    jq --arg file "$test_file" \
       --arg base "$base_class" \
       --argjson traits "$traits" \
       --argjson fixtures "$fixtures" \
       --arg mocks "$mocks" \
       --argjson related "$related_tests" \
       '.tests[$file] = {
           "base_class": $base,
           "traits": $traits,
           "fixtures": $fixtures,
           "has_mocks": ($mocks | tonumber > 0),
           "related_tests": $related,
           "last_modified": now | todate
       }' "$graph_file" > "${graph_file}.tmp" && mv "${graph_file}.tmp" "$graph_file"

    echo "$related_tests"
}

# Calculate impact zone before modification
calculate_impact_zone() {
    local test_file="$1"
    local modification_type="$2"
    local session_id="$3"
    local graph_file="/tmp/test-dependencies/${session_id}/graph.json"

    local impact_report="/tmp/test-dependencies/${session_id}/impacts/$(basename "$test_file").json"

    # Initialize impact report
    cat > "$impact_report" << EOF
{
    "target": "$test_file",
    "modification_type": "$modification_type",
    "risk_level": "unknown",
    "affected_tests": [],
    "shared_resources_impact": [],
    "estimated_breakage": 0,
    "recommendations": []
}
EOF

    # Analyze based on modification type
    case "$modification_type" in
        "base_class")
            # HIGH RISK - affects all tests with same base
            local base_class=$(jq -r --arg f "$test_file" '.tests[$f].base_class' "$graph_file")
            local affected=$(jq -r --arg b "$base_class" \
                '[.tests | to_entries[] | select(.value.base_class == $b) | .key] | length' "$graph_file")

            jq --arg risk "CRITICAL" \
               --arg affected "$affected" \
               '.risk_level = $risk |
                .estimated_breakage = ($affected | tonumber) |
                .recommendations += ["Create test-specific base class instead", "Use composition over inheritance"]' \
               "$impact_report" > "${impact_report}.tmp" && mv "${impact_report}.tmp" "$impact_report"
            ;;

        "fixture")
            # MEDIUM RISK - affects tests using same fixtures
            local fixtures=$(jq -r --arg f "$test_file" '.tests[$f].fixtures | join(",")' "$graph_file")

            jq --arg risk "MEDIUM" \
               '.risk_level = $risk |
                .recommendations += ["Isolate fixture to this test", "Use test-specific data"]' \
               "$impact_report" > "${impact_report}.tmp" && mv "${impact_report}.tmp" "$impact_report"
            ;;

        "assertion"|"mock")
            # LOW RISK - usually isolated
            jq --arg risk "LOW" \
               '.risk_level = $risk |
                .recommendations += ["Safe to proceed with validation"]' \
               "$impact_report" > "${impact_report}.tmp" && mv "${impact_report}.tmp" "$impact_report"
            ;;
    esac

    cat "$impact_report"
}

# Track modification for conflict detection
track_modification() {
    local test_file="$1"
    local modification_type="$2"
    local session_id="$3"
    local graph_file="/tmp/test-dependencies/${session_id}/graph.json"

    # Add to modification history
    jq --arg file "$test_file" \
       --arg type "$modification_type" \
       '.modification_history += [{
           "file": $file,
           "type": $type,
           "timestamp": now | todate,
           "session": env.TEST_SESSION_ID
       }]' "$graph_file" > "${graph_file}.tmp" && mv "${graph_file}.tmp" "$graph_file"
}

# Check for conflicting modifications
check_for_conflicts() {
    local test_file="$1"
    local planned_change="$2"
    local session_id="$3"
    local graph_file="/tmp/test-dependencies/${session_id}/graph.json"

    # Check recent modifications (last 5 minutes)
    local cutoff_time=$(date -u -d '5 minutes ago' +%s)

    local conflicts=$(jq --arg file "$test_file" \
                         --arg change "$planned_change" \
                         --arg cutoff "$cutoff_time" \
                         '[.modification_history[] |
                          select((.timestamp | fromdate) > ($cutoff | tonumber)) |
                          select(.type == $change or
                                 (.type == "base_class" and $change == "fixture") or
                                 (.type == "fixture" and $change == "base_class"))] |
                          length' "$graph_file")

    if [ "$conflicts" -gt 0 ]; then
        echo "WARNING: Conflicting modifications detected!"
        echo "Recent changes may conflict with planned modification."
        echo "Consider waiting or coordinating with other agents."
        return 1
    fi

    return 0
}

# Create rollback checkpoint
create_rollback_point() {
    local test_file="$1"
    local session_id="$2"
    local rollback_dir="/tmp/test-dependencies/${session_id}/rollback"

    local timestamp=$(date +%s)
    local backup_file="${rollback_dir}/$(basename "$test_file").${timestamp}"

    cp "$test_file" "$backup_file"
    echo "$backup_file"
}

# Validate related tests after modification
validate_related_tests() {
    local test_file="$1"
    local session_id="$2"
    local graph_file="/tmp/test-dependencies/${session_id}/graph.json"

    # Get related tests
    local related_tests=$(jq -r --arg f "$test_file" '.tests[$f].related_tests[]' "$graph_file")

    local failures=0
    for related in $related_tests; do
        echo "Validating related test: $related"

        # Run the related test
        if ! run_single_test "$related"; then
            echo "ERROR: Related test failed: $related"
            ((failures++))
        fi
    done

    if [ $failures -gt 0 ]; then
        echo "ERROR: $failures related tests failed after modification!"
        return 1
    fi

    echo "✅ All related tests still passing"
    return 0
}

# Main dependency-aware modification flow
modify_test_with_dependencies() {
    local test_file="$1"
    local modification_type="$2"
    local modification_content="$3"

    # Initialize session
    local session_id=$(init_dependency_tracking)
    export TEST_SESSION_ID="$session_id"

    echo "=== DEPENDENCY-AWARE TEST MODIFICATION ==="
    echo "Session: $session_id"
    echo "Target: $test_file"
    echo "Type: $modification_type"
    echo ""

    # Step 1: Analyze dependencies
    echo "Step 1: Analyzing dependencies..."
    local related_tests=$(analyze_test_dependencies "$test_file" "$session_id")
    echo "Found $(echo "$related_tests" | jq '. | length') related tests"

    # Step 2: Calculate impact
    echo "Step 2: Calculating impact zone..."
    calculate_impact_zone "$test_file" "$modification_type" "$session_id"

    # Step 3: Check for conflicts
    echo "Step 3: Checking for conflicts..."
    if ! check_for_conflicts "$test_file" "$modification_type" "$session_id"; then
        echo "Aborting due to conflicts"
        return 1
    fi

    # Step 4: Create rollback point
    echo "Step 4: Creating rollback point..."
    local rollback_file=$(create_rollback_point "$test_file" "$session_id")
    echo "Rollback saved to: $rollback_file"

    # Step 5: Apply modification
    echo "Step 5: Applying modification..."
    echo "$modification_content" > "$test_file"

    # Step 6: Track modification
    track_modification "$test_file" "$modification_type" "$session_id"

    # Step 7: Validate the modified test
    echo "Step 6: Validating modified test..."
    if ! run_single_test "$test_file"; then
        echo "ERROR: Modified test failed! Rolling back..."
        cp "$rollback_file" "$test_file"
        return 1
    fi

    # Step 8: Validate related tests
    echo "Step 7: Validating related tests..."
    if ! validate_related_tests "$test_file" "$session_id"; then
        echo "ERROR: Related tests broken! Rolling back..."
        cp "$rollback_file" "$test_file"
        return 1
    fi

    echo "✅ Modification successful with all validations passed!"
    return 0
}
```

## Usage in Test Agents

All test agents MUST integrate this tracker:

```bash
# In any test fixer agent:
source /path/to/test-dependency-tracker.md

# Before ANY test modification:
modify_test_with_dependencies "path/to/test.php" "assertion" "$new_content"
```

## Enforcement Rules

1. **MANDATORY**: Call `analyze_test_dependencies` before any modification
2. **MANDATORY**: Check impact zone for risk assessment
3. **MANDATORY**: Create rollback points for all changes
4. **MANDATORY**: Validate related tests after modifications
5. **MANDATORY**: Track all modifications in session history

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

## 🔗 REGRESSION RISK FOR SHARED CODE

**Track shared code dependencies to assess regression risk:**

### Shared Code Categories
```yaml
shared_code_types:
  test_infrastructure:
    examples: ["BaseTestCase", "TestFixtures", "MockFactories"]
    regression_risk: CRITICAL
    reason: "Changes affect ALL tests using this infrastructure"

  test_utilities:
    examples: ["TestHelpers", "Assertions", "DataBuilders"]
    regression_risk: HIGH
    reason: "Changes affect many tests"

  production_code:
    examples: ["Services", "Repositories", "Controllers"]
    regression_risk: MEDIUM-HIGH
    reason: "Tests depend on specific behavior"

  configuration:
    examples: ["phpunit.xml", "jest.config.js", ".env.testing"]
    regression_risk: HIGH
    reason: "Changes affect test execution environment"
```

### Dependency Tracking
```yaml
dependency_map:
  UserService.php:
    tested_by:
      - UserServiceTest (unit)
      - UserControllerTest (integration)
      - AdminUserTest (integration)
    test_utilities_used:
      - UserFactory
      - MockUserRepository

  BaseTestCase.php:
    extended_by: 147 test files
    regression_impact: CRITICAL
    changes_require: full suite verification + manual review
```

### Risk Assessment Before Fix
```python
def assess_fix_risk(fix: Fix) -> RiskAssessment:
    affected_shared_code = find_shared_code(fix.changed_files)

    if "BaseTestCase" in affected_shared_code:
        return RiskAssessment(
            level="CRITICAL",
            message="Modifying base test class affects ALL tests",
            action="Require manual approval and staged rollout"
        )

    dependent_tests = get_dependent_tests(fix.changed_files)

    return RiskAssessment(
        level=calculate_risk(len(dependent_tests)),
        affected_tests=dependent_tests,
        action=get_recommended_action(dependent_tests)
    )
```