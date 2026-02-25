---
name: summarize
description: Track and summarize problems solved by Claude Code with full context for knowledge sharing
version: 1.0.0
author: Claude
tags: [knowledge, documentation, history, problem-solving]
---

# Summarize Command - Claude Code Problem-Solving Tracker

Track, document, and share problems solved by Claude Code across sessions with comprehensive context.

## Core Functionality

This command creates a structured knowledge base of problems Claude Code has solved, enabling:
- Future Claude sessions to understand past solutions
- Team knowledge sharing and collaboration
- Problem-solving pattern recognition
- Solution reusability and adaptation

## Command Structure

```bash
# Add a new problem/solution entry
claude summarize add [--title "Problem Title"] [--auto-detect]

# List all solved problems
claude summarize list [--category <category>] [--recent <n>]

# View specific problem details
claude summarize show <problem-id>

# Export documentation
claude summarize export [--format md|html] [--output <path>]
# Exports to changelog/exports/ by default

# Sync from git history and sessions
claude summarize sync [--since <date>] [--branch <branch>]

# Search problems
claude summarize search <query>

# Generate summary report
claude summarize report [--period <week|month|all>]
```

## Storage Structure

Problems are stored in `changelog/` with this organization:

```
changelog/
├── index.yaml           # Master index of all problems
├── 2025-01/            # Organized by date
│   ├── problem-001.yaml
│   └── session-summary.md
├── by-category/        # Symbolic links by category (optional)
│   ├── bug-fixes/
│   ├── features/
│   ├── refactoring/
│   └── performance/
└── latest.md           # Symlink to most recent summary
```

## Problem Entry Schema

Each problem is stored as a YAML file with comprehensive context:

```yaml
id: "2025-01-18-001"
title: "Implement parallel test execution"
category: "performance"
severity: "medium"
created_at: "2025-01-18T10:30:00Z"
updated_at: "2025-01-18T11:45:00Z"

problem:
  description: |
    Test suite taking too long to run, blocking development workflow.
    Sequential execution causing 15-minute wait times.
  
  context:
    files_affected:
      - src/test-runner.js
      - tests/config.js
    
    error_messages:
      - "Timeout: Test execution exceeded 900 seconds"
    
    user_request: |
      "The tests are taking forever to run. Can you make them faster?"
    
    constraints:
      - "Must maintain test isolation"
      - "Cannot modify test logic"
      - "Must work with existing CI/CD"

solution:
  approach: |
    Implemented parallel test execution using worker threads.
    Grouped tests by module for optimal parallelization.
  
  implementation:
    changes:
      - file: "src/test-runner.js"
        description: "Added worker pool management"
        lines_modified: 145
      
      - file: "tests/config.js"
        description: "Configured parallelization settings"
        lines_modified: 23
    
    code_snippets:
      - language: "javascript"
        description: "Worker pool implementation"
        code: |
          const { Worker } = require('worker_threads');
          const pool = new WorkerPool({ size: os.cpus().length });
    
    patterns_used:
      - "Worker thread pool pattern"
      - "Test sharding strategy"
      - "Promise-based coordination"
  
  results:
    performance_improvement: "75% reduction in test execution time"
    metrics:
      before: "15 minutes"
      after: "3.5 minutes"
    
    validation:
      - "All tests passing"
      - "CI/CD integration successful"
      - "No flaky tests introduced"

git_info:
  commits:
    - hash: "abc123def"
      message: "feat: implement parallel test execution"
      timestamp: "2025-01-18T11:30:00Z"
      files_changed: 2
      insertions: 168
      deletions: 12
    
    - hash: "def456ghi"
      message: "fix: resolve race condition in parallel tests"
      timestamp: "2025-01-18T11:45:00Z"
      files_changed: 1
      insertions: 8
      deletions: 2
  
  branch: "feature/parallel-tests"
  pr_number: 142
  pr_url: "https://github.com/user/repo/pull/142"

session_info:
  session_id: "session-2025-01-18-093000"
  duration_minutes: 75
  commands_used:
    - "/test diagnose"
    - "/test optimize"
    - "/git commit"
  
  agents_spawned:
    - type: "test-fixer"
      purpose: "Analyze test bottlenecks"
    - type: "general-purpose"
      purpose: "Research parallelization patterns"

tags: ["performance", "testing", "parallelization", "worker-threads"]

lessons_learned:
  - "Worker threads provide excellent test isolation"
  - "Sharding by module size optimizes resource usage"
  - "Promise.all with timeout prevents hanging workers"

related_problems:
  - "2025-01-10-003"  # Previous test optimization
  - "2024-12-22-018"  # CI/CD performance tuning
```

