# Module Detection Patterns and Utilities

> Shared patterns for detecting and organizing module-based documentation structures

## 📦 Module-Based Documentation Pattern

### Core Concept: 1:1 MAPPING
**AUTOMATIC RULE: For EVERY module in src/, create a corresponding docs directory**

```
EVERY src/{Module}/ → MUST have docs/{module}/
```

### Pattern Recognition

#### Space-Utils Pattern (Reference Implementation)
The pattern creates a docs directory for EACH source module:

```
src/Algorithm/    →  docs/algorithm/    (automatic)
src/Annotation/   →  docs/annotation/   (automatic)
src/Cache/        →  docs/cache/        (automatic)
src/Database/     →  docs/database/     (automatic)
src/Entity/       →  docs/entity/       (automatic)
src/Lock/         →  docs/lock/         (automatic)
src/Pipeline/     →  docs/pipeline/     (automatic)
src/Process/      →  docs/process/      (automatic)
src/Promise/      →  docs/promise/      (automatic)
src/Structure/    →  docs/structure/    (automatic)
... (continues for ALL 60+ modules)
```

**Key Characteristics:**
- **EVERY** source module gets documentation (no exceptions)
- Source modules use PascalCase: `Algorithm`, `Cache`, `Database`
- Documentation uses lowercase: `algorithm`, `cache`, `database`
- Automatic creation - no manual selection needed

## 🔍 Module Detection Logic

### Source Directory Patterns
```yaml
module_detection:
  # Common source directory patterns
  source_patterns:
    - "src/{ModuleName}/"      # Most common: src/Algorithm/
    - "lib/{ModuleName}/"      # Library pattern: lib/Cache/
    - "modules/{ModuleName}/"  # Modular apps: modules/Auth/
    - "packages/{ModuleName}/" # Monorepo: packages/Core/
  
  # Module indicators (3+ means module-based project)
  module_indicators:
    - "Multiple PascalCase directories under src/"
    - "Each directory contains substantial code (>3 files)"
    - "Directories have independent functionality"
    - "Example directories follow module names"
    - "No traditional MVC structure (controllers/, models/, views/)"
```

### Naming Convention Transformations
```yaml
naming_transformations:
  # PascalCase to lowercase (space-utils pattern)
  pascal_to_lower:
    "Algorithm" → "algorithm"
    "DataProcessor" → "dataprocessor"
    "HTTPClient" → "httpclient"
  
  # PascalCase to kebab-case (alternative)
  pascal_to_kebab:
    "Algorithm" → "algorithm"
    "DataProcessor" → "data-processor"
    "HTTPClient" → "http-client"
  
  # Preserve special cases
  special_cases:
    "API" → "api"
    "URL" → "url"
    "JSON" → "json"
```

## 📂 Module-Based Path Configuration

### Smart Content-Based Documentation Structure
```yaml
intelligent_documentation:
  rule: "Create docs/{module}/ with smart file separation"
  
  # Process ALL modules with intelligent structure
  process:
    1. "Scan src/ directory for modules"
    2. "Analyze each module for major concepts/functions"
    3. "Transform module names: PascalCase → lowercase"
    4. "Create docs/{module}/ directory"
    5. "Generate files based on content analysis"
  
  # Dynamic file creation based on content
  file_creation_rules:
    always_create:
      - "README.md"              # Navigation hub + overview
    
    create_when_justified:
      - "[concept].md"           # When distinct functionality exists
      - "[feature].md"           # When content > 100 lines
      - "[pattern].md"           # When unique patterns present
    
    decision_criteria:
      separate_file_when:
        - "Content forms complete concept (>100 lines)"
        - "Different audience or use case"
        - "Distinct functionality or API"
        - "Complex configuration or setup"
        - "Significant troubleshooting needs"
```

