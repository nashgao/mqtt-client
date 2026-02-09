# Command: doc-from-code
Generate comprehensive documentation from existing codebase using proven multi-step approach

## 🚨 CRITICAL OUTPUT PATH CONFIGURATION

**ALL generated documentation MUST be written to the `docs/` directory structure.**

### Standard Documentation Output Structure
- **Main Documentation Hub**: `docs/README.md` - Project overview and navigation
- **Getting Started**: `docs/getting-started/` - Installation and quick start guides
- **API Reference**: `docs/api/` - Complete API documentation
- **Architecture**: `docs/architecture/` - System design and decisions
- **Examples**: `docs/examples/` - Code samples and use cases
- **Contributing**: `docs/contributing/` - Developer guides

Refer to `templates/shared/documentation-patterns.md` for complete path specifications.

## Usage
```
/doc-from-code [path] [options]
```

## Description
Analyzes your entire codebase and generates complete documentation following the research-proven 3-phase approach that delivers 40-60% better quality than single-step prompts.

## Implementation

### Phase 1: Codebase Analysis
```xml
<instructions>
Analyze the provided codebase to identify:
1. Main components and modules
2. Core functions and data structures
3. API endpoints and interfaces
4. Dependencies and external integrations
5. Project architecture patterns

FOR MODULE-BASED PROJECTS (--module flag):
- Enumerate ALL directories in src/
- Create list of EVERY module found
- Plan docs/{module}/ for EACH one
- No manual selection - document ALL
</instructions>

<context>
Project type: {{auto_detect}}
Documentation audience: {{audience_level|intermediate developers}}
Analysis depth: comprehensive
</context>

<output_format>
- Project structure overview
- Component dependency map
- Key functionality summary
- Documentation gap analysis
</output_format>
```

### Phase 2: Documentation Structure
Apply the Diátaxis framework to organize content:

1. **Tutorials** - Learning-oriented guides
2. **How-to Guides** - Task-oriented instructions  
3. **Technical Reference** - Information-oriented specs
4. **Explanations** - Understanding-oriented concepts

### Phase 3: Documentation Generation

#### For Thematic Structure:
```xml
<instructions>
Generate detailed documentation for: {{section_name}}
</instructions>

<context>
Section type: {{section_type}}
Target audience: {{audience_level}}
Code context: {{relevant_code}}
Style: Clear, concise with practical examples
</context>
```

#### For Module-Based Structure:
```xml
<instructions>
FOR EACH MODULE IN src/:
1. Process module: {{module_name}}
2. Create docs/{{module_lowercase}}/
3. Generate ALL standard files:
   - README.md (overview)
   - api.md (complete API)
   - examples.md (usage examples)
   - usage.md (how-to guide)
   - patterns.md (best practices)
   - troubleshooting.md (common issues)
</instructions>

<automation>
Repeat for ALL modules found - no exceptions
Every src/Module/ gets docs/module/
</automation>

<requirements>
- Include working code examples
- Explain design decisions
- Document error handling
- Add usage examples
- Cross-reference related sections
</requirements>

<output_format>
Markdown with:
- Clear headings and structure
- Syntax-highlighted code blocks
- Practical examples
- Links to related documentation
</output_format>
```

## Iterative Refinement Process
The command automatically performs 3 refinement iterations:

**Iteration 1: Initial Generation**
- Generate comprehensive documentation covering all public APIs
- Focus on completeness and technical accuracy

**Iteration 2: Technical Review**
- Review for technical accuracy and add missing edge cases
- Ensure all error conditions are documented
- Validate code examples work correctly

**Iteration 3: Polish & Clarity**
- Simplify language for target audience
- Add more practical examples
- Ensure consistent voice and terminology

## Output Structure

### Thematic Structure (Default)
**All files created under `docs/` directory following centralized documentation path standards:**

