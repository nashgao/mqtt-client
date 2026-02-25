# Language-Specific Coder Template Design

## Overview

This document outlines the design and architecture of language-specific coder templates for the Claude Code agent system. These templates provide specialized agents for different programming languages with advanced patterns, framework awareness, and production-ready development capabilities.

## Template Architecture

### Core Design Principles

1. **Language Specialization**: Each template focuses on idiomatic patterns and best practices specific to its language
2. **Framework Awareness**: Automatic detection and optimization for popular frameworks within each ecosystem
3. **Multi-Agent Coordination**: Complex projects spawn specialized sub-agents for parallel development
4. **Quality Assurance**: Built-in quality gates and validation specific to language conventions
5. **Performance Focus**: Language-specific optimization patterns and performance considerations

### Template Hierarchy

```
templates/agents/
├── code-js-coder.md         # JavaScript/Node.js specialist
├── code-python-coder.md     # Python development specialist  
├── code-typescript-coder.md # TypeScript with advanced type safety
├── code-go-coder.md         # Go systems programming specialist
├── code-rust-coder.md       # Rust memory safety and performance
└── code-php-transformer.md  # PHP Space-Utils transformer (existing)
```

## Language-Specific Features

### JavaScript Coder (`code-js-coder.md`)

**Specializations:**
- Modern ES6+ patterns and features
- React/Vue/Angular component development
- Node.js API development with Express/Fastify
- Async/await and Promise patterns
- Performance optimization and bundling

**Framework Detection:**
- React ecosystem (hooks, context, state management)
- Vue.js (composition API, reactivity)
- Node.js (Express, Fastify, NestJS)
- Testing (Jest, Vitest, Cypress)

**Quality Gates:**
- ESLint compliance with zero warnings
- Prettier formatting consistency  
- Bundle size optimization
- Performance metrics validation
- Comprehensive test coverage

### Python Coder (`code-python-coder.md`)

**Specializations:**
- Type hints and modern Python 3.8+ features
- FastAPI/Django/Flask web development
- Data processing with pandas/NumPy
- Async programming with asyncio
- Machine learning integration patterns

**Framework Detection:**
- Web frameworks (FastAPI, Django, Flask)
- Data science (pandas, NumPy, scikit-learn)
- Testing (pytest, unittest, hypothesis)
- ORM systems (SQLAlchemy, Django ORM)

**Quality Gates:**
- MyPy type checking passes
- Black formatting and isort imports
- Pytest coverage >90%
- PEP 8 compliance
- Security scanning with bandit

### TypeScript Coder (`code-typescript-coder.md`)

**Specializations:**
- Advanced type system features (generics, mapped types, conditional types)
- React with strict TypeScript patterns
- Node.js backends with full type safety
- Runtime validation alignment with types
- Zero `any` type usage in production

**Framework Detection:**
- React with TypeScript (hooks, components, context)
- Node.js APIs with type safety
- Testing frameworks with TypeScript support
- Build tools (Vite, Webpack, esbuild)

**Quality Gates:**
- Strict TypeScript configuration enabled
- Zero `any` types in production code
- Runtime validation matches TypeScript types
- Comprehensive type coverage >95%
- Advanced patterns used appropriately

### Go Coder (`code-go-coder.md`)

**Specializations:**
- Idiomatic Go patterns and conventions
- High-performance concurrent programming
- HTTP servers with proper middleware
- Microservices and distributed systems
- Error handling with custom error types

**Framework Detection:**
- HTTP frameworks (net/http, Gin, Echo, Fiber)
- Database libraries (GORM, sqlx)
- Testing patterns (table-driven tests)
- Deployment (Docker, Kubernetes)

**Quality Gates:**
- golangci-lint passes without warnings
- Race condition detection with -race flag
- Performance benchmarks meet requirements
- Proper error handling throughout
- Memory usage optimized and profiled

### Rust Coder (`code-rust-coder.md`)

