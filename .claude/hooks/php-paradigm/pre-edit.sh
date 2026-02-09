#!/bin/bash
# PHP Paradigm Pre-Edit Hook
# Validates coding standards before Claude Code makes changes

set -euo pipefail

# Configuration
PHP_PARADIGM_PATH="${PHP_PARADIGM_PATH:-$(pwd)/coding-standards}"
HOOK_NAME="pre-edit"
LOG_FILE=".claude/logs/hooks.log"

# Logging function
log() {
    local level="$1"
    local message="$2"
    echo "$(date -Iseconds) [$level] [$HOOK_NAME] $message" >> "$LOG_FILE" 2>/dev/null || true
    if [[ "$level" == "ERROR" || "$level" == "WARN" ]]; then
        echo "🚨 $message" >&2
    fi
}

# Initialize
mkdir -p "$(dirname "$LOG_FILE")" 2>/dev/null || true
log "INFO" "Starting pre-edit validation"

# Get file being edited from arguments
FILE_PATH="${1:-}"
if [[ -z "$FILE_PATH" ]]; then
    log "WARN" "No file path provided"
    exit 0
fi

log "INFO" "Validating file: $FILE_PATH"

# Check if PHP paradigm standards exist
if [[ ! -d "$PHP_PARADIGM_PATH" ]]; then
    log "WARN" "PHP paradigm standards not found at: $PHP_PARADIGM_PATH"
    exit 0
fi

