---
allowed-tools: all
description: Continuous test execution with intelligent file watching and incremental testing for rapid feedback
intensity: ⚡⚡
pattern: 👁️👁️
---

# 👁️👁️ CRITICAL CONTINUOUS TEST EXECUTION: INTELLIGENT WATCH MODE! 👁️👁️

**THIS IS NOT A SIMPLE FILE WATCHER - THIS IS A COMPREHENSIVE CONTINUOUS TESTING SYSTEM!**

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

---

When you run `/test watch`, you are REQUIRED to:

1. **MONITOR** file changes and trigger intelligent test execution
2. **EXECUTE** only relevant tests based on code changes
3. **PROVIDE** real-time feedback and immediate test results
4. **USE MULTIPLE AGENTS** for parallel watch monitoring using Task tool
5. **OPTIMIZE** test execution speed with intelligent caching
6. **MAINTAIN** test reliability and consistency during development

## TASK TOOL AGENT SPAWNING (MANDATORY)

I'll spawn 5 specialized agents using Task tool for comprehensive continuous testing:

### File Watcher Agent:
```markdown
<function_calls>
<invoke name="Task">
<parameter name="subagent_type">file-watcher-monitor</parameter>
<parameter name="description">Monitor file changes for continuous testing</parameter>
<parameter name="prompt">You are the File Watcher Agent for continuous test execution.

Your responsibilities:
1. Setup intelligent file system monitoring for test-relevant files
2. Monitor source code changes and trigger appropriate test execution
3. Track file dependencies and impact relationships
4. Optimize file watching patterns to avoid excessive monitoring
5. Maintain watch mode stability and performance

MANDATORY FILE MONITORING:
You MUST actually setup and monitor file changes:
- Use file system watching to monitor src/, app/, lib/ directories
- Watch for changes in supported file extensions (.js, .ts, .py, .go, .php, .java, etc.)
- Implement debouncing to batch related changes (300ms default)
- Track file dependencies to determine affected test scope
- Monitor test files themselves for changes that require re-execution

MANDATORY RESULT TRACKING:
- You MUST save monitoring results to /tmp/file-watcher-monitor-results.json
- Include success: true/false, files_monitored, watch_active, change_events_detected
- Document file watching configuration and detected changes
- Report any file system access errors or monitoring failures
- Continuously update status while monitoring is active

CRITICAL: Continuous file monitoring is essential for responsive test execution.</parameter>
</invoke>
</function_calls>
```

### Test Selector Agent:
```markdown
<function_calls>
<invoke name="Task">
<parameter name="subagent_type">test-selector-intelligent</parameter>
<parameter name="description">Select relevant tests based on file changes</parameter>
<parameter name="prompt">You are the Test Selector Agent for intelligent test execution.

Your responsibilities:
1. Analyze file changes to determine which tests should run
2. Implement smart test selection to avoid running unnecessary tests
3. Track test dependencies and impact analysis
4. Optimize test selection for speed while maintaining coverage
5. Cache test selection decisions for performance

MANDATORY TEST SELECTION:
You MUST actually analyze changes and select appropriate tests:
- Monitor File Watcher Agent results for change events
- Map changed source files to their corresponding test files
- Analyze dependency relationships to include affected tests
- Implement intelligent selection: unit → integration → e2e as needed
- Use test result caching to avoid redundant test execution

MANDATORY RESULT TRACKING:
- You MUST save selection results to /tmp/test-selector-intelligent-results.json
- Include success: true/false, tests_selected, selection_criteria, execution_strategy
- Document test selection logic and affected test count
- Only execute after File Watcher Agent detects changes
- Continuously process new change events as they occur

CRITICAL: Smart test selection prevents slow full test suite execution on every change.</parameter>
</invoke>
</function_calls>
```

### Test Executor Agent:
```markdown
<function_calls>
<invoke name="Task">
<parameter name="subagent_type">test-executor-parallel</parameter>
<parameter name="description">Execute selected tests with optimal performance</parameter>
<parameter name="prompt">You are the Test Executor Agent for parallel test execution.

Your responsibilities:
1. Execute tests selected by Test Selector Agent with optimal performance
2. Implement parallel test execution where possible for speed
3. Handle different test frameworks and execution strategies
4. Monitor test execution performance and optimization opportunities
5. Provide execution status and results in real-time

MANDATORY TEST EXECUTION:
You MUST actually execute the selected tests:
- Read test selection from Test Selector Agent results
- Execute tests using appropriate framework commands (jest, pytest, go test, etc.)
- Implement parallel execution to improve test execution speed
- Handle test failures gracefully and provide detailed error information
- Cache successful test results to avoid redundant execution

MANDATORY RESULT TRACKING:
- You MUST save execution results to /tmp/test-executor-parallel-results.json
- Include success: true/false, tests_executed, test_results, execution_time
- Document specific test outcomes, failures, and performance metrics
- Only execute after Test Selector Agent completes test selection
- Provide real-time updates on test execution progress

CRITICAL: Fast, reliable test execution is essential for continuous development feedback.</parameter>
</invoke>
</function_calls>
```

