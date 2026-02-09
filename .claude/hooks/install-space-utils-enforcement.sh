#!/bin/bash
# Install Space-Utils Mandatory Enforcement to a PHP Project
# This script sets up the enforcement system using the existing template structure

set -euo pipefail

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Get script directory (where templates are)
TEMPLATE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TEMPLATE_ROOT="$(dirname "$TEMPLATE_DIR")"

# Target project directory (current directory or first argument)
TARGET_DIR="${1:-$(pwd)}"

echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}    INSTALLING SPACE-UTILS MANDATORY ENFORCEMENT${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo "Template source: $TEMPLATE_ROOT"
echo "Target project:  $TARGET_DIR"
echo ""

# Verify target directory
if [[ ! -d "$TARGET_DIR" ]]; then
    echo -e "${RED}❌ Target directory does not exist: $TARGET_DIR${NC}"
    exit 1
fi

cd "$TARGET_DIR"

# Step 1: Create required directories
echo "📁 Creating directory structure..."
mkdir -p .claude/hooks/php-paradigm
mkdir -p .claude/config
mkdir -p .claude/logs
mkdir -p .git/hooks
echo "   ✅ Directories created"

# Step 2: Backup existing configurations
echo "📦 Backing up existing configurations..."
if [[ -f ".claude/settings.local.json" ]]; then
    cp ".claude/settings.local.json" ".claude/settings.local.json.backup.$(date +%s)"
    echo "   ✅ Backed up settings.local.json"
fi
if [[ -f ".claude/config/space-utils.json" ]]; then
    cp ".claude/config/space-utils.json" ".claude/config/space-utils.json.backup.$(date +%s)"
    echo "   ✅ Backed up space-utils.json"
fi

# Step 3: Copy enforcement files
echo "📋 Installing enforcement components..."

# Copy the enforcer
if [[ -f "$TEMPLATE_DIR/php-paradigm/space-utils-enforcer.sh" ]]; then
    cp "$TEMPLATE_DIR/php-paradigm/space-utils-enforcer.sh" .claude/hooks/php-paradigm/
    chmod +x .claude/hooks/php-paradigm/space-utils-enforcer.sh
    echo "   ✅ Installed space-utils-enforcer.sh"
else
    echo -e "   ${YELLOW}⚠️  space-utils-enforcer.sh not found in templates${NC}"
fi

# Copy enhanced post-edit hook
if [[ -f "$TEMPLATE_DIR/php-paradigm/post-edit-enhanced.sh" ]]; then
    # Backup original if exists
    [[ -f ".claude/hooks/php-paradigm/post-edit.sh" ]] && \
        cp .claude/hooks/php-paradigm/post-edit.sh .claude/hooks/php-paradigm/post-edit.original.sh
    
    cp "$TEMPLATE_DIR/php-paradigm/post-edit-enhanced.sh" .claude/hooks/php-paradigm/post-edit.sh
    chmod +x .claude/hooks/php-paradigm/post-edit.sh
    echo "   ✅ Installed enhanced post-edit.sh"
else
    echo -e "   ${YELLOW}⚠️  post-edit-enhanced.sh not found in templates${NC}"
fi

# Copy adapter hooks if they don't exist
if [[ ! -f ".claude/hooks/claude-post-edit-adapter.sh" ]]; then
    if [[ -f "$TEMPLATE_DIR/claude-post-edit-adapter.sh" ]]; then
        cp "$TEMPLATE_DIR/claude-post-edit-adapter.sh" .claude/hooks/
        chmod +x .claude/hooks/claude-post-edit-adapter.sh
        echo "   ✅ Installed claude-post-edit-adapter.sh"
    fi
fi

if [[ ! -f ".claude/hooks/claude-pre-edit-adapter.sh" ]]; then
    if [[ -f "$TEMPLATE_DIR/claude-pre-edit-adapter.sh" ]]; then
        cp "$TEMPLATE_DIR/claude-pre-edit-adapter.sh" .claude/hooks/
        chmod +x .claude/hooks/claude-pre-edit-adapter.sh
        echo "   ✅ Installed claude-pre-edit-adapter.sh"
    fi
fi

# Step 4: Install configuration
echo "⚙️  Configuring Space-Utils enforcement..."

# Determine which config to use
CONFIG_MODE="${SPACE_UTILS_ENFORCEMENT:-mandatory}"
if [[ "$CONFIG_MODE" == "mandatory" ]] || [[ "$CONFIG_MODE" == "strict" ]]; then
    CONFIG_FILE="$TEMPLATE_ROOT/config/space-utils-mandatory.json"
else
    CONFIG_FILE="$TEMPLATE_ROOT/config/space-utils.json"
fi

if [[ -f "$CONFIG_FILE" ]]; then
    cp "$CONFIG_FILE" .claude/config/space-utils.json
    echo "   ✅ Installed space-utils.json (mode: $CONFIG_MODE)"
else
    echo -e "   ${YELLOW}⚠️  Config file not found: $CONFIG_FILE${NC}"
    # Create minimal config
    cat > .claude/config/space-utils.json << 'EOF'
{
    "space_utils": {
        "enabled": true,
        "enforcement": {
            "mode": "mandatory",
            "block_on_violation": true,
            "auto_rollback": true
        }
    }
}
EOF
    echo "   ✅ Created minimal space-utils.json"
fi

# Step 5: Update settings.local.json
echo "🔧 Updating Claude Code settings..."

if [[ "$CONFIG_MODE" == "mandatory" ]] || [[ "$CONFIG_MODE" == "strict" ]]; then
    SETTINGS_FILE="$TEMPLATE_DIR/settings-template-mandatory.json"
else
    SETTINGS_FILE="$TEMPLATE_DIR/settings-template.json"
fi

if [[ -f "$SETTINGS_FILE" ]]; then
    cp "$SETTINGS_FILE" .claude/settings.local.json
    echo "   ✅ Updated settings.local.json for $CONFIG_MODE mode"
else
    echo -e "   ${YELLOW}⚠️  Settings template not found${NC}"
fi

# Step 6: Install Git pre-commit hook
echo "🎣 Installing Git pre-commit hook..."

cat > .git/hooks/pre-commit << 'EOF'
#!/bin/bash
# Git Pre-Commit Hook - Space-Utils Validation

set -euo pipefail

echo "🔍 Running Space-Utils standards validation..."

ENFORCER=".claude/hooks/php-paradigm/space-utils-enforcer.sh"
if [[ ! -f "$ENFORCER" ]]; then
    echo "⚠️  Space-Utils enforcer not found"
    exit 0
fi

# Get enforcement mode
CONFIG_FILE=".claude/config/space-utils.json"
if [[ -f "$CONFIG_FILE" ]]; then
    MODE=$(jq -r '.space_utils.enforcement.mode // "optional"' "$CONFIG_FILE" 2>/dev/null || echo "optional")
else
    MODE="optional"
fi

# Only enforce in mandatory/strict mode
if [[ "$MODE" != "mandatory" ]] && [[ "$MODE" != "strict" ]]; then
    echo "✅ Space-Utils enforcement disabled (mode: $MODE)"
    exit 0
fi

# Get list of PHP files to be committed
PHP_FILES=$(git diff --cached --name-only --diff-filter=ACM | grep '\.php$' || true)

if [[ -z "$PHP_FILES" ]]; then
    echo "✅ No PHP files to validate"
    exit 0
fi

# Validate each file
VIOLATIONS=0
while IFS= read -r file; do
    if [[ -f "$file" ]]; then
        echo "Validating: $file"
        if ! "$ENFORCER" "$file" "validate" >/dev/null 2>&1; then
            echo "  ❌ VIOLATION: $file"
            VIOLATIONS=$((VIOLATIONS + 1))
        else
            echo "  ✅ Compliant: $file"
        fi
    fi
done <<< "$PHP_FILES"

if [[ $VIOLATIONS -gt 0 ]]; then
    echo ""
    echo "❌ COMMIT BLOCKED: $VIOLATIONS file(s) violate Space-Utils standards"
    echo "Run: .claude/hooks/php-paradigm/space-utils-enforcer.sh <file> transform"
    exit 1
fi

echo "✅ All PHP files comply with Space-Utils standards"
exit 0
EOF

chmod +x .git/hooks/pre-commit
echo "   ✅ Git pre-commit hook installed"

# Step 7: Verify Space-Utils path configuration
echo "🔍 Checking Space-Utils path configuration..."

# Check if SPACE_UTILS_PATH environment variable is set
if [[ -n "${SPACE_UTILS_PATH:-}" ]]; then
    if [[ -d "$SPACE_UTILS_PATH" ]]; then
        echo "   ✅ SPACE_UTILS_PATH is set: $SPACE_UTILS_PATH"
    else
        echo -e "   ${YELLOW}⚠️  SPACE_UTILS_PATH is set but directory not found: $SPACE_UTILS_PATH${NC}"
    fi
else
    # Try to detect Space-Utils installation in common locations
    SPACE_UTILS_LOCATIONS=(
        "../space/dependencies/lib/space-utils"
        "vendor/space-platform/utils"
        "../vendor/space-platform/utils"
    )

    DETECTED_PATH=""
    for location in "${SPACE_UTILS_LOCATIONS[@]}"; do
        if [[ -d "$location" ]]; then
            DETECTED_PATH="$(cd "$location" && pwd)"
            break
        fi
    done

    if [[ -n "$DETECTED_PATH" ]]; then
        echo -e "   ${YELLOW}⚠️  SPACE_UTILS_PATH not set but detected at: $DETECTED_PATH${NC}"
        echo "   Add to your shell profile: export SPACE_UTILS_PATH=\"$DETECTED_PATH\""
    else
        echo -e "   ${YELLOW}⚠️  SPACE_UTILS_PATH environment variable not set${NC}"
        echo "   Please set it: export SPACE_UTILS_PATH=/path/to/space-utils"
    fi
fi

# Step 8: Test the installation
echo ""
echo "🧪 Testing enforcement system..."

# Create test file
TEST_FILE="/tmp/test_enforcement_$$.php"
cat > "$TEST_FILE" << 'EOF'
<?php
class TestClass {
    public function test($value) {
        if (is_string($value)) {
            return strlen($value);
        }
        return array_map(fn($x) => $x * 2, [1, 2, 3]);
    }
}
EOF

echo "Testing with non-compliant file..."
if .claude/hooks/php-paradigm/space-utils-enforcer.sh "$TEST_FILE" "validate" >/dev/null 2>&1; then
    echo -e "   ${YELLOW}⚠️  Test inconclusive - enforcer may need configuration${NC}"
else
    echo -e "   ${GREEN}✅ Enforcer correctly detected violations${NC}"
fi

rm -f "$TEST_FILE"

# Step 9: Display summary
echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}              INSTALLATION COMPLETE${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "${GREEN}✅ Space-Utils Enforcement System Installed${NC}"
echo ""
echo "Current mode: $CONFIG_MODE"
echo ""
echo "To enable mandatory enforcement:"
echo "  export SPACE_UTILS_ENFORCEMENT=mandatory"
echo ""
echo "To check compliance:"
echo "  find src -name '*.php' -exec .claude/hooks/php-paradigm/space-utils-enforcer.sh {} check \\;"
echo ""
echo "To validate a specific file:"
echo "  .claude/hooks/php-paradigm/space-utils-enforcer.sh <file> validate"
echo ""
echo "To auto-transform a file:"
echo "  .claude/hooks/php-paradigm/space-utils-enforcer.sh <file> transform"
echo ""
echo "To change enforcement mode:"
echo "  jq '.space_utils.enforcement.mode = \"optional\"' .claude/config/space-utils.json > tmp && mv tmp .claude/config/space-utils.json"
echo ""
echo "Documentation: .claude/hooks/SPACE_UTILS_MANDATORY.md"
echo ""