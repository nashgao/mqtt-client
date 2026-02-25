---
allowed-tools: all
description: Create a new release with automatic versioning and changelog
---

# Git Release Create

Automatically creates a new release with proper versioning, changelog generation, and GitHub release.

**Usage:** `/git/release/create <version-type> [options]`

## Version Types

- `patch` - Bug fixes and minor changes (0.0.X)
- `minor` - New features, backward compatible (0.X.0)
- `major` - Breaking changes (X.0.0)

## Quick Examples

```bash
# Create patch release (most common)
/git/release/create patch

# Create minor release with feature
/git/release/create minor "Added new analytics module"

# Create major release
/git/release/create major "Breaking API changes"
```

## Full Implementation

```bash
# Main release creation function
function create_release() {
    local version_type="${1:-patch}"
    local release_message="${2:-}"
    
    echo "🚀 Creating $version_type release..."
    
    # Step 1: Validate environment
    validate_release_environment || return 1
    
    # Step 2: Calculate version
    local current_version=$(get_current_version)
    local next_version=$(calculate_next_version "$current_version" "$version_type")
    
    echo "📦 Version: $current_version → $next_version"
    
    # Step 3: Generate changelog
    echo "📝 Generating changelog..."
    local changelog=$(generate_smart_changelog "$current_version")
    
    # Step 4: Create release commit
    create_release_commit "$next_version" "$release_message"
    
    # Step 5: Tag the release
    git tag -a "$next_version" -m "Release $next_version

$changelog"
    
    # Step 6: Push to origin
    push_release "$next_version"
    
    # Step 7: Create GitHub release
    publish_github_release "$next_version" "$changelog"
    
    echo "✅ Release $next_version created successfully!"
}

# Get current version from git tags
function get_current_version() {
    local current=$(git describe --tags --abbrev=0 2>/dev/null)
    if [[ -z "$current" ]]; then
        echo "0.0.0"
    else
        echo "${current#v}"  # Remove 'v' prefix if present
    fi
}

# Calculate next version based on type
function calculate_next_version() {
    local current="$1"
    local bump_type="$2"
    
    # Parse version components
    IFS='.' read -r major minor patch <<< "$current"
    
    # Increment based on type
    case "$bump_type" in
        major)
            major=$((major + 1))
            minor=0
            patch=0
            ;;
        minor)
            minor=$((minor + 1))
            patch=0
            ;;
        patch|*)
            patch=$((patch + 1))
            ;;
    esac
    
    echo "${major}.${minor}.${patch}"
}

# Generate smart changelog (Space-Analytics style)
function generate_smart_changelog() {
    local from_tag="${1:-$(git describe --tags --abbrev=0 2>/dev/null)}"
    local to_ref="HEAD"
    
    local changelog=""
    
    # Changes section
    changelog+="## Changes\n"
    
    # Parse commits by conventional type
    local has_changes=false
    
    # Features
    local features=$(git log "$from_tag..$to_ref" --grep="^feat" --pretty="- **Feature**: %s" 2>/dev/null | sed 's/^feat[:(].*[):] *//')
    if [[ -n "$features" ]]; then
        changelog+="$features\n"
        has_changes=true
    fi
    
    # Performance improvements
    local perf=$(git log "$from_tag..$to_ref" --grep="^perf" --pretty="- **Performance**: %s" 2>/dev/null | sed 's/^perf[:(].*[):] *//')
    if [[ -n "$perf" ]]; then
        changelog+="$perf\n"
        has_changes=true
    fi
    
    # Refactoring
    local refactor=$(git log "$from_tag..$to_ref" --grep="^refactor" --pretty="- **Refactoring**: %s" 2>/dev/null | sed 's/^refactor[:(].*[):] *//')
    if [[ -n "$refactor" ]]; then
        changelog+="$refactor\n"
        has_changes=true
    fi
    
    # Bug fixes
    local fixes=$(git log "$from_tag..$to_ref" --grep="^fix" --pretty="- **Fix**: %s" 2>/dev/null | sed 's/^fix[:(].*[):] *//')
    if [[ -n "$fixes" ]]; then
        changelog+="$fixes\n"
        has_changes=true
    fi
    
    # Cleanup
    local cleanup=$(git log "$from_tag..$to_ref" --grep="cleanup\|remove" --pretty="- **Cleanup**: %s" 2>/dev/null | head -5)
    if [[ -n "$cleanup" ]]; then
        changelog+="$cleanup\n"
        has_changes=true
    fi
    
    # If no conventional commits, show recent commits
    if [[ "$has_changes" == "false" ]]; then
        local recent=$(git log "$from_tag..$to_ref" --pretty="- %s" 2>/dev/null | head -10)
        if [[ -n "$recent" ]]; then
            changelog+="$recent\n"
        fi
    fi
    
    # Improvements section
    changelog+="\n## Improvements\n"
    
    # Extract improvement-related commits
    local improvements=$(git log "$from_tag..$to_ref" --grep -E "improve|enhance|optimize|better" --pretty="- %s" 2>/dev/null | head -5)
    if [[ -n "$improvements" ]]; then
        changelog+="$improvements\n"
    else
        changelog+="- Continuous improvements and optimizations\n"
    fi
    
    # Technical Details
    changelog+="\n## Technical Details\n"
    
    # Get file change summary
    local stats=$(git diff "$from_tag..$to_ref" --shortstat 2>/dev/null)
    if [[ -n "$stats" ]]; then
        changelog+="- $stats\n"
    fi
    
    # Most changed files
    local top_files=$(git diff "$from_tag..$to_ref" --stat 2>/dev/null | head -5 | tail -4)
    if [[ -n "$top_files" ]]; then
        changelog+="- Key files modified:\n$top_files\n"
    fi
    
    # Add attribution
    changelog+="\n🤖 Generated with [Claude Code](https://claude.ai/code)"
    
    echo -e "$changelog"
}

# Validate release environment
function validate_release_environment() {
    # Check for uncommitted changes
    if [[ -n $(git status --porcelain) ]]; then
        echo "⚠️ Uncommitted changes detected!"
        read -p "Stash changes and continue? (y/n) " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            git stash push -m "Auto-stash before release"
        else
            return 1
        fi
    fi
    
    # Check we're on main/master branch
    local current_branch=$(git branch --show-current)
    if [[ "$current_branch" != "main" && "$current_branch" != "master" ]]; then
        echo "⚠️ Not on main branch (current: $current_branch)"
        read -p "Switch to main? (y/n) " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            git checkout main || git checkout master
        else
            return 1
        fi
    fi
    
    # Ensure we're up to date
    git fetch origin
    local behind=$(git rev-list HEAD..origin/main --count 2>/dev/null || echo 0)
    if [[ "$behind" -gt 0 ]]; then
        echo "⚠️ Branch is $behind commits behind origin"
        git pull origin main
    fi
    
    return 0
}

# Create release commit
function create_release_commit() {
    local version="$1"
    local message="${2:-Release $version}"
    
    # Create or update version file if it exists
    if [[ -f "VERSION" ]]; then
        echo "$version" > VERSION
        git add VERSION
    fi
    
    # Create release commit (allow empty for tags)
    git commit --allow-empty -m "chore: $message"
}

# Push release to origin
function push_release() {
    local version="$1"
    
    echo "📤 Pushing release to origin..."
    
    # Push commits
    git push origin main
    
    # Push tag
    git push origin "$version"
}

# Publish GitHub release
function publish_github_release() {
    local version="$1"
    local changelog="$2"
    
    # Check for gh CLI
    if ! command -v gh &> /dev/null; then
        echo "⚠️ GitHub CLI not installed. Skipping GitHub release."
        echo "Install with: brew install gh"
        return 0
    fi
    
    echo "📢 Creating GitHub release..."
    
    # Create release
    gh release create "$version" \
        --title "$version" \
        --notes "$changelog" \
        --latest
    
    # Show release URL
    local url=$(gh release view "$version" --json url -q .url 2>/dev/null)
    if [[ -n "$url" ]]; then
        echo "🔗 Release URL: $url"
    fi
}
```

## Options

### --draft
Create as draft release for review:
```bash
/git/release/create minor --draft
```

### --no-push
Create release locally without pushing:
```bash
/git/release/create patch --no-push
```

### --message
Custom release message:
```bash
/git/release/create minor --message "Analytics module v2"
```

## Integration

This command works with:
- `/git/release` - Main release command
- `/git/release/changelog` - Generate changelog only
- `/git/release/publish` - Publish existing tag to GitHub