### Feedback Agent:
```markdown
<function_calls>
<invoke name="Task">
<parameter name="subagent_type">feedback-realtime</parameter>
<parameter name="description">Provide real-time feedback on test results</parameter>
<parameter name="prompt">You are the Feedback Agent for real-time test result reporting.

Your responsibilities:
1. Monitor test execution results and provide immediate feedback
2. Generate comprehensive test result summaries and notifications
3. Track test success/failure trends over time
4. Provide actionable feedback on test failures and performance
5. Maintain feedback history for analysis and improvement

MANDATORY FEEDBACK PROVISION:
You MUST actually provide real-time test feedback:
- Monitor Test Executor Agent results for completion status
- Generate immediate feedback on test success/failure outcomes
- Provide detailed failure analysis with error messages and stack traces
- Show test execution performance metrics and trends
- Display summary of changes that triggered test execution

MANDATORY RESULT TRACKING:
- You MUST save feedback results to /tmp/feedback-realtime-results.json
- Include success: true/false, feedback_provided, notification_sent, test_summary
- Document test outcomes, failure details, and performance trends
- Only execute after Test Executor Agent completes test execution
- Maintain continuous feedback provision during watch mode

CRITICAL: Immediate, clear feedback enables rapid development iteration and issue resolution.</parameter>
</invoke>
</function_calls>
```

### Watch Coordinator:
```markdown
<function_calls>
<invoke name="Task">
<parameter name="subagent_type">watch-coordinator</parameter>
<parameter name="description">Coordinate continuous watch mode operation</parameter>
<parameter name="prompt">You are the Watch Coordinator for continuous testing orchestration.

Your responsibilities:
1. Coordinate all watch mode agents and ensure smooth operation
2. Monitor overall watch mode health and performance
3. Handle watch mode lifecycle management and error recovery
4. Optimize watch mode configuration for best performance
5. Generate comprehensive watch mode status and metrics

MANDATORY COORDINATION:
You MUST actually coordinate the watch mode operation:
- Monitor all other watch agents for health and performance
- Ensure proper communication flow between agents
- Handle watch mode startup, operation, and shutdown procedures
- Implement error recovery and agent restart capabilities
- Optimize watch mode configuration based on performance metrics

MANDATORY RESULT TRACKING:
- You MUST save coordination results to /tmp/watch-coordinator-results.json
- Include success: true/false, watch_active, agents_healthy, performance_metrics
- Document overall watch mode status and agent coordination health
- Continuously monitor and update status throughout watch mode operation
- Only execute after all other watch agents are initialized

CRITICAL: Effective coordination ensures reliable, high-performance continuous testing.</parameter>
</invoke>
</function_calls>
```

## 🚨 FORBIDDEN BEHAVIORS

**NEVER:**
- ❌ Run all tests on every change → NO! Use intelligent test selection!
- ❌ Ignore test dependencies → NO! Run affected tests only!
- ❌ Skip test result caching → NO! Optimize with intelligent caching!
- ❌ Provide delayed feedback → NO! Real-time feedback is essential!
- ❌ Ignore file system events → NO! Monitor all relevant changes!
- ❌ "Watch mode is too slow" → NO! Optimize execution speed!

**MANDATORY WORKFLOW:**
```
1. File system monitoring → Setup intelligent file watching
2. IMMEDIATELY spawn 5 agents using Task tool for parallel watch monitoring
3. Agent result verification → Ensure all watch agents are running successfully
4. Continuous test execution → Monitor and execute tests based on file changes
5. Real-time feedback → Provide immediate test results and validation
6. VERIFY watch mode reliability and optimization effectiveness
```

**YOU ARE NOT DONE UNTIL:**
- ✅ All 5 agents spawned using Task tool and running continuously
- ✅ Agent result verification confirms all watch agents are active
- ✅ File watching is monitoring all relevant changes
- ✅ Test execution is optimized for speed and accuracy
- ✅ Real-time feedback is provided for all test results
- ✅ Test caching is working effectively
- ✅ Only relevant tests are executed based on changes
- ✅ Watch mode is stable and reliable

---

🛑 **MANDATORY CONTINUOUS TEST EXECUTION CHECK** 🛑
1. Re-read ~/.claude/CLAUDE.md RIGHT NOW
2. Check current project structure and test organization
3. Verify you understand the continuous testing requirements

