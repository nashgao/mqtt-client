# /persistent-ci-fix - Automated CI Monitoring & Fixing

**CATEGORY**: CI/CD  
**COMPLEXITY**: MEDIUM  
**AGENT SPAWNING**: AUTOMATIC

## Overview

Continuously monitors CI pipeline status and automatically applies fixes when failures are detected. Provides intelligent retry logic, parallel agent spawning for complex issues, and comprehensive progress tracking.

## Usage

```bash
/persistent-ci-fix [mode] [options]
```

### Basic Usage
- `/persistent-ci-fix` - Start with default settings (thorough mode, 3 attempts)
- `/persistent-ci-fix quick` - Fast fixes only, 5 attempts max
- `/persistent-ci-fix emergency` - All available fixes, unlimited attempts until success

### Advanced Configuration
- `/persistent-ci-fix --max-attempts 10` - Set maximum retry attempts
- `/persistent-ci-fix --sleep 30` - Set sleep time between attempts (seconds)
- `/persistent-ci-fix --parallel` - Enable parallel agent spawning for complex issues
- `/persistent-ci-fix --watch-branch main` - Monitor specific branch
- `/persistent-ci-fix --stop-on-success` - Stop after first successful run

## Fixing Modes

### 🚀 Quick Mode
```bash
/persistent-ci-fix quick
```
- **Focus**: Common, fast-to-fix issues
- **Max Attempts**: 5
- **Sleep Time**: 15 seconds
- **Agent Spawning**: Single agent for simple fixes
- **Fixes Applied**:
  - Linting errors (formatting, imports)
  - Type errors (basic type annotations)
  - Test compilation issues
  - Simple dependency conflicts

### 🔧 Thorough Mode (Default)
```bash
/persistent-ci-fix thorough
# or simply
/persistent-ci-fix
```
- **Focus**: Comprehensive error analysis and fixing
- **Max Attempts**: 3
- **Sleep Time**: 30 seconds
- **Agent Spawning**: Multi-agent for complex issues
- **Fixes Applied**:
  - All quick mode fixes
  - Complex test failures
  - Build configuration issues
  - Cross-module dependency problems
  - Performance optimization warnings

### 🚨 Emergency Mode
```bash
/persistent-ci-fix emergency
```
- **Focus**: Get CI green by any means necessary
- **Max Attempts**: Unlimited (until success or user interruption)
- **Sleep Time**: 45 seconds
- **Agent Spawning**: Maximum parallelization
- **Fixes Applied**:
  - All thorough mode fixes
  - Aggressive refactoring when needed
  - Temporary workarounds for complex issues
  - Infrastructure and configuration changes
  - Emergency rollbacks if necessary

## Configuration Options

### Attempt Configuration
```bash
# Set maximum retry attempts
/persistent-ci-fix --max-attempts 5

# Set sleep time between attempts
/persistent-ci-fix --sleep 60

# Combine mode with custom settings
/persistent-ci-fix thorough --max-attempts 8 --sleep 20
```

### Branch and Target Configuration
```bash
# Monitor specific branch
/persistent-ci-fix --watch-branch develop

# Target specific CI workflow
/persistent-ci-fix --workflow "tests"

# Monitor multiple workflows
/persistent-ci-fix --workflows "tests,lint,build"
```

### Agent Spawning Configuration
```bash
# Force parallel execution
/persistent-ci-fix --parallel

# Limit number of concurrent agents
/persistent-ci-fix --max-agents 4

# Disable agent spawning (single-threaded)
/persistent-ci-fix --no-agents
```

## Real-Time Progress Display

The command provides live updates during execution:

```
🔄 Persistent CI Fix Started - Thorough Mode
   Branch: main | Max Attempts: 3 | Sleep: 30s

[Attempt 1/3] 🔍 Checking CI Status...
├─ ❌ Tests: 4 failures detected
├─ ❌ Lint: 12 formatting issues
└─ ✅ Build: Passing

[Fixing] 🚀 Spawning 3 specialized agents...
├─ Agent 1: test-failure-resolver (4 test failures)
├─ Agent 2: lint-formatter (12 formatting issues)
└─ Agent 3: ci-status-monitor (watching pipeline)

[Progress] 🔧 Applying fixes...
├─ ✅ Fixed: Import ordering in src/utils.py
├─ ✅ Fixed: Type annotation in src/models.py
├─ 🔄 Working: Test assertion in test_auth.py
└─ 🔄 Working: Mock configuration in test_api.py

[Attempt 1/3] ⏱️  Waiting 30s for CI to re-run...

[Attempt 2/3] 🔍 Re-checking CI Status...
├─ ✅ Tests: All passing
├─ ✅ Lint: All checks passed
└─ ✅ Build: Passing

🎉 SUCCESS! CI is now green after 1 attempt(s)
```

## Parallel Agent Spawning

When complex issues are detected, the command automatically spawns specialized agents:

### Automatic Triggers for Agent Spawning
- **Test failures ≥ 3**: Spawn `testing-orchestrator` with failure-specific agents
- **Lint errors ≥ 10**: Spawn `code-quality-enforcer` agents
- **Build failures**: Spawn `build-diagnostic` and `dependency-resolver` agents
- **Cross-module issues**: Spawn `architecture-analyzer` agents

### Agent Coordination Example
```bash
/persistent-ci-fix thorough --parallel

# Results in:
# Agent 1: test-unit-fixer (handles unit test failures)
# Agent 2: test-integration-fixer (handles integration test failures)  
# Agent 3: lint-style-enforcer (handles style violations)
# Agent 4: type-error-resolver (handles typing issues)
# Agent 5: ci-coordinator (monitors overall progress)
```

## Examples

### Basic Continuous Monitoring
```bash
# Start basic monitoring with defaults
/persistent-ci-fix

# Expected output:
# 🔄 Monitoring CI on branch 'main'
# 🔧 Thorough mode: Will attempt up to 3 fixes
# ⏱️  Sleep interval: 30 seconds between attempts
```

### Quick Development Workflow
```bash
# Fast fixes during active development
/persistent-ci-fix quick --max-attempts 10 --sleep 15

# Perfect for:
# - Active development sessions
# - Quick iteration cycles
# - Simple error fixing
```

### Production Emergency Response
```bash
# Emergency mode for critical CI failures
/persistent-ci-fix emergency --watch-branch main --parallel

# Characteristics:
# - Unlimited retry attempts
# - Maximum agent parallelization
# - All available fixing strategies
# - Real-time status updates
```

### Branch-Specific Monitoring
```bash
# Monitor feature branch during development
/persistent-ci-fix --watch-branch feature/new-auth --max-attempts 5

# Monitor release branch with conservative approach
/persistent-ci-fix quick --watch-branch release/v2.1 --sleep 60
```

### Custom Workflow Targeting
```bash
# Target specific CI workflows
/persistent-ci-fix --workflows "unit-tests,integration-tests" --parallel

# Focus on build and deployment
/persistent-ci-fix emergency --workflows "build,deploy" --max-attempts 20
```

## Fix Summary Report

After completion, the command provides a comprehensive summary:

```
📊 Persistent CI Fix Summary
═══════════════════════════════════════

🎯 Target: main branch
🕐 Duration: 3 minutes 45 seconds
🔄 Attempts: 2/3
🤖 Agents Used: 4 specialized agents

📈 Issues Resolved:
├─ Tests
│  ├─ ✅ Fixed failing assertion in test_user_auth.py
│  ├─ ✅ Updated mock configuration in test_api_client.py
│  └─ ✅ Resolved import cycle in test_models.py
├─ Linting
│  ├─ ✅ Fixed 8 formatting issues (auto-formatter)
│  ├─ ✅ Added missing type annotations (3 functions)
│  └─ ✅ Organized imports (PEP8 compliance)
└─ Build
   └─ ✅ Already passing (no fixes needed)

🚀 Performance:
├─ Parallel Execution: 3x faster than sequential
├─ Agent Specialization: 85% success rate improvement
└─ Total Files Modified: 12

🎉 Result: CI is now GREEN ✅
💡 Recommendation: Consider adding pre-commit hooks to prevent future formatting issues
```

## Configuration File Support

Create `.claude/ci-fix-config.yaml` for persistent settings:

```yaml
# CI Fix Configuration
default_mode: thorough
max_attempts: 5
sleep_interval: 30
watch_branch: main
enable_parallel: true
max_agents: 6

# Mode-specific overrides
modes:
  quick:
    max_attempts: 8
    sleep_interval: 15
    enable_parallel: false
  emergency:
    max_attempts: -1  # unlimited
    sleep_interval: 45
    enable_parallel: true
    max_agents: 10

# Agent preferences
agents:
  auto_spawn_threshold: 3  # spawn agents when ≥3 issues
  specialized_agents: true
  agent_timeout: 300  # 5 minutes per agent

# Notification settings
notifications:
  success: true
  failure: true
  progress_updates: true
```

## Integration with Other Commands

### Chain with Other CI Commands
```bash
# Run persistent fix, then generate report
/persistent-ci-fix thorough && /ci-status --detailed

# Quick fix before deployment
/persistent-ci-fix quick --max-attempts 3 && /deploy-check
```

### Git Integration
```bash
# Fix CI issues and create commit
/persistent-ci-fix --auto-commit "CI: Automated fixes from persistent-ci-fix"

# Fix issues on feature branch before PR
/persistent-ci-fix --watch-branch feature/auth --create-pr-ready
```

## Error Handling and Edge Cases

### Timeout Handling
- Individual agent timeout: 5 minutes
- Overall command timeout: 30 minutes (configurable)
- Graceful degradation when agents fail

### Network Issues
- Automatic retry with exponential backoff
- Offline detection and graceful handling
- CI service outage detection

### Permission Issues
- Clear error messages for insufficient permissions
- Suggestions for required access levels
- Fallback to read-only monitoring mode

## Safety Features

### Backup and Rollback
- Automatic backup before applying fixes
- One-click rollback if fixes cause regressions
- Commit history preservation

### Change Validation
- Dry-run mode for testing fixes
- Change preview before application
- User confirmation for major modifications

### Rate Limiting
- Respects CI service rate limits
- Adaptive timing based on service response
- Queue management for multiple fix attempts

## Best Practices

### When to Use Each Mode

**Quick Mode**: 
- Active development
- Simple formatting/typing issues
- Frequent small commits

**Thorough Mode**:
- Feature branch completion
- Pre-merge CI verification
- Regular maintenance

**Emergency Mode**:
- Production CI failures
- Release branch issues
- Critical deployment blockers

### Recommended Configurations

**Development Team**:
```bash
/persistent-ci-fix thorough --max-attempts 3 --sleep 30 --parallel
```

**CI/CD Pipeline Integration**:
```bash
/persistent-ci-fix quick --max-attempts 5 --auto-commit --stop-on-success
```

**Production Monitoring**:
```bash
/persistent-ci-fix emergency --watch-branch main --notifications --max-agents 8
```

---

**Note**: This command automatically spawns appropriate agents based on issue complexity. For maximum effectiveness, ensure your project has proper CI configuration and the necessary permissions for automated fixes.