### Concept Detection Logic
```yaml
concept_detection:
  # Analyze module for major concepts
  analysis_patterns:
    cache_module:
      concepts: ["ttl", "invalidation", "providers", "strategies"]
      likely_files: ["ttl-strategies.md", "invalidation.md", "providers.md"]
    
    database_module:
      concepts: ["connections", "transactions", "migrations", "queries"]
      likely_files: ["connections.md", "transactions.md", "migrations.md"]
    
    async_module:
      concepts: ["promises", "callbacks", "streams", "error-handling"]
      likely_files: ["promises.md", "streams.md", "error-handling.md"]
  
  # Content volume thresholds
  thresholds:
    minimum_for_separate_file: 100    # lines
    maximum_for_single_file: 300      # lines
    split_recommendation: 200         # lines
  
  # File naming conventions
  naming:
    use_concept_name: true            # e.g., "caching.md"
    use_kebab_case: true             # e.g., "error-handling.md"
    avoid_generic: true              # Not "misc.md" or "other.md"
```

### Cross-Module Documentation
```yaml
cross_module_paths:
  # Project-level documentation
  project_readme: "docs/README.md"        # Main documentation hub
  modules_index: "docs/modules-index.md"  # Module discovery/navigation
  
  # Cross-cutting concerns
  patterns: "docs/patterns/"              # Cross-module patterns
  integration: "docs/integration/"        # Inter-module integration
  architecture: "docs/architecture/"      # Overall architecture
```

## 🎯 Detection Algorithm

### Module Enumeration Script (LISTS ALL MODULES)
```bash
#!/bin/bash
# Enumerate ALL modules and create docs for each

enumerate_and_document_modules() {
  local src_dir="${1:-src}"
  
  echo "=== Module Enumeration and Documentation ==="
  echo "Scanning: $src_dir"
  echo ""
  
  # Step 1: Find ALL modules (any directory in src/)
  if [ -d "$src_dir" ]; then
    modules=$(find "$src_dir" -maxdepth 1 -type d ! -path "$src_dir" -exec basename {} \; | sort)
    module_count=$(echo "$modules" | wc -l)
    
    echo "Found $module_count modules to document:"
    echo ""
    
    # Step 2: List each module and its documentation path
    for module in $modules; do
      # Transform PascalCase to lowercase
      lowercase_name=$(echo "$module" | tr '[:upper:]' '[:lower:]')
      
      echo "  $module → docs/$lowercase_name/"
      
      # Create documentation structure
      mkdir -p "docs/$lowercase_name"
      
      # Create standard files for this module
      touch "docs/$lowercase_name/README.md"
      touch "docs/$lowercase_name/api.md"
      touch "docs/$lowercase_name/examples.md"
      touch "docs/$lowercase_name/usage.md"
      touch "docs/$lowercase_name/patterns.md"
      touch "docs/$lowercase_name/troubleshooting.md"
    done
    
    echo ""
    echo "✅ Created documentation directories for ALL $module_count modules"
    
    # Step 3: Create main index
    echo "Creating docs/README.md with module index..."
    {
      echo "# Module Documentation"
      echo ""
      echo "## Available Modules"
      echo ""
      for module in $modules; do
        lowercase_name=$(echo "$module" | tr '[:upper:]' '[:lower:]')
        echo "- [$module](/$lowercase_name/) - $module module documentation"
      done
    } > docs/README.md
    
    echo "✅ Module documentation structure complete"
  else
    echo "❌ Source directory $src_dir not found"
  fi
}

# Alternative: Just detect if module-based
detect_module_structure() {
  local src_dir="${1:-src}"
  
  if [ -d "$src_dir" ]; then
    # ANY subdirectories in src/ means module-based
    module_count=$(find "$src_dir" -maxdepth 1 -type d ! -path "$src_dir" | wc -l)
    
    if [ "$module_count" -ge 1 ]; then
      echo "module-based"
      echo "Modules to document: $module_count"
    else
      echo "thematic"
    fi
  else
    echo "thematic"
  fi
}
```

## 🔧 Usage in Commands and Agents