**Specializations:**
- Memory safety with ownership system
- Zero-cost abstractions and generics
- Async programming with tokio/async-std
- Systems programming patterns
- Performance optimization with profiling

**Framework Detection:**
- Async runtimes (tokio, async-std, smol)
- Web frameworks (axum, warp, actix-web)
- Testing (cargo test, criterion benchmarks)
- Build systems (cargo, cross-compilation)

**Quality Gates:**
- Memory safety guaranteed (justified unsafe only)
- Clippy lints addressed
- Proper error handling with Result types
- Performance benchmarks meet requirements
- Comprehensive rustdoc documentation

## Multi-Agent Coordination Pattern

### Standardized Agent Spawning

All language coders follow a consistent 5-agent spawning pattern for complex projects:

1. **Setup Agent**: Environment, tooling, and project initialization
2. **Core Logic Agent**: Business logic and language-specific implementations
3. **Framework Agent**: Framework-specific development (web, UI, etc.)
4. **Testing Agent**: Comprehensive testing and quality assurance
5. **Deployment Agent**: Performance optimization and production readiness

### Coordination Variables

```yaml
coordination_variables:
  timestamp: "$(date +%s)"           # Unique coordination identifier
  session_id: "{lang}-dev-$(date +%s)" # Session tracking
  pwd: "$(pwd)"                      # Working directory context
  framework_type: "detected_framework" # Core framework in use
  ui_framework: "detected_ui"        # UI framework (if applicable)
  test_framework: "detected_test"    # Testing framework
  deployment_target: "target_env"    # Deployment environment
```

### Inter-Agent Communication

Agents coordinate through temporary JSON files:
- `/tmp/{lang}-setup-{timestamp}.json` - Setup configuration
- `/tmp/{lang}-core-{timestamp}.json` - Core implementation details  
- `/tmp/{lang}-{framework}-{timestamp}.json` - Framework-specific details
- `/tmp/{lang}-testing-{timestamp}.json` - Testing configuration
- Final cleanup removes coordination files

## Framework Detection and Adaptation

### Automatic Framework Detection

Each language coder automatically detects and adapts to popular frameworks:

```yaml
detection_patterns:
  javascript:
    package_json_dependencies:
      - react: "React ecosystem patterns"
      - vue: "Vue.js composition patterns"
      - express: "Express.js server patterns"
      - next: "Next.js full-stack patterns"
    
  python:
    requirements_txt:
      - fastapi: "FastAPI async patterns"
      - django: "Django ORM and views"
      - flask: "Flask blueprint patterns"
      - pandas: "Data processing patterns"
    
  typescript:
    package_json_devdependencies:
      - "@types/react": "React TypeScript patterns"
      - "@types/node": "Node.js TypeScript patterns"
      - "vite": "Vite build optimization"
    
  go:
    go_mod_dependencies:
      - "github.com/gin-gonic/gin": "Gin HTTP patterns"
      - "github.com/gorilla/mux": "Gorilla mux patterns"
      - "gorm.io/gorm": "GORM database patterns"
    
  rust:
    cargo_toml_dependencies:
      - tokio: "Tokio async patterns"
      - axum: "Axum web framework patterns"
      - serde: "Serialization patterns"
```

### Adaptive Code Generation

Based on detected frameworks, coders automatically apply:
- Framework-specific code patterns
- Appropriate dependency injection patterns
- Framework testing conventions
- Build and deployment configurations
- Performance optimization techniques

## Quality Assurance Framework

### Language-Specific Quality Gates

Each language implements mandatory quality gates before completion:

```yaml
quality_gates:
  javascript:
    - eslint_compliance: "Zero warnings allowed"
    - prettier_formatting: "Consistent code formatting"
    - bundle_size_optimization: "Size limits met"
    - test_coverage: ">80% coverage required"
    
  python:
    - type_checking: "MyPy passes without errors"
    - code_formatting: "Black and isort applied"
    - test_coverage: ">90% coverage required"
    - security_scan: "Bandit security check passes"
    
  typescript:
    - strict_typescript: "Strict mode enabled and passing"
    - zero_any_types: "No any types in production"
    - type_coverage: ">95% type coverage"
    - runtime_validation: "Types match runtime validation"
    
  go:
    - linting: "golangci-lint passes"
    - race_detection: "go test -race passes"
    - benchmarks: "Performance requirements met"
    - error_handling: "Proper error propagation"
    
  rust:
    - clippy_lints: "All clippy warnings addressed"
    - memory_safety: "No unjustified unsafe code"
    - error_handling: "Result types used consistently"
    - performance: "Benchmark requirements met"
```

### Progressive Complexity Validation

All templates implement the mandatory complexity triage system:
- 🟢 **SIMPLE**: Direct implementation with existing patterns
- 🟡 **MODERATE**: Advanced patterns requiring justification
- 🔴 **COMPLEX**: Multi-agent approach with user approval
- 🔵 **ADVANCED**: Specialized expertise with explicit consent

## Integration Patterns

### Cross-Language Project Support

For polyglot projects, language coders coordinate through:
- Shared API contract definitions
- Common data format specifications  
- Unified error handling patterns
- Consistent logging and monitoring
- Coordinated deployment strategies

### Tool Integration

Each language coder integrates with Claude Code tools:
- **Bash Tool**: Language-specific command execution
- **Glob Tool**: File pattern matching for language files
- **Grep Tool**: Language-aware pattern searching  
- **Read Tool**: Configuration and code analysis
- **Edit/MultiEdit Tools**: Language-specific refactoring

## Performance Optimization Strategies

### Language-Specific Optimizations

```yaml
optimization_focus:
  javascript:
    - bundle_splitting: "Code splitting and lazy loading"
    - tree_shaking: "Unused code elimination"
    - runtime_optimization: "V8 optimization patterns"
    
  python:
    - algorithmic: "NumPy vectorization, pandas optimization"
    - async_patterns: "asyncio and concurrent.futures"
    - memory_management: "Generator usage, memory profiling"
    
  typescript:
    - build_optimization: "Advanced bundling strategies"
    - type_optimization: "Compile-time type checking efficiency"
    - runtime_safety: "Type guards and validation"
    
  go:
    - concurrency: "Goroutine and channel optimization"
    - memory_allocation: "Pool usage, escape analysis"
    - profiling: "pprof-based optimization"
    
  rust:
    - zero_cost_abstractions: "Compile-time optimization"
    - simd_usage: "Vector instruction utilization"
    - memory_layout: "Cache-friendly data structures"
```

## Deployment and Production Readiness

### Environment-Specific Configurations

Each language coder provides production-ready deployment:
- Docker containerization with multi-stage builds
- Environment-specific configuration management
- Health checks and graceful shutdown patterns
- Monitoring and observability integration
- Security best practices implementation

### CI/CD Integration

Templates include configuration for:
- Automated testing pipelines
- Code quality checks
- Security vulnerability scanning
- Performance regression testing
- Automated deployment processes

## Extension and Customization

### Adding New Language Templates

To add a new language coder template:

1. **Follow Naming Convention**: `code-{language}-coder.md`
2. **Implement Core Sections**:
   - Multi-agent coordination pattern
   - Language-specific quality gates
   - Framework detection and adaptation
   - Performance optimization strategies
   - Production deployment configuration

3. **Include Standard Features**:
   - Progressive complexity categorization
   - Tool integration patterns
   - Error handling best practices
   - Testing and validation frameworks
   - Documentation standards

### Template Customization

Templates support customization through:
- User-specific configuration overrides
- Project-specific pattern adaptations
- Framework-specific extensions
- Performance requirement adjustments
- Deployment target customizations

## Conclusion

The language-specific coder template system provides specialized, production-ready development capabilities for major programming languages. Through consistent multi-agent coordination, comprehensive quality assurance, and framework-aware patterns, these templates enable efficient development of robust, maintainable applications across diverse technology stacks.