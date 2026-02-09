#!/bin/bash

# Hooks Validation Script
# Verifies Claude Code Hooks Mastery integration is working

set -e

echo "🔍 Claude Code Hooks Validation"
echo "================================"
echo ""

# Check 1: Hooks directory exists
echo "✓ Checking hooks installation..."
if [ -d ".claude/hooks" ]; then
    hook_count=$(find .claude/hooks -name "*.py" -type f | wc -l | tr -d ' ')
    echo "  ✅ Found $hook_count Python hooks"
else
    echo "  ❌ .claude/hooks directory not found"
    exit 1
fi

# Check 2: Required hooks present
echo ""
echo "✓ Checking required hooks..."
required_hooks=(
    "notification.py"
    "session_start.py"
)

missing_hooks=0
for hook in "${required_hooks[@]}"; do
    if [ -f ".claude/hooks/$hook" ]; then
        echo "  ✅ $hook"
    else
        echo "  ❌ $hook (missing)"
        ((missing_hooks++))
    fi
done

if [ $missing_hooks -gt 0 ]; then
    echo ""
    echo "❌ $missing_hooks hooks are missing!"
    exit 1
fi

# Check 3: Settings configuration
echo ""
echo "✓ Checking settings configuration..."
if [ -f ".claude/settings.local.json" ]; then
    if grep -q "\"hooks\":" .claude/settings.local.json; then
        echo "  ✅ Hooks configuration found"
    else
        echo "  ⚠️  Settings file exists but no hooks configured"
    fi
else
    echo "  ⚠️  No settings.local.json found"
fi

# Check 4: UV availability (required for Python hooks)
echo ""
echo "✓ Checking dependencies..."
if command -v uv &> /dev/null; then
    echo "  ✅ UV (Python package manager) installed"
else
    echo "  ⚠️  UV not installed - hooks may not execute"
    echo "     Install: curl -LsSf https://astral.sh/uv/install.sh | sh"
fi

# Summary
echo ""
echo "================================"
echo "📊 Validation Summary"
echo "================================"
echo "  Hooks installed: $hook_count"
echo "  Missing hooks: $missing_hooks"
echo "  Status: $([ $missing_hooks -eq 0 ] && echo '✅ READY' || echo '❌ INCOMPLETE')"
echo ""

if [ $missing_hooks -eq 0 ]; then
    echo "🎉 All hooks are installed correctly!"
    echo ""
    echo "Next steps:"
    echo "  1. Start Claude Code in this directory"
    echo "  2. Watch for hook execution messages"
    echo "  3. Check .claude/logs/ for hook activity"
    echo ""
    echo "To enable debug output:"
    echo "  export CLAUDE_HOOKS_DEBUG=true"
    exit 0
else
    echo "⚠️  Some hooks are missing. Run 'claude-merge' again."
    exit 1
fi
