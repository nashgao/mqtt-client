#!/bin/bash
# Enhanced PHP Paradigm Pre-Edit Hook with Space-Utils Integration
# Validates and prepares files before Claude Code makes changes

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
HOOK_NAME="space-utils-pre-edit"
LOG_FILE=".claude/logs/hooks.log"

# Logging function
log() {
    local level="$1"
    local message="$2"
    echo "$(date -Iseconds) [$level] [$HOOK_NAME] $message" >> "$LOG_FILE" 2>/dev/null || true
    if [[ "$level" == "ERROR" || "$level" == "WARN" ]]; then
        echo "🔍 $message" >&2
    fi
}

# Initialize
mkdir -p "$(dirname "$LOG_FILE")" 2>/dev/null || true
log "INFO" "Starting space-utils pre-edit validation"

# Get file being edited
FILE_PATH="${1:-}"
if [[ -z "$FILE_PATH" ]]; then
    log "WARN" "No file path provided"
    exit 0
fi

log "INFO" "Pre-edit validation for: $FILE_PATH"

# Check if space-utils was found
if [[ -z "$SPACE_UTILS_PATH" ]]; then
    log "WARN" "Space-utils not found. Set SPACE_UTILS_PATH environment variable or install via composer"
    echo "⚠️  Space-utils not found. To enable space-utils integration:"
    echo "   • Set environment variable: export SPACE_UTILS_PATH=/path/to/space-utils"
    echo "   • Or install via composer: composer require space-platform/utils"
fi

# STEP 1: Check for native PHP functions that should use space-utils
if [[ "$FILE_PATH" == *.php && -f "$FILE_PATH" ]]; then
    log "INFO" "Checking for native PHP functions that should use space-utils"
    
    # Look for function registry
    REGISTRY=""
    if [[ -f ".claude/mappings/function-registry.json" ]]; then
        REGISTRY=".claude/mappings/function-registry.json"
    elif [[ -f "$SPACE_UTILS_PATH/.claude/mappings/function-registry.json" ]]; then
        REGISTRY="$SPACE_UTILS_PATH/.claude/mappings/function-registry.json"
    fi
    
    if [[ -n "$REGISTRY" ]]; then
        # Check for common native functions
        NATIVE_FUNCTIONS=(
            "array_map"
            "array_filter"
            "array_reduce"
            "file_get_contents"
            "file_put_contents"
            "json_encode"
            "json_decode"
            "curl_init"
            "fopen"
            "fwrite"
        )
        
        for func in "${NATIVE_FUNCTIONS[@]}"; do
            if grep -q "\b$func\b" "$FILE_PATH" 2>/dev/null; then
                log "WARN" "Found native PHP function '$func' - should use space-utils equivalent"
                echo "⚠️  RECOMMENDATION: Replace '$func' with space-utils equivalent"
                
                # Try to find the space-utils equivalent from registry
                if command -v jq >/dev/null 2>&1 && [[ -f "$REGISTRY" ]]; then
                    SPACE_UTILS_FUNC=$(jq -r ".\"$func\".space_utils // empty" "$REGISTRY" 2>/dev/null)
                    if [[ -n "$SPACE_UTILS_FUNC" && "$SPACE_UTILS_FUNC" != "null" ]]; then
                        echo "   Use: $SPACE_UTILS_FUNC instead of $func"
                    fi
                fi
            fi
        done
    fi
fi