## Implementation Details

### Duplicate Detection and Smart Merge

```bash
# Check if problem already exists
check_existing_problem() {
    local problem_id="$1"
    local problem_file="changelog/$(date +%Y-%m)/${problem_id}_problems_solved.yaml"
    [[ -f "$problem_file" ]]
}

# Generate next available problem ID
generate_next_problem_id() {
    local base_date="$(date +%Y-%m-%d)"
    local counter=1
    local changelog_dir="changelog/$(date +%Y-%m)"
    mkdir -p "$changelog_dir"
    while check_existing_problem "${base_date}-$(printf '%03d' $counter)"; do
        ((counter++))
    done
    echo "${base_date}-$(printf '%03d' $counter)"
}

# Check if recently summarized (within 5 minutes)
check_recent_summarization() {
    local session_file=".milestones/sessions/current-summary.txt"
    if [[ -f "$session_file" ]]; then
        local last_run=$(stat -f %m "$session_file" 2>/dev/null || stat -c %Y "$session_file")
        local now=$(date +%s)
        if [[ $((now - last_run)) -lt 300 ]]; then
            return 0  # Recently summarized
        fi
    fi
    return 1  # Not recent
}

# Update session timestamp
update_session_timestamp() {
    local session_file=".milestones/sessions/current-summary.txt"
    mkdir -p "$(dirname "$session_file")"
    date +%s > "$session_file"
}

# Merge git commits into existing problem
merge_git_commits() {
    local existing_file="$1"
    local new_commits="$2"
    
    # Use yq if available, otherwise use sed
    if command -v yq &>/dev/null; then
        yq eval ".git_info.commits += $new_commits" -i "$existing_file"
    else
        # Simple append strategy using sed
        sed -i.bak '/^git_info:/,/^[^ ]/ {
            /commits:/ {
                a\
    '"$new_commits"'
            }
        }' "$existing_file"
        rm -f "${existing_file}.bak"
    fi
}

# Handle duplicate problem
handle_duplicate_problem() {
    local problem_id="$1"
    local problem_file="changelog/$(date +%Y-%m)/${problem_id}_problems_solved.yaml"
    
    if [[ "$FORCE_OVERRIDE" == "true" ]] || [[ "$2" == "--force" ]]; then
        echo "🔄 Force override enabled - replacing existing problem"
        return 0
    fi
    
    if check_recent_summarization; then
        echo "⏭️  Problem recently summarized (< 5 minutes ago) - skipping"
        return 1
    fi
    
    echo "📝 Problem already exists: $problem_id"
    echo "Options:"
    echo "  1) Append new information (merge)"
    echo "  2) Replace existing entry"
    echo "  3) Create new entry with different ID"
    echo "  4) Skip"
    
    read -p "Choice [1-4]: " choice
    case $choice in
        1) echo "🔀 Merging new information..."; return 2 ;;
        2) echo "🔄 Replacing existing entry..."; return 0 ;;
        3) echo "➕ Creating new entry..."; return 3 ;;
        4) echo "⏭️  Skipping..."; return 1 ;;
        *) echo "⏭️  Invalid choice - skipping"; return 1 ;;
    esac
}
```

### Adding Problems (`summarize add`)

```bash
# Interactive mode - Claude guides through problem documentation
claude summarize add

# Auto-detect from current session
claude summarize add --auto-detect

# Quick add with title
claude summarize add --title "Fixed authentication bug" --category "bug-fix"

# Force override existing entry
claude summarize add --force
```

The command will:
1. Check for existing problems with duplicate detection
2. Generate unique problem ID with auto-increment
3. Extract context from current git state
4. Analyze recent commits for relevant changes
5. Check session history for commands and agents used
6. Handle duplicates intelligently (merge/replace/skip)
7. Update session timestamp to prevent re-summarization
8. Store in structured YAML format

**Duplicate Handling Flow:**
```bash
# When adding a problem
problem_id=$(generate_next_problem_id)

if check_existing_problem "$problem_id"; then
    handle_duplicate_problem "$problem_id" "$@"
    case $? in
        0) # Replace existing
            backup_existing_problem "$problem_id"
            create_problem_entry "$problem_id"
            ;;
        1) # Skip
            exit 0
            ;;
        2) # Merge
            merge_problem_data "$problem_id"
            ;;
        3) # New ID
            problem_id=$(generate_next_problem_id)
            create_problem_entry "$problem_id"
            ;;
    esac
else
    create_problem_entry "$problem_id"
fi

# Update session tracking
update_session_timestamp
```