Execute comprehensive continuous test execution for: $ARGUMENTS

**FORBIDDEN SHORTCUT PATTERNS:**
- "Running all tests is safer" → NO, use intelligent test selection!
- "Caching is too complex" → NO, implement smart caching!
- "File watching is resource intensive" → NO, optimize monitoring!
- "Real-time feedback is hard" → NO, provide immediate results!
- "Watch mode doesn't need optimization" → NO, optimize for speed!

Let me ultrathink about the comprehensive continuous testing architecture and watch strategy.

## AGENT RESULT VERIFICATION (MANDATORY)

After spawning all 5 watch mode agents, you MUST verify their initialization:

```bash
# MANDATORY: Verify all agents are running successfully
AGENT_RESULTS_DIR="/tmp"
AGENT_FILES=("file-watcher-monitor-results.json" "test-selector-intelligent-results.json" "test-executor-parallel-results.json" "feedback-realtime-results.json" "watch-coordinator-results.json")

for result_file in "${AGENT_FILES[@]}"; do
    FULL_PATH="$AGENT_RESULTS_DIR/$result_file"
    if [ -f "$FULL_PATH" ]; then
        # Use jq to parse agent results
        AGENT_SUCCESS=$(jq -r '.success // false' "$FULL_PATH" 2>/dev/null || echo 'false')
        if [ "$AGENT_SUCCESS" != "true" ]; then
            echo "❌ CRITICAL: Watch mode agent failed to initialize successfully"
            echo "   Failed agent result: $result_file"
            echo "   Check agent logs for initialization failures"
            exit 1
        fi
    else
        echo "❌ CRITICAL: Missing watch mode agent result file: $result_file"
        echo "   Agent may have failed to initialize or save results"
        exit 1
    fi
done

echo "✅ All watch mode agents initialized successfully"
```

## CONTINUOUS WATCH MODE MONITORING (MANDATORY)

After agent initialization, you MUST monitor continuous watch mode operation:

```bash
# Load watch coordinator status for ongoing monitoring
if [ -f "/tmp/watch-coordinator-results.json" ]; then
    echo "👁️ Monitoring continuous watch mode operation..."

    # Extract watch mode metrics from coordinator
    WATCH_ACTIVE=$(jq -r '.watch_active // false' "/tmp/watch-coordinator-results.json" 2>/dev/null || echo 'false')
    AGENTS_HEALTHY=$(jq -r '.agents_healthy // 0' "/tmp/watch-coordinator-results.json" 2>/dev/null || echo '0')
    FILES_MONITORED=$(jq -r '.files_monitored // 0' "/tmp/file-watcher-monitor-results.json" 2>/dev/null || echo '0')

    echo "👁️ Continuous Watch Mode Status:"
    echo "   Watch Active: $WATCH_ACTIVE"
    echo "   Healthy Agents: $AGENTS_HEALTHY/5"
    echo "   Files Monitored: $FILES_MONITORED"
    echo "   Mode: Continuous monitoring and testing"

    # Validate watch mode is properly active
    if [ "$WATCH_ACTIVE" != "true" ]; then
        echo "❌ CRITICAL: Watch mode is not active"
        echo "   Watch mode failed to start properly"
        echo "   Check coordinator and file watcher agent status"
        exit 1
    fi

    if [ "$AGENTS_HEALTHY" -lt 5 ]; then
        echo "⚠️  WARNING: Some watch agents are not healthy"
        echo "   $AGENTS_HEALTHY/5 agents reporting healthy status"
        echo "   Check individual agent status for issues"
    fi

    echo "✅ Continuous watch mode is active and monitoring"
    echo "   File changes will trigger intelligent test execution"
    echo "   Real-time feedback will be provided for all test results"
    echo "   Watch mode will continue until manually stopped"
else
    echo "❌ CRITICAL: Watch coordinator status not found"
    echo "   Watch mode coordination may have failed to initialize"
    exit 1
fi
```

🚨 **REMEMBER: Fast feedback loops improve development velocity and code quality!** 🚨

## Watch Mode Architecture and Setup
- Configure intelligent file system monitoring
- Set up test dependency mapping and impact analysis
- Initialize test result caching and optimization
- Configure real-time feedback mechanisms
- Set up performance monitoring for watch mode

**Step 1: Intelligent File System Monitoring**

**File Watch Configuration:**
**Step 2: Framework-Specific Watch Configuration**

**Watch Configuration by Framework:**

