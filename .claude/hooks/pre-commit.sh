#!/bin/bash
# Claude Code Pre-Commit Hook
# Runs quality checks before allowing commits

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_status() {
    echo -e "${BLUE}[HOOK]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[HOOK]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[HOOK]${NC} $1"
}

print_error() {
    echo -e "${RED}[HOOK]${NC} $1"
}

# Get project root
PROJECT_ROOT="$(git rev-parse --show-toplevel)"
cd "$PROJECT_ROOT"

# Load project configuration if it exists
if [[ -f ".claude/config/env.sh" ]]; then
    source ".claude/config/env.sh"
fi

print_status "Running pre-commit quality checks..."

# Check if CLAUDE.md exists and has mandatory sections
check_claude_md() {
    if [[ -f "CLAUDE.md" ]]; then
        if ! grep -q "Development Partnership" "CLAUDE.md"; then
            print_warning "CLAUDE.md missing 'Development Partnership' section"
        fi
        if ! grep -q "Research → Plan → Implement" "CLAUDE.md"; then
            print_warning "CLAUDE.md missing critical workflow section"
        fi
    else
        print_warning "No CLAUDE.md found - consider adding development guidelines"
    fi
}

# Run linting if command is available
run_linting() {
    if [[ -n "$LINT_COMMAND" ]] && command -v ${LINT_COMMAND%% *} >/dev/null 2>&1; then
        print_status "Running linter: $LINT_COMMAND"
        if ! $LINT_COMMAND; then
            print_error "Linting failed - fix issues before committing"
            return 1
        fi
        print_success "Linting passed"
    else
        print_status "No linter configured or available"
    fi
}

# Run formatting if command is available
run_formatting() {
    if [[ -n "$FORMAT_COMMAND" ]] && command -v ${FORMAT_COMMAND%% *} >/dev/null 2>&1; then
        print_status "Running formatter: $FORMAT_COMMAND"
        if ! $FORMAT_COMMAND; then
            print_warning "Formatting command failed"
        else
            print_success "Code formatted"
        fi
    else
        print_status "No formatter configured or available"
    fi
}

# Run tests if command is available
run_tests() {
    if [[ -n "$TEST_COMMAND" ]] && command -v ${TEST_COMMAND%% *} >/dev/null 2>&1; then
        print_status "Running tests: $TEST_COMMAND"
        if ! $TEST_COMMAND; then
            print_error "Tests failed - fix issues before committing"
            return 1
        fi
        print_success "All tests passed"
    else
        print_status "No test command configured or available"
    fi
}