### Listing Problems (`summarize list`)

```bash
# List all problems
claude summarize list

# Filter by category
claude summarize list --category "performance"

# Show recent problems
claude summarize list --recent 10

# List with brief descriptions
claude summarize list --verbose
```

Output format:
```
📊 Claude Code Problem-Solving History
════════════════════════════════════════

🔧 Recent Problems Solved:

[2025-01-18-001] Implement parallel test execution
  Category: performance | Severity: medium
  Impact: 75% test execution time reduction
  Git: 2 commits, PR #142

[2025-01-17-003] Fix memory leak in data processor
  Category: bug-fix | Severity: high
  Impact: Reduced memory usage by 60%
  Git: 3 commits, PR #138

[2025-01-16-002] Add real-time collaboration features
  Category: feature | Severity: medium
  Impact: New WebSocket-based collaboration
  Git: 8 commits, PR #135

Total: 127 problems solved | 89% success rate
```

### Viewing Problem Details (`summarize show`)

```bash
# View specific problem
claude summarize show 2025-01-18-001

# View with code snippets
claude summarize show 2025-01-18-001 --include-code

# View in browser
claude summarize show 2025-01-18-001 --browser
```

### Exporting Documentation (`summarize export`)

```bash
# Export all problems to markdown
claude summarize export

# Export specific period
claude summarize export --since "2025-01-01"

# Export to HTML with styling
claude summarize export --format html --output docs/problems-solved.html

# Generate team knowledge base
claude summarize export --format md --category "all" --include-lessons
```

### Syncing from Git History (`summarize sync`)

```bash
# Sync all problems from git history
claude summarize sync

# Sync from specific date
claude summarize sync --since "2025-01-01"

# Sync specific branch
claude summarize sync --branch "main"

# Dry run to preview what would be added
claude summarize sync --dry-run
```

The sync process:
1. Parses git log for Claude Code signatures
2. Extracts problem context from commit messages
3. Analyzes file changes for solution patterns
4. Links related commits and PRs
5. Creates problem entries with git metadata

### Searching Problems (`summarize search`)

```bash
# Search by keyword
claude summarize search "authentication"

# Search by pattern
claude summarize search "error.*timeout"

# Search in specific fields
claude summarize search --in "solution" "worker threads"

# Full-text search with ranking
claude summarize search --full-text "performance optimization"
```

### Generating Reports (`summarize report`)

```bash
# Generate weekly report
claude summarize report --period week

# Generate comprehensive monthly report
claude summarize report --period month --verbose

# Generate metrics report
claude summarize report --metrics

# Generate patterns report
claude summarize report --patterns
```

Report includes:
- Problems solved by category
- Success/failure rates
- Common patterns identified
- Performance improvements achieved
- Lessons learned summary
- Team collaboration metrics

## Integration Points

### Git Integration
- Automatic commit extraction with `git log --format`
- PR linking via GitHub CLI (`gh pr view`)
- Branch analysis for feature tracking
- Diff analysis for solution patterns

### Session Management
- Links to session summaries in `changelog/`
- Command history tracking
- Agent utilization metrics
- Duration and complexity analysis

### Milestone System
- Cross-references milestone completions
- Tracks problems within milestone context
- Aggregates milestone-level metrics
- Enables project-wide problem tracking

### Documentation System
- Exports integrate with `/docs` command
- Generates team knowledge base
- Creates searchable problem database
- Supports multiple output formats

## Advanced Features

### Problem Pattern Recognition
```bash
# Analyze patterns across problems
claude summarize analyze-patterns

# Suggest solutions based on similar problems
claude summarize suggest --for "current problem description"
```

### Team Collaboration
```bash
# Share problem database with team
claude summarize share --team

# Import problems from another Claude instance
claude summarize import --from <path>

# Merge problem databases
claude summarize merge --with <remote-path>
```

### Metrics and Analytics
```bash
# Generate performance metrics
claude summarize metrics --type performance

# Analyze problem complexity trends
claude summarize metrics --type complexity

# Track solution effectiveness
claude summarize metrics --type effectiveness
```

## Multiple Execution Handling

### Intelligent Duplicate Management

The `/summarize` command is designed to handle multiple executions intelligently:

