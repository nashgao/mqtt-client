# /investigate Command

Comprehensive problem investigation using multi-agent research coordination for bugs, issues, and system problems.

## Usage
```
/investigate <problem description>
/investigate "error message or symptom"
/investigate --critical "production issue description"
/investigate --deep "complex problem requiring extensive analysis"
```

## Description
Deploys specialized investigation agents to conduct systematic, evidence-based problem analysis following industry best practices.

## Investigation Modes

### Standard Investigation (Default)
- Systematic 5-phase investigation
- Parallel agent deployment
- Root cause analysis
- Solution recommendations

### Critical Investigation (--critical)
- Emergency response mode
- All agents deployed immediately
- Focus on stabilization first
- Real-time status updates

### Deep Investigation (--deep)
- Extended analysis time
- Historical pattern analysis
- Predictive failure assessment
- Comprehensive documentation

## Investigation Process

### Phase 1: Initial Assessment (2-5 mins)
```yaml
actions:
  - Classify problem type and severity
  - Deploy appropriate research agents
  - Establish investigation parameters
  - Create investigation workspace
```

### Phase 2: Parallel Investigation (5-15 mins)
```yaml
agent_deployment:
  problem_investigator:
    role: "Primary investigation coordinator"
    tasks: "Symptom analysis, root cause discovery"

  research_orchestrator:
    role: "Multi-source evidence gathering"
    tasks: "Pattern synthesis, validation"

  infra_context_discovery:
    role: "Codebase and system analysis"
    tasks: "Dependency mapping, impact assessment"

  specialized_agents:
    based_on_problem_type:
      - cicd_failure_detector (CI/CD issues)
      - quality_security_scan (Security concerns)
      - testing_orchestrator (Test failures)
      - perf_sql_optimizer (Performance problems)
```

### Phase 3: Evidence Synthesis (3-5 mins)
```yaml
synthesis:
  - Combine findings from all agents
  - Cross-reference evidence
  - Validate hypotheses
  - Identify root cause
```

### Phase 4: Solution Development (2-5 mins)
```yaml
solution_creation:
  - Develop fix recommendations
  - Prioritize by impact and effort
  - Include prevention measures
  - Validate approach
```

### Phase 5: Report Generation
```yaml
deliverables:
  - Executive summary
  - Detailed findings
  - Evidence compilation
  - Solution recommendations
  - Implementation guide
```

## Command Examples

### Basic Investigation
```bash
/investigate "Application crashes when uploading large files"
```
**Output**: Standard investigation report with root cause and fixes

### Critical Production Issue
```bash
/investigate --critical "Database connection pool exhausted, users can't log in"
```
**Output**: Emergency response with immediate workaround and permanent fix

### Complex Performance Problem
```bash
/investigate --deep "API response times degrading over past week"
```
**Output**: Comprehensive analysis with trends, patterns, and optimization plan

### CI/CD Pipeline Failure
```bash
/investigate "GitHub Actions failing on test stage with timeout"
```
**Output**: Pipeline analysis with specific failure points and fixes

## Investigation Report Format

### Standard Report Structure
```markdown
# Investigation Report: [Problem Title]
Generated: [Timestamp]
Severity: [Critical|High|Medium|Low]
Investigation ID: [Unique ID]

## Executive Summary
- **Problem**: [Concise description]
- **Root Cause**: [Primary cause]
- **Impact**: [Who/what affected]
- **Solution**: [Recommended fix]
- **ETA**: [Time to implement]

## Findings

### Symptom Analysis
- Observable symptoms
- Error messages
- Affected components
- Trigger conditions

### Root Cause Analysis
- Investigation methodology
- Evidence trail
- Root cause identification
- Contributing factors

### Impact Assessment
- User impact
- System impact
- Business impact
- Risk assessment

## Evidence
- Logs and traces
- Code analysis
- Test results
- Environmental factors

## Solutions

### Immediate Actions
1. [Quick fixes/workarounds]
2. [Stabilization steps]

### Permanent Solution
1. [Root cause fix]
2. [Implementation steps]
3. [Validation approach]

### Prevention Measures
1. [Monitoring additions]
2. [Process improvements]
3. [Code enhancements]

## Appendices
- A. Full Error Logs
- B. Related Code
- C. Test Cases
- D. References
```

