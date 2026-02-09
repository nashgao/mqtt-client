---
allowed-tools: all
description: Automated git release management with changelog generation
---

# Git Release

Streamlined release management with automatic versioning, changelog generation, and GitHub release creation.

**Usage:** `/git/release <subcommand> [options]`

## Available Sub-commands

- `create` - Create a new release with automatic changelog
- `prepare` - Prepare release (validate, test, build)
- `publish` - Publish release to GitHub
- `rollback` - Rollback to previous release
- `status` - Check current release status

## Quick Start

```bash
# Create a new patch release
/git/release create patch

# Create a minor release with custom message
/git/release create minor --message "New feature release"

# Create a major release
/git/release create major
```

## Release Creation

The `create` sub-command automates the entire release process:

```bash
# Automatic version bumping
function create_release() {
    local version_type="${1:-patch}"  # major, minor, patch
    local custom_message="$2"
    
    # Get current version
    local current_version=$(git describe --tags --abbrev=0 2>/dev/null || echo "0.0.0")
    
    # Calculate next version
    local next_version=$(bump_version "$current_version" "$version_type")
    
    echo "📦 Creating release $next_version from $current_version"
    
    # Generate changelog
    local changelog=$(generate_changelog "$current_version")
    
    # Create release commit
    git add .
    git commit -m "chore: release $next_version" --allow-empty
    
    # Create and push tag
    git tag -a "$next_version" -m "Release $next_version"
    git push origin main --tags
    
    # Create GitHub release
    create_github_release "$next_version" "$changelog"
}

# Version bumping logic
function bump_version() {
    local version="$1"
    local type="$2"
    
    # Remove 'v' prefix if present
    version="${version#v}"
    
    # Split version into components
    IFS='.' read -r major minor patch <<< "$version"
    
    case "$type" in
        major)
            major=$((major + 1))
            minor=0
            patch=0
            ;;
        minor)
            minor=$((minor + 1))
            patch=0
            ;;
        patch)
            patch=$((patch + 1))
            ;;
    esac
    
    echo "${major}.${minor}.${patch}"
}
```

## Changelog Generation

Automatically generates structured changelog from commit history:

```bash
function generate_changelog() {
    local from_tag="${1:-$(git describe --tags --abbrev=0 2>/dev/null)}"
    local to_ref="${2:-HEAD}"
    
    echo "## Changes"
    echo ""
    
    # Group commits by type
    local features=$(git log "$from_tag..$to_ref" --grep="^feat" --pretty="- **Feature**: %s" | sed 's/^feat[:(].*[):] *//')
    local fixes=$(git log "$from_tag..$to_ref" --grep="^fix" --pretty="- **Fix**: %s" | sed 's/^fix[:(].*[):] *//')
    local perf=$(git log "$from_tag..$to_ref" --grep="^perf" --pretty="- **Performance**: %s" | sed 's/^perf[:(].*[):] *//')
    local refactor=$(git log "$from_tag..$to_ref" --grep="^refactor" --pretty="- **Refactoring**: %s" | sed 's/^refactor[:(].*[):] *//')
    
    # Output sections if they have content
    if [[ -n "$features" ]]; then
        echo "$features"
    fi
    
    if [[ -n "$fixes" ]]; then
        echo "$fixes"
    fi
    
    if [[ -n "$perf" ]]; then
        echo "$perf"
    fi
    
    if [[ -n "$refactor" ]]; then
        echo "$refactor"
    fi
    
    # Add improvements section for commits without conventional format
    echo ""
    echo "## Improvements"
    git log "$from_tag..$to_ref" --pretty="- %s" | \
        grep -v -E "^- (feat|fix|perf|refactor|chore|docs|style|test|build|ci)[(:]" | \
        head -10
    
    # Add technical details if available
    echo ""
    echo "## Technical Details"
    git diff "$from_tag..$to_ref" --stat | tail -5
    
    # Add Claude Code attribution
    echo ""
    echo "🤖 Generated with [Claude Code](https://claude.ai/code)"
}
```

## GitHub Release Creation

Creates GitHub release with generated changelog:

```bash
function create_github_release() {
    local version="$1"
    local changelog="$2"
    local draft="${3:-false}"
    
    # Check if gh CLI is available
    if ! command -v gh &> /dev/null; then
        echo "⚠️ GitHub CLI not found. Install with: brew install gh"
        return 1
    fi
    
    # Create release using gh CLI
    gh release create "$version" \
        --title "$version" \
        --notes "$changelog" \
        $([ "$draft" = "true" ] && echo "--draft") \
        --latest
    
    echo "✅ Release $version created successfully!"
    echo "📝 View at: $(gh release view "$version" --json url -q .url)"
}
```

## Release Preparation

Validates and prepares the release:

```bash
function prepare_release() {
    echo "🔍 Validating release requirements..."
    
    # Check working tree is clean
    if [[ -n $(git status --porcelain) ]]; then
        echo "❌ Working tree is not clean. Commit or stash changes."
        return 1
    fi
    
    # Run tests if available
    if [[ -f "Makefile" ]] && grep -q "^test:" Makefile; then
        echo "🧪 Running tests..."
        make test || return 1
    fi
    
    # Check branch is up to date
    git fetch origin
    local behind=$(git rev-list HEAD..origin/main --count)
    if [[ "$behind" -gt 0 ]]; then
        echo "⚠️ Branch is $behind commits behind origin/main"
        read -p "Pull latest changes? (y/n) " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            git pull origin main
        fi
    fi
    
    echo "✅ Release preparation complete!"
}
```

## Release Rollback

Emergency rollback functionality:

```bash
function rollback_release() {
    local target_version="${1:-$(git describe --tags --abbrev=0 HEAD~1)}"
    
    echo "🔄 Rolling back to $target_version"
    
    # Create rollback branch
    git checkout -b "rollback-to-$target_version"
    
    # Reset to target version
    git reset --hard "$target_version"
    
    # Force push if confirmed
    read -p "⚠️ Force push rollback? This will overwrite remote! (yes/no) " -r
    if [[ "$REPLY" == "yes" ]]; then
        git push --force-with-lease origin main
        echo "✅ Rolled back to $target_version"
    else
        echo "❌ Rollback cancelled"
    fi
}
```

## Release Status

Check current release status:

```bash
function release_status() {
    echo "📊 Release Status"
    echo "================"
    
    # Current version
    local current=$(git describe --tags --abbrev=0 2>/dev/null || echo "No releases")
    echo "Current Release: $current"
    
    # Commits since last release
    if [[ "$current" != "No releases" ]]; then
        local commits=$(git rev-list "$current..HEAD" --count)
        echo "Commits since release: $commits"
        
        if [[ "$commits" -gt 0 ]]; then
            echo ""
            echo "Recent changes:"
            git log "$current..HEAD" --oneline --max-count=5
        fi
    fi
    
    # Latest GitHub release
    if command -v gh &> /dev/null; then
        echo ""
        echo "GitHub Releases:"
        gh release list --limit 3
    fi
}
```

## Examples

### Example 1: Standard Patch Release
```bash
# Prepare and create patch release
/git/release prepare
/git/release create patch
```

### Example 2: Feature Release with Custom Message
```bash
# Create minor release for new feature
/git/release create minor --message "Added analytics dashboard"
```

### Example 3: Major Release with Draft
```bash
# Create draft major release for review
/git/release create major --draft
```

## Integration with Existing Workflow

This command integrates with the comprehensive release workflow at `/git/workflows/release` for advanced scenarios:

```bash
# Use full workflow for complex releases
/git/workflows/release --phase planning
/git/workflows/release --phase preparation
/git/workflows/release --phase staging
/git/workflows/release --phase production
```

## Configuration

Set default behaviors:

```bash
# Set default version bump type
git config --global claude.release.default-bump "patch"

# Set auto-push behavior
git config --global claude.release.auto-push "true"

# Set changelog format
git config --global claude.release.changelog-format "conventional"
```

## Best Practices

1. **Semantic Versioning**: Follow semver (major.minor.patch)
2. **Conventional Commits**: Use conventional commit format for better changelogs
3. **Testing**: Always run tests before releasing
4. **Documentation**: Update docs with release notes
5. **Rollback Plan**: Have a rollback strategy ready

## Troubleshooting

### Issue: GitHub CLI not authenticated
```bash
gh auth login
```

### Issue: GPG signing required
```bash
git config --global commit.gpgsign true
git config --global user.signingkey YOUR_KEY_ID
```

### Issue: Protected branch
Ensure you have permissions or use:
```bash
gh pr create --title "Release $version" --body "$changelog"
```