**Session Awareness:**
- Tracks recent summarizations (< 5 minutes) to prevent accidental duplicates
- Session state stored in `.milestones/sessions/current-summary.txt`
- Automatically skips if recently executed unless `--force` flag used

**Smart ID Generation:**
- Automatically generates unique IDs: `YYYY-MM-DD-001`, `YYYY-MM-DD-002`, etc.
- Increments counter for same-day problems
- Prevents ID collisions even with rapid execution

**Duplicate Detection:**
- Checks if problem file already exists before creation
- Offers intelligent options when duplicate detected:
  1. **Merge**: Append new git commits and update timestamps
  2. **Replace**: Backup existing and create fresh entry
  3. **New Entry**: Generate next available ID
  4. **Skip**: Leave existing entry unchanged

**Force Override:**
```bash
# Skip all duplicate checks and replace existing
claude summarize add --force

# Environment variable for scripting
FORCE_OVERRIDE=true claude summarize add
```

**Example Scenarios:**

1. **Immediate Re-execution (< 5 minutes):**
   ```
   $ claude summarize add
   ⏭️  Problem recently summarized (< 5 minutes ago) - skipping
   ```

2. **Same Day, Different Problem:**
   ```
   $ claude summarize add
   Generated ID: 2025-01-18-002  # Automatically incremented
   ```

3. **Updating Existing Problem:**
   ```
   $ claude summarize add
   📝 Problem already exists: 2025-01-18-001
   Options:
     1) Append new information (merge)
     2) Replace existing entry
     3) Create new entry with different ID
     4) Skip
   Choice [1-4]: 1
   🔀 Merging new information...
   ```

## Best Practices

### When to Add Problems
- After solving significant bugs or issues
- When implementing new features
- After performance optimizations
- When refactoring complex code
- After debugging difficult problems

### Documentation Quality
- Include specific error messages
- Document before/after metrics
- Capture user's original request
- Note constraints and requirements
- Record lessons learned

### Knowledge Sharing
- Export weekly summaries for team review
- Create category-specific documentation
- Share patterns and solutions
- Build searchable knowledge base
- Enable cross-session learning

## Implementation Notes

### Storage Adapter Pattern
Uses the proven hybrid storage approach:
- File-based for < 100 problems
- File + SQLite indexing for 100-1000 problems  
- Full database for > 1000 problems

### Performance Optimization
- Lazy loading of problem details
- Indexed search capabilities
- Cached export generation
- Incremental sync updates
- Parallel git analysis

### Error Handling
- Graceful degradation without git
- Automatic recovery from corrupted entries
- Validation of problem schema
- Rollback capabilities for sync
- Comprehensive error logging

## Example Workflow

```bash
# 1. Start working on a problem
claude session start --goal "Optimize database queries"

# 2. Claude solves the problem
# ... implementation happens ...

# 3. Document the solution
claude summarize add --auto-detect

# 4. Review problems solved this week
claude summarize list --recent 7

# 5. Export for team knowledge base
claude summarize export --format md --output docs/

# 6. Search for similar problems later
claude summarize search "database optimization"

# 7. Generate monthly report
claude summarize report --period month
```

## Future Enhancements

### Planned Features
- AI-powered pattern recognition
- Automatic solution suggestions
- Problem complexity scoring
- Success rate predictions
- Integration with issue trackers

### Extensibility
- Plugin architecture for custom analyzers
- Webhook support for external systems
- API for programmatic access
- Custom export templates
- Multi-language support

## Troubleshooting

### Common Issues

**Problem**: Sync not finding Claude commits
**Solution**: Ensure commits include Claude signature in message

**Problem**: Storage directory not accessible
**Solution**: Check permissions on `.milestones/` directory

**Problem**: Export formatting issues
**Solution**: Verify markdown processor compatibility

**Problem**: Search not returning results
**Solution**: Rebuild search index with `summarize reindex`

## Command Aliases

For convenience, these shorter aliases are available:
- `claude sum` → `claude summarize`
- `claude problems` → `claude summarize list`
- `claude solved` → `claude summarize list --recent`

## Conclusion

The `/summarize` command provides comprehensive problem-solving documentation, enabling:
- Knowledge preservation across sessions
- Team learning and collaboration
- Pattern recognition and reuse
- Continuous improvement tracking
- Effective problem-solving strategies

By maintaining a structured database of solved problems, Claude Code becomes more effective over time, learning from past solutions to provide better assistance in future sessions.