---
allowed-tools: all
description: Generate comprehensive changelog from git history
---

# Git Release Changelog

Generate structured changelogs from commit history following the Space-Analytics pattern.

**Usage:** `/git/release/changelog [from-tag] [to-ref]`

## Quick Examples

```bash
# Generate changelog since last release
/git/release/changelog

# Generate changelog between specific versions
/git/release/changelog v1.0.0 v2.0.0

# Generate changelog for upcoming release
/git/release/changelog HEAD
```

## Changelog Generation

```bash
# Main changelog generation function
function generate_changelog() {
    local from_ref="${1:-$(git describe --tags --abbrev=0 2>/dev/null || git rev-list --max-parents=0 HEAD)}"
    local to_ref="${2:-HEAD}"
    
    echo "📝 Generating changelog from $from_ref to $to_ref"
    echo ""
    
    # Generate structured changelog
    generate_structured_changelog "$from_ref" "$to_ref"
}

# Generate structured changelog with categories
function generate_structured_changelog() {
    local from="$1"
    local to="$2"
    
    # Initialize sections
    local changes=""
    local improvements=""
    local technical=""
    local dependencies=""
    
    # == CHANGES SECTION ==
    echo "## Changes"
    
    # Parse commits by type
    parse_commit_type "feat" "Feature" "$from" "$to"
    parse_commit_type "perf" "Performance" "$from" "$to"
    parse_commit_type "refactor" "Refactoring" "$from" "$to"
    parse_commit_type "fix" "Fix" "$from" "$to"
    parse_commit_type "cleanup" "Cleanup" "$from" "$to"
    
    # Handle non-conventional commits
    local other_commits=$(git log "$from..$to" --pretty="%s" 2>/dev/null | \
        grep -v -E "^(feat|fix|perf|refactor|chore|docs|style|test|build|ci|cleanup)[(:]" | \
        head -10)
    
    if [[ -n "$other_commits" ]]; then
        while IFS= read -r commit; do
            classify_and_format_commit "$commit"
        done <<< "$other_commits"
    fi
    
    # == IMPROVEMENTS SECTION ==
    echo ""
    echo "## Improvements"
    
    # Extract improvement keywords
    local improvement_commits=$(git log "$from..$to" --pretty="%s" 2>/dev/null | \
        grep -E -i "improve|enhance|optimize|better|upgrade|speed|fast|cache|efficient" | \
        head -10)
    
    if [[ -n "$improvement_commits" ]]; then
        while IFS= read -r commit; do
            echo "- $commit"
        done <<< "$improvement_commits"
    else
        # Default improvements based on file changes
        analyze_improvements "$from" "$to"
    fi
    
    # == TECHNICAL DETAILS SECTION ==
    echo ""
    echo "## Technical Details"
    
    # File statistics
    local stats=$(git diff "$from..$to" --shortstat 2>/dev/null)
    if [[ -n "$stats" ]]; then
        echo "- Changes: $stats"
    fi
    
    # Most modified files
    echo "- Key files modified:"
    git diff "$from..$to" --stat 2>/dev/null | \
        sort -rn -k 3 | \
        head -5 | \
        while read -r file changes; do
            if [[ -n "$file" && "$file" != *"|"* ]]; then
                continue
            fi
            echo "  - $file"
        done
    
    # Language-specific changes
    analyze_language_changes "$from" "$to"
    
    # == DEPENDENCIES SECTION (if applicable) ==
    check_dependency_changes "$from" "$to"
    
    # Attribution
    echo ""
    echo "🤖 Generated with [Claude Code](https://claude.ai/code)"
}

# Parse commits by conventional type
function parse_commit_type() {
    local type="$1"
    local label="$2"
    local from="$3"
    local to="$4"
    
    local commits=$(git log "$from..$to" --grep="^$type" --pretty="%s" 2>/dev/null)
    
    if [[ -n "$commits" ]]; then
        while IFS= read -r commit; do
            # Remove conventional commit prefix
            local clean_commit=$(echo "$commit" | sed -E "s/^$type(\([^)]+\))?:\s*//")
            echo "- **$label**: $clean_commit"
        done <<< "$commits"
    fi
}

# Classify non-conventional commits
function classify_and_format_commit() {
    local commit="$1"
    
    # Classify based on keywords
    if echo "$commit" | grep -q -E -i "add|create|implement|introduce"; then
        echo "- **Feature**: $commit"
    elif echo "$commit" | grep -q -E -i "fix|repair|correct|resolve"; then
        echo "- **Fix**: $commit"
    elif echo "$commit" | grep -q -E -i "update|modify|change|adjust"; then
        echo "- **Update**: $commit"
    elif echo "$commit" | grep -q -E -i "remove|delete|clean"; then
        echo "- **Cleanup**: $commit"
    elif echo "$commit" | grep -q -E -i "refactor|restructure|reorganize"; then
        echo "- **Refactoring**: $commit"
    else
        echo "- $commit"
    fi
}

# Analyze improvements from diff
function analyze_improvements() {
    local from="$1"
    local to="$2"
    
    # Check for performance improvements
    local perf_files=$(git diff "$from..$to" --name-only 2>/dev/null | \
        grep -E "(cache|index|optimize|performance)" | head -3)
    
    if [[ -n "$perf_files" ]]; then
        echo "- Performance optimizations in key components"
    fi
    
    # Check for test improvements
    local test_changes=$(git diff "$from..$to" --name-only 2>/dev/null | \
        grep -E "(test|spec)" | wc -l)
    
    if [[ "$test_changes" -gt 0 ]]; then
        echo "- Enhanced test coverage ($test_changes test files modified)"
    fi
    
    # Check for documentation
    local doc_changes=$(git diff "$from..$to" --name-only 2>/dev/null | \
        grep -E "\.(md|txt|doc)" | wc -l)
    
    if [[ "$doc_changes" -gt 0 ]]; then
        echo "- Documentation updates ($doc_changes files)"
    fi
    
    # Default improvement message
    echo "- Code quality and maintainability improvements"
}

# Analyze language-specific changes
function analyze_language_changes() {
    local from="$1"
    local to="$2"
    
    # Count changes by file type
    local py_changes=$(git diff "$from..$to" --name-only 2>/dev/null | grep "\.py$" | wc -l)
    local js_changes=$(git diff "$from..$to" --name-only 2>/dev/null | grep "\.js$" | wc -l)
    local php_changes=$(git diff "$from..$to" --name-only 2>/dev/null | grep "\.php$" | wc -l)
    local go_changes=$(git diff "$from..$to" --name-only 2>/dev/null | grep "\.go$" | wc -l)
    
    local changes=""
    [[ "$py_changes" -gt 0 ]] && changes+="Python ($py_changes files), "
    [[ "$js_changes" -gt 0 ]] && changes+="JavaScript ($js_changes files), "
    [[ "$php_changes" -gt 0 ]] && changes+="PHP ($php_changes files), "
    [[ "$go_changes" -gt 0 ]] && changes+="Go ($go_changes files), "
    
    if [[ -n "$changes" ]]; then
        echo "- Language changes: ${changes%, }"
    fi
}

# Check for dependency changes
function check_dependency_changes() {
    local from="$1"
    local to="$2"
    
    local dep_files=$(git diff "$from..$to" --name-only 2>/dev/null | \
        grep -E "(package\.json|requirements\.txt|go\.mod|composer\.json|Gemfile|pom\.xml)")
    
    if [[ -n "$dep_files" ]]; then
        echo ""
        echo "## Dependencies"
        
        while IFS= read -r file; do
            case "$file" in
                package.json|package-lock.json)
                    echo "- Node.js dependencies updated"
                    ;;
                requirements.txt|Pipfile|setup.py)
                    echo "- Python dependencies updated"
                    ;;
                go.mod|go.sum)
                    echo "- Go modules updated"
                    ;;
                composer.json|composer.lock)
                    echo "- PHP dependencies updated"
                    ;;
                *)
                    echo "- Dependencies updated in $file"
                    ;;
            esac
        done <<< "$dep_files"
    fi
}
```