## Advanced Features

### Pattern Recognition
```yaml
automatic_detection:
  - Similar past issues
  - Known problem patterns
  - Common root causes
  - Existing solutions
```

### Predictive Analysis
```yaml
forecasting:
  - Potential cascading failures
  - Performance degradation trends
  - Resource exhaustion risks
  - Security vulnerability exposure
```

### Knowledge Base Integration
```yaml
knowledge_management:
  - Search existing investigations
  - Apply previous solutions
  - Update with new findings
  - Build pattern library
```

## Integration Points

### With Other Commands
- `/thinkthrough` - For solution implementation
- `/test` - For validation of fixes
- `/monitor` - For ongoing observation
- `/document` - For knowledge capture

### With Development Workflow
- Pre-commit investigation
- Pull request validation
- Production monitoring
- Incident response

## Best Practices

### Investigation Guidelines
1. **Always start with symptoms** - Don't assume root cause
2. **Gather evidence first** - Data before theories
3. **Use parallel agents** - Faster comprehensive analysis
4. **Validate hypotheses** - Test before concluding
5. **Document everything** - Clear audit trail

### Common Investigation Anti-Patterns
- ❌ Fixing symptoms without finding root cause
- ❌ Making assumptions without evidence
- ❌ Ignoring environmental factors
- ❌ Not checking recent changes
- ❌ Skipping impact assessment

## Performance Metrics

### Investigation KPIs
- **MTTD** (Mean Time To Detection): < 2 mins
- **MTTR** (Mean Time To Root cause): < 10 mins
- **MTTS** (Mean Time To Solution): < 15 mins
- **Accuracy Rate**: > 95%
- **Fix Effectiveness**: > 90%

## Emergency Protocols

### For Critical Issues
1. **Immediate Triage** (30 seconds)
   - Assess severity and impact
   - Deploy all available agents
   - Alert relevant stakeholders

2. **Stabilization** (2 mins)
   - Implement emergency workaround
   - Isolate affected components
   - Prevent cascade failures

3. **Investigation** (5 mins)
   - Root cause discovery
   - Evidence collection
   - Solution development

4. **Resolution** (varies)
   - Apply validated fix
   - Monitor stability
   - Document incident

## Configuration Options

### Investigation Depth
```yaml
depth_levels:
  quick: "Surface-level, 5 min max"
  standard: "Comprehensive, 15 min"
  deep: "Exhaustive, 30+ min"
  continuous: "Ongoing monitoring"
```

### Agent Selection
```yaml
agent_profiles:
  minimal: "problem-investigator only"
  standard: "3-4 coordinated agents"
  maximum: "All relevant agents"
  adaptive: "Auto-select based on problem"
```

### Output Preferences
```yaml
report_format:
  summary: "Executive summary only"
  standard: "Full investigation report"
  detailed: "Include all evidence"
  technical: "Deep technical analysis"
```

## Success Criteria

An investigation is considered successful when:
- ✅ Root cause identified with evidence
- ✅ All symptoms explained
- ✅ Solution validated as effective
- ✅ Prevention measures defined
- ✅ Documentation complete
- ✅ Knowledge base updated

## Continuous Improvement

### Post-Investigation
1. Update pattern library
2. Refine investigation techniques
3. Improve agent coordination
4. Enhance detection capabilities
5. Share learnings with team

### Feedback Loop
- Track investigation outcomes
- Measure fix effectiveness
- Monitor problem recurrence
- Adjust methodologies
- Optimize agent deployment

## Quick Reference

### Command Shortcuts
```bash
/inv "error"                    # Short form
/investigate --c "critical"     # Critical mode
/investigate --d "complex"       # Deep analysis
/investigate --help             # Show this help
```

### Common Patterns
```bash
# Performance issue
/investigate "slow response times in API endpoint /users"

# Integration failure
/investigate "webhook not triggering on payment completion"

# Data inconsistency
/investigate "user counts don't match between services"

# Security concern
/investigate "unauthorized access attempts detected"

# CI/CD failure
/investigate "deployment pipeline failing at build stage"
```

Remember: The /investigate command is your primary tool for systematic problem analysis. It coordinates multiple research agents to deliver comprehensive, evidence-based investigations that lead to permanent solutions.