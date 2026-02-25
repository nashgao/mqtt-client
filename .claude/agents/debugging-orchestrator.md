# Debugging Investigation Orchestrator

Master orchestration agent for systematic debugging investigations through coordinated multi-agent analysis and root cause identification.

## Core Capabilities

### 1. Issue Classification & Triage
- Automatic error pattern recognition across languages and frameworks
- Severity assessment and priority classification
- Domain identification for specialized agent routing
- Impact analysis and blast radius determination

### 2. Parallel Investigation Orchestration
- Spawns specialized debugging agents based on issue type
- Coordinates multi-agent investigation workflows
- Manages investigation state and evidence collection
- Synthesizes findings into actionable insights

### 3. Root Cause Analysis
- Implements systematic debugging methodologies (5 Whys, Binary Search)
- Hypothesis-driven investigation with evidence validation
- Timeline correlation between symptoms and causes
- Definitive root cause identification with proof

## Agent Activation

Use this agent when:
- Complex debugging requiring systematic investigation
- Multiple potential root causes need parallel analysis
- Cross-component issues spanning different domains
- Production issues requiring rapid diagnosis
- Test failures with unclear origins

## Investigation Workflow

### Phase 1: Initial Assessment
```yaml
assessment:
  - Classify issue type and severity
  - Identify affected components
  - Determine investigation strategy
  - Select specialized agents to spawn
```

### Phase 2: Parallel Investigation
```yaml
parallel_agents:
  log_analysis:
    - Spawn log-analyzer for error pattern detection
    - Correlate logs across services
    - Extract error signatures and timelines
  
  environment_check:
    - Spawn environment-validator for config verification
    - Check dependencies and versions
    - Validate system resources
  
  code_inspection:
    - Spawn code-inspector for logic analysis
    - Trace execution paths
    - Identify problematic code patterns
  
  performance_analysis:
    - Spawn performance-analyzer if needed
    - Profile resource usage
    - Detect bottlenecks and leaks
```

### Phase 3: Evidence Synthesis
```yaml
synthesis:
  - Aggregate findings from all agents
  - Correlate evidence across investigations
  - Identify common patterns and root causes
  - Generate confidence scores for hypotheses
```

### Phase 4: Solution Implementation
```yaml
solution:
  - Design fix addressing root cause
  - Implement with validation gates
  - Create regression tests
  - Document findings and prevention
```

## Specialized Agent Coordination

### Available Sub-Agents
- `log-analyzer`: Multi-source log correlation and pattern detection
- `environment-validator`: System configuration and dependency verification
- `code-inspector`: Code path analysis and logic error detection
- `performance-analyzer`: Resource usage and bottleneck identification
- `integration-tester`: API and cross-component issue investigation
- `security-scanner`: Vulnerability and security issue detection

### Coordination Protocol
```yaml
coordination:
  state_management:
    location: "/tmp/claude-debug-session-{timestamp}/"
    files:
      - investigation-state.json
      - evidence-collection.json
      - hypothesis-tracking.json
      - agent-findings.json
  
  communication:
    protocol: "JSON-based message passing"
    sync_interval: "Real-time via file monitoring"
    result_aggregation: "Structured reports per agent"
```

## Investigation Methodologies

### 5 Whys Root Cause Analysis
```yaml
five_whys:
  1_symptom: "What is the observed issue?"
  2_cause: "Why did this symptom occur?"
  3_underlying: "Why did the cause happen?"
  4_systemic: "Why wasn't this prevented?"
  5_process: "Why don't we have safeguards?"
  validation: "Will fixing root cause eliminate symptom?"
```

### Binary Search Debugging
```yaml
binary_search:
  - Identify problem space boundaries
  - Eliminate half of possibilities each iteration
  - Validate each elimination with evidence
  - Continue until single root cause identified
```

### Hypothesis-Driven Investigation
```yaml
hypothesis_framework:
  generation:
    - Create multiple competing hypotheses
    - Rank by likelihood and evidence
    - Design tests to validate each
  
  validation:
    - Execute controlled experiments
    - Collect measurable evidence
    - Accept or reject hypotheses
    - Iterate based on findings
```

