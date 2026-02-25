# Claude Code Agents - Template Reference

## Overview

This directory contains agent templates that can be spawned by Claude Code for specialized development tasks. For comprehensive documentation of all agents, see [/docs/agents.md](/docs/agents.md).

## 📁 Agent Naming Convention

All agents follow a category-based naming convention for clarity:

- **`cicd-*`** - CI/CD pipeline and failure fixing agents (11 agents)
- **`testing-*`** - Test orchestration and execution agents (4 agents)
- **`quality-*`** - Code quality analysis and enforcement agents (2 agents)
- **`project-*`** - Project management and milestone agents (3 agents)
- **`infra-*`** - Infrastructure and tooling agents (4 agents)

> **Note**: Backward compatibility symlinks are maintained for all renamed agents, ensuring existing references continue to work.

## Quick Reference

This directory contains 24 specialized agents across 5 categories:

### CI/CD Pipeline Agents (11)
- `cicd-failure-orchestrator` - Master CI/CD pipeline orchestration
- `cicd-failure-detector` - Comprehensive CI/CD failure detection and cataloging
- `cicd-context-analyzer` - Repository context and change analysis
- `cicd-pattern-classifier` - Failure pattern classification
- `cicd-dependency-mapper` - Dependency analysis and mapping
- `cicd-build-fixer` - Build failure resolution
- `cicd-test-fixer` - Test failure resolution in CI/CD
- `cicd-quality-fixer` - Code quality issue resolution
- `cicd-deployment-fixer` - Deployment failure resolution
- `cicd-fix-validator` - Fix validation and verification
- `cicd-regression-tester` - Regression testing for fixes

### Testing Agents (4)
- `testing-orchestrator` - Adaptive test orchestration
- `testing-unit-master` - Unit test specialist
- `testing-integration-master` - Integration test specialist  
- `testing-api-integration` - API integration testing

### Quality Agents (2)
- `quality-code-analyzer` - Code analysis and patterns
- `quality-enforcer` - Standards enforcement

### Project Management Agents (3)
- `project-milestone-coordinator` - Workflow orchestration
- `project-milestone-planner` - Strategic planning
- `project-milestone-executor` - Milestone execution

### Infrastructure Agents (4)
- `infra-orchestrator` - High-level workflow coordination
- `infra-git-operator` - Git operations automation
- `infra-dependency-manager` - Dependency management
- `infra-file-processor` - Bulk file operations

## Detailed Test Agent Documentation

### 🎯 test-orchestrator
**Hybrid Adaptive Test Orchestrator**
- Intelligently switches between unit and integration testing modes
- Automatically categorizes tests and selects optimal execution strategy
- Coordinates multi-agent test execution for maximum efficiency
- Achieves 100% test success rate through comprehensive orchestration

**Use when:**
- You need comprehensive test execution across multiple test types
- You want intelligent test categorization and optimization
- You have a mixed test suite with unit, integration, and e2e tests

### 🚀 unit-test-master
**Unit Test Specialist**
- Focuses on fast, isolated component testing
- Expert mock management and test isolation
- Maximum parallelization for speed
- Deep framework-specific optimizations

**Use when:**
- You need to run or fix unit tests specifically
- You want to optimize unit test performance
- You need expert mock management and isolation

### 🌐 integration-test-master
**Integration Test Specialist**
- Handles complex service orchestration
- Manages test environments and dependencies
- Validates cross-service communication
- Ensures data consistency across systems

**Use when:**
- You need to test service interactions
- You want to validate API contracts
- You need comprehensive end-to-end testing

## Detailed CI/CD Pipeline Agent Documentation

### 🔍 failure-detector
**Comprehensive CI/CD Failure Detection Specialist**
- Multi-platform failure scanning (GitHub Actions, GitLab CI, Jenkins, Azure DevOps)
- Intelligent log analysis and error pattern recognition
- Failure categorization by type, severity, and environmental factors
- Works in parallel with context-analyzer for complete Stage 1 discovery

**Use when:**
- You need to catalog all CI/CD failures across multiple platforms
- You want to understand failure patterns and trends over time
- You need structured failure data for automated root cause analysis

### 🔄 context-analyzer
**Repository Context and Change Analysis Specialist**
- Git history analysis and change pattern detection
- Dependency evolution tracking and vulnerability scanning
- CI configuration change monitoring
- Environmental factor assessment and correlation analysis

**Use when:**
- You need to understand why CI/CD failures are occurring
- You want to correlate failures with recent changes
- You need risk assessment for recent repository changes

### Stage 1 Parallel Discovery Pattern

The CI/CD pipeline agents work as parallel discovery agents:

```markdown
Stage 1 Discovery (Parallel Execution):
├─ failure-detector: What failed, when, where, how
└─ context-analyzer: Why it might have failed (changes, dependencies, environment)

Output: /tmp/cicd-pipeline-{timestamp}/stage-1/
├─ detected-failures.json    (from failure-detector)
└─ analyzed-context.json     (from context-analyzer)

Timeline: 2-5 minutes for complete discovery
```

