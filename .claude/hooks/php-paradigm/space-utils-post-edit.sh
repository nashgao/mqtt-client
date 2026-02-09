#!/bin/bash
# Enhanced PHP Paradigm Post-Edit Hook with Space-Utils Integration
# Applies space-utils transformations and coding standards after Claude Code makes changes

set -euo pipefail

# Smart detection function for space-utils path
detect_space_utils_path() {
    # 1. Check explicit environment variable
    if [[ -n "${SPACE_UTILS_PATH:-}" ]]; then
        if [[ -d "$SPACE_UTILS_PATH" ]]; then
            echo "$SPACE_UTILS_PATH"
            return
        fi
    fi
    
    # 2. Check vendor directory (composer install)
    if [[ -d "vendor/space-platform/utils" ]]; then
        echo "$(pwd)/vendor/space-platform/utils"
        return
    fi
    
    # 3. Check common development paths relative to project
    local potential_paths=(
        "../space-utils"
        "../../space-utils"
        "../lib/space-utils"
        "../../lib/space-utils"
        "../dependencies/lib/space-utils"
        "../../dependencies/lib/space-utils"
        "$HOME/Desktop/project/space/dependencies/lib/space-utils"
    )
    
    for path in "${potential_paths[@]}"; do
        if [[ -d "$path" ]]; then
            echo "$(cd "$path" && pwd)"
            return
        fi
    done
    
    # 4. Not found - return empty
    echo ""
}

# Configuration with smart detection
SPACE_UTILS_PATH="$(detect_space_utils_path)"
PHP_PARADIGM_PATH="${PHP_PARADIGM_PATH:-$SPACE_UTILS_PATH/coding-standards}"
HOOK_NAME="space-utils-post-edit"
LOG_FILE=".claude/logs/hooks.log"

# Logging function
log() {
    local level="$1"
    local message="$2"
    echo "$(date -Iseconds) [$level] [$HOOK_NAME] $message" >> "$LOG_FILE" 2>/dev/null || true
    if [[ "$level" == "ERROR" || "$level" == "WARN" ]]; then
        echo "🔧 $message" >&2
    fi
}

# Initialize
mkdir -p "$(dirname "$LOG_FILE")" 2>/dev/null || true
log "INFO" "Starting space-utils post-edit processing"

# Get file being edited
FILE_PATH="${1:-}"
if [[ -z "$FILE_PATH" ]]; then
    log "WARN" "No file path provided"
    exit 0
fi

log "INFO" "Processing file: $FILE_PATH"

# Track if file was modified
FILE_MODIFIED=false

# STEP 1: Apply space-utils transformation for PHP files
if [[ "$FILE_PATH" == *.php && -f "$FILE_PATH" ]]; then
    log "INFO" "Running space-utils transformation for: $FILE_PATH"
    
    # Look for space-utils transformer in multiple locations
    TRANSFORMER=""
    if [[ -x ".claude/hooks/space-utils-transformer.php" ]]; then
        TRANSFORMER=".claude/hooks/space-utils-transformer.php"
    elif [[ -x "$SPACE_UTILS_PATH/.claude/hooks/space-utils-transformer.php" ]]; then
        TRANSFORMER="$SPACE_UTILS_PATH/.claude/hooks/space-utils-transformer.php"
    fi
    
    if [[ -n "$TRANSFORMER" ]]; then
        log "INFO" "Using transformer: $TRANSFORMER"
        if php "$TRANSFORMER" -f "$FILE_PATH" 2>/dev/null; then
            FILE_MODIFIED=true
            echo "✅ Space-utils transformations applied to: $FILE_PATH"
            log "INFO" "Space-utils transformation successful"
        else
            log "WARN" "Space-utils transformation failed or no changes needed"
        fi
    else
        log "WARN" "Space-utils transformer not found"
    fi
fi

# STEP 2: Apply PHP coding standards
if [[ "$FILE_PATH" == *.php && -f "$FILE_PATH" ]]; then
    log "INFO" "Applying PHP coding standards for: $FILE_PATH"
    
    # Add strict types if missing
    if ! grep -q "declare(strict_types=1)" "$FILE_PATH"; then
        if grep -q "<?php" "$FILE_PATH"; then
            log "INFO" "Adding declare(strict_types=1)"
            {
                head -n 1 "$FILE_PATH"
                echo "declare(strict_types=1);"
                echo ""
                tail -n +2 "$FILE_PATH"
            } > "$FILE_PATH.tmp" && mv "$FILE_PATH.tmp" "$FILE_PATH"
            FILE_MODIFIED=true
            echo "✅ Added declare(strict_types=1) to $FILE_PATH"
        fi
    fi
    
    # Apply namespace conventions (snake_case for properties)
    # This is a simplified check - real implementation would be more sophisticated
    if grep -q "private \$[a-z][a-zA-Z]*;" "$FILE_PATH"; then
        log "INFO" "Converting property names to snake_case"
        # Create backup
        cp "$FILE_PATH" "$FILE_PATH.autofix-backup.$(date +%s).$(openssl rand -hex 8)"
        
        # Convert camelCase properties to snake_case
        perl -pi -e 's/private \$([a-z])([a-zA-Z]*);/"private \$" . lc($1) . lc($2) =~ s|([A-Z])|"_" . lc($1)|ger . ";"/ge' "$FILE_PATH"
        perl -pi -e 's/protected \$([a-z])([a-zA-Z]*);/"protected \$" . lc($1) . lc($2) =~ s|([A-Z])|"_" . lc($1)|ger . ";"/ge' "$FILE_PATH"
        perl -pi -e 's/public \$([a-z])([a-zA-Z]*);/"public \$" . lc($1) . lc($2) =~ s|([A-Z])|"_" . lc($1)|ger . ";"/ge' "$FILE_PATH"
        
        FILE_MODIFIED=true
        echo "✅ Property names converted to snake_case in $FILE_PATH"
    fi
    
    # Check for required annotations
    if grep -q "class .* {" "$FILE_PATH"; then
        if ! grep -B5 "^class " "$FILE_PATH" | grep -q "/\*\*"; then
            log "WARN" "Class missing PHPDoc documentation in: $FILE_PATH"
            echo "⚠️  MANUAL FIX NEEDED: Add PHPDoc documentation to class in $FILE_PATH"
        fi
    fi
    
    # Check for superglobal usage (forbidden in space-utils standards)
    FORBIDDEN_GLOBALS='$GLOBALS|$_POST|$_GET|$_REQUEST|$_SESSION|$_COOKIE'
    if grep -E "$FORBIDDEN_GLOBALS" "$FILE_PATH" 2>/dev/null; then
        log "ERROR" "Forbidden superglobals found in: $FILE_PATH"
        echo "❌ ERROR: Forbidden superglobals found! Use Hyperf dependency injection instead"
        echo "   Replace: $_POST, $_GET → Request object"
        echo "   Replace: $_SESSION → Session service"
        echo "   Replace: $_COOKIE → Cookie service"
    fi
fi

# STEP 3: Apply test file standards
if [[ "$FILE_PATH" == *Test.php || "$FILE_PATH" == test/* || "$FILE_PATH" == tests/* ]]; then
    if [[ -f "$FILE_PATH" ]]; then
        log "INFO" "Applying test standards for: $FILE_PATH"
        
        # Check for PSR-4 test namespace
        if grep -q "^namespace " "$FILE_PATH"; then
            NAMESPACE=$(grep "^namespace " "$FILE_PATH" | head -1 | sed 's/namespace \(.*\);/\1/')
            if [[ ! "$NAMESPACE" == *"\\Test\\"* ]] && [[ ! "$NAMESPACE" == *"\\Tests\\"* ]]; then
                log "WARN" "Test file namespace doesn't follow PSR-4 convention: $NAMESPACE"
                echo "⚠️  MANUAL FIX: Update namespace to include \\Test\\ or \\Tests\\"
            fi
        fi
        
        # Add test groups if missing
        if ! grep -q "#\[Group(" "$FILE_PATH" && grep -q "class.*Test" "$FILE_PATH"; then
            GROUPS=""
            
            if [[ "$FILE_PATH" == */Unit/* ]]; then
                GROUPS="#[Group('unit')]"
            elif [[ "$FILE_PATH" == */Integration/* ]]; then
                GROUPS="#[Group('integration')]"
            elif [[ "$FILE_PATH" == */Performance/* ]]; then
                GROUPS="#[Group('performance')]"
            fi
            
            if [[ -n "$GROUPS" ]]; then
                log "INFO" "Adding test groups"
                sed -i.bak "s/class \(.*\)Test/$GROUPS\nclass \1Test/" "$FILE_PATH" && rm -f "$FILE_PATH.bak"
                FILE_MODIFIED=true
                echo "✅ Added test groups to $FILE_PATH"
            fi
        fi
        
        # Check for test documentation
        if grep -q "public function test" "$FILE_PATH"; then
            if ! grep -B3 "public function test" "$FILE_PATH" | grep -q "/\*\*"; then
                log "WARN" "Test methods missing documentation"
                echo "⚠️  RECOMMENDATION: Add PHPDoc to test methods explaining intent and test case rationale"
            fi
        fi
    fi
fi

# STEP 4: Clean up autofix backups after successful processing
if [[ "$FILE_MODIFIED" == true ]]; then
    log "INFO" "Cleaning up autofix backup files"
    find "$(dirname "$FILE_PATH")" -name "*.autofix-backup.*" -type f -mmin +1 -delete 2>/dev/null || true
fi

# STEP 5: Run additional validation if available
if [[ -x "$PHP_PARADIGM_PATH/validation/pre-edit-validator.php" ]]; then
    log "INFO" "Running additional validation"
    if php "$PHP_PARADIGM_PATH/validation/pre-edit-validator.php" "$FILE_PATH" 2>/dev/null; then
        log "INFO" "Additional validation passed"
    else
        log "WARN" "Additional validation found issues - check output above"
    fi
fi

# Report final status
if [[ "$FILE_MODIFIED" == true ]]; then
    echo "✅ Space-utils standards and transformations applied successfully"
    log "INFO" "Post-edit processing completed with modifications"
else
    log "INFO" "Post-edit processing completed without modifications"
fi

exit 0