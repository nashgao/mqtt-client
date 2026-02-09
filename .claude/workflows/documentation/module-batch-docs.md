# Module Batch Documentation Workflow

> Automated workflow for generating documentation for ALL source modules with intelligent content-based file separation

## 🎯 Core Principle: Smart Documentation for EVERY Module

**The Rule**: For EVERY directory in `src/`, create a corresponding `docs/{module}/` directory with intelligently organized documentation based on content analysis.

## 📦 Workflow Overview

```mermaid
graph TD
    A[Start: Detect --module flag] --> B[Enumerate ALL src/ modules]
    B --> C[Analyze each module's content]
    C --> D[Plan smart file separation]
    D --> E[Transform names: PascalCase → lowercase]
    E --> F[Spawn doc-module-generator for EACH]
    F --> G[Parallel intelligent generation]
    G --> H[Create cross-module index]
    H --> I[Verify all modules documented]
    I --> J[Complete: Smart docs/{module}/ created]
```

## 🔧 Implementation Steps

### Step 1: Module Enumeration and Analysis
```bash
# Find ALL modules in src/ and analyze their content
modules=$(find src -maxdepth 1 -type d ! -path src -exec basename {} \; | sort)

echo "Modules to document: $modules"
# Output: Algorithm Annotation Cache Database Entity Lock Pipeline Process Promise Structure ...

# Analyze each module for content volume and concepts
for module in $modules; do
  echo "Analyzing $module for major concepts..."
  # Count lines, identify major functions/classes, detect patterns
done
```

### Step 2: Intelligent Batch Agent Spawning
```javascript
// Spawn one agent per module with smart file planning
const modules = getAllSourceModules('src');

modules.forEach(module => {
  const lowercaseName = module.toLowerCase();
  const concepts = analyzeModuleConcepts(`src/${module}/`);
  
  spawnAgent({
    type: 'doc-module-generator',
    module: module,
    source: `src/${module}/`,
    output: `docs/${lowercaseName}/`,
    strategy: 'intelligent',
    concepts: concepts,  // Detected major concepts/functions
    threshold: 100,       // Lines threshold for separation
    files: generateFileStructure(concepts)  // Dynamic file list
  });
});
```

### Step 3: Dynamic Documentation Structure
Each module gets intelligently organized documentation:
```yaml
# Complex Module (e.g., Cache with multiple concepts)
docs/cache/:
  README.md:           # ALWAYS: Navigation hub + overview
  ttl-strategies.md:   # Major concept (150+ lines)
  invalidation.md:     # Major concept (200+ lines)  
  providers.md:        # Storage backends (180+ lines)

# Medium Module (e.g., Logger with some concepts)
docs/logger/:
  README.md:           # Overview + basic usage
  formatters.md:       # Log formatters (120+ lines)
  transports.md:       # Output transports (140+ lines)

# Simple Module (e.g., Validation)
docs/validation/:
  README.md:           # All content in one file (<300 lines)
```

### Step 4: Cross-Module Documentation
After all modules are documented:
```markdown
# docs/README.md - Module Index
## Available Modules

- [Algorithm](./algorithm/) - Algorithm utilities and helpers
- [Annotation](./annotation/) - Annotation processing
- [Cache](./cache/) - Caching mechanisms
- [Database](./database/) - Database operations
- [Entity](./entity/) - Entity management
- [Lock](./lock/) - Locking mechanisms
- [Pipeline](./pipeline/) - Pipeline processing
- [Process](./process/) - Process management
- [Promise](./promise/) - Promise utilities
- [Structure](./structure/) - Data structures
... [ALL modules listed]
```

## 🚀 Parallel Processing Strategy

### Agent Coordination
```python
# Maximum parallel agents
MAX_PARALLEL = 10

# Process modules in batches
def process_modules_batch(modules):
    batches = chunk(modules, MAX_PARALLEL)
    
    for batch in batches:
        agents = []
        for module in batch:
            agent = spawn_doc_agent(module)
            agents.append(agent)
        
        # Wait for batch to complete
        wait_all(agents)
    
    # Create index after all complete
    create_module_index(modules)
```

## 🧠 Concept Detection Algorithm

