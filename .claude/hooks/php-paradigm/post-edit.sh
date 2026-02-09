#!/bin/bash
# PHP Paradigm Post-Edit Hook
# Applies coding standards after Claude Code makes changes

set -euo pipefail

# Configuration
PHP_PARADIGM_PATH="${PHP_PARADIGM_PATH:-/path/to/your/php-paradigm-standards}"
HOOK_NAME="post-edit"
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
log "INFO" "Starting post-edit validation and fixes"

# Get file being edited from arguments
FILE_PATH="${1:-}"
if [[ -z "$FILE_PATH" ]]; then
    log "WARN" "No file path provided"
    exit 0
fi

log "INFO" "Processing file: $FILE_PATH"

# Check if PHP paradigm standards exist
if [[ ! -d "$PHP_PARADIGM_PATH" ]]; then
    log "WARN" "PHP paradigm standards not found at: $PHP_PARADIGM_PATH"
    exit 0
fi

# Track if file was modified
FILE_MODIFIED=false

# PHP file auto-fixes
if [[ "$FILE_PATH" == *.php && -f "$FILE_PATH" ]]; then
    log "INFO" "Running PHP auto-fixes for: $FILE_PATH"
    
    # Add strict types if missing
    if ! grep -q "declare(strict_types=1)" "$FILE_PATH"; then
        # Check if it's a PHP file with opening tag
        if grep -q "<?php" "$FILE_PATH"; then
            log "INFO" "Adding declare(strict_types=1) to: $FILE_PATH"
            # Create temporary file with strict types added
            {
                head -n 1 "$FILE_PATH"  # Get <?php line
                echo "declare(strict_types=1);"
                echo ""
                tail -n +2 "$FILE_PATH"  # Get rest of file
            } > "$FILE_PATH.tmp" && mv "$FILE_PATH.tmp" "$FILE_PATH"
            FILE_MODIFIED=true
            echo "✅ AUTO-FIXED: Added declare(strict_types=1) to $FILE_PATH"
        fi
    fi
    
    # Basic constant type checking and suggestion
    if grep -q "const [A-Z_]* =" "$FILE_PATH" && ! grep -q "const [A-Z_]*: " "$FILE_PATH"; then
        log "WARN" "Untyped constants found in: $FILE_PATH - manual fix required"
        echo "⚠️  MANUAL FIX NEEDED: Add type declarations to constants in $FILE_PATH"
        echo "   Example: const MY_CONSTANT: string = 'value';"
    fi
fi