### Command Flag Support
```markdown
## Usage with --module flag
/docs --module              # Use module-based documentation structure
/doc-from-code --module     # Generate module-specific documentation
/doc --module Algorithm     # Document specific module

## Auto-detection
/docs --auto-detect         # Automatically detect and use appropriate structure
```

### Agent Integration
```javascript
// Module detection in agents
const detectModuleStructure = () => {
  // Check for src/ directory with PascalCase subdirectories
  const srcModules = glob.sync('src/[A-Z]*/');
  
  // Check for matching example directories
  const hasExamples = srcModules.some(module => {
    const moduleName = path.basename(module);
    const examplePath = `example/${moduleName.toLowerCase()}`;
    return fs.existsSync(examplePath);
  });
  
  return {
    isModuleBased: srcModules.length >= 3,
    modules: srcModules.map(m => path.basename(m)),
    hasExamples,
    documentationPattern: srcModules.length >= 3 ? 'module' : 'thematic'
  };
};
```

## 📊 Decision Matrix

### When to Use Module-Based Documentation
```yaml
use_module_based:
  strong_indicators:
    - "src/ contains 3+ PascalCase directories"
    - "Each module has example/[module]/ directory"
    - "Modules are independently functional"
    - "Project is a library/framework with distinct components"
  
  project_types:
    - "Multi-module libraries (like space-utils)"
    - "Component libraries"
    - "Plugin systems"
    - "Microservice collections"
    - "Monorepo packages"

use_thematic:
  strong_indicators:
    - "Traditional MVC structure"
    - "Single application"
    - "src/ has functional grouping (utils/, helpers/, services/)"
    - "No clear module boundaries"
  
  project_types:
    - "Web applications"
    - "APIs"
    - "CLI tools"
    - "Simple libraries"
```

## 🚀 Implementation Checklist

### For Commands
- [ ] Add `--module` flag parsing
- [ ] Implement module detection logic
- [ ] Update output paths based on flag/detection
- [ ] Generate module-specific documentation structure
- [ ] Create module index/navigation

### For Agents
- [ ] Check for module flag in parameters
- [ ] Detect source module structure
- [ ] Transform module names (PascalCase → lowercase)
- [ ] Output to `docs/{module}/` instead of `docs/{type}/`
- [ ] Generate cross-module navigation

## 📚 Examples

### Module-Based Project Structure
```
project/
├── src/
│   ├── Algorithm/       # Module: algorithm
│   ├── Cache/          # Module: cache
│   ├── Database/       # Module: database
│   └── Pipeline/       # Module: pipeline
├── example/
│   ├── algorithm/      # Examples for Algorithm module
│   ├── cache/          # Examples for Cache module
│   └── database/       # Examples for Database module
└── docs/
    ├── README.md       # Main documentation hub
    ├── algorithm/      # Algorithm module docs
    │   ├── README.md
    │   ├── api-reference.md
    │   └── examples.md
    ├── cache/          # Cache module docs
    │   ├── README.md
    │   └── api-reference.md
    └── database/       # Database module docs
        ├── README.md
        └── configuration.md
```

### Usage in Documentation Commands
```bash
# Detect and use module structure
claude /docs --module

# Document specific module
claude /doc --module Cache

# Force module structure even if not detected
claude /doc-from-code --module --force

# Auto-detect structure
claude /docs --auto-detect
```

## 🔄 Migration Path

### From Thematic to Module-Based
```yaml
migration_steps:
  1_analyze:
    - "Run module detection"
    - "Identify source modules"
    - "Map existing documentation"
  
  2_restructure:
    - "Create docs/{module}/ directories"
    - "Move relevant documentation to module directories"
    - "Update cross-references"
  
  3_generate:
    - "Generate module-specific documentation"
    - "Create module index"
    - "Update navigation"
```

## 📋 Quality Checks

### Module Documentation Validation
- [ ] Each source module has corresponding docs/ directory
- [ ] Module names follow consistent transformation (PascalCase → lowercase)
- [ ] Cross-module navigation exists
- [ ] No orphaned documentation
- [ ] Examples align with module structure