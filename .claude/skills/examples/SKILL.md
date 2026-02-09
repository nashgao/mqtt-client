# Command: examples
Comprehensive example code generation coordinator - helps select the right example tool

## Usage
```
/examples [subcommand] [options]
```

## Description
Main entry point for all example generation commands. Provides intelligent routing to specialized example generators based on your needs. Creates working, validated, production-ready code examples with progressive complexity.

### Documentation Structure Integration
Examples are organized following standardized documentation structures:
- **Production Projects**: Examples in `docs/user-guides/tutorials/` and `docs/api-reference/examples/`
- **Single Libraries**: Examples in `docs/examples/` with basic, advanced, and recipe sections
- **Aggregated Libraries**: Examples in `docs/[module]/examples/` for each sub-library

See documentation library components:
- `templates/resources/documentation-library/core/structure-manager.md`
- `templates/resources/documentation-library/generators/`
- `templates/resources/documentation-library/patterns/progressive-disclosure.md`

All examples are placed in the appropriate `docs/` folder structure based on project type.

## Available Sub-Commands

### 🔧 Core Generation
**`/examples-generate`** - Generate comprehensive, working code examples
- Creates fully functional, tested examples
- Includes error handling and validation
- Provides "why" explanations for implementation choices
- Time: 2-5 minutes per example

### 📈 Progressive Learning
**`/examples-progressive`** - Create skill-appropriate examples from beginner to advanced
- Smooth progression through complexity levels
- Each level builds on previous knowledge
- Self-assessment checkpoints included
- Time: 5-10 minutes for complete progression

### ✅ Validation & Testing
**`/examples-validate`** - Automatically test and validate code examples
- Syntax checking and static analysis
- Execution testing with output verification
- Performance and security validation
- Time: 1-2 minutes per example

### 🏢 Production Scenarios
**`/examples-real-world`** - Generate production-ready examples for real business problems
- Complete with monitoring, logging, security
- Docker and Kubernetes configurations
- CI/CD pipeline integration
- Time: 10-15 minutes per scenario

### 📚 Interactive Tutorials
**`/examples-tutorial`** - Create step-by-step tutorials with exercises
- Interactive code cells and checkpoints
- Progressive exercises with hints
- Self-assessment quizzes
- Time: 15-20 minutes per tutorial

## Quick Decision Guide

### "I need to..."

#### **"...see how something works"**
→ Use `/examples-generate`

#### **"...learn a new concept from scratch"**
→ Use `/examples-progressive`

#### **"...verify my examples work correctly"**
→ Use `/examples-validate`

#### **"...build something production-ready"**
→ Use `/examples-real-world`

#### **"...create a learning tutorial"**
→ Use `/examples-tutorial`

## Usage Examples

### Basic Example Generation
```bash
# Generate a simple example
/examples-generate function

# Generate with specific language
/examples-generate class --language python

# Generate with validation
/examples-generate api-endpoint --validate
```

### Progressive Learning Path
```bash
# Create beginner to advanced examples
/examples-progressive sorting-algorithms --all

# Just intermediate level
/examples-progressive database-queries --level intermediate
```

### Validation Workflow
```bash
# Validate existing examples
/examples-validate ./my-examples/

# Validate with performance metrics
/examples-validate example.py --metrics

# Full test suite
/examples-validate . --tests all
```

### Production Examples
```bash
# Web API with all features
/examples-real-world --scenario api

# Data processing pipeline
/examples-real-world --scenario data

# Complete with deployment
/examples-real-world --scenario web --deploy
```

### Interactive Tutorials
```bash
# Create Jupyter notebook tutorial
/examples-tutorial async-programming --format notebook

# Markdown tutorial with exercises
/examples-tutorial design-patterns --format markdown
```

## Example Generation Philosophy

All example commands follow these principles:

### 🎯 Core Principles
1. **Working Code First** - Every example must run successfully
2. **Progressive Complexity** - Start simple, build understanding
3. **Real-World Relevance** - Solve actual problems
4. **Self-Documenting** - Clear code with explanations
5. **Validated Quality** - Tested, secure, performant

### 📊 Quality Standards
- **Syntax**: Valid and follows language conventions
- **Execution**: Runs without errors
- **Output**: Produces expected results
- **Performance**: Meets timing requirements
- **Security**: No vulnerable patterns
- **Documentation**: Explains "why" not just "what"

### 🔄 Progressive Learning Path
```
Beginner → Intermediate → Advanced → Production
   ↓           ↓            ↓           ↓
Concepts   Patterns    Optimization  Deployment
```

## Command Selection Matrix

