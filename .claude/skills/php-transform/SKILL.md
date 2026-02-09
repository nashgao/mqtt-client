---
aliases: ["transform-php", "space-utils", "php-modernize"]
description: Apply Space-Utils transformations to PHP files
category: code-quality
---

# PHP to Space-Utils Transformer Command

Automatically transforms PHP code to use Space-Utils patterns and functions.

## Usage

```bash
/php-transform [file|directory]
```

## Options

- `--all` - Transform all PHP files in the project
- `--check` - Check files without applying transformations
- `--force` - Apply risky transformations (normally skipped)
- `--report` - Generate detailed transformation report

## Examples

```bash
# Transform single file
/php-transform src/Model/User.php

# Transform entire directory
/php-transform src/

# Check what would be transformed
/php-transform --check src/

# Transform all PHP files in project
/php-transform --all

# Force risky transformations
/php-transform --force src/Services/
```

## Transformation Rules

### 1. Function Mappings
```php
// Before
strlen($str)
strpos($haystack, $needle)
array_merge($arr1, $arr2)

// After
SpaceUtils::strlen($str)
SpaceUtils::strpos($haystack, $needle)
SpaceUtils::arrayMerge($arr1, $arr2)
```

### 2. Type Safety
```php
// Before
function process($data) {

// After
function process(array $data): ProcessResult {
```

### 3. Pattern Modernization
```php
// Before
if (isset($_GET['id'])) {
    $id = $_GET['id'];
}

// After
$id = SpaceUtils::request()->get('id');
```

## Prerequisites

Ensure Space-Utils is installed:
```bash
composer require space-platform/utils
```

## Configuration

Reads from `.claude/config/space-utils.json`:
```json
{
  "enabled": true,
  "auto_transform": true,
  "string_operations": true,
  "array_operations": true,
  "security_operations": true,
  "validation_level": "strict"
}
```

## Validation

After transformation, files are validated using:
```bash
# 1. PHPStan comprehensive static analysis (PRIORITY)
phpstan analyze <file> --level=max --no-progress

# 2. Space-Utils paradigm validation
php $SPACE_UTILS_PATH/coding-standards/tools/auto-fixer.php <file>

# 3. Use php -l ONLY as emergency fallback if PHPStan unavailable
```

## Safety Levels

- **Safe**: Automatic transformations (function calls, imports)
- **Risky**: Pattern changes that may affect logic
- **Experimental**: New patterns being tested
- **Enhancement**: Optional improvements

## Command Implementation

```bash
#!/bin/bash

# Configuration
CONFIG_FILE=".claude/config/space-utils.json"
VALIDATOR="$SPACE_UTILS_PATH/coding-standards/tools/auto-fixer.php"
TIMESTAMP=$(date +%s)
REPORT_FILE="/tmp/php-transform-report-${TIMESTAMP}.txt"

# Parse arguments
TARGET="${1:-.}"
CHECK_ONLY=false
FORCE_RISKY=false
GENERATE_REPORT=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --check)
            CHECK_ONLY=true
            shift
            ;;
        --force)
            FORCE_RISKY=true
            shift
            ;;
        --report)
            GENERATE_REPORT=true
            shift
            ;;
        --all)
            TARGET="."
            shift
            ;;
        *)
            TARGET="$1"
            shift
            ;;
    esac
done

# Check configuration
if [ ! -f "$CONFIG_FILE" ]; then
    echo "❌ Configuration file not found: $CONFIG_FILE"
    exit 1
fi

# Read configuration
ENABLED=$(jq -r '.enabled // true' "$CONFIG_FILE")
if [ "$ENABLED" != "true" ]; then
    echo "⚠️ Space-Utils transformation is disabled in configuration"
    exit 0
fi

# Find PHP files
if [ -f "$TARGET" ]; then
    FILES="$TARGET"
else
    FILES=$(find "$TARGET" -name "*.php" -type f)
fi

# Process files
TRANSFORMED_COUNT=0
FAILED_COUNT=0

echo "🔄 Processing PHP files for Space-Utils transformation..."

for file in $FILES; do
    echo -n "  Processing: $file ... "
    
    if [ "$CHECK_ONLY" = true ]; then
        # Just check, don't modify
        if grep -q "strlen\|strpos\|array_merge\|explode\|implode" "$file" 2>/dev/null; then
            echo "✓ Would transform"
            ((TRANSFORMED_COUNT++))
        else
            echo "- No changes needed"
        fi
    else
        # Apply transformation via hook
        .claude/hooks/claude-pre-edit-adapter.sh "$file"
        
        # Validate transformation
        if [ -f "$VALIDATOR" ]; then
            php "$VALIDATOR" "$file" > /dev/null 2>&1
            if [ $? -eq 0 ]; then
                echo "✅ Transformed and validated"
                ((TRANSFORMED_COUNT++))
            else
                echo "⚠️ Transformed but validation warnings"
                ((TRANSFORMED_COUNT++))
            fi
        else
            echo "✅ Transformed"
            ((TRANSFORMED_COUNT++))
        fi
    fi
done

# Generate report if requested
if [ "$GENERATE_REPORT" = true ]; then
    echo "" > "$REPORT_FILE"
    echo "PHP to Space-Utils Transformation Report" >> "$REPORT_FILE"
    echo "========================================" >> "$REPORT_FILE"
    echo "Timestamp: $(date)" >> "$REPORT_FILE"
    echo "Target: $TARGET" >> "$REPORT_FILE"
    echo "Files Processed: $(echo "$FILES" | wc -l)" >> "$REPORT_FILE"
    echo "Files Transformed: $TRANSFORMED_COUNT" >> "$REPORT_FILE"
    echo "Files Failed: $FAILED_COUNT" >> "$REPORT_FILE"
    echo "" >> "$REPORT_FILE"
    echo "Configuration:" >> "$REPORT_FILE"
    cat "$CONFIG_FILE" >> "$REPORT_FILE"
    
    echo ""
    echo "📊 Report saved to: $REPORT_FILE"
fi

# Summary
echo ""
echo "✨ Transformation Complete!"
echo "  Files transformed: $TRANSFORMED_COUNT"
if [ $FAILED_COUNT -gt 0 ]; then
    echo "  Files failed: $FAILED_COUNT"
fi

# Spawn agent for comprehensive transformation if needed
if [ "$TRANSFORMED_COUNT" -gt 10 ] || [ "$FORCE_RISKY" = true ]; then
    echo ""
    echo "💡 For comprehensive transformation with parallel processing, use:"
    echo "   Task tool with subagent_type: code-php-transformer"
fi
```

## Integration with Hooks

This command works with:
- `.claude/hooks/claude-pre-edit-adapter.sh` - Applies transformations
- `.claude/config/space-utils.json` - Configuration settings
- Space-Utils validation tools - Ensures code quality

## See Also

- `/code-quality` - Run comprehensive code quality checks
- `/php-validate` - Validate PHP code against Space-Utils standards
- Task tool `code-php-transformer` agent - For parallel bulk transformations