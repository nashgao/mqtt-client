#!/bin/bash
# Space-Utils Standards Enforcer - Core Engine
# This is the main enforcement logic used by pre/post edit hooks

set -euo pipefail

# Configuration
CONFIG_FILE=".claude/config/space-utils.json"
ENFORCEMENT_LOG=".claude/logs/enforcement.log"
VIOLATIONS_FILE=".claude/logs/violations.json"
# Require SPACE_UTILS_PATH to be set
if [[ -z "${SPACE_UTILS_PATH:-}" ]]; then
    echo "⚠️ SPACE_UTILS_PATH not set. Skipping Space-Utils enforcement." >&2
    exit 0
fi

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Initialize
mkdir -p "$(dirname "$ENFORCEMENT_LOG")" 2>/dev/null || true
mkdir -p "$(dirname "$VIOLATIONS_FILE")" 2>/dev/null || true

# Logging function
log_enforcement() {
    local level="$1"
    local message="$2"
    echo "$(date -Iseconds) [$level] [ENFORCER] $message" >> "$ENFORCEMENT_LOG"
    
    if [[ "$level" == "VIOLATION" ]]; then
        echo -e "${RED}❌ VIOLATION: $message${NC}" >&2
    elif [[ "$level" == "BLOCK" ]]; then
        echo -e "${RED}🚫 BLOCKED: $message${NC}" >&2
    elif [[ "$level" == "SUCCESS" ]]; then
        echo -e "${GREEN}✅ $message${NC}"
    elif [[ "$level" == "INFO" ]]; then
        echo "ℹ️  $message"
    fi
}

# Check enforcement mode
get_enforcement_mode() {
    local mode="optional"
    
    if [[ -f "$CONFIG_FILE" ]]; then
        mode=$(jq -r '.space_utils.enforcement.mode // "optional"' "$CONFIG_FILE" 2>/dev/null || echo "optional")
    fi
    
    # Environment variable override
    mode="${SPACE_UTILS_ENFORCEMENT:-$mode}"
    
    echo "$mode"
}

# Check if enforcement is enabled
is_enforcement_enabled() {
    local mode=$(get_enforcement_mode)
    [[ "$mode" == "mandatory" || "$mode" == "strict" ]]
}

# Check if should block on violations
should_block_on_violation() {
    if [[ -f "$CONFIG_FILE" ]]; then
        jq -e '.space_utils.enforcement.block_on_violation == true' "$CONFIG_FILE" >/dev/null 2>&1
    else
        return 1
    fi
}

# Check if should auto-rollback
should_auto_rollback() {
    if [[ -f "$CONFIG_FILE" ]]; then
        jq -e '.space_utils.enforcement.auto_rollback == true' "$CONFIG_FILE" >/dev/null 2>&1
    else
        return 1
    fi
}

# Validate PHP file compliance
validate_space_utils_compliance() {
    local file_path="$1"
    local violations=()
    local violation_count=0
    
    if [[ ! "$file_path" =~ \.php$ ]]; then
        return 0
    fi
    
    if [[ ! -f "$file_path" ]]; then
        log_enforcement "WARN" "File not found: $file_path"
        return 0
    fi
    
    log_enforcement "INFO" "Validating: $file_path"
    
    # Load validation rules from config
    local rules_file="/tmp/validation_rules_$$.json"
    if [[ -f "$CONFIG_FILE" ]]; then
        jq '.space_utils.validation_rules // {}' "$CONFIG_FILE" > "$rules_file" 2>/dev/null || echo '{}' > "$rules_file"
    else
        echo '{}' > "$rules_file"
    fi
    
    # Check 1: Native string functions
    local string_functions=$(jq -r '.native_functions.string_functions.prohibited[]' "$rules_file" 2>/dev/null | paste -sd '|' -)
    if [[ -n "$string_functions" ]] && grep -qE "$string_functions" "$file_path"; then
        violations+=("NATIVE_STRING: Uses native PHP string functions instead of IString")
        ((violation_count++))
        
        if [[ "$(get_enforcement_mode)" == "strict" ]]; then
            grep -nE "$string_functions" "$file_path" | while read -r line; do
                log_enforcement "VIOLATION" "Line $(echo "$line" | cut -d: -f1): $(echo "$line" | cut -d: -f2-)"
            done
        fi
    fi
    
    # Check 2: Native array functions
    local array_functions=$(jq -r '.native_functions.array_functions.prohibited[]' "$rules_file" 2>/dev/null | paste -sd '|' -)
    if [[ -n "$array_functions" ]] && grep -qE "$array_functions" "$file_path"; then
        violations+=("NATIVE_ARRAY: Uses native PHP array functions instead of Collection")
        ((violation_count++))
        
        if [[ "$(get_enforcement_mode)" == "strict" ]]; then
            grep -nE "$array_functions" "$file_path" | while read -r line; do
                log_enforcement "VIOLATION" "Line $(echo "$line" | cut -d: -f1): $(echo "$line" | cut -d: -f2-)"
            done
        fi
    fi
    
    # Check 3: Native file functions
    local file_functions=$(jq -r '.native_functions.file_functions.prohibited[]' "$rules_file" 2>/dev/null | paste -sd '|' -)
    if [[ -n "$file_functions" ]] && grep -qE "$file_functions" "$file_path"; then
        violations+=("NATIVE_FILE: Uses native PHP file functions instead of File::of()")
        ((violation_count++))
    fi
    
    # Check 4: declare(strict_types=1)
    if ! grep -q "declare(strict_types=1)" "$file_path"; then
        violations+=("STRICT_TYPES: Missing declare(strict_types=1)")
        ((violation_count++))
    fi
    
    # Check 5: Factory pattern violations
    if [[ "$file_path" =~ Factory\.php$ ]]; then
        local create_count=$(grep -c "public.*function create" "$file_path" || echo 0)
        local min_variants=$(jq -r '.patterns.factory_pattern.min_variants // 3' "$rules_file")
        if [[ $create_count -lt $min_variants ]]; then
            violations+=("FACTORY_PATTERN: Factory with <$min_variants variants (found $create_count)")
            ((violation_count++))
        fi
    fi
    
    # Check 6: Mixed types without justification
    if grep -q "mixed" "$file_path" && ! grep -q "@phpstan-ignore\|@justified" "$file_path"; then
        violations+=("MIXED_TYPE: Uses 'mixed' type without justification")
        ((violation_count++))
    fi
    
    # Save violations
    if [[ ${#violations[@]} -gt 0 ]]; then
        echo "{
    \"file\": \"$file_path\",
    \"timestamp\": \"$(date -Iseconds)\",
    \"mode\": \"$(get_enforcement_mode)\",
    \"violation_count\": $violation_count,
    \"violations\": [
        $(printf '"%s",' "${violations[@]}" | sed 's/,$//')
    ]
}" > "$VIOLATIONS_FILE"
        
        if is_enforcement_enabled; then
            log_enforcement "VIOLATION" "Found $violation_count violation(s) in $file_path"
        fi
    else
        log_enforcement "SUCCESS" "No violations found in $file_path"
    fi
    
    # Cleanup
    rm -f "$rules_file"
    
    return $violation_count
}

# Apply automatic transformations
apply_space_utils_transformations() {
    local file_path="$1"
    local transformed=false
    
    if [[ ! "$file_path" =~ \.php$ ]] || [[ ! -f "$file_path" ]]; then
        return 0
    fi
    
    log_enforcement "INFO" "Attempting automatic transformations on: $file_path"
    
    # Create backup
    local backup_file="$file_path.backup.$(date +%s)"
    cp "$file_path" "$backup_file"
    log_enforcement "INFO" "Created backup: $backup_file"
    
    # Add use statements if missing
    if ! grep -q "use SpacePlatform\\\\Utils" "$file_path"; then
        if grep -q "^namespace" "$file_path"; then
            sed -i '/^namespace/a\
\
use SpacePlatform\\Utils\\Functional\\Monad\\Scalar\\IString;\
use SpacePlatform\\Utils\\Structure\\Collection;\
use SpacePlatform\\Utils\\FileSystem\\Component\\File\\File;' "$file_path"
            transformed=true
            log_enforcement "SUCCESS" "Added Space-Utils use statements"
        fi
    fi
    
    # Add declare(strict_types=1) if missing
    if ! grep -q "declare(strict_types=1)" "$file_path"; then
        if grep -q "<?php" "$file_path"; then
            sed -i '/<\?php/a\
declare(strict_types=1);' "$file_path"
            transformed=true
            log_enforcement "SUCCESS" "Added declare(strict_types=1)"
        fi
    fi
    
    # Run Space-Utils transformer if available
    local transformer="${SPACE_UTILS_PATH}/.claude/hooks/space-utils-transformer.php"
    if [[ -f "$transformer" ]]; then
        log_enforcement "INFO" "Running Space-Utils transformer"
        if php "$transformer" "$file_path" 2>/dev/null; then
            transformed=true
            log_enforcement "SUCCESS" "Space-Utils transformer completed"
        fi
    fi
    
    if $transformed; then
        log_enforcement "SUCCESS" "Transformations applied to: $file_path"
        return 0
    else
        log_enforcement "INFO" "No transformations needed for: $file_path"
        return 1
    fi
}

# Main enforcement function
enforce_standards() {
    local file_path="$1"
    local operation="${2:-validate}"  # validate, transform, or check
    
    # Check if enforcement is enabled
    if ! is_enforcement_enabled && [[ "$operation" != "check" ]]; then
        log_enforcement "INFO" "Enforcement disabled (mode: $(get_enforcement_mode))"
        return 0
    fi
    
    # Validate file
    local violation_count=0
    validate_space_utils_compliance "$file_path" || violation_count=$?
    
    if [[ $violation_count -gt 0 ]]; then
        if [[ "$operation" == "transform" ]]; then
            log_enforcement "INFO" "Attempting automatic transformation..."
            apply_space_utils_transformations "$file_path"
            
            # Re-validate after transformation
            local new_violation_count=0
            validate_space_utils_compliance "$file_path" || new_violation_count=$?
            
            if [[ $new_violation_count -eq 0 ]]; then
                log_enforcement "SUCCESS" "All violations fixed through transformation"
                return 0
            elif [[ $new_violation_count -lt $violation_count ]]; then
                log_enforcement "INFO" "Reduced violations from $violation_count to $new_violation_count"
                violation_count=$new_violation_count
            fi
        fi
        
        # Check if we should block
        if should_block_on_violation; then
            log_enforcement "BLOCK" "Operation blocked due to $violation_count violation(s)"
            
            echo -e "${RED}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
            echo -e "${RED}🚫 SPACE-UTILS STANDARDS VIOLATION - OPERATION BLOCKED${NC}"
            echo -e "${RED}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
            echo ""
            echo -e "${YELLOW}Fix the violations or disable enforcement mode.${NC}"
            echo -e "${YELLOW}Violations log: $VIOLATIONS_FILE${NC}"
            echo ""
            
            return 1
        else
            log_enforcement "WARN" "Found $violation_count violation(s) but not blocking (enforcement mode: $(get_enforcement_mode))"
        fi
    fi
    
    return 0
}

# Entry point
main() {
    local file_path="${1:-}"
    local operation="${2:-validate}"
    
    if [[ -z "$file_path" ]]; then
        echo "Usage: $0 <file_path> [validate|transform|check]"
        exit 1
    fi
    
    # Run enforcement
    enforce_standards "$file_path" "$operation"
}

# Run if not sourced
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    main "$@"
fi