```yaml
# Jest (JavaScript/TypeScript)
watch_config:
  patterns: ["src/**/*.{js,ts,jsx,tsx}", "test/**/*.{js,ts}"]
  ignore: ["node_modules", "build", "dist"]
  command: "jest --watch --coverage"

# pytest (Python)
watch_config:
  patterns: ["src/**/*.py", "tests/**/*.py"]
  ignore: ["__pycache__", ".pytest_cache", "venv"]
  command: "pytest --watch"

# Go
watch_config:
  patterns: ["**/*.go", "go.mod", "go.sum"]
  ignore: ["vendor"]
  command: "go test ./... -v"

# PHPUnit
watch_config:
  patterns: ["src/**/*.php", "tests/**/*.php"]
  ignore: ["vendor", "composer.lock"]
  command: "phpunit --watch"

# RSpec (Ruby)
watch_config:
  patterns: ["lib/**/*.rb", "spec/**/*.rb"]
  ignore: ["vendor", ".bundle"]
  command: "rspec --watch"
```

**Step 3: Smart Test Selection**

Watch mode agents automatically determine which tests to run based on:
- File dependencies and imports
- Test-to-source mapping patterns
- Previous test execution results
- Change impact analysis

**Step 4: Performance Optimization**

- **Debounced File Changes**: 300ms delay to batch rapid changes
- **Incremental Testing**: Run only affected tests initially
- **Parallel Execution**: Use multiple cores for test execution
- **Result Caching**: Cache passed tests, re-run only when needed
- **Memory Management**: Optimize memory usage for long-running watch sessions

## CONTINUOUS WATCH MODE EXECUTION (MANDATORY)

After spawning all watch agents, continuous monitoring begins:

```bash
# Start continuous watch mode monitoring
echo "🔄 Starting continuous test watch mode..."

# Monitor agent health and test execution
while true; do
    # Check agent status every 30 seconds
    sleep 30

    # Verify all watch agents are healthy
    for result_file in "${AGENT_FILES[@]}"; do
        FULL_PATH="$AGENT_RESULTS_DIR/$result_file"
        if [ -f "$FULL_PATH" ]; then
            AGENT_HEALTHY=$(jq -r '.healthy // false' "$FULL_PATH" 2>/dev/null || echo 'false')
            if [ "$AGENT_HEALTHY" != "true" ]; then
                echo "⚠️  WARNING: Watch agent may have stopped: $(basename $result_file)"
                # Attempt to restart failed agent
            fi
        fi
    done

    echo "✅ Watch mode: All agents healthy and monitoring"
done
```

**Success Metrics:**

- 🎯 **Fast Feedback**: Test results within 5 seconds of file changes
- 🎯 **Smart Selection**: Only affected tests executed initially
- 🎯 **High Performance**: Parallel execution with optimal resource usage
- 🎯 **Reliable Monitoring**: Stable file watching without missed changes
- 🎯 **Comprehensive Coverage**: All relevant file types monitored

**Watch Mode Agent Coordination:**

The command coordinates 5 specialized agents continuously:

├── File Watcher Agent: Monitor file system changes with intelligent debouncing
├── Test Selection Agent: Determine which tests to run intelligently
├── Test Execution Agent: Execute selected tests with optimal parallelization
├── Cache Agent: Manage test result caching and optimization
├── Feedback Agent: Provide real-time notifications and feedback
└── Performance Agent: Monitor and optimize watch mode performance

Each agent will work continuously while coordinating to provide the fastest possible feedback loop."
```

**Anti-Patterns to Avoid:**
- ❌ Running all tests on every change (slow feedback)
- ❌ Ignoring test dependencies (missing affected tests)
- ❌ No test result caching (redundant execution)
- ❌ Poor file watching patterns (excessive monitoring)
- ❌ Sequential test execution (slow feedback)
- ❌ No performance optimization (resource waste)

**Final Verification:**
Before completing continuous test execution:
- Is file watching monitoring all relevant changes?
- Are only relevant tests being executed?
- Is test caching working effectively?
- Is real-time feedback being provided?
- Is performance optimized for speed?
- Is watch mode stable and reliable?

**Final Commitment:**
- **I will**: Set up intelligent file watching with optimized patterns
- **I will**: Use multiple agents for parallel watch monitoring
- **I will**: Implement smart test selection based on changes
- **I will**: Provide real-time feedback and notifications
- **I will NOT**: Run all tests on every change
- **I will NOT**: Ignore test caching and optimization
- **I will NOT**: Provide delayed or poor feedback

**REMEMBER:**
This is CONTINUOUS TEST EXECUTION mode - intelligent file watching, smart test selection, and real-time feedback. The goal is to provide the fastest possible feedback loop while maintaining test accuracy and reliability.

Executing comprehensive continuous test execution protocol for optimal development velocity...