# Detect and handle backup files to prevent them from being committed
detect_and_handle_backups() {
    local backup_files_found=false
    local staged_backups=()
    local autofix_backups=()
    
    # Get list of staged files and check for backup patterns
    while IFS= read -r staged_file; do
        # Check if file matches backup patterns
        if [[ "$staged_file" =~ \.backup\.[0-9]+ ]] || 
           [[ "$staged_file" =~ \.pre-rollback\.[0-9]+ ]] || 
           [[ "$staged_file" == *"_ENHANCED.md" ]] || 
           [[ "$staged_file" =~ \.(bak|orig)$ ]] || 
           [[ "$staged_file" == *"~" ]] || 
           [[ "$staged_file" =~ \.eslint-backup\. ]] || 
           [[ "$staged_file" =~ \.prettier-backup\. ]] || 
           [[ "$staged_file" =~ \.format-backup\. ]] || 
           [[ "$staged_file" =~ \.backup\.[0-9]{8}-[0-9]{6} ]]; then
            staged_backups+=("$staged_file")
            backup_files_found=true
        elif [[ "$staged_file" =~ \.autofix-backup\. ]]; then
            autofix_backups+=("$staged_file")
            backup_files_found=true
        fi
    done < <(git diff --cached --name-only)
    
    # Also check for autofix backup files in working directory (not just staged)
    while IFS= read -r -d '' autofix_file; do
        if [[ ! " ${autofix_backups[*]} " =~ " ${autofix_file} " ]]; then
            autofix_backups+=("$autofix_file")
        fi
    done < <(find . -name "*.autofix-backup.*" -type f -print0 2>/dev/null)
    
    if [[ "$backup_files_found" == true ]] || [[ ${#autofix_backups[@]} -gt 0 ]]; then
        if [[ ${#staged_backups[@]} -gt 0 ]]; then
            print_warning "Backup files detected in staging area:"
            for file in "${staged_backups[@]}"; do
                echo "  - $file"
            done
            
            # Unstage regular backup files
            for file in "${staged_backups[@]}"; do
                git reset HEAD -- "$file" 2>/dev/null || true
            done
            
            print_success "Backup files automatically unstaged (${#staged_backups[@]} files)"
            print_status "These files remain in your working directory but won't be committed"
        fi
        
        if [[ ${#autofix_backups[@]} -gt 0 ]]; then
            print_warning "Autofix backup files detected:"
            for file in "${autofix_backups[@]}"; do
                echo "  - $file"
            done
            
            # Unstage autofix backup files if they were staged
            for file in "${autofix_backups[@]}"; do
                git reset HEAD -- "$file" 2>/dev/null || true
            done
            
            # Remove autofix backup files completely
            for file in "${autofix_backups[@]}"; do
                if [[ -f "$file" ]]; then
                    rm -f "$file"
                fi
            done
            
            print_success "Autofix backup files automatically removed (${#autofix_backups[@]} files)"
        fi
        
        # Log the action for transparency
        if [[ ! -d ".claude/logs" ]]; then
            mkdir -p ".claude/logs"
        fi
        if [[ ${#staged_backups[@]} -gt 0 ]]; then
            echo "[$(date '+%Y-%m-%d %H:%M:%S')] Unstaged backup files: ${staged_backups[*]}" >> ".claude/logs/backup-prevention.log"
        fi
        if [[ ${#autofix_backups[@]} -gt 0 ]]; then
            echo "[$(date '+%Y-%m-%d %H:%M:%S')] Removed autofix backup files: ${autofix_backups[*]}" >> ".claude/logs/backup-prevention.log"
        fi
    fi
}

# =============================================================================
# ENVIRONMENT & TEST SEPARATION CHECKS (DEFENSE IN DEPTH)
# =============================================================================
# Validates PHP files don't have forbidden patterns in app/ directory
check_environment_test_separation() {
    local staged_app_php
    local violations_found=false

    # Get staged PHP files in app/ directory
    staged_app_php=$(git diff --cached --name-only --diff-filter=ACMR | grep -E '^app/.*\.php$' || true)

    if [[ -z "$staged_app_php" ]]; then
        return 0
    fi

    print_status "Checking environment/test separation compliance..."

    # Check for $_ENV[] access in app/
    local env_violations
    env_violations=$(echo "$staged_app_php" | xargs grep -l '\$_ENV\[' 2>/dev/null || true)
    if [[ -n "$env_violations" ]]; then
        print_error "BLOCKED: Direct \$_ENV[] access in app/ directory"
        echo "   Files with violations:"
        echo "$env_violations" | while read -r file; do echo "     ❌ $file"; done
        echo ""
        echo "   Fix: Use config() helper to access configuration values"
        echo "   Example: config('app.setting') instead of \$_ENV['APP_SETTING']"
        echo ""
        violations_found=true
    fi

    # Check for $_SERVER[] access in app/
    local server_violations
    server_violations=$(echo "$staged_app_php" | xargs grep -l '\$_SERVER\[' 2>/dev/null || true)
    if [[ -n "$server_violations" ]]; then
        print_error "BLOCKED: Direct \$_SERVER[] access in app/ directory"
        echo "   Files with violations:"
        echo "$server_violations" | while read -r file; do echo "     ❌ $file"; done
        echo ""
        echo "   Fix: Use Request object to access server values"
        echo "   Example: \$request->header('Host') instead of \$_SERVER['HTTP_HOST']"
        echo ""
        violations_found=true
    fi

    # Check for env() function outside config/
    local env_func_violations
    env_func_violations=$(echo "$staged_app_php" | xargs grep -lE '\benv\s*\(' 2>/dev/null || true)
    if [[ -n "$env_func_violations" ]]; then
        print_error "BLOCKED: env() function used in app/ directory"
        echo "   Files with violations:"
        echo "$env_func_violations" | while read -r file; do echo "     ❌ $file"; done
        echo ""
        echo "   Fix: env() is only allowed in config/ files"
        echo "   Use config() helper in app/ code"
        echo ""
        violations_found=true
    fi

    # Check for getenv() function in app/
    local getenv_violations
    getenv_violations=$(echo "$staged_app_php" | xargs grep -lE '\bgetenv\s*\(' 2>/dev/null || true)
    if [[ -n "$getenv_violations" ]]; then
        print_error "BLOCKED: getenv() function used in app/ directory"
        echo "   Files with violations:"
        echo "$getenv_violations" | while read -r file; do echo "     ❌ $file"; done
        echo ""
        echo "   Fix: getenv() is only allowed in config/ files"
        echo "   Use config() helper in app/ code"
        echo ""
        violations_found=true
    fi

    # Check for Mockery usage in production code
    local mockery_violations
    mockery_violations=$(echo "$staged_app_php" | xargs grep -lE '(Mockery::|\\Mockery\\)' 2>/dev/null || true)
    if [[ -n "$mockery_violations" ]]; then
        print_error "BLOCKED: Mockery usage in production code (app/ directory)"
        echo "   Files with violations:"
        echo "$mockery_violations" | while read -r file; do echo "     ❌ $file"; done
        echo ""
        echo "   Fix: Keep all test doubles in test/ directory"
        echo "   Use dependency injection with interfaces instead"
        echo ""
        violations_found=true
    fi

    # Check for test environment checks in app/
    local test_env_violations
    test_env_violations=$(echo "$staged_app_php" | xargs grep -lE "env\(['\"]APP_ENV['\"]\).*testing|isTestMode\(\)|app\(\)->environment\(['\"]testing['\"]\)" 2>/dev/null || true)
    if [[ -n "$test_env_violations" ]]; then
        print_error "BLOCKED: Test environment check in production code"
        echo "   Files with violations:"
        echo "$test_env_violations" | while read -r file; do echo "     ❌ $file"; done
        echo ""
        echo "   Fix: Use dependency injection with interfaces"
        echo "   Override bindings in test setUp() methods"
        echo ""
        violations_found=true
    fi

    if [[ "$violations_found" == "true" ]]; then
        echo ""
        echo "  ═══════════════════════════════════════════════════════════════"
        echo "  ENVIRONMENT/TEST SEPARATION RULES (see CLAUDE.md for details):"
        echo "  ═══════════════════════════════════════════════════════════════"
        echo "  ❌ FORBIDDEN in app/ directory:"
        echo "     - \$_ENV[], \$_SERVER[] - Direct superglobal access"
        echo "     - env(), getenv() - Only allowed in config/ files"
        echo "     - Mockery::, \\Mockery\\ - Test code in production"
        echo "     - isTestMode(), env('APP_ENV') === 'testing'"
        echo ""
        echo "  ✅ REQUIRED patterns:"
        echo "     - Use config() helper to access configuration"
        echo "     - Use dependency injection with interfaces"
        echo "     - Keep test doubles in test/ directory"
        echo "  ═══════════════════════════════════════════════════════════════"
        echo ""
        return 1
    fi

    print_success "Environment/test separation checks passed"
    return 0
}

# =============================================================================
# PSR-4 SINGLE CLASS PER FILE VALIDATION
# =============================================================================
# Validates that each PHP file contains exactly one class/interface/trait/enum
validate_single_class_per_file() {
    local violations=()
    local php_files

    # Get staged PHP files (added, copied, modified)
    php_files=$(git diff --cached --name-only --diff-filter=ACM | grep '\.php$' || true)

    if [[ -z "$php_files" ]]; then
        return 0
    fi

    print_status "Checking PSR-4 single class per file compliance..."

    while IFS= read -r file; do
        if [[ -z "$file" ]] || [[ ! -f "$file" ]]; then
            continue
        fi

        # Skip vendor directory
        if [[ "$file" == *"/vendor/"* ]]; then
            continue
        fi

        # Count class/interface/trait/enum declarations
        # Pattern matches: abstract class, final class, readonly class, class, interface, trait, enum
        local count
        count=$(grep -cE '^\s*(abstract\s+|final\s+|readonly\s+)*(class|interface|trait|enum)\s+[A-Za-z_]' "$file" 2>/dev/null || echo 0)

        if [[ $count -gt 1 ]]; then
            violations+=("$file (found $count declarations)")
        fi
    done <<< "$php_files"

    if [[ ${#violations[@]} -gt 0 ]]; then
        print_error "PSR-4 VIOLATION: Multiple classes in single file!"
        echo ""
        echo "  The following files contain multiple class/interface/trait/enum declarations:"
        echo ""
        for violation in "${violations[@]}"; do
            echo "    ❌ $violation"
        done
        echo ""
        echo "  SOLUTION: Split each file into separate files (one class per file):"
        echo "    php templates/tools/php/class-file-splitter.php <file>"
        echo ""
        echo "  Or use the validator to check all files:"
        echo "    php templates/tools/php/single-class-validator.php <directory>"
        echo ""
        echo "  This is a HARD REQUIREMENT - commits with PSR-4 violations are BLOCKED."
        echo ""
        return 1
    fi

    print_success "PSR-4 single class per file check passed"
    return 0
}

# Check for secrets or sensitive data
check_secrets() {
    local files_to_check=$(git diff --cached --name-only)
    local secrets_found=false
    
    if [[ -n "$files_to_check" ]]; then
        for file in $files_to_check; do
            if [[ -f "$file" ]]; then
                # Check for common secret patterns
                if grep -q -E "(api_key|password|secret|token|private_key)" "$file" 2>/dev/null; then
                    print_warning "Potential secret detected in $file - please review"
                fi
                
                # Check for common secret file patterns
                if [[ "$file" =~ \.(env|key|pem|p12)$ ]]; then
                    print_warning "Sensitive file type detected: $file"
                fi
            fi
        done
    fi
}

# Main execution
main() {
    local exit_code=0
    
    # Detect and handle backup files first (non-blocking)
    detect_and_handle_backups
    
    # Always check CLAUDE.md
    check_claude_md

    # Check for secrets
    check_secrets

    # Environment/test separation checks (BLOCKING)
    if ! check_environment_test_separation; then
        exit_code=1
    fi

    # PSR-4 single class per file validation (BLOCKING)
    if ! validate_single_class_per_file; then
        exit_code=1
    fi
    
    # Run quality checks (these can fail the commit)
    if ! run_linting; then
        exit_code=1
    fi
    
    # Run formatting (this should not fail the commit)
    run_formatting
    
    # Run tests if enabled for pre-commit
    if [[ "${CLAUDE_HOOKS_RUN_TESTS_PRECOMMIT:-false}" == "true" ]]; then
        if ! run_tests; then
            exit_code=1
        fi
    else
        print_status "Pre-commit tests disabled (set CLAUDE_HOOKS_RUN_TESTS_PRECOMMIT=true to enable)"
    fi
    
    if [[ $exit_code -eq 0 ]]; then
        print_success "Pre-commit checks passed!"
    else
        print_error "Pre-commit checks failed!"
    fi
    
    return $exit_code
}

# Run main function
main "$@"