#!/bin/bash

# Enhanced Standards Feedback for Claude Code
# Provides clear feedback when standards are violated

set -euo pipefail

# Colors
RED='\033[0;31m'
YELLOW='\033[1;33m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

file="$1"
# Use environment variable for standards path
if [[ -z "${SPACE_UTILS_PATH:-}" ]]; then
    echo "⚠️ SPACE_UTILS_PATH not set. Set it to enable standards feedback." >&2
    exit 0
fi
STANDARDS_PATH="$SPACE_UTILS_PATH/coding-standards"

# Function to provide specific feedback
provide_feedback() {
    local file="$1"
    local violations=()
    
    # Check for over-engineering patterns
    if grep -q "Factory" "$file" && ! grep -c "Factory" "$file" > 3; then
        violations+=("${RED}⚠️  Factory pattern detected with potentially <3 variants${NC}")
        violations+=("  📖 See: $STANDARDS_PATH/core-principles/simplicity.md")
    fi
    
    if grep -q "Repository" "$file" && [ $(grep -c "implements.*Repository" "$file") -eq 1 ]; then
        violations+=("${RED}⚠️  Repository pattern with single implementation${NC}")
        violations+=("  💡 Consider using direct implementation instead")
    fi
    
    # Check for missing strict types
    if [[ "$file" =~ \.php$ ]] && ! grep -q "declare(strict_types=1)" "$file"; then
        violations+=("${YELLOW}⚠️  Missing strict_types declaration${NC}")
        violations+=("  📖 See: $STANDARDS_PATH/language-features/strong-typing-standards.md")
    fi
    
    # Check for untyped parameters
    if grep -E "function\s+\w+\s*\([^)]*\$\w+[^:,)]*[,)]" "$file" > /dev/null 2>&1; then
        violations+=("${YELLOW}⚠️  Possible untyped function parameters detected${NC}")
        violations+=("  💡 Add type declarations to all parameters")
    fi
    
    # Check for space-utils opportunities
    if grep -E "array_map|array_filter|array_reduce" "$file" > /dev/null 2>&1; then
        violations+=("${BLUE}💡 Consider using Space-Utils functions:${NC}")
        violations+=("  • space_array_map() instead of array_map()")
        violations+=("  • space_array_filter() instead of array_filter()")
    fi
    
    # Output feedback
    if [ ${#violations[@]} -gt 0 ]; then
        echo -e "\n${YELLOW}═══ Space-Utils Standards Feedback ═══${NC}"
        for violation in "${violations[@]}"; do
            echo -e "$violation"
        done
        echo -e "${GREEN}Run validation:${NC} php $STANDARDS_PATH/tools/auto-fixer.php $file"
        echo -e "${BLUE}Quick check:${NC} /checkstandards all\n"
        return 1
    else
        echo -e "${GREEN}✅ Code follows Space-Utils standards${NC}"
        return 0
    fi
}

# Main execution
if [[ -f "$file" ]] && [[ "$file" =~ \.php$ ]]; then
    provide_feedback "$file"
fi

exit 0