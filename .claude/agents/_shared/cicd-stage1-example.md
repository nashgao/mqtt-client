# Stage 1 CI/CD Discovery - Usage Example

## Parallel Agent Execution

When analyzing CI/CD pipeline failures, both agents work simultaneously:

### User Request
```
"Our CI pipelines have been failing frequently. Can you analyze what's going wrong?"
```

### Orchestrator Response
```markdown
I'll deploy both failure-detector and context-analyzer agents in parallel to give you a comprehensive analysis.

<function_calls>
<invoke name="Task">
<parameter name="subagent_type">failure-detector</parameter>
<parameter name="description">Detect and catalog all CI/CD failures</parameter>
<parameter name="prompt">You are the Failure Detection Specialist. 

Analyze CI/CD failures across all platforms:
1. Scan GitHub Actions, GitLab CI, Jenkins, and other detected platforms
2. Catalog all failures from the last 30 days
3. Extract failure patterns and categorize by type and severity
4. Generate comprehensive failure metadata for root cause analysis
5. Output structured data to /tmp/cicd-pipeline-20240115-103000/stage-1/detected-failures.json

Work in parallel with context-analyzer to provide complete pipeline discovery.</parameter>
</invoke>
</function_calls>

<function_calls>
<invoke name="Task">
<parameter name="subagent_type">context-analyzer</parameter>
<parameter name="description">Analyze repository context and recent changes</parameter>
<parameter name="prompt">You are the Context Analysis Specialist.

Analyze repository context that may contribute to CI failures:
1. Review git history for the last 30 days and identify change patterns
2. Track dependency updates and security vulnerabilities  
3. Analyze CI configuration changes and environmental factors
4. Correlate changes with timing of failures from failure-detector
5. Output risk assessment to /tmp/cicd-pipeline-20240115-103000/stage-1/analyzed-context.json

Work in parallel with failure-detector to provide complete pipeline discovery.</parameter>
</invoke>
</function_calls>
```

### Expected Timeline
- **Parallel execution**: 2-5 minutes
- **Stage 1 completion**: Both agents output structured JSON
- **Stage 2 ready**: Data available for root cause analysis agents

### Output Structure
```
/tmp/cicd-pipeline-20240115-103000/
└── stage-1/
    ├── detected-failures.json    # From failure-detector
    ├── analyzed-context.json     # From context-analyzer
    └── correlation-ready.json    # Combined insights
```

### Stage 2 Handoff
Once both agents complete, Stage 2 agents can consume the structured data for:
- Root cause analysis
- Automated fix generation
- Prevention recommendations
- Priority-based resolution planning

This parallel approach ensures comprehensive discovery of both symptoms (failures) and potential causes (context) within the same timeframe.