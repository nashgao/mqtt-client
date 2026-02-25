# Milestone Output Adapter
# Provides compatibility layer for milestone commands to use new output structure

> **Pattern Documentation**: For the full Milestone Output Adapter pattern implementation, see [docs/patterns/milestone-output-adapter/](../docs/patterns/milestone-output-adapter/)

## Path Mappings

Maps legacy `.milestones/` paths to new `.claude/outputs/` structure while maintaining backward compatibility.

### Core Functions

```bash
# Get the appropriate output path for milestone data
get_milestone_output_path() {
    local type="${1:-status}"
    local milestone_id="${2:-current}"
    local base_path=".claude/outputs"
    
    case "${type}" in
        deliverables)
            echo "${base_path}/artifacts/milestones/${milestone_id}"
            ;;
        logs)
            echo "${base_path}/logs/milestones/${milestone_id}"
            ;;
        exports)
            echo "${base_path}/exports/milestones/${milestone_id}"
            ;;
        reports)
            echo "${base_path}/reports/milestones/${milestone_id}"
            ;;
        status)
            echo "${base_path}/changelog/$(date +%Y-%m)/milestone_${milestone_id}_status.md"
            ;;
        *)
            # Fallback to legacy path for compatibility
            echo ".milestones/${type}/${milestone_id}"
            ;;
    esac
}

# Create symlinks for backward compatibility
create_milestone_symlinks() {
    local milestone_id="$1"
    
    # Create backward compatibility symlinks
    mkdir -p ".milestones"
    
    # Link deliverables
    local new_path=".claude/outputs/artifacts/milestones/${milestone_id}"
    local old_path=".milestones/deliverables/${milestone_id}"
    if [[ -d "${new_path}" && ! -e "${old_path}" ]]; then
        mkdir -p "$(dirname "${old_path}")"
        ln -s "../../${new_path}" "${old_path}"
    fi
    
    # Link logs
    new_path=".claude/outputs/logs/milestones/${milestone_id}"
    old_path=".milestones/logs/${milestone_id}"
    if [[ -d "${new_path}" && ! -e "${old_path}" ]]; then
        mkdir -p "$(dirname "${old_path}")"
        ln -s "../../${new_path}" "${old_path}"
    fi
    
    echo "✅ Created compatibility symlinks for milestone ${milestone_id}"
}

# Save milestone output with proper organization
save_milestone_output() {
    local content="$1"
    local milestone_id="${2:-current}"
    local output_type="${3:-status}"
    local description="${4:-update}"
    
    # Get the appropriate output path
    local output_path=$(get_milestone_output_path "${output_type}" "${milestone_id}")
    
    # Create directory if needed
    mkdir -p "$(dirname "${output_path}")"
    
    # Save the content
    echo "${content}" > "${output_path}"
    
    # Create backward compatibility symlinks
    create_milestone_symlinks "${milestone_id}"
    
    # Update latest symlink for status reports
    if [[ "${output_type}" == "status" ]]; then
        local latest_link=".claude/outputs/changelog/milestone_latest.md"
        [[ -L "${latest_link}" ]] && rm "${latest_link}"
        ln -s "../${output_path#.claude/outputs/}" "${latest_link}"
    fi
    
    echo "💾 Milestone output saved to: ${output_path}"
}

# Export milestone report
export_milestone_report() {
    local milestone_id="$1"
    local format="${2:-md}"
    local custom_path="${3:-}"
    
    # Determine output path
    local output_path
    if [[ -n "${custom_path}" ]]; then
        output_path="${custom_path}"
    else
        output_path=".claude/outputs/exports/milestones/$(date +%Y%m%d)_milestone_${milestone_id}.${format}"
    fi
    
    # Create directory if needed
    mkdir -p "$(dirname "${output_path}")"
    
    # Generate and save report (placeholder - actual generation would be milestone-specific)
    echo "# Milestone ${milestone_id} Report" > "${output_path}"
    echo "Generated: $(date)" >> "${output_path}"
    
    echo "📤 Exported milestone report to: ${output_path}"
}
```

## Migration Support

### Automatic Path Resolution

```bash
# Resolve path to new structure with fallback
resolve_milestone_path() {
    local legacy_path="$1"
    
    # Check if already using new structure
    if [[ "${legacy_path}" =~ ^\.claude/outputs/ ]]; then
        echo "${legacy_path}"
        return
    fi
    
    # Map legacy paths to new structure
    case "${legacy_path}" in
        .milestones/deliverables/*)
            echo "${legacy_path//.milestones\/deliverables/.claude\/outputs\/artifacts\/milestones}"
            ;;
        .milestones/logs/*)
            echo "${legacy_path//.milestones\/logs/.claude\/outputs\/logs\/milestones}"
            ;;
        .milestones/exports/*)
            echo "${legacy_path//.milestones\/exports/.claude\/outputs\/exports\/milestones}"
            ;;
        .milestones/web-dashboard*)
            echo "${legacy_path//.milestones\/web-dashboard/.claude\/outputs\/artifacts\/dashboard}"
            ;;
        *)
            # Return original path if no mapping found
            echo "${legacy_path}"
            ;;
    esac
}
```

### Compatibility Layer

```bash
# Ensure both old and new paths work
ensure_milestone_compatibility() {
    # Check if .milestones exists and is not a symlink
    if [[ -d ".milestones" && ! -L ".milestones" ]]; then
        echo "⚠️  Legacy .milestones directory detected"
        echo "Creating compatibility symlinks..."
        
        # Create symlinks for each subdirectory
        for dir in deliverables logs exports; do
            if [[ -d ".milestones/${dir}" ]]; then
                local new_dir=".claude/outputs/${dir}/milestones"
                mkdir -p "${new_dir}"
                
                # Copy existing files to new location
                cp -r ".milestones/${dir}"/* "${new_dir}/" 2>/dev/null || true
                
                echo "✅ Migrated .milestones/${dir} to ${new_dir}"
            fi
        done
    fi
}
```

## Integration Examples

### In Milestone Commands

```bash
# At the top of milestone command files
source_milestone_adapter() {
    # Source the milestone output adapter
    # (Functions would be injected during template processing)
    :
}

# When saving milestone status
save_milestone_output "${status_content}" "milestone-001" "status" "progress-update"

# When exporting reports
export_milestone_report "milestone-001" "html"

# When resolving paths
deliverable_path=$(resolve_milestone_path ".milestones/deliverables/task-001/design")
```

### Path Resolution in Existing Code

Replace direct path references:

```bash
# Old way
local config_file=".milestones/config/ui-config.yaml"

# New way with compatibility
local config_file=$(resolve_milestone_path ".milestones/config/ui-config.yaml")
```

## Transition Timeline

1. **Phase 1 (Immediate)**: Both paths work via symlinks
2. **Phase 2 (3 months)**: Deprecation warnings for legacy paths
3. **Phase 3 (6 months)**: Remove legacy path support

## Benefits

- ✅ No breaking changes to existing milestone commands
- ✅ Gradual migration path
- ✅ Centralized output organization
- ✅ Backward compatibility maintained
- ✅ Clean separation of concerns