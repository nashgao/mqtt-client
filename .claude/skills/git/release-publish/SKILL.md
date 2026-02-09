---
allowed-tools: all
description: Publish existing git tag as GitHub release
---

# Git Release Publish

Publish an existing git tag as a GitHub release with changelog.

**Usage:** `/git/release/publish <tag> [options]`

## Quick Examples

```bash
# Publish latest tag as release
/git/release/publish

# Publish specific tag
/git/release/publish v2.1.0

# Publish as draft for review
/git/release/publish v2.1.0 --draft

# Publish with custom changelog
/git/release/publish v2.1.0 --changelog="Custom release notes"
```

## Implementation

```bash
# Main publish function
function publish_release() {
    local tag="${1:-$(git describe --tags --abbrev=0)}"
    local draft="${2:-false}"
    local custom_changelog="$3"
    
    echo "📢 Publishing release for tag: $tag"
    
    # Validate tag exists
    if ! git rev-parse "$tag" >/dev/null 2>&1; then
        echo "❌ Tag $tag does not exist"
        echo "Available tags:"
        git tag -l | tail -10
        return 1
    fi
    
    # Check if release already exists
    if gh release view "$tag" >/dev/null 2>&1; then
        echo "⚠️ Release $tag already exists on GitHub"
        read -p "Update existing release? (y/n) " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            return 1
        fi
        update_existing_release "$tag" "$custom_changelog"
        return $?
    fi
    
    # Generate or use custom changelog
    local changelog
    if [[ -n "$custom_changelog" ]]; then
        changelog="$custom_changelog"
    else
        echo "📝 Generating changelog..."
        changelog=$(generate_release_changelog "$tag")
    fi
    
    # Create GitHub release
    create_github_release "$tag" "$changelog" "$draft"
}

# Generate changelog for specific tag
function generate_release_changelog() {
    local tag="$1"
    
    # Get previous tag
    local prev_tag=$(git describe --tags --abbrev=0 "$tag^" 2>/dev/null)
    
    if [[ -z "$prev_tag" ]]; then
        # First release - get all commits
        prev_tag=$(git rev-list --max-parents=0 HEAD)
    fi
    
    # Generate structured changelog
    local changelog=""
    
    # Changes section
    changelog+="## Changes\n"
    
    # Categorize commits
    categorize_commits "$prev_tag" "$tag"
    
    # Improvements section
    changelog+="\n## Improvements\n"
    analyze_improvements_for_tag "$prev_tag" "$tag"
    
    # Technical details
    changelog+="\n## Technical Details\n"
    add_technical_details "$prev_tag" "$tag"
    
    # Attribution
    changelog+="\n🤖 Generated with [Claude Code](https://claude.ai/code)"
    
    echo -e "$changelog"
}

# Categorize commits by type
function categorize_commits() {
    local from="$1"
    local to="$2"
    
    # Features
    local features=$(git log "$from..$to" --grep="^feat" --pretty="%s" 2>/dev/null | \
        sed 's/^feat[:(].*[):] *//' | \
        while read -r line; do
            [[ -n "$line" ]] && echo "- **Feature**: $line"
        done)
    
    [[ -n "$features" ]] && echo "$features"
    
    # Performance
    local perf=$(git log "$from..$to" --grep="^perf" --pretty="%s" 2>/dev/null | \
        sed 's/^perf[:(].*[):] *//' | \
        while read -r line; do
            [[ -n "$line" ]] && echo "- **Performance**: $line"
        done)
    
    [[ -n "$perf" ]] && echo "$perf"
    
    # Refactoring
    local refactor=$(git log "$from..$to" --grep="^refactor" --pretty="%s" 2>/dev/null | \
        sed 's/^refactor[:(].*[):] *//' | \
        while read -r line; do
            [[ -n "$line" ]] && echo "- **Refactoring**: $line"
        done)
    
    [[ -n "$refactor" ]] && echo "$refactor"
    
    # Fixes
    local fixes=$(git log "$from..$to" --grep="^fix" --pretty="%s" 2>/dev/null | \
        sed 's/^fix[:(].*[):] *//' | \
        while read -r line; do
            [[ -n "$line" ]] && echo "- **Fix**: $line"
        done)
    
    [[ -n "$fixes" ]] && echo "$fixes"
    
    # Cleanup
    local cleanup=$(git log "$from..$to" --grep -E "cleanup|remove" --pretty="%s" 2>/dev/null | \
        head -5 | \
        while read -r line; do
            [[ -n "$line" ]] && echo "- **Cleanup**: $line"
        done)
    
    [[ -n "$cleanup" ]] && echo "$cleanup"
    
    # Other significant commits
    local others=$(git log "$from..$to" --pretty="%s" 2>/dev/null | \
        grep -v -E "^(feat|fix|perf|refactor|chore|docs|style|test|build|ci)[(:]" | \
        head -5 | \
        while read -r line; do
            [[ -n "$line" ]] && echo "- $line"
        done)
    
    [[ -n "$others" ]] && echo "$others"
}

# Analyze improvements for the release
function analyze_improvements_for_tag() {
    local from="$1"
    local to="$2"
    
    # Look for improvement-related commits
    local improvements=$(git log "$from..$to" --pretty="%s" 2>/dev/null | \
        grep -E -i "improve|enhance|optimize|better|upgrade" | \
        head -5 | \
        while read -r line; do
            [[ -n "$line" ]] && echo "- $line"
        done)
    
    if [[ -n "$improvements" ]]; then
        echo "$improvements"
    else
        # Generic improvements based on changes
        local file_count=$(git diff "$from..$to" --name-only 2>/dev/null | wc -l)
        echo "- Code improvements across $file_count files"
        echo "- Enhanced maintainability and performance"
    fi
}

# Add technical details
function add_technical_details() {
    local from="$1"
    local to="$2"
    
    # Statistics
    local stats=$(git diff "$from..$to" --shortstat 2>/dev/null)
    [[ -n "$stats" ]] && echo "- $stats"
    
    # Commit count
    local commit_count=$(git rev-list "$from..$to" --count 2>/dev/null)
    echo "- $commit_count commits since previous release"
    
    # Contributors
    local contributors=$(git log "$from..$to" --pretty="%an" 2>/dev/null | \
        sort -u | wc -l)
    echo "- $contributors contributor(s)"
    
    # Most changed files
    echo "- Key files modified:"
    git diff "$from..$to" --stat 2>/dev/null | \
        sort -rn -k 3 | \
        head -3 | \
        sed 's/|.*$//' | \
        while read -r file; do
            [[ -n "$file" ]] && echo "  - $file"
        done
}

# Create GitHub release
function create_github_release() {
    local tag="$1"
    local changelog="$2"
    local draft="$3"
    
    echo "🚀 Creating GitHub release..."
    
    # Build gh release command
    local cmd="gh release create \"$tag\""
    cmd+=" --title \"$tag\""
    cmd+=" --notes \"$changelog\""
    
    if [[ "$draft" == "true" ]]; then
        cmd+=" --draft"
    else
        cmd+=" --latest"
    fi
    
    # Execute release creation
    if eval "$cmd"; then
        echo "✅ Release $tag published successfully!"
        
        # Get and display release URL
        local url=$(gh release view "$tag" --json url -q .url 2>/dev/null)
        [[ -n "$url" ]] && echo "🔗 View release: $url"
    else
        echo "❌ Failed to create release"
        return 1
    fi
}

# Update existing release
function update_existing_release() {
    local tag="$1"
    local changelog="${2:-}"
    
    echo "📝 Updating release $tag..."
    
    if [[ -z "$changelog" ]]; then
        changelog=$(generate_release_changelog "$tag")
    fi
    
    # Update release notes
    gh release edit "$tag" \
        --notes "$changelog" \
        --latest
    
    echo "✅ Release $tag updated!"
}

# List existing releases
function list_releases() {
    echo "📋 Recent GitHub Releases:"
    echo "========================"
    
    gh release list --limit 10
    
    echo ""
    echo "📌 Local tags not yet published:"
    
    # Get all tags
    local all_tags=$(git tag -l | sort -V -r | head -10)
    
    # Check which ones don't have releases
    while IFS= read -r tag; do
        if ! gh release view "$tag" >/dev/null 2>&1; then
            echo "  - $tag (unpublished)"
        fi
    done <<< "$all_tags"
}
```

## Options

### --draft
Create as draft release:
```bash
/git/release/publish v2.1.0 --draft
```

### --prerelease
Mark as pre-release:
```bash
/git/release/publish v2.1.0-beta --prerelease
```

### --latest
Force as latest release:
```bash
/git/release/publish v2.1.0 --latest
```

### --list
List all releases:
```bash
/git/release/publish --list
```

## Advanced Usage

### Publish with Assets
```bash
# Build and attach assets
make build
/git/release/publish v2.1.0 --attach="dist/*.tar.gz"
```

### Update Existing Release
```bash
# Update release notes
/git/release/publish v2.1.0 --update --changelog="Updated notes"
```

### Bulk Publishing
```bash
# Publish all unpublished tags
for tag in $(git tag -l | grep -v $(gh release list --json tagName -q '.[].tagName')); do
    /git/release/publish "$tag"
done
```

## Integration

Works seamlessly with other release commands:

```bash
# Full release workflow
/git/release/create patch      # Create and tag
/git/release/publish           # Publish to GitHub

# Or use main command
/git/release create patch --publish
```