# Test file auto-fixes
if [[ "$FILE_PATH" == *Test.php || "$FILE_PATH" == *test.php || "$FILE_PATH" == test/* || "$FILE_PATH" == tests/* ]]; then
    if [[ -f "$FILE_PATH" ]]; then
        log "INFO" "Running test auto-fixes for: $FILE_PATH"
        
        # Basic test group addition (simplified - real implementation would be more sophisticated)
        if ! grep -q "#\[Group(" "$FILE_PATH" && grep -q "class.*Test" "$FILE_PATH"; then
            # Determine groups based on file path
            GROUPS=""
            
            # Test category based on directory
            if [[ "$FILE_PATH" == */Unit/* ]]; then
                GROUPS="#[Group('unit-test')]"
            elif [[ "$FILE_PATH" == */Integration/* ]]; then
                GROUPS="#[Group('integration-test')]"
            elif [[ "$FILE_PATH" == */Functional/* ]]; then
                GROUPS="#[Group('functional-test')]"
            elif [[ "$FILE_PATH" == test/* ]]; then
                # Legacy structure
                if [[ "$FILE_PATH" == *Entity* ]]; then
                    GROUPS="#[Group('entity-test')]"
                fi
            fi
            
            if [[ -n "$GROUPS" ]]; then
                log "INFO" "Adding basic test groups to: $FILE_PATH"
                # Insert groups before class declaration
                sed -i.bak "s/class \(.*\)Test/$GROUPS\nclass \1Test/" "$FILE_PATH" && rm -f "$FILE_PATH.bak"
                FILE_MODIFIED=true
                echo "✅ AUTO-FIXED: Added basic test groups to $FILE_PATH"
                echo "   Note: Review and add additional specific groups as needed"
            fi
        fi
    fi
fi

# =========================================================================
# MANDATORY PHPStan ANALYSIS
# =========================================================================
if ! command -v phpstan >/dev/null 2>&1; then
    log "ERROR" "PHPStan is required but not installed"
    echo "❌ PHPStan is REQUIRED for PHP development"
    echo "   Install: composer require --dev phpstan/phpstan"
    echo ""
    echo "   PHPStan detects: type errors, runtime errors, dead code, anti-patterns"
    echo "   php -l only catches: basic parse errors (misses 90% of real issues)"
else
    # Determine PHPStan config path
    PHPSTAN_CONFIG=""
    if [[ -n "${SPACE_UTILS_PATH:-}" && -f "${SPACE_UTILS_PATH}/coding-standards/phpstan.neon" ]]; then
        PHPSTAN_CONFIG="${SPACE_UTILS_PATH}/coding-standards/phpstan.neon"
        log "INFO" "Using Space-Utils PHPStan config: $PHPSTAN_CONFIG"
    elif [[ -f "phpstan.neon" ]]; then
        PHPSTAN_CONFIG="phpstan.neon"
    elif [[ -f "phpstan.neon.dist" ]]; then
        PHPSTAN_CONFIG="phpstan.neon.dist"
    fi

    # Run PHPStan analysis (MANDATORY)
    log "INFO" "Running PHPStan analysis (MANDATORY): $FILE_PATH"
    local phpstan_cmd="phpstan analyze \"$FILE_PATH\" --level=max --no-progress"
    if [[ -n "$PHPSTAN_CONFIG" ]]; then
        phpstan_cmd="phpstan analyze \"$FILE_PATH\" --configuration=\"$PHPSTAN_CONFIG\" --level=max --no-progress"
    fi

    if ! eval "$phpstan_cmd" 2>/dev/null; then
        log "ERROR" "PHPStan found critical issues in: $FILE_PATH"
        echo "❌ PHPStan detected issues that must be fixed in $FILE_PATH"
        echo "   Run: phpstan analyze $FILE_PATH --level=max"
        echo ""
        echo "   ╔══════════════════════════════════════════════════════════╗"
        echo "   ║  DIAGNOSE FIRST — Search before creating or removing    ║"
        echo "   ╠══════════════════════════════════════════════════════════╣"
        echo "   ║  1. SEARCH the codebase for the missing class/method    ║"
        echo "   ║  2. If found → FIX the import path or reference         ║"
        echo "   ║  3. If not found but needed → CREATE the missing code   ║"
        echo "   ║  4. If not found and unneeded → REMOVE the reference    ║"
        echo "   ║  • NEVER blindly delete or create without searching     ║"
        echo "   ╚══════════════════════════════════════════════════════════╝"
    else
        log "INFO" "PHPStan validation passed for: $FILE_PATH"
    fi
fi

# =========================================================================
# ENVIRONMENT & TEST SEPARATION CHECKS (Post-validation)
# =========================================================================
if [[ "$FILE_PATH" == app/* && -f "$FILE_PATH" ]]; then
    log "INFO" "Running environment/test separation checks for: $FILE_PATH"
    local violations_found=false

    # Check for $_ENV[] access
    if grep -q '\$_ENV\[' "$FILE_PATH"; then
        log "ERROR" "Direct \$_ENV access in app/ file: $FILE_PATH"
        echo "❌ VIOLATION: Direct \$_ENV[] access in app/ directory"
        echo "   File: $FILE_PATH"
        echo "   Fix: Use config() helper instead"
        violations_found=true
    fi

    # Check for $_SERVER[] access
    if grep -q '\$_SERVER\[' "$FILE_PATH"; then
        log "ERROR" "Direct \$_SERVER access in app/ file: $FILE_PATH"
        echo "❌ VIOLATION: Direct \$_SERVER[] access in app/ directory"
        echo "   File: $FILE_PATH"
        echo "   Fix: Use Request object instead"
        violations_found=true
    fi

    # Check for env() function
    if grep -qE '\benv\s*\(' "$FILE_PATH"; then
        log "ERROR" "env() function in app/ file: $FILE_PATH"
        echo "❌ VIOLATION: env() function used in app/ directory"
        echo "   File: $FILE_PATH"
        echo "   Fix: env() is only allowed in config/ files. Use config() instead."
        violations_found=true
    fi

    # Check for getenv() function
    if grep -qE '\bgetenv\s*\(' "$FILE_PATH"; then
        log "ERROR" "getenv() function in app/ file: $FILE_PATH"
        echo "❌ VIOLATION: getenv() function used in app/ directory"
        echo "   File: $FILE_PATH"
        echo "   Fix: getenv() is only allowed in config/ files. Use config() instead."
        violations_found=true
    fi

    # Check for Mockery usage
    if grep -qE '(Mockery::|\\Mockery\\)' "$FILE_PATH"; then
        log "ERROR" "Mockery usage in app/ file: $FILE_PATH"
        echo "❌ VIOLATION: Mockery usage in production code"
        echo "   File: $FILE_PATH"
        echo "   Fix: Keep test doubles in test/ directory"
        violations_found=true
    fi

    # Check for test environment checks
    if grep -qE "env\(['\"]APP_ENV['\"]\).*testing|isTestMode\(\)|app\(\)->environment\(['\"]testing['\"]\)" "$FILE_PATH"; then
        log "ERROR" "Test environment check in app/ file: $FILE_PATH"
        echo "❌ VIOLATION: Test environment check in production code"
        echo "   File: $FILE_PATH"
        echo "   Fix: Use dependency injection with interfaces"
        violations_found=true
    fi

    if [[ "$violations_found" == "true" ]]; then
        echo ""
        echo "   ═══════════════════════════════════════════════════════════"
        echo "   See CLAUDE.md → Environment Access Rules"
        echo "   See CLAUDE.md → Test/Production Separation Rules"
        echo "   ═══════════════════════════════════════════════════════════"
    fi
fi

# Run PHP paradigm auto-fixer if available
PHP_PARADIGM_FIXER="$PHP_PARADIGM_PATH/tools/auto-fixer.php"
if [[ -f "$PHP_PARADIGM_FIXER" ]]; then
    log "INFO" "Running PHP paradigm auto-fixer: $PHP_PARADIGM_FIXER"
    if php "$PHP_PARADIGM_FIXER" "$FILE_PATH" 2>/dev/null; then
        log "INFO" "PHP paradigm auto-fixer completed for: $FILE_PATH"
        FILE_MODIFIED=true
    fi
fi

# Run code formatting if available
if command -v php-cs-fixer >/dev/null 2>&1; then
    log "INFO" "Running PHP-CS-Fixer on: $FILE_PATH"
    if php-cs-fixer fix "$FILE_PATH" --quiet 2>/dev/null; then
        log "INFO" "PHP-CS-Fixer completed for: $FILE_PATH"
        FILE_MODIFIED=true
    fi
fi

# Final validation
PHP_PARADIGM_VALIDATOR="$PHP_PARADIGM_PATH/validation/post-edit-validator.php"
if [[ -f "$PHP_PARADIGM_VALIDATOR" ]]; then
    log "INFO" "Running final validation: $PHP_PARADIGM_VALIDATOR"
    if ! php "$PHP_PARADIGM_VALIDATOR" "$FILE_PATH" 2>/dev/null; then
        log "WARN" "Post-edit validation found remaining issues in: $FILE_PATH"
        echo "⚠️  Some PHP paradigm standards may require manual attention in $FILE_PATH"
    fi
fi

if [[ "$FILE_MODIFIED" == "true" ]]; then
    log "INFO" "File automatically modified: $FILE_PATH"
    echo "🔧 PHP paradigm auto-fixes applied to $FILE_PATH"
else
    log "INFO" "No auto-fixes needed for: $FILE_PATH"
fi

log "INFO" "Post-edit processing completed for: $FILE_PATH"
exit 0