# PHP file validation
if [[ "$FILE_PATH" == *.php ]]; then
    log "INFO" "Running PHP validation for: $FILE_PATH"

    # MANDATORY: PHPStan must be installed
    if ! command -v phpstan >/dev/null 2>&1; then
        log "ERROR" "PHPStan is required but not installed"
        echo "❌ BLOCKED: PHPStan is required for PHP development"
        echo "   Install: composer require --dev phpstan/phpstan"
        echo ""
        echo "   PHPStan detects: type errors, runtime errors, dead code, anti-patterns"
        echo "   php -l only catches: basic parse errors (misses 90% of real issues)"
        echo ""
        # Allow to proceed with warning for initial setup
        log "WARN" "Proceeding without PHPStan (installation required)"
    else
        # Determine PHPStan config path
        PHPSTAN_CONFIG=""
        if [[ -n "${SPACE_UTILS_PATH:-}" && -f "${SPACE_UTILS_PATH}/coding-standards/phpstan.neon" ]]; then
            PHPSTAN_CONFIG="${SPACE_UTILS_PATH}/coding-standards/phpstan.neon"
            log "INFO" "Using Space-Utils PHPStan config: $PHPSTAN_CONFIG"
        elif [[ -f "phpstan.neon" ]]; then
            PHPSTAN_CONFIG="phpstan.neon"
            log "INFO" "Using project PHPStan config: $PHPSTAN_CONFIG"
        elif [[ -f "phpstan.neon.dist" ]]; then
            PHPSTAN_CONFIG="phpstan.neon.dist"
            log "INFO" "Using project PHPStan config: $PHPSTAN_CONFIG"
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
            # Don't exit - let Claude Code see the issues and fix them
        else
            log "INFO" "PHPStan validation passed for: $FILE_PATH"
        fi
    fi

    # =========================================================================
    # ENVIRONMENT & TEST SEPARATION CHECKS (Pre-validation)
    # =========================================================================
    if [[ -f "$FILE_PATH" && "$FILE_PATH" == app/* ]]; then
        log "INFO" "Running environment/test separation checks for: $FILE_PATH"
        local violations_found=false

        # Check for $_ENV[] access
        if grep -q '\$_ENV\[' "$FILE_PATH"; then
            log "ERROR" "Direct \$_ENV access in app/ file: $FILE_PATH"
            echo "❌ BLOCKED: Direct \$_ENV[] access in app/ directory"
            echo "   File: $FILE_PATH"
            echo "   Fix: Use config() helper instead"
            violations_found=true
        fi

        # Check for $_SERVER[] access
        if grep -q '\$_SERVER\[' "$FILE_PATH"; then
            log "ERROR" "Direct \$_SERVER access in app/ file: $FILE_PATH"
            echo "❌ BLOCKED: Direct \$_SERVER[] access in app/ directory"
            echo "   File: $FILE_PATH"
            echo "   Fix: Use Request object instead"
            violations_found=true
        fi

        # Check for env() function
        if grep -qE '\benv\s*\(' "$FILE_PATH"; then
            log "ERROR" "env() function in app/ file: $FILE_PATH"
            echo "❌ BLOCKED: env() function used in app/ directory"
            echo "   File: $FILE_PATH"
            echo "   Fix: env() is only allowed in config/ files"
            violations_found=true
        fi

        # Check for getenv() function
        if grep -qE '\bgetenv\s*\(' "$FILE_PATH"; then
            log "ERROR" "getenv() function in app/ file: $FILE_PATH"
            echo "❌ BLOCKED: getenv() function used in app/ directory"
            echo "   File: $FILE_PATH"
            echo "   Fix: getenv() is only allowed in config/ files"
            violations_found=true
        fi

        # Check for Mockery usage
        if grep -qE '(Mockery::|\\Mockery\\)' "$FILE_PATH"; then
            log "ERROR" "Mockery usage in app/ file: $FILE_PATH"
            echo "❌ BLOCKED: Mockery usage in production code"
            echo "   File: $FILE_PATH"
            echo "   Fix: Keep test doubles in test/ directory"
            violations_found=true
        fi

        # Check for test environment checks
        if grep -qE "env\(['\"]APP_ENV['\"]\).*testing|isTestMode\(\)|app\(\)->environment\(['\"]testing['\"]\)" "$FILE_PATH"; then
            log "ERROR" "Test environment check in app/ file: $FILE_PATH"
            echo "❌ BLOCKED: Test environment check in production code"
            echo "   File: $FILE_PATH"
            echo "   Fix: Use dependency injection with interfaces"
            violations_found=true
        fi

        if [[ "$violations_found" == "true" ]]; then
            echo ""
            echo "   See CLAUDE.md for Environment Access Rules and Test/Production Separation"
            # Don't exit - let Claude Code see the issues and fix them
        fi
    fi

    # Check for strict types declaration
    if [[ -f "$FILE_PATH" ]]; then
        if ! grep -q "declare(strict_types=1)" "$FILE_PATH"; then
            log "ERROR" "Missing declare(strict_types=1) in: $FILE_PATH"
            echo "❌ PHP PARADIGM VIOLATION: Missing declare(strict_types=1) in $FILE_PATH"
            echo "   Required by PHP paradigm type safety standards"
            # Don't exit - let Claude Code proceed with warning
        fi

        # Check for untyped constants (basic check)
        if grep -q "const [A-Z_]* =" "$FILE_PATH" && ! grep -q "const [A-Z_]*: " "$FILE_PATH"; then
            log "WARN" "Potential untyped constants in: $FILE_PATH"
            echo "⚠️  PHP PARADIGM WARNING: Potential untyped constants in $FILE_PATH"
            echo "   All constants should have explicit type declarations"
        fi
    fi
fi

# Test file validation
if [[ "$FILE_PATH" == *Test.php || "$FILE_PATH" == *test.php || "$FILE_PATH" == test/* || "$FILE_PATH" == tests/* ]]; then
    log "INFO" "Running test validation for: $FILE_PATH"
    
    if [[ -f "$FILE_PATH" ]]; then
        # Check for test group hierarchy
        if ! grep -q "#\[Group(" "$FILE_PATH"; then
            log "ERROR" "Missing test groups in: $FILE_PATH"
            echo "❌ PHP PARADIGM VIOLATION: Missing test groups in $FILE_PATH"
            echo "   Required by PHP paradigm testing standards"
            echo "   Example: #[Group('unit-test')] #[Group('entity-test')]"
        fi
    fi
fi

# ConfigProvider validation
if [[ "$FILE_PATH" == *ConfigProvider.php ]]; then
    log "INFO" "Running ConfigProvider validation for: $FILE_PATH"
    
    if [[ -f "$FILE_PATH" ]]; then
        if ! grep -q "class.*ConfigProvider" "$FILE_PATH"; then
            log "WARN" "File named ConfigProvider but no ConfigProvider class found: $FILE_PATH"
        fi
    fi
fi

# Run PHP paradigm validation script if available
PHP_PARADIGM_VALIDATOR="$PHP_PARADIGM_PATH/validation/pre-edit-validator.php"
if [[ -f "$PHP_PARADIGM_VALIDATOR" ]]; then
    log "INFO" "Running PHP paradigm validator: $PHP_PARADIGM_VALIDATOR"
    if ! php "$PHP_PARADIGM_VALIDATOR" "$FILE_PATH"; then
        log "WARN" "PHP paradigm validator reported issues for: $FILE_PATH"
    fi
fi

log "INFO" "Pre-edit validation completed for: $FILE_PATH"
exit 0