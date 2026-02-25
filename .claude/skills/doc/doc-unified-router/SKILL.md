# Documentation Command Router

Routes legacy documentation commands to the unified documentation generator while preserving backward compatibility.

## Command Routing Map

All documentation commands now route through the unified generator with appropriate mode selection:

```yaml
command_routing:
  # README Generation
  "/doc-readme-create":
    agent: "doc-unified-generator"
    mode: "readme"
    preserves: "All badge generation, platform detection, and formatting"
    
  # Getting Started Guide
  "/doc-getting-started":
    agent: "doc-unified-generator"
    mode: "getting-started"
    preserves: "15-minute target, progressive complexity, platform instructions"
    
  # API Documentation
  "/doc-api-endpoints":
    agent: "doc-unified-generator"
    mode: "api"
    preserves: "OpenAPI specs, multi-language examples, authentication docs"
    
  # Architecture Documentation
  "/doc-architecture-adr":
    agent: "doc-unified-generator"
    mode: "architecture"
    preserves: "MADR format, multi-option evaluation, impact analysis"
    
  # Documentation Audit
  "/doc-audit-existing":
    agent: "doc-unified-generator"
    mode: "audit"
    preserves: "Quality analysis, rewritten content, improvement plans"
    
  # Module Documentation
  "/doc-from-code":
    agent: "doc-unified-generator"
    mode: "module"
    preserves: "Per-module generation, smart file separation, cross-references"
    
  # Book Generation (NEW)
  "/doc-book":
    agent: "doc-unified-generator"
    mode: "book"
    new: "Complete book structure with chapters and navigation"
```

## Implementation

### Command Detection and Routing

```javascript
function routeDocumentationCommand(command, args) {
  const routingMap = {
    'doc-readme-create': { mode: 'readme' },
    'doc-getting-started': { mode: 'getting-started' },
    'doc-api-endpoints': { mode: 'api' },
    'doc-architecture-adr': { mode: 'architecture' },
    'doc-audit-existing': { mode: 'audit' },
    'doc-from-code': { mode: 'module' },
    'doc-functions-explain': { mode: 'module', submode: 'functions' },
    'doc-book': { mode: 'book' }
  };
  
  const routing = routingMap[command];
  
  if (routing) {
    return spawnUnifiedGenerator({
      mode: routing.mode,
      submode: routing.submode,
      args: args,
      legacy: true  // Preserves exact output format
    });
  }
  
  return handleUnknownCommand(command);
}
```

### Unified Generator Invocation

```xml
<function_calls>
<invoke name="Task">
<parameter name="subagent_type">doc-unified-generator</parameter>
<parameter name="description">Generate {{mode}} documentation</parameter>
<parameter name="prompt">Generate documentation using unified generator.

Mode: {{mode}}
Legacy Command: {{original_command}}
Arguments: {{args}}
Preserve Format: {{preserve_format}}

Ensure backward compatibility:
- Maintain exact output structure of legacy agent
- Preserve all original functionality
- Use same file paths and naming conventions
- Apply legacy formatting rules if specified

{{#if mode_config}}
Mode Configuration:
{{mode_config}}
{{/if}}

Session: doc-unified-{{timestamp}}
Working Directory: {{pwd}}
</parameter>
</invoke>
</function_calls>
```

## Backward Compatibility Features

### Output Path Preservation

Legacy commands maintain their original output paths:

```yaml
legacy_paths:
  readme:
    legacy: "README.md" or "docs/README.md"
    unified: "docs/README.md"
    
  getting_started:
    legacy: "docs/getting-started/"
    unified: "docs/getting-started/"
    
  api:
    legacy: "docs/api/"
    unified: "docs/api/"
    
  architecture:
    legacy: "docs/architecture/decisions/"
    unified: "docs/architecture/decisions/"
```

### Format Preservation

Each mode preserves the exact formatting of the original agent:

```javascript
const formatPreservation = {
  readme: {
    badges: 'original-badge-format',
    sections: 'original-section-order',
    emoji: 'original-emoji-usage'
  },
  api: {
    openapi: 'openapi-3.0',
    examples: 'multi-language',
    structure: 'original-hierarchy'
  },
  // ... other modes
};
```

### Command Alias Support

Multiple command variations route to the same mode:

```yaml
aliases:
  readme:
    - doc-readme-create
    - doc-readme
    - readme-generate
    - generate-readme
    
  api:
    - doc-api-endpoints
    - doc-api
    - api-docs
    - generate-api-docs
```

## Migration Process

### Phase 1: Transparent Routing (Current)
- All commands route through unified generator
- Zero breaking changes
- Identical output to legacy agents

### Phase 2: Deprecation Notices (Future)
```bash
# Shows deprecation notice but still works
$ claude doc-readme-create
⚠️ Note: 'doc-readme-create' is deprecated. Use 'claude docs readme' instead.
Generating README documentation...
```