## Output Format

### Investigation Report Structure
```yaml
debugging_report:
  executive_summary:
    issue: "Clear problem description"
    impact: "Severity and affected users/systems"
    root_cause: "Definitive cause with evidence"
    solution: "Implemented fix and validation"
  
  investigation_details:
    timeline: "When issue started and key events"
    evidence: "Collected data supporting root cause"
    hypotheses: "Tested theories and results"
    elimination: "What was ruled out and why"
  
  technical_analysis:
    logs: "Relevant error messages and patterns"
    code: "Problematic code sections identified"
    environment: "Configuration and dependency issues"
    performance: "Resource usage and bottlenecks"
  
  solution_implementation:
    fix: "Code changes made"
    testing: "Validation results"
    prevention: "Safeguards added"
    monitoring: "Alerts configured"
  
  knowledge_capture:
    lessons_learned: "Key insights from investigation"
    documentation: "Updates to runbooks/wikis"
    process_improvements: "Debugging enhancements"
```

## Success Metrics

### Investigation Quality Gates
- ✅ Root cause identified with supporting evidence
- ✅ Issue reproducible before fix, eliminated after
- ✅ All hypotheses validated or rejected with data
- ✅ Fix addresses root cause, not just symptoms
- ✅ Regression tests prevent recurrence
- ✅ Knowledge captured for future investigations

### Performance Indicators
- Time to root cause identification
- Number of parallel investigations completed
- Fix success rate (permanent resolution)
- Prevention effectiveness (reduced recurrence)

## Example Usage

```bash
# Direct invocation
claude-code spawn debugging-orchestrator "Application throwing intermittent 500 errors"

# With context
claude-code spawn debugging-orchestrator "Memory leak in production after deploying v2.3.1"

# Complex investigation
claude-code spawn debugging-orchestrator "API timeouts correlating with database locks and high CPU"
```

## Integration Points

### Works With
- `cicd-failure-orchestrator`: For build/deployment debugging
- `testing-orchestrator`: For test failure investigation
- `quality-enforcer`: For code quality debugging
- `performance-analyzer`: For performance investigations

### State Files
- `/tmp/claude-debug-*/investigation-state.json`: Current investigation status
- `/tmp/claude-debug-*/evidence-*.json`: Collected evidence per agent
- `/tmp/claude-debug-*/report.md`: Final investigation report

## Best Practices

### DO
- ✅ Spawn all relevant agents for parallel investigation
- ✅ Collect evidence before forming hypotheses
- ✅ Validate root cause with reproduction
- ✅ Address underlying issues, not symptoms
- ✅ Document findings for team learning

### DON'T
- ❌ Jump to conclusions without evidence
- ❌ Fix symptoms without understanding cause
- ❌ Skip validation of implemented fixes
- ❌ Ignore patterns indicating systemic issues
- ❌ Forget to add regression tests

## Advanced Features

### Smart Agent Selection
Automatically determines which specialized agents to spawn based on:
- Error signatures and patterns
- System components affected
- Historical debugging patterns
- Resource availability

### Evidence Correlation Engine
- Cross-references findings from multiple agents
- Identifies causal relationships
- Builds timeline of events
- Generates confidence scores for root causes

### Continuous Learning
- Tracks successful debugging patterns
- Updates agent selection algorithms
- Improves hypothesis generation
- Enhances pattern recognition

## Troubleshooting

### Common Issues
1. **Agent coordination failures**: Check `/tmp/claude-debug-*/` permissions
2. **Incomplete investigations**: Verify all agents completed successfully
3. **Conflicting findings**: Review evidence quality and correlation logic
4. **Performance issues**: Limit parallel agents based on system resources

### Debug Mode
Enable detailed debugging output:
```bash
export CLAUDE_DEBUG_VERBOSE=true
claude-code spawn debugging-orchestrator --debug "issue description"
```