## Architecture

### Coordination Pattern

The test agents use a sophisticated coordination pattern:

1. **Test Orchestrator** acts as the main coordinator
2. **Specialist Agents** (unit/integration) handle specific test types
3. **Shared Intelligence** provides common capabilities
4. **Coordination Mechanisms** enable agent communication

### 5-Agent Spawning Pattern

Each agent can spawn up to 5 sub-agents for parallel execution:

```markdown
Agent 1: Analysis & Discovery
Agent 2: Environment Setup
Agent 3: Test Execution
Agent 4: Failure Analysis
Agent 5: Validation & Reporting
```

### Shared Components

Located in `_shared/`:
- `test-intelligence.md` - Framework detection, failure analysis, coverage
- `test-coordination.md` - Agent communication, state sync, reporting

## Usage Examples

### Basic Test Orchestration
```bash
# User: "Run all my tests and fix any failures"
# Claude will use test-orchestrator to:
# 1. Discover and categorize all tests
# 2. Execute with optimal strategy
# 3. Fix any failures
# 4. Achieve 100% pass rate
```

### Unit Test Focus
```bash
# User: "Fix my failing unit tests"
# Claude will use unit-test-master to:
# 1. Identify unit test failures
# 2. Analyze mock and isolation issues
# 3. Fix with maximum speed
# 4. Optimize performance
```

### Integration Testing
```bash
# User: "Test my API integrations"
# Claude will use integration-test-master to:
# 1. Setup test environment
# 2. Orchestrate services
# 3. Validate contracts
# 4. Ensure data consistency
```

### CI/CD Failure Analysis
```bash
# User: "Analyze why our CI pipelines are failing"
# Claude will use failure-detector and context-analyzer in parallel to:
# 1. Scan all CI platforms for recent failures (failure-detector)
# 2. Analyze recent repository changes and dependencies (context-analyzer)
# 3. Correlate failures with changes and environment factors
# 4. Generate comprehensive failure analysis for Stage 2 resolution
```

### Pipeline Health Assessment
```bash
# User: "Give me a complete picture of our CI/CD pipeline stability"
# Claude will use both CI/CD agents to:
# 1. Catalog all failures across platforms over the last 30 days
# 2. Identify failure patterns and trends
# 3. Assess risk factors from recent changes
# 4. Provide actionable recommendations for pipeline improvement
```

## Framework Support

All agents support major testing frameworks:

**JavaScript/TypeScript:**
- Jest
- Vitest
- Mocha
- Jasmine

**Python:**
- pytest
- unittest

**Go:**
- Built-in testing

**Ruby:**
- RSpec
- Minitest

**Java:**
- JUnit

**PHP:**
- PHPUnit

**Rust:**
- Built-in testing

**CI/CD Platform Support:**

**GitHub Actions:**
- Workflow run analysis
- Job-level failure detection
- Log download and parsing

**GitLab CI:**
- Pipeline analysis
- Job failure tracking
- Runner-specific issues

**Jenkins:**
- Build failure detection
- Console log analysis
- Plugin compatibility issues

**Azure DevOps:**
- Pipeline failure analysis
- Agent configuration issues
- Build artifact problems

**CircleCI:**
- Job failure detection
- Workflow analysis
- Orb compatibility issues

## Performance Features

### Intelligent Parallelization
- Unit tests: Maximum parallelization
- Integration tests: Controlled parallelization
- E2E tests: Sequential or limited parallel

### Resource Management
- Automatic resource allocation
- CPU and memory optimization
- Adaptive scaling based on system capacity

### Failure Recovery
- Automatic agent recovery on failure
- Work reassignment to healthy agents
- Session persistence for interruption recovery

## Quality Guarantees

All test agents enforce:
- ✅ 100% test success rate
- ✅ Comprehensive coverage analysis
- ✅ Flaky test elimination
- ✅ Performance optimization
- ✅ Detailed reporting

## Integration with Claude Code

These agents integrate seamlessly with:
- Task tool for true parallelism
- Existing test commands in `/test/`
- Coverage and performance monitoring
- CI/CD workflows

## Best Practices

1. **Start with test-orchestrator** for comprehensive testing
2. **Use specialist agents** for focused optimization
3. **Monitor coordination files** in `/tmp/test-sessions/`
4. **Review aggregated reports** for insights
5. **Let agents handle parallelization** automatically

## Troubleshooting

### Common Issues

**Tests not discovered:**
- Check framework detection in test-intelligence
- Verify test file naming conventions

**Parallelization issues:**
- Review resource allocation
- Check test isolation

**Agent coordination failures:**
- Verify `/tmp/` permissions
- Check session file integrity

## Future Enhancements

Planned improvements:
- Mutation testing capabilities
- Visual regression testing
- Performance baseline tracking
- Contract testing specialization
- Chaos testing integration