```
docs/
├── README.md                    # Main documentation hub (NOT project root)
├── getting-started/             # Onboarding tutorials
│   ├── README.md                 # Getting started overview
│   ├── installation.md           # Installation guide
│   ├── quick-start.md            # 5-minute tutorial
│   └── troubleshooting.md        # Setup issues
├── api/                         # API reference
│   ├── README.md                 # API overview
│   ├── endpoints/                # REST API endpoints
│   ├── schemas/                  # Data models
│   └── examples/                 # API usage examples
├── guides/                      # How-to guides
│   ├── README.md                 # Guides overview
│   ├── user-guide.md             # User documentation
│   └── advanced-usage.md         # Advanced topics
├── architecture/                # System architecture
│   ├── README.md                 # Architecture overview
│   ├── decisions/                # ADRs
│   ├── diagrams/                 # Architecture diagrams
│   └── patterns/                 # Design patterns
├── examples/                    # Code samples
│   ├── README.md                 # Examples index
│   ├── basic/                    # Basic examples
│   └── advanced/                 # Advanced examples
└── contributing/                # Developer docs
    ├── README.md                 # Contributing guide
    ├── development.md            # Dev environment setup
    └── testing.md                # Testing procedures
```

### Module-Based Structure (with --module flag)
**AUTOMATIC 1:1 MAPPING - Creates docs directory for EVERY source module:**

**The Rule: For EVERY `src/[Module]/` → Create `docs/[module]/`**

Example with space-utils modules:
```
src/                             docs/
├── Algorithm/        →          ├── algorithm/
├── Annotation/       →          ├── annotation/
├── Cache/           →          ├── cache/
├── Database/        →          ├── database/
├── Entity/          →          ├── entity/
├── Lock/            →          ├── lock/
├── Pipeline/        →          ├── pipeline/
├── Process/         →          ├── process/
├── Promise/         →          ├── promise/
├── Structure/       →          ├── structure/
└── [60+ more...]    →          └── [60+ more docs dirs...]
```

**Each module's docs directory automatically contains:**
```
docs/{module}/
├── README.md                    # Module overview & quick start
├── api.md                       # Complete API reference
├── examples.md                  # Working code examples
├── usage.md                     # Common use cases
├── patterns.md                  # Best practices
└── troubleshooting.md          # Common issues & solutions
```

**Plus overall documentation:**
```
docs/
├── README.md                    # Main hub listing ALL modules
├── modules-index.md             # Searchable catalog of all modules
└── patterns/                    # Cross-module patterns
    └── integration.md           # How modules work together
```

**Automatic Detection**: When src/ contains modules (PascalCase directories):
- Detects ALL modules in src/ directory
- Creates docs/{module}/ for EACH one
- Transforms name: PascalCase → lowercase (Algorithm → algorithm)

## Options
- `--audience [level]` - Set documentation audience (beginner/intermediate/expert)
- `--style [type]` - Documentation style (concise/detailed/tutorial)
- `--framework` - Auto-detect and apply framework-specific patterns
- `--include-examples` - Generate extensive code examples
- `--languages` - Include examples in multiple programming languages
- `--module` - Use module-based documentation structure (docs/{module}/)
- `--auto-detect` - Automatically detect module vs thematic structure

## Integration
Automatically integrates with:
- Version control (Git) for documentation versioning
- CI/CD pipelines for continuous updates
- Static site generators (Docusaurus, VitePress, Jekyll)

## Quality Metrics
- **Completeness**: All public APIs documented
- **Clarity**: Fog index < 12 (readable by high school students)
- **Examples**: Minimum 1 example per major function
- **Consistency**: Uniform terminology and style
- **Accuracy**: Technical validation passes

## Example Usage

### Basic Usage
```bash
# Analyze src/ and generate docs in docs/ directory
/doc-from-code ./src

# Output: Complete docs/ structure with all documentation
```

### With Options
```bash
# Generate comprehensive beginner-friendly documentation
/doc-from-code ./my-project --audience beginner --style tutorial --include-examples

# Output: docs/ with tutorial-style guides and extensive examples
```

### For Specific Framework
```bash
# Generate framework-specific documentation
/doc-from-code ./react-app --framework react --languages "javascript,typescript"

# Output: docs/ with React-specific patterns and TypeScript examples
```

### Custom Documentation Path
```bash
# Generate documentation in custom location (still under docs/)
/doc-from-code ./api --output docs/api-v2/

# Output: docs/api-v2/ with API-specific documentation structure
```

## Success Indicators
✅ All public APIs have documentation
✅ Getting started guide completes in <15 minutes
✅ Examples are executable and tested
✅ Documentation stays synchronized with code
✅ Reduced support tickets by 30-50%