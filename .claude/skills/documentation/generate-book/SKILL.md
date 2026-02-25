# Generate Documentation Book Command

Generate comprehensive, book-like documentation with natural chapter progression and cross-references using the unified documentation generator.

## Basic Usage

```bash
# Generate complete book using configuration file
claude docs book

# Generate with specific configuration
claude docs book --config my-book.yaml

# Generate specific chapter only
claude docs book --chapter 02-getting-started

# Update existing book (incremental generation)
claude docs book --update

# Preview without writing files
claude docs book --dry-run
```

## Configuration

The book generation is controlled by a `book.yaml` configuration file. By default, it looks for:
1. `book.yaml` in the current directory
2. `docs/book.yaml`
3. `.claude/book.yaml`

## Generation Process

### 1. Planning Phase
- Load and validate book configuration
- Analyze project structure
- Identify content dependencies
- Plan generation strategy

### 2. Chapter Generation
- Spawn parallel agents for each chapter
- Generate content according to mode
- Apply consistent formatting
- Create cross-references

### 3. Assembly Phase
- Integrate all chapters
- Generate table of contents
- Build navigation structure
- Create search index

### 4. Validation Phase
- Test all links and references
- Validate code examples
- Check content completeness
- Verify reading flow

## Chapter Modes

Each chapter can use different generation modes:

### Core Modes
- `readme` - Project overview with badges
- `getting-started` - 15-minute quick start
- `api` - OpenAPI documentation
- `architecture` - ADRs and design docs
- `changelog` - Release history
- `module` - Per-module documentation
- `custom` - Custom content from templates

### Mode Configuration
```yaml
chapters:
  - id: "01-introduction"
    mode: "readme"
    config:
      include_badges: true
      
  - id: "03-custom"
    mode: "custom"
    source: "templates/custom.md"
```

## Output Structure

### Default Book Structure
```
docs/
├── README.md                    # Book navigation hub
├── book.yaml                    # Configuration
├── 01-introduction/
│   └── README.md               # Chapter content
├── 02-getting-started/
│   ├── README.md               # Main content
│   ├── Installation.md         # Split topics
│   └── QuickStart.md
└── ...
```

### Navigation Elements
- **Table of Contents**: Auto-generated in main README
- **Chapter Links**: Previous/Next navigation
- **Breadcrumbs**: Hierarchical navigation
- **Cross-References**: Automatic linking

## Advanced Features

### Parallel Generation
```bash
# Generate multiple chapters in parallel
claude docs book --parallel

# Specify parallelism level
claude docs book --parallel=4
```

### Incremental Updates
```bash
# Only regenerate changed content
claude docs book --update

# Force regenerate specific chapters
claude docs book --update --chapters 01,03,05
```

### Custom Variables
```yaml
variables:
  product_name: "MyApp"
  version: "2.0.0"
  support_email: "help@myapp.com"
```

Use in templates: `{{product_name}}`, `{{version}}`, etc.

### Conditional Content
```yaml
audiences:
  - id: "developers"
  - id: "users"
```

In content:
```markdown
<!-- audience: developers -->
## API Implementation Details
...
<!-- /audience -->

<!-- audience: users -->
## Using the Application
...
<!-- /audience -->
```

## Quality Validation

### Automatic Checks
- ✅ Link validation
- ✅ Code example testing
- ✅ Content completeness
- ✅ Reading flow verification
- ✅ Cross-reference integrity

### Manual Review
```bash
# Generate validation report
claude docs book --validate

# Check specific quality metrics
claude docs book --check-links
claude docs book --test-examples
```

## Integration Options

### CI/CD Integration
```yaml
# In .github/workflows/docs.yml
steps:
  - name: Generate Documentation
    run: claude docs book
  
  - name: Deploy to GitHub Pages
    if: success()
    uses: peaceiris/actions-gh-pages@v3
```

### Git Integration
```bash
# Auto-commit after generation
claude docs book --commit

# Custom commit message
claude docs book --commit --message "Update documentation"
```

## Troubleshooting

### Common Issues

**Configuration not found:**
```bash
claude docs book --config ./path/to/book.yaml
```

**Chapter generation fails:**
```bash
# Generate single chapter for debugging
claude docs book --chapter 03 --verbose
```

**Links broken after generation:**
```bash
# Validate and fix links
claude docs book --fix-links
```

### Debug Mode
```bash
# Verbose output for debugging
claude docs book --verbose

# Show generation plan without executing
claude docs book --plan
```

## Migration from Legacy Agents

### Automatic Migration
Existing commands automatically route to unified generator:
- `claude docs readme` → Uses unified generator in readme mode
- `claude docs api` → Uses unified generator in api mode
- etc.

### Manual Migration
1. Create `book.yaml` with chapter definitions
2. Map existing content to chapters
3. Run `claude docs book --migrate`
4. Review and adjust generated structure

## Best Practices

### Chapter Organization
1. **Logical Flow**: Introduction → Basics → Advanced → Reference
2. **Progressive Complexity**: Simple concepts before complex
3. **Clear Transitions**: Each chapter builds on previous
4. **Consistent Structure**: Similar chapters follow same pattern

### Content Guidelines
- Keep chapters focused on single topic
- Use cross-references for related content
- Include examples in each chapter
- Provide next steps at chapter end

### File Size Management
- Target 250-350 lines per file
- Auto-split at 400 lines
- Use topic-based separation
- Maintain logical groupings

## Examples

### Basic Book Generation
```bash
# Simple book with defaults
claude docs book
```

### Custom Configuration
```bash
# Advanced book with options
claude docs book \
  --config custom-book.yaml \
  --parallel \
  --validate \
  --commit
```

### Chapter-Specific Generation
```bash
# Generate only API and Architecture chapters
claude docs book --chapters 05-api,06-architecture
```

### Continuous Documentation
```bash
# Watch mode for auto-regeneration
claude docs book --watch

# Incremental updates on file change
claude docs book --watch --incremental
```

## Performance Tips

### Optimization Strategies
1. **Use Parallel Generation**: `--parallel` flag
2. **Enable Incremental Mode**: `--incremental` flag  
3. **Cache Templates**: Reuse common content
4. **Optimize Images**: Compress before including
5. **Limit TOC Depth**: Set appropriate `toc_depth`

### Resource Management
- **Memory**: ~100MB per chapter typical
- **CPU**: Scales with parallelism level
- **Disk**: 2-5MB typical output size
- **Time**: 1-2 minutes for full book

## Support

For issues or questions:
- Check troubleshooting section above
- Run `claude docs book --help`
- View logs: `~/.claude/logs/doc-generation.log`
- Report issues: GitHub repository

## See Also

- [Unified Documentation Generator](../../agents/doc-unified-generator.md)
- [Book Configuration Schema](./book-config.yaml)
- [Documentation Templates](../../shared/doc-templates/)
- [Migration Guide](./migration-guide.md)