| Need | Command | Output | Complexity |
|------|---------|--------|------------|
| Quick example | `/examples-generate` | Working code with docs | Simple |
| Learn concept | `/examples-progressive` | Multi-level examples | Progressive |
| Test examples | `/examples-validate` | Validation report | N/A |
| Production code | `/examples-real-world` | Full implementation | Complex |
| Teach others | `/examples-tutorial` | Interactive tutorial | Educational |

## Global Options

These options work with all example commands:

- `--language [lang]` - Target programming language
- `--validate` - Run validation after generation
- `--metrics` - Include performance metrics
- `--format [type]` - Output format (code/markdown/notebook)
- `--output [path]` - Save location for generated examples
- `--verbose` - Detailed output during generation

## Integration with Documentation

Examples integrate seamlessly with documentation commands:

```bash
# Generate examples for documentation
/doc-from-code --include-examples

# Add examples to existing docs
/examples-generate | /doc-functions-explain

# Create tutorial documentation
/examples-tutorial | /doc-getting-started
```

## Best Practices Integration

All examples incorporate:

### Code Quality
- Clear variable naming
- Proper error handling
- Input validation
- Type hints/annotations
- Performance considerations

### Documentation
- Inline comments for complex logic
- Docstrings for functions/classes
- Usage examples
- Expected output
- Common pitfalls

### Testing
- Unit test examples
- Integration test patterns
- Performance benchmarks
- Security validation
- Edge case handling

## Example Organization by Project Type

### Production Projects
```
docs/
├── user-guides/
│   └── tutorials/           # Step-by-step tutorials with examples
│       ├── basic-workflow.md
│       ├── advanced-usage.md
│       └── integration-guide.md
├── api-reference/
│   └── examples/           # API usage examples
│       ├── authentication.md
│       ├── data-operations.md
│       └── error-handling.md
└── recipes/                # Complete solution examples
    ├── common-patterns.md
    └── best-practices.md
```

### Single Libraries
```
docs/
└── examples/
    ├── basic-usage.md      # Simple examples for getting started
    ├── advanced-patterns.md # Complex usage patterns
    └── recipes/            # Common use case solutions
        ├── authentication.md
        ├── error-handling.md
        └── performance.md
```

### Aggregated Libraries
```
docs/
├── [module-name]/
│   └── examples/           # Module-specific examples
│       ├── basic.md       # Basic module usage
│       ├── advanced.md    # Advanced patterns
│       └── integration.md # Integration with other modules
├── patterns/              # Cross-module examples
│   ├── async-patterns.md
│   └── pipeline-patterns.md
└── recipes/              # Multi-module solution examples
    ├── web-application.md
    └── data-processing.md
```

## Visual Progress Indicators

All commands provide clear progress feedback:

```
Generating Example: API Endpoint
[████████░░] 80% - Adding error handling
✅ Syntax valid
✅ Imports verified
✅ Execution tested
✅ Output correct
⏳ Security scan...
```

## Success Metrics

Using these example commands delivers:

- ✅ **100% working examples** - All code tested
- ✅ **Progressive learning** - Smooth skill development
- ✅ **Production readiness** - Real-world patterns
- ✅ **Quality assured** - Validated and secure
- ✅ **Educational value** - Learn while coding
- ✅ **Time efficiency** - Quick generation

## Getting Started

1. **Choose your goal** from the sub-commands above
2. **Run the appropriate command** with your requirements
3. **Review generated examples** and customize as needed
4. **Validate if needed** with `/examples-validate`
5. **Deploy or share** your working examples

## Common Workflows

### Learning New Technology
```bash
# 1. Start with basics
/examples-progressive new-framework --level beginner

# 2. Build understanding
/examples-generate practical-example

# 3. Create production version
/examples-real-world --scenario web

# 4. Validate everything
/examples-validate . --tests all
```

### Creating Documentation
```bash
# 1. Generate examples
/examples-generate feature-examples

# 2. Create tutorial
/examples-tutorial feature-guide

# 3. Validate correctness
/examples-validate examples/

# 4. Integrate with docs
/doc-from-code --include examples/
```

### Teaching Others
```bash
# 1. Design learning path
/examples-progressive concept --all

# 2. Create interactive tutorial
/examples-tutorial concept --format notebook

# 3. Add real-world context
/examples-real-world --scenario relevant

# 4. Package for distribution
/examples-validate --format markdown
```

## Need Help?

- Run `/examples` without arguments to see this guide
- Each sub-command has detailed help: `/examples-generate --help`
- Examples build on research-proven practices
- Community patterns from successful projects

---

*These commands create comprehensive, working examples following best practices from successful open source projects and production systems.*