### Phase 3: Full Migration (Long-term)
```bash
# New unified command structure
$ claude docs readme        # README generation
$ claude docs api           # API documentation
$ claude docs book          # Book generation
```

## Testing Backward Compatibility

### Validation Checklist

For each legacy command, verify:

- [ ] Command executes without errors
- [ ] Output matches legacy format exactly
- [ ] File paths remain unchanged
- [ ] All features work as before
- [ ] No breaking changes in output

### Test Script

```bash
#!/bin/bash
# Test backward compatibility

# Test each legacy command
commands=(
  "doc-readme-create"
  "doc-getting-started"
  "doc-api-endpoints"
  "doc-architecture-adr"
  "doc-audit-existing"
  "doc-from-code"
)

for cmd in "${commands[@]}"; do
  echo "Testing: $cmd"
  
  # Run legacy command through router
  claude $cmd --test
  
  # Verify output
  if [ $? -eq 0 ]; then
    echo "✅ $cmd: PASS"
  else
    echo "❌ $cmd: FAIL"
    exit 1
  fi
done

echo "All backward compatibility tests passed!"
```

## Advanced Routing Features

### Conditional Mode Selection

```javascript
// Automatically detect best mode based on context
function intelligentModeSelection(context) {
  if (context.hasAPIEndpoints) {
    return 'api';
  }
  if (context.isNewProject) {
    return 'getting-started';
  }
  if (context.needsDocumentation) {
    return 'readme';
  }
  if (context.hasBook.yaml) {
    return 'book';
  }
  return 'readme'; // default
}
```

### Mode Chaining

```bash
# Generate multiple documentation types in sequence
claude docs readme && \
claude docs api && \
claude docs getting-started

# Or use unified book mode for all
claude docs book
```

### Custom Mode Configuration

```bash
# Override mode defaults while maintaining compatibility
claude doc-readme-create \
  --badges=custom \
  --sections=minimal \
  --format=github
```

## Error Handling

### Graceful Fallback

```javascript
try {
  // Try unified generator
  result = await unifiedGenerator(mode, args);
} catch (error) {
  if (legacyAgentExists(mode)) {
    // Fallback to legacy agent if available
    console.warn('Falling back to legacy agent');
    result = await legacyAgent(mode, args);
  } else {
    throw error;
  }
}
```

### Clear Error Messages

```bash
$ claude doc-unknown-command
❌ Error: Unknown documentation command 'doc-unknown-command'

Did you mean one of these?
  • doc-readme-create - Generate README documentation
  • doc-api-endpoints - Generate API documentation
  • doc-book - Generate book-style documentation

For help, run: claude docs --help
```

## Performance Optimization

### Mode-Specific Loading

Only load required functionality for each mode:

```javascript
const modeLoaders = {
  readme: () => import('./modes/readme'),
  api: () => import('./modes/api'),
  book: () => import('./modes/book'),
  // Lazy load only when needed
};

async function loadMode(mode) {
  const loader = modeLoaders[mode];
  return loader ? await loader() : null;
}
```

### Caching

```javascript
// Cache mode configurations
const modeCache = new Map();

function getCachedMode(mode) {
  if (!modeCache.has(mode)) {
    modeCache.set(mode, loadModeConfig(mode));
  }
  return modeCache.get(mode);
}
```

## Monitoring and Analytics

Track usage of legacy vs. new commands:

```javascript
const usage = {
  legacy: {
    'doc-readme-create': 0,
    'doc-api-endpoints': 0,
    // ...
  },
  unified: {
    'docs readme': 0,
    'docs api': 0,
    'docs book': 0,
    // ...
  }
};

// Help identify migration patterns
function trackUsage(command, type) {
  usage[type][command]++;
  
  // Periodic reporting
  if (shouldReport()) {
    reportUsageAnalytics(usage);
  }
}
```

## Support and Documentation

### Help System Integration

```bash
$ claude docs --help
Documentation Generation Commands:

MODERN COMMANDS (Recommended):
  claude docs readme         Generate README documentation
  claude docs api           Generate API documentation
  claude docs book          Generate book-style documentation
  
LEGACY COMMANDS (Backward Compatible):
  claude doc-readme-create   (Deprecated) Use 'docs readme'
  claude doc-api-endpoints   (Deprecated) Use 'docs api'
  
For detailed help on any command:
  claude docs [mode] --help
```

### Migration Guide

Users can check migration status:

```bash
$ claude docs --migration-status

Migration Status:
✅ All legacy commands supported
✅ New unified generator active
✅ Book mode available

Deprecated commands still working:
- doc-readme-create → docs readme
- doc-api-endpoints → docs api
- doc-getting-started → docs getting-started

New features available:
- Book generation: claude docs book
- Unified configuration: book.yaml
```