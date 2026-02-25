#!/bin/bash

# Space-Utils Claude Hook Configuration
# This script configures Claude Code to use space-utils transformations

HOOK_DIR="$(dirname "$0")"
TRANSFORMER="$HOOK_DIR/space-utils-transformer.php"
CONFIG="$HOOK_DIR/../config/space-utils.json"
REGISTRY="$HOOK_DIR/../mappings/function-registry.json"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to check if file was modified
check_php_file() {
    local file="$1"
    
    # Only process PHP files
    if [[ ! "$file" =~ \.php$ ]]; then
        return 0
    fi
    
    echo -e "${YELLOW}[SPACE-UTILS]${NC} Checking: $file"
    
    # Run transformer
    php "$TRANSFORMER" -f "$file" -v
    
    return $?
}

# Pre-edit hook: Prepare environment
pre_edit_hook() {
    local file="$1"
    
    echo -e "${GREEN}[PRE-EDIT]${NC} Preparing space-utils environment..."
    
    # Check config exists (optional)
    if [[ -f "$CONFIG" ]]; then
        echo -e "${GREEN}[INFO]${NC} Using config: $CONFIG"
    else
        echo -e "${YELLOW}[INFO]${NC} No config found, using defaults"
    fi
    
    # Ensure registry exists
    if [[ ! -f "$REGISTRY" ]]; then
        echo -e "${RED}[ERROR]${NC} Function registry not found!"
        exit 1
    fi
    
    # Check if composer.json exists and add space-utils if needed
    if [[ -f "composer.json" ]]; then
        if ! grep -q "space-platform/utils" composer.json; then
            echo -e "${YELLOW}[INFO]${NC} Adding space-utils to composer.json..."
            # This would need proper JSON manipulation in production
        fi
    fi
    
    return 0
}

# Post-edit hook: Transform generated code
post_edit_hook() {
    local file="$1"
    
    echo -e "${GREEN}[POST-EDIT]${NC} Transforming to use space-utils..."
    
    # Transform the file
    check_php_file "$file"
    
    # Run code style fixer if available
    if command -v composer &> /dev/null && [[ -f "composer.json" ]]; then
        if grep -q "cs-fix" composer.json; then
            echo -e "${YELLOW}[INFO]${NC} Running code style fixer..."
            composer cs-fix "$file" 2>/dev/null || true
        fi
    fi
    
    return 0
}

# Main execution
main() {
    local hook_type="$1"
    local file="$2"
    
    case "$hook_type" in
        "pre-edit")
            pre_edit_hook "$file"
            ;;
        "post-edit")
            post_edit_hook "$file"
            ;;
        *)
            echo "Usage: $0 {pre-edit|post-edit} <file>"
            exit 1
            ;;
    esac
}

# Execute if called directly
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    main "$@"
fi