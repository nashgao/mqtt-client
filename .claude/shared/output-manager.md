# Output Manager Utility
# Centralized management for Claude Code output files
# Automatically creates changelog/ directory in project root for all summaries

## Core Functions

### Initialize Changelog Directory
```bash
init_changelog_dir() {
    local changelog_dir="changelog"
    local year_month=$(date +%Y-%m)
    
    # Create directory structure
    mkdir -p "${changelog_dir}/${year_month}"
    
    # Create index file if doesn't exist
    if [[ ! -f "${changelog_dir}/index.yaml" ]]; then
        cat > "${changelog_dir}/index.yaml" << 'EOF'
# Claude Code Changelog Index
# Auto-generated - Do not edit manually
entries: []
last_updated: null
EOF
    fi
    
    echo "${changelog_dir}/${year_month}"
}
```

### Generate Changelog Filename
```bash
generate_changelog_filename() {
    local type="${1:-summary}"  # summary, problems, changes, etc.
    local timestamp=$(date +%Y%m%d_%H%M%S)
    local year_month=$(date +%Y-%m)
    
    echo "changelog/${year_month}/${timestamp}_${type}.md"
}
```

### Save to Changelog
```bash
save_to_changelog() {
    local content="$1"
    local type="${2:-session_summary}"
    
    # Initialize directory
    local changelog_dir=$(init_changelog_dir)
    
    # Generate filename
    local filename=$(generate_changelog_filename "$type")
    
    # Write content
    echo "$content" > "$filename"
    
    # Update latest symlink
    update_latest_symlink "$filename"
    
    # Update index
    update_changelog_index "$filename" "$type"
    
    echo "💾 Saved to: $filename"
    echo "📍 Latest at: changelog/latest.md"
    
    return 0
}
```

### Update Latest Symlink
```bash
update_latest_symlink() {
    local target_file="$1"
    local latest_link="changelog/latest.md"
    
    # Remove old symlink if exists
    [[ -L "$latest_link" ]] && rm "$latest_link"
    
    # Create new symlink (relative path)
    local relative_path=$(realpath --relative-to="$(dirname "$latest_link")" "$target_file" 2>/dev/null || echo "$target_file")
    ln -s "$relative_path" "$latest_link"
}
```

### Update Changelog Index
```bash
update_changelog_index() {
    local filename="$1"
    local type="$2"
    local index_file="changelog/index.yaml"
    
    # Add entry to index (append to YAML)
    cat >> "$index_file" << EOF

- file: "$filename"
  type: "$type"
  timestamp: "$(date -Iseconds)"
  size: $(stat -f%z "$filename" 2>/dev/null || stat -c%s "$filename")
EOF
    
    # Update last_updated
    sed -i.bak "s/last_updated:.*/last_updated: $(date -Iseconds)/" "$index_file"
    rm -f "${index_file}.bak"
}
```

### Clean Old Changelog Entries
```bash
clean_old_changelog() {
    local days_to_keep="${1:-90}"
    local changelog_dir="changelog"
    
    # Find and remove old files (keep directory structure)
    find "$changelog_dir" -type f -mtime +$days_to_keep -name "*.md" -o -name "*.yaml" | \
        grep -v "index.yaml" | \
        while read file; do
            echo "Removing old changelog entry: $file"
            rm "$file"
        done
}
```

### List Recent Changelog Entries
```bash
list_recent_changelog() {
    local count="${1:-10}"
    local changelog_dir="changelog"
    
    echo "📋 Recent Changelog Entries:"
    echo "============================"
    
    find "$changelog_dir" -type f \( -name "*.md" -o -name "*.yaml" \) | \
        grep -v "index.yaml" | \
        xargs ls -lt 2>/dev/null | \
        head -n "$count" | \
        awk '{print $9, $10, $11}' | \
        while read file; do
            local basename=$(basename "$file")
            local type=$(echo "$basename" | sed 's/.*_\(.*\)\..*/\1/')
            echo "  - $basename ($type)"
        done
}
```

### Get Latest Changelog File
```bash
get_latest_changelog() {
    local type="${1:-}"  # Optional filter by type
    local changelog_dir="changelog"
    
    if [[ -n "$type" ]]; then
        find "$changelog_dir" -name "*_${type}.*" -type f | \
            xargs ls -t 2>/dev/null | \
            head -1
    else
        find "$changelog_dir" -type f \( -name "*.md" -o -name "*.yaml" \) | \
            grep -v "index.yaml" | \
            xargs ls -t 2>/dev/null | \
            head -1
    fi
}
```

## Usage Examples

### Save a session summary
```bash
# Automatically creates changelog/YYYY-MM/ directory and saves
save_to_changelog "# Session Summary\n\nCompleted feature X" "session_summary"
```

### Save a problem solution
```bash
save_to_changelog "# Problem: Performance Issue\n\nSolution: ..." "problems_solved"
```

### List recent changelog entries
```bash
list_recent_changelog 10   # Show last 10 entries
list_recent_changelog 20   # Show last 20 entries
```

### Get the latest changelog file
```bash
get_latest_changelog              # Any type
get_latest_changelog "summary"    # Only summaries
```

### Clean old changelog entries
```bash
clean_old_changelog 90   # Remove entries older than 90 days
clean_old_changelog 30   # Remove entries older than 30 days
```

## Integration with Commands

### From Session Command
```bash
# When ending a session
session_end() {
    # Generate session summary
    local summary="## Session Summary\n\n..."
    
    # Save to changelog
    local saved_file=$(save_to_changelog "$summary" "session_summary")
    
    echo "Session summary saved to: $saved_file"
    echo "Latest summary available at: changelog/latest.md"
}
```

### From Summarize Command
```bash
# When adding a problem
summarize_add() {
    # Generate problem entry
    local problem_yaml="..."
    
    # Save to changelog
    local saved_file=$(save_to_changelog "$problem_yaml" "problems_solved")
    
    echo "Problem documented in: $saved_file"
}
```

## Benefits

- **Zero Configuration**: Works immediately without setup
- **Clean Project Root**: No summary files in main directory
- **Automatic Organization**: Year-month folders created as needed
- **Easy Access**: Latest work always at `changelog/latest.md`
- **Historical Tracking**: All summaries preserved chronologically