## Output Formats

### Markdown (Default)
```bash
/git/release/changelog
```

### Plain Text
```bash
/git/release/changelog --format=text
```

### JSON
```bash
/git/release/changelog --format=json
```

## Advanced Options

### Include Author Information
```bash
/git/release/changelog --with-authors
```

### Include Issue Links
```bash
/git/release/changelog --link-issues
```

### Custom Date Range
```bash
/git/release/changelog --since="2025-01-01" --until="2025-08-29"
```

## Integration with Release Process

Use changelog in release workflow:

```bash
# Generate and save changelog
CHANGELOG=$(/git/release/changelog)

# Use in release creation
/git/release/create patch --changelog="$CHANGELOG"

# Or update CHANGELOG.md file
/git/release/changelog > CHANGELOG.md
git add CHANGELOG.md
git commit -m "docs: update changelog for release"
```

## Examples from Space-Analytics

Based on actual Space-Analytics releases:

### Version 0.2.308
```markdown
## Changes
- **Performance**: Optimized Visit DAO with caching for unique visitor queries
- **Refactoring**: Extracted getAdvanceUniqueVisitor method to ValidVisitDao class
- **Cleanup**: Removed incomplete documentation files

## Improvements
- Added 30-minute TTL cache to reduce database queries
- Better code organization with logic in appropriate DAO class
- Simplified query by removing redundant filters

## Technical Details
- Moved unique visitor query logic from MySQLUbertoothHistoryVisitDao to MySQLUbertoothValidVisitDao
- Added @Cacheable annotation with proper cache key
- Deprecated old method with proper annotation
```

### Version 0.2.307
```markdown
## Changes
- Refactored advanceUniqueVisitor method in MySQL Ubertooth History Visit DAO
- Improved method organization and caching implementation
- Maintained backward compatibility with deprecated method

## Improvements
- Added new getAdvanceUniqueVisitor method with @Cacheable annotation
- Method properly placed in class hierarchy
- Caching TTL set to 1800 seconds for performance

## Dependencies
- No dependency changes in this release
```