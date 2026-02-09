# Documentation Patterns and Best Practices

> Shared patterns and guidelines for creating effective technical documentation

## 📂 Centralized Documentation Path Configuration

### Standard Documentation Output Paths
**ALL documentation MUST be written to the `docs/` directory. NO exceptions.**

```yaml
# Documentation Path Standards - USE THESE PATHS IN ALL AGENTS/COMMANDS
documentation_paths:
  base_dir: "docs/"                     # All documentation under docs/
  
  # Primary documentation files
  main_readme: "docs/README.md"         # Main documentation hub (NOT project root)
  project_readme: "README.md"           # Brief project overview ONLY (if needed)
  
  # Documentation categories
  api:
    base: "docs/api/"
    endpoints: "docs/api/endpoints/"
    schemas: "docs/api/schemas/"
    examples: "docs/api/examples/"
  
  architecture:
    base: "docs/architecture/"
    decisions: "docs/architecture/decisions/"  # ADRs
    diagrams: "docs/architecture/diagrams/"
    patterns: "docs/architecture/patterns/"
  
  guides:
    getting_started: "docs/getting-started/"
    tutorials: "docs/tutorials/"
    how_to: "docs/how-to/"
    user_guide: "docs/user-guide/"
  
  reference:
    base: "docs/reference/"
    api: "docs/reference/api/"
    cli: "docs/reference/cli/"
    config: "docs/reference/config/"
  
  development:
    contributing: "docs/contributing/"
    changelog: "docs/changelog/"
    migration: "docs/migration/"
  
  examples:
    base: "docs/examples/"
    basic: "docs/examples/basic/"
    advanced: "docs/examples/advanced/"
    integration: "docs/examples/integration/"

# Module-Based Documentation Paths (Space-Utils Pattern)
# AUTOMATIC 1:1 MAPPING: Creates docs/{module}/ for EVERY src/{Module}/
module_based_paths:
  enabled: false                        # Set to true with --module flag
  
  # Core Rule: EVERY source module gets its own docs directory
  mapping_rule: "src/{Module}/ → docs/{module}/"
  
  # Examples of 1:1 mapping
  mapping_examples:
    "src/Algorithm/": "docs/algorithm/"
    "src/Annotation/": "docs/annotation/"
    "src/Cache/": "docs/cache/"
    "src/Database/": "docs/database/"
    "src/Entity/": "docs/entity/"
    "src/Lock/": "docs/lock/"
    "src/Pipeline/": "docs/pipeline/"
    "src/Process/": "docs/process/"
    "src/Promise/": "docs/promise/"
    "src/Structure/": "docs/structure/"
    # ... continues for ALL modules in src/
    
  # Module detection (see module-detection-patterns.md for details)
  detection:
    source_patterns:
      - "src/*/"                        # Detect ALL directories in src/
    auto_create_docs: true               # Automatically create docs/{module}/ for each
    
  # Module naming transformation
  naming:
    pattern: "PascalCase → lowercase"   # Algorithm → algorithm
    
  # Standard files created in EACH module's docs directory
  per_module_files:
    - "docs/{module}/README.md"         # Module overview
    - "docs/{module}/api.md"            # API reference
    - "docs/{module}/examples.md"       # Code examples
    - "docs/{module}/usage.md"          # How to use
    - "docs/{module}/patterns.md"       # Best practices
    - "docs/{module}/troubleshooting.md" # Common issues
  
  # Cross-module documentation (in addition to per-module docs)
  cross_module:
    index: "docs/README.md"             # Lists ALL modules
    modules_list: "docs/modules-index.md" # Searchable catalog
    patterns: "docs/patterns/"          # Cross-module patterns
    integration: "docs/integration/"    # Inter-module integration

# Topic-Based Documentation Structure (NEW STANDARD)
# MANDATORY FOR ALL DOCUMENTATION GENERATION
topic_based_structure:
  enabled: true                          # Always use topic-based organization
  pattern: "category-based-topics"       # Split by logical topics, not file types
  
  # File Size Management
  automatic_splitting:
    enabled: true
    threshold: 400                      # Split files > 400 lines
    target: 300                         # Optimal file size
    min_size: 150                       # Don't create tiny files
    
  # Category Detection and Mapping
  category_mapping:
    # Core concepts
    "Actor.*|Creation|Lifecycle|Supervision": "actors/"
    "Message|Communication|Tell|Ask": "actors/"
    
    # API documentation
    "API|Endpoint|Route|REST|GraphQL": "api/"
    "Request|Response|Schema": "api/"
    
    # Architecture & Design
    "Architecture|Design|Pattern|Structure": "architecture/"
    "Component|Module|System": "architecture/"
    
    # Distributed/Clustering
    "Cluster|Remote|Distributed|Node": "clustering/"
    "Shard|Partition|Replication": "clustering/"
    
    # State Management
    "Persist|State|Event|Store": "persistence/"
    "Snapshot|Journal|Recovery": "persistence/"
    
    # Examples & Tutorials
    "Example|Sample|Demo|Tutorial": "examples/"
    "QuickStart|GettingStarted": "examples/"
    
    # Troubleshooting
    "Error|Debug|Issue|Problem": "troubleshooting/"
    "Monitor|Log|Trace|Profile": "troubleshooting/"
    
    # Configuration
    "Config|Setting|Option|Parameter": "configuration/"
    "Environment|Variable": "configuration/"
    
  # File Organization Pattern
  file_naming:
    pattern: "docs/{category}/{Topic}.md"  # Topic in PascalCase
    index: "docs/{category}/README.md"     # Category overview
    main: "docs/README.md"                 # Main navigation hub
    
  # Content Splitting Strategy
  splitting_strategy:
    by_headers:
      level: 2                          # Split on ## headers
      group_related: true               # Keep related sections together
      preserve_hierarchy: true          # Maintain parent-child relationships
      
    by_content:
      similar_threshold: 0.75           # 75% similarity stays together
      topic_coherence: true             # Ensure logical boundaries
      
  # Navigation Generation
  navigation:
    auto_generate: true
    include_breadcrumbs: true
    cross_references: true
    category_indexes: true
    
  # Example Structure Output
  example_output: |
    docs/
    ├── README.md                      # Main navigation hub
    ├── actors/
    │   ├── README.md                  # Actors overview & index
    │   ├── Fundamentals.md            # Core concepts
    │   ├── Lifecycle.md               # Actor lifecycle
    │   ├── Creation.md                # Creating actors
    │   ├── Communication.md           # Message passing
    │   ├── StateManagement.md         # Managing state
    │   └── Hierarchies.md             # Supervision trees
    ├── api/
    │   ├── README.md                  # API overview
    │   ├── CoreAPI.md                 # Core API reference
    │   ├── ActorAPI.md                # Actor-specific APIs
    │   └── SystemAPI.md               # System management APIs
    └── ...
```

