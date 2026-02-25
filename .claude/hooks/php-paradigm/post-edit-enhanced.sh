#!/bin/bash
# Enhanced PHP Paradigm Post-Edit Hook with Space-Utils Mandatory Mode
# Applies coding standards after Claude Code makes changes

set -euo pipefail

# Configuration
PHP_PARADIGM_PATH="${PHP_PARADIGM_PATH:-/path/to/your/php-paradigm-standards}"
# Require SPACE_UTILS_PATH to be set
if [[ -z "${SPACE_UTILS_PATH:-}" ]]; then
    echo "⚠️ SPACE_UTILS_PATH not set. Skipping Space-Utils checks." >&2
    exit 0
fi
HOOK_NAME="post-edit"
LOG_FILE=".claude/logs/hooks.log"
CONFIG_FILE=".claude/config/space-utils.json"

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Logging function
log() {
    local level="$1"
    local message="$2"
    echo "$(date -Iseconds) [$level] [$HOOK_NAME] $message" >> "$LOG_FILE" 2>/dev/null || true
    if [[ "$level" == "ERROR" || "$level" == "WARN" ]]; then
        echo "🔧 $message" >&2
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

# Initialize
mkdir -p "$(dirname "$LOG_FILE")" 2>/dev/null || true
log "INFO" "Starting post-edit validation and fixes (mode: $(get_enforcement_mode))"

# Get file being edited from arguments
FILE_PATH="${1:-}"
if [[ -z "$FILE_PATH" ]]; then
    log "WARN" "No file path provided"
    exit 0
fi

log "INFO" "Processing file: $FILE_PATH"

# Check enforcement mode
ENFORCEMENT_MODE=$(get_enforcement_mode)

# For mandatory/strict mode, use the enforcer
if [[ "$ENFORCEMENT_MODE" == "mandatory" || "$ENFORCEMENT_MODE" == "strict" ]]; then
    log "INFO" "Running Space-Utils mandatory enforcement"
    
    # Check if enforcer exists
    ENFORCER=".claude/hooks/php-paradigm/space-utils-enforcer.sh"
    if [[ ! -f "$ENFORCER" ]]; then
        # Try to use from template
        ENFORCER="${SPACE_UTILS_PATH}/.claude/hooks/space-utils-enforcer.sh"
        if [[ ! -f "$ENFORCER" ]]; then
            log "ERROR" "Space-Utils enforcer not found"
            echo -e "${RED}❌ ERROR: Space-Utils enforcer not installed${NC}" >&2
            echo "Run: claude-merge install-space-utils-enforcement" >&2
            exit 1
        fi
    fi
    
    # Make enforcer executable if needed
    [[ -x "$ENFORCER" ]] || chmod +x "$ENFORCER"
    
    # Run transformation first
    log "INFO" "Attempting automatic transformations"
    "$ENFORCER" "$FILE_PATH" "transform"
    
    # Then validate
    if ! "$ENFORCER" "$FILE_PATH" "validate"; then
        log "ERROR" "File violates mandatory Space-Utils standards"
        
        # Check if we should rollback
        if [[ -f "$CONFIG_FILE" ]] && jq -e '.space_utils.enforcement.auto_rollback == true' "$CONFIG_FILE" >/dev/null 2>&1; then
            # Find latest backup
            BACKUP=$(ls -t "$FILE_PATH.backup."* 2>/dev/null | head -1)
            if [[ -n "$BACKUP" && -f "$BACKUP" ]]; then
                log "WARN" "Rolling back to: $BACKUP"
                cp "$BACKUP" "$FILE_PATH"
                echo -e "${YELLOW}⚠️  File rolled back due to standards violations${NC}"
            fi
        fi
        
        # Exit with error for mandatory mode
        echo -e "${RED}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
        echo -e "${RED}❌ POST-EDIT BLOCKED: Space-Utils standards violations detected${NC}"
        echo -e "${RED}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
        exit 1
    else
        log "INFO" "File complies with Space-Utils standards"
        echo -e "${GREEN}✅ Space-Utils standards validation passed${NC}"
    fi
else
    # Optional mode - run normal PHP paradigm fixes
    log "INFO" "Running optional PHP paradigm fixes"
    
    # Check if PHP paradigm standards exist
    if [[ ! -d "$PHP_PARADIGM_PATH" ]]; then
        log "WARN" "PHP paradigm standards not found at: $PHP_PARADIGM_PATH"
        # Don't exit, continue with basic fixes
    fi
    
    # Track if file was modified
    FILE_MODIFIED=false
    
    # PHP file auto-fixes
    if [[ "$FILE_PATH" == *.php && -f "$FILE_PATH" ]]; then
        log "INFO" "Running PHP auto-fixes for: $FILE_PATH"
        
        # Add strict types if missing
        if ! grep -q "declare(strict_types=1)" "$FILE_PATH"; then
            if grep -q "<?php" "$FILE_PATH"; then
                log "INFO" "Adding declare(strict_types=1) to: $FILE_PATH"
                {
                    head -n 1 "$FILE_PATH"
                    echo "declare(strict_types=1);"
                    echo ""
                    tail -n +2 "$FILE_PATH"
                } > "$FILE_PATH.tmp" && mv "$FILE_PATH.tmp" "$FILE_PATH"
                FILE_MODIFIED=true
                echo "✅ AUTO-FIXED: Added declare(strict_types=1) to $FILE_PATH"
            fi
        fi
        
        # Basic constant type checking
        if grep -q "const [A-Z_]* =" "$FILE_PATH" && ! grep -q "const [A-Z_]*: " "$FILE_PATH"; then
            log "WARN" "Untyped constants found in: $FILE_PATH - manual fix required"
            echo "⚠️  MANUAL FIX NEEDED: Add type declarations to constants in $FILE_PATH"
        fi
        
        # Check for Space-Utils patterns if config exists
        if [[ -f "$CONFIG_FILE" ]] && jq -e '.space_utils.enabled == true' "$CONFIG_FILE" >/dev/null 2>&1; then
            # Check for native functions that should use Space-Utils
            if grep -qE "is_string|strlen|substr|array_map|array_filter|file_get_contents" "$FILE_PATH"; then
                echo "💡 TIP: Consider using Space-Utils functions (IString, Collection, File) for better performance"
                echo "   Enable mandatory mode to enforce Space-Utils patterns: export SPACE_UTILS_ENFORCEMENT=mandatory"
            fi
        fi
    fi
    
    # Test file auto-fixes
    if [[ "$FILE_PATH" == *Test.php || "$FILE_PATH" == test/* || "$FILE_PATH" == tests/* ]]; then
        if [[ -f "$FILE_PATH" ]]; then
            log "INFO" "Running test auto-fixes for: $FILE_PATH"
            # Test group addition logic here...
        fi
    fi
    
    # Run PHP paradigm auto-fixer if available
    PHP_PARADIGM_FIXER="$PHP_PARADIGM_PATH/tools/auto-fixer.php"
    if [[ -f "$PHP_PARADIGM_FIXER" ]]; then
        log "INFO" "Running PHP paradigm auto-fixer"
        if php "$PHP_PARADIGM_FIXER" "$FILE_PATH" 2>/dev/null; then
            FILE_MODIFIED=true
        fi
    fi
    
    # Run code formatting if available
    if command -v php-cs-fixer >/dev/null 2>&1; then
        log "INFO" "Running PHP-CS-Fixer"
        if php-cs-fixer fix "$FILE_PATH" --quiet 2>/dev/null; then
            FILE_MODIFIED=true
        fi
    fi
    
    if [[ "$FILE_MODIFIED" == "true" ]]; then
        log "INFO" "File automatically modified: $FILE_PATH"
        echo "🔧 PHP paradigm auto-fixes applied to $FILE_PATH"
    else
        log "INFO" "No auto-fixes needed for: $FILE_PATH"
    fi
fi

# Clean up old backups (keep only last 5)
if ls "$FILE_PATH.backup."* >/dev/null 2>&1; then
    ls -t "$FILE_PATH.backup."* 2>/dev/null | tail -n +6 | xargs -r rm
    log "INFO" "Cleaned up old backup files"
fi

log "INFO" "Post-edit processing completed for: $FILE_PATH"
exit 0