### Automatic Concept Identification
```python
def analyze_module_concepts(module_path):
    concepts = []
    
    # Analyze source code for major functions/classes
    for file in module_files:
        # Detect major classes
        classes = extract_classes(file)
        for cls in classes:
            if cls.line_count > 50:  # Significant class
                concepts.append({
                    'name': cls.name,
                    'type': 'class',
                    'lines': cls.line_count,
                    'file': separate_file_if(cls.line_count > 100)
                })
        
        # Detect major functions/patterns
        patterns = extract_patterns(file)
        for pattern in patterns:
            if pattern.is_major_concept():
                concepts.append({
                    'name': pattern.name,
                    'type': 'pattern',
                    'lines': pattern.doc_lines_needed(),
                    'file': separate_file_if(pattern.complexity > 'medium')
                })
    
    return optimize_file_structure(concepts)
```

## ✅ Validation Checklist

### Per-Module Validation
For EACH module, verify:
- [ ] `docs/{module}/` directory exists
- [ ] README.md navigation hub created
- [ ] Major concepts have dedicated files (if >100 lines)
- [ ] All content logically organized
- [ ] Examples tested and working
- [ ] Cross-references valid

### Smart Separation Validation
- [ ] No unnecessary file proliferation
- [ ] Clear conceptual boundaries
- [ ] Files don't exceed 500 lines
- [ ] Related content stays together
- [ ] Navigation is intuitive

### Overall Validation
- [ ] ALL src/ modules have docs/ directories
- [ ] No modules skipped or missed
- [ ] Main index lists all modules
- [ ] Module count matches: `ls src/ | wc -l` == `ls docs/ | wc -l`

## 📊 Example: Space-Utils Smart Processing

```bash
# Input: 63 modules in src/
src/Algorithm/      # Complex: sorting, searching, graph algorithms
src/Cache/         # Complex: TTL, invalidation, providers
src/Validation/    # Simple: basic validation functions
src/Database/      # Complex: connections, transactions, migrations
... (59 more)

# Output: 63 documentation directories with smart structure
docs/algorithm/
├── README.md           # Navigation hub
├── sorting.md         # Major concept (180 lines)
├── searching.md       # Major concept (150 lines)
└── graph.md           # Major concept (220 lines)

docs/cache/
├── README.md           # Navigation hub
├── ttl-strategies.md   # Major concept
├── invalidation.md     # Major concept
└── providers.md        # Storage backends

docs/validation/
└── README.md           # All content (250 lines total)

docs/database/
├── README.md           # Navigation hub
├── connections.md      # Connection pooling
├── transactions.md     # Transaction management
└── migrations.md       # Schema migrations

# Statistics
✅ 63 source modules → 63 docs directories
✅ ~180 total files (varies based on content analysis)
✅ Average 3 files per complex module, 1 for simple modules
✅ Smart separation based on concepts, not fixed count
```

## 🔄 Incremental Updates

### Adding New Modules
When new modules are added to src/:
1. Detect new modules not in docs/
2. Generate documentation for new modules only
3. Update main index to include new modules

### Updating Existing Modules
When module code changes:
1. Detect which modules changed
2. Regenerate only changed module docs
3. Preserve custom documentation sections

## 📋 Command Integration

### Usage in /docs Command
```bash
# Automatic module documentation
claude /docs --module

# Process specific source directory
claude /docs --module --source lib/

# Force regeneration
claude /docs --module --force
```

### Usage in /doc-from-code Command
```bash
# Generate module-based documentation from code
claude /doc-from-code --module

# With custom source directory
claude /doc-from-code --module --source packages/
```

## 🚨 Important Rules

1. **NO FILTERING**: Document EVERY module found
2. **NO SELECTION**: All modules processed automatically
3. **NO EXCEPTIONS**: Every src/Module/ gets docs/module/
4. **CONSISTENT NAMING**: Always PascalCase → lowercase
5. **COMPLETE DOCS**: All 6 files for every module

## 🎯 Success Metrics

Documentation is complete when:
- ✅ Module count: `src/` count == `docs/` count
- ✅ File count: modules × 6 files each
- ✅ All API functions documented
- ✅ All examples working
- ✅ Main index complete
- ✅ No broken links