### Path Resolution Rules
1. **NEVER write documentation to project root** (except minimal README.md if absolutely required)
2. **ALWAYS use `docs/` prefix** for all documentation files
3. **CREATE subdirectories** as specified in the path configuration
4. **VALIDATE path exists** before writing (create if needed)

### Documentation File Naming Conventions
```yaml
naming_conventions:
  # Use lowercase with hyphens
  files: "feature-name.md"              # ✅ Good
  not: "FeatureName.md"                 # ❌ Bad
  
  # Be descriptive but concise
  good: "authentication-setup.md"       # ✅ Clear purpose
  bad: "auth.md"                       # ❌ Too vague
  
  # Index files for directories
  index: "README.md"                    # Directory overview
  
  # Versioned documentation
  versioned: "api-v2.md"                # Version in filename
  changelog: "CHANGELOG-2024.md"        # Year-based changelogs
```

### Agent/Command Output Path Requirements
**Every documentation agent and command MUST:**
1. Import/reference these standard paths
2. Use `docs/` as the base directory for ALL output
3. Create subdirectories following the structure above
4. Never write documentation files to project root
5. Validate and create paths before writing

### Path Usage Examples
```javascript
// ✅ CORRECT: Writing to docs directory
const apiDocPath = 'docs/api/endpoints/users.md';
const gettingStartedPath = 'docs/getting-started/README.md';
const changelogPath = 'docs/changelog/CHANGELOG-2024.md';

// ❌ INCORRECT: Writing to project root
const wrongPath1 = 'API.md';           // Should be docs/api/README.md
const wrongPath2 = 'GETTING-STARTED.md'; // Should be docs/getting-started/README.md
const wrongPath3 = './README.md';      // Should be docs/README.md for main docs
```

## 📋 Core Principles

### 1. Progressive Disclosure
Structure information in layers, from simple to complex:
- **Level 1** (30 seconds): Quick overview, installation, basic example
- **Level 2** (5 minutes): Common use cases, configuration
- **Level 3** (30 minutes): Complete reference, advanced features
- **Level 4** (Deep dive): Architecture, contributing, internals

### 2. Example-Driven Documentation
Every concept should have a working example:
```javascript
// ❌ Bad: Explanation without example
// The process() method transforms input data

// ✅ Good: Explanation with example
// The process() method transforms input data
const result = await processor.process({
  data: 'raw input',
  format: 'json'
});
// Result: { processed: true, output: {...} }
```

### 3. User-Centric Organization
Organize by user goals, not technical architecture:
- ❌ Bad: `/docs/classes/`, `/docs/functions/`, `/docs/interfaces/`
- ✅ Good: `/docs/getting-started/`, `/docs/guides/`, `/docs/api-reference/`

## 🎯 Documentation Types by Project

### Production Project Documentation
**Focus**: End users, operators, developers
```
Primary: User guides, deployment, operations
Secondary: API reference, development
Pattern: Task-oriented, role-based
```

### Single Library Documentation
**Focus**: Developers integrating the library
```
Primary: API reference, examples, integration
Secondary: Contributing, architecture
Pattern: API-centric, framework-specific
```

### Aggregated Library Documentation
**Focus**: Module discovery, cross-module patterns
```
Primary: Module catalog, patterns, recipes
Secondary: Individual module APIs
Pattern: Discovery-oriented, pattern-based
```

## 📝 Common Documentation Sections

### README.md Structure
```markdown
# Project Name
> One-line description

## Badges
[Version] [Build] [Coverage] [License]

## Features
- Key feature 1
- Key feature 2

## Installation
\`\`\`bash
install command
\`\`\`

## Quick Start
\`\`\`language
minimal working example
\`\`\`

## Documentation
Links to full docs

## Contributing
How to contribute

## License
License type
```

### API Reference Structure
```markdown
# API Reference

## Class/Module Name

### Overview
Brief description

### Constructor/Initialization
Parameters and options

### Methods/Functions

#### methodName(parameters)

**Purpose**: What it does

**Parameters**:
- \`param1\` (Type): Description
- \`param2\` (Type, optional): Description

**Returns**:
- Type: Description

**Throws**:
- ErrorType: When this happens

**Example**:
\`\`\`language
example code
\`\`\`

**See Also**:
- [Related Method](#related)
- [Pattern Guide](../patterns/pattern.md)
```

## 🔧 Writing Guidelines

### Clear and Concise
- Use active voice: "The function returns..." not "A value is returned..."
- Be specific: "Returns user ID (integer)" not "Returns a value"
- Avoid jargon: Use simple language where possible

### Code Examples
```javascript
// ✅ Good Example Characteristics:
// - Imports shown
// - Variables have meaningful names
// - Error handling included
// - Output shown in comments

const Database = require('database-module');

async function getUserData(userId) {
  const db = new Database({ timeout: 5000 });
  
  try {
    const user = await db.query('SELECT * FROM users WHERE id = ?', [userId]);
    console.log('User found:', user.name);
    // Output: User found: John Doe
    return user;
  } catch (error) {
    console.error('Database error:', error.message);
    throw error;
  }
}
```

### Tables for Comparison
```markdown
| Approach | Pros | Cons | Use When |
|----------|------|------|----------|
| Approach A | Fast, Simple | Limited features | Small datasets |
| Approach B | Feature-rich | Complex setup | Enterprise needs |
| Approach C | Balanced | Moderate learning | Most cases |
```

## 🎨 Formatting Standards

### Headings Hierarchy
```markdown
# Page Title (H1 - One per page)
## Main Sections (H2)
### Subsections (H3)
#### Details (H4)
##### Rarely used (H5)
```

### Code Block Languages
Always specify the language for syntax highlighting:
- `javascript` or `js` for JavaScript
- `typescript` or `ts` for TypeScript
- `python` or `py` for Python
- `bash` or `shell` for shell commands
- `json` for JSON data
- `yaml` or `yml` for YAML
- `markdown` or `md` for Markdown

### Linking Patterns
```markdown
<!-- Internal links (relative) -->
[Link Text](../other-file.md)
[Link to Section](#section-heading)

<!-- External links (absolute) -->
[External Resource](https://example.com)

<!-- Reference-style links for repeated URLs -->
[Link Text][ref1]
[Another Link][ref1]

[ref1]: https://repeated-url.com
```

## 📊 Documentation Metrics

### Coverage Metrics
- **API Coverage**: Percentage of public API documented
- **Example Coverage**: Percentage of methods with examples
- **Link Validity**: Percentage of working internal links
- **Freshness**: Days since last update

### Quality Metrics
- **Readability Score**: Flesch Reading Ease > 60
- **Example Runnability**: All examples execute without errors
- **Completeness**: No "TODO" or "TBD" sections
- **Consistency**: Uniform formatting and structure

## 🚀 Automation Tools

### Documentation Generation
```bash
# Generate API docs from code comments
npm run docs:generate

# Validate all internal links
npm run docs:check-links

# Check for outdated examples
npm run docs:test-examples

# Generate table of contents
npm run docs:toc
```

### Documentation Templates
```javascript
/**
 * Document generator template
 * @param {string} name - Parameter description
 * @param {Object} options - Configuration options
 * @param {boolean} options.flag - Flag description
 * @returns {Promise<Result>} Description of return value
 * @throws {Error} When something goes wrong
 * @example
 * const result = await functionName('value', { flag: true });
 * console.log(result); // { success: true }
 */
```

## 📚 Cross-Referencing Strategy

### Bidirectional Links
Every page should link to and from related content:
```markdown
## See Also
- [Related Topic 1](../related1.md) - When you need X
- [Related Topic 2](../related2.md) - For Y scenarios
- [Parent Topic](../index.md) - Back to overview

## Referenced By
- [Page A](../page-a.md) - Uses this for X
- [Page B](../page-b.md) - Extends this concept
```

### Context-Aware Suggestions
```markdown
> **Working with databases?** You might also need:
> - [Connection Pooling](../pooling.md)
> - [Transaction Management](../transactions.md)
> - [Query Optimization](../optimization.md)
```

## 🔍 Searchability Optimization

### SEO-Friendly Headers
```markdown
<!-- ❌ Bad: Vague headers -->
## Introduction
## Method
## Notes

<!-- ✅ Good: Descriptive headers -->
## Getting Started with Authentication
## How to Configure OAuth 2.0
## Common Authentication Errors and Solutions
```

### Keywords and Synonyms
Include common search terms and synonyms:
```markdown
# Database Connection (DB, Data Source, Connection Pool)

This guide covers database connectivity, connection pooling,
data source configuration, and DB connection management.
```

## 🛠️ Maintenance Patterns

### Version-Specific Documentation
```markdown
# Feature Name

> Available since: v2.0.0
> Deprecated: v3.0.0 (use [NewFeature](./new-feature.md) instead)
> Removed: v4.0.0

## Version Compatibility

| Version | Status | Notes |
|---------|--------|-------|
| 1.x | ❌ Not supported | - |
| 2.x | ⚠️ Deprecated | Security updates only |
| 3.x | ✅ Stable | Recommended |
| 4.x | 🚀 Beta | Testing phase |
```

### Update Tracking
```markdown
---
last-updated: 2024-01-15
authors: [contributor1, contributor2]
reviews: [reviewer1, reviewer2]
---

# Document Title

> ⚠️ **Note**: This document was last verified with version 3.2.0
```

## 📋 Quality Checklist

### Before Publishing
- [ ] All code examples tested and working
- [ ] Internal links validated
- [ ] Spell check completed
- [ ] Technical review by subject matter expert
- [ ] Editorial review for clarity
- [ ] Version compatibility noted
- [ ] Related topics linked
- [ ] Table of contents updated

### Regular Maintenance
- [ ] Monthly: Check for broken links
- [ ] Quarterly: Review for accuracy
- [ ] Major release: Update examples
- [ ] Annually: Full content audit

## 🎯 Common Pitfalls to Avoid

### Documentation Anti-Patterns
1. **Explaining the obvious**: Don't document self-evident code
2. **Missing context**: Always explain "why" not just "what"
3. **Outdated examples**: Keep examples current with API
4. **Walls of text**: Break up content with headers, lists, examples
5. **Assuming knowledge**: Define terms and provide context
6. **No navigation**: Always provide ways to related content
7. **Missing errors**: Document failure cases, not just success

## 📚 Resources

### Style Guides
- [Google Developer Documentation Style Guide](https://developers.google.com/style)
- [Microsoft Writing Style Guide](https://docs.microsoft.com/style-guide)
- [Write the Docs](https://www.writethedocs.org/)

### Tools
- [Markdown Guide](https://www.markdownguide.org/)
- [Mermaid Diagrams](https://mermaid-js.github.io/)
- [JSDoc](https://jsdoc.app/)
- [TypeDoc](https://typedoc.org/)