# Sub-Agent Isolation

Execute tasks in isolated sub-agent context without contaminating the main conversation.

## How It Works

This command spawns a dedicated sub-agent to handle your request in complete isolation:
- **Clean context**: Sub-agent starts fresh, no prior conversation history
- **Parallel execution**: Can run multiple sub-agents simultaneously
- **Result-only return**: Only the final output returns to main context
- **No pollution**: Sub-agent's internal processing never affects main conversation

## Usage Patterns

### Basic Isolation
Simply prefix any request with `/sub-agent`:
```
/sub-agent analyze this complex codebase for security issues
/sub-agent research best practices for database migrations
/sub-agent generate comprehensive test suite for this module
```

### Parallel Sub-Agents
Run multiple isolated tasks simultaneously:
```
/sub-agent [task1] research authentication patterns
/sub-agent [task2] analyze performance bottlenecks
/sub-agent [task3] generate API documentation
```

### Specialized Sub-Agents
Use specific agent types for better results:
```
/sub-agent --type=research-orchestrator investigate caching strategies
/sub-agent --type=quality-code-analyzer review this module
/sub-agent --type=testing-orchestrator create comprehensive tests
```

## Available Agent Types

### Research & Analysis
- `general-purpose`: Complex multi-step tasks
- `research-orchestrator`: Comprehensive research with multi-source validation
- `quality-code-analyzer`: Deep code analysis and pattern detection
- `infra-context-discovery`: Codebase context and pattern mining

### Code Generation
- `generic-coder`: Universal coding tasks
- `typescript-coder`: TypeScript with advanced type safety
- `python-coder`: Python with modern best practices
- `go-coder`: Idiomatic Go patterns
- `rust-coder`: Memory-safe Rust development
- `js-coder`: Modern JavaScript/Node.js

### Testing & Quality
- `testing-orchestrator`: Comprehensive test orchestration
- `testing-unit-master`: Specialized unit testing
- `testing-integration-master`: Integration test expertise
- `quality-enforcer`: Code quality audit and fixes
- `quality-security-scan`: Security vulnerability detection

### Documentation
- `doc-api-documenter`: OpenAPI and endpoint documentation
- `doc-readme-generator`: Professional README creation
- `doc-module-generator`: Module-specific documentation
- `doc-architecture-designer`: Architecture Decision Records (ADRs)

### Infrastructure & DevOps
- `infra-orchestrator`: Multi-agent workflow coordination
- `infra-dependency-manager`: Dependency auditing and updates
- `infra-environment-guardian`: Environment consistency checks
- `cicd-failure-orchestrator`: CI/CD failure analysis and fixing

## Implementation

When you use `/sub-agent`, I will:

1. **Spawn isolated agent** with the Task tool
2. **Execute in clean context** without conversation history
3. **Return only results** back to you
4. **Maintain isolation** - no context pollution

## Examples

### Example 1: Clean Research
```
User: /sub-agent research the latest React 19 features and migration guide