# STEP 2: Validate PHP coding standards compliance
if [[ "$FILE_PATH" == *.php && -f "$FILE_PATH" ]]; then
    log "INFO" "Validating PHP coding standards"
    
    # Check for missing strict types
    if ! grep -q "declare(strict_types=1)" "$FILE_PATH"; then
        if grep -q "<?php" "$FILE_PATH"; then
            log "WARN" "Missing declare(strict_types=1)"
            echo "⚠️  PRE-EDIT WARNING: File missing declare(strict_types=1) - will be auto-added"
        fi
    fi
    
    # Check for property naming convention violations
    if grep -q "private \$[a-z][a-zA-Z]*;" "$FILE_PATH"; then
        log "WARN" "Properties using camelCase instead of snake_case"
        echo "⚠️  PRE-EDIT WARNING: Properties should use snake_case naming - will be auto-fixed"
    fi
    
    # Check for forbidden superglobals
    FORBIDDEN_GLOBALS='$GLOBALS|$_POST|$_GET|$_REQUEST|$_SESSION|$_COOKIE'
    if grep -E "$FORBIDDEN_GLOBALS" "$FILE_PATH" 2>/dev/null; then
        log "ERROR" "Forbidden superglobals detected"
        echo "❌ PRE-EDIT ERROR: File contains forbidden superglobals!"
        echo "   These MUST be replaced with Hyperf dependency injection:"
        echo "   - $_POST, $_GET → Request object"
        echo "   - $_SESSION → Session service"
        echo "   - $_COOKIE → Cookie service"
    fi
    
    # Check for missing PHPDoc on classes
    if grep -q "^class " "$FILE_PATH"; then
        if ! grep -B5 "^class " "$FILE_PATH" | grep -q "/\*\*"; then
            log "WARN" "Class missing PHPDoc documentation"
            echo "⚠️  PRE-EDIT WARNING: Class should have PHPDoc documentation"
        fi
    fi
fi

# STEP 3: Check test file conventions
if [[ "$FILE_PATH" == *Test.php || "$FILE_PATH" == test/* || "$FILE_PATH" == tests/* ]]; then
    if [[ -f "$FILE_PATH" ]]; then
        log "INFO" "Validating test file conventions"
        
        # Check for PSR-4 test namespace
        if grep -q "^namespace " "$FILE_PATH"; then
            NAMESPACE=$(grep "^namespace " "$FILE_PATH" | head -1 | sed 's/namespace \(.*\);/\1/')
            if [[ ! "$NAMESPACE" == *"\\Test\\"* ]] && [[ ! "$NAMESPACE" == *"\\Tests\\"* ]]; then
                log "WARN" "Test namespace doesn't follow PSR-4 convention"
                echo "⚠️  PRE-EDIT WARNING: Test namespace should include \\Test\\ or \\Tests\\"
                echo "   Current: $NAMESPACE"
                echo "   Expected pattern: SpacePlatform\\Utils\\Test\\..."
            fi
        fi
        
        # Check for test groups
        if ! grep -q "#\[Group(" "$FILE_PATH" && grep -q "class.*Test" "$FILE_PATH"; then
            log "WARN" "Test class missing group annotations"
            echo "⚠️  PRE-EDIT WARNING: Test class should have #[Group(...)] annotations"
        fi
        
        # Check for test method documentation
        if grep -q "public function test" "$FILE_PATH"; then
            if ! grep -B3 "public function test" "$FILE_PATH" | grep -q "/\*\*"; then
                log "WARN" "Test methods missing documentation"
                echo "⚠️  PRE-EDIT WARNING: Test methods should have PHPDoc explaining intent"
            fi
        fi
    fi
fi

# STEP 4: Run additional validation if available
if [[ -x "$PHP_PARADIGM_PATH/validation/pre-edit-validator.php" ]]; then
    log "INFO" "Running additional pre-edit validation"
    if php "$PHP_PARADIGM_PATH/validation/pre-edit-validator.php" "$FILE_PATH" 2>/dev/null; then
        log "INFO" "Additional validation passed"
    else
        log "WARN" "Additional validation found issues"
    fi
fi

# STEP 5: Provide space-utils usage hints
if [[ "$FILE_PATH" == *.php ]]; then
    echo ""
    echo "📚 SPACE-UTILS REMINDER:"
    echo "   • Use Collection instead of array_* functions"
    echo "   • Use FileSystem instead of file_* functions"
    echo "   • Use Json instead of json_* functions"
    echo "   • Use Http instead of curl_* functions"
    echo "   • Use Entity for data objects with validation"
    echo "   • Use Pipeline for sequential data transformations"
    echo "   • Properties should use snake_case naming"
    echo "   • Always include declare(strict_types=1)"
    echo ""
fi

log "INFO" "Pre-edit validation completed"
exit 0