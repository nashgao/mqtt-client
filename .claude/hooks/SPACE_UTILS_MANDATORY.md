# Space-Utils Mandatory Enforcement Mode

This document describes the mandatory enforcement mode for Space-Utils standards in PHP projects using Claude Code.

## 🚀 Quick Start

### Enable Mandatory Mode (3 Methods)

#### Method 1: Configuration File (Recommended)
```bash
# Copy the mandatory config to your project
cp templates/config/space-utils-mandatory.json .claude/config/space-utils.json

# Or modify existing config
jq '.space_utils.enforcement.mode = "mandatory"' .claude/config/space-utils.json > tmp && mv tmp .claude/config/space-utils.json
```

#### Method 2: Environment Variable
```bash
# Set for current session
export SPACE_UTILS_ENFORCEMENT=mandatory

# Or add to your shell profile
echo 'export SPACE_UTILS_ENFORCEMENT=mandatory' >> ~/.bashrc
```

#### Method 3: Settings File
```bash
# Use the mandatory settings template
cp templates/hooks/settings-template-mandatory.json .claude/settings.local.json
```

## 📋 Enforcement Modes

### **optional** (Default)
- Standards are suggested but not enforced
- Auto-fixes applied where possible
- No blocking on violations
- Informational warnings only

### **mandatory**
- Standards are strictly enforced
- Blocks edits that violate standards
- Auto-transforms code where possible
- Rolls back non-compliant changes
- Prevents commits with violations

### **strict**
- Same as mandatory plus:
- Detailed line-by-line violation reporting
- No tolerance for any violations
- Comprehensive validation of all patterns
- Performance impact analysis

## 🎯 What Gets Enforced

### Native Function Replacements

#### String Operations
```php
// ❌ PROHIBITED
if (is_string($value)) {
    $length = strlen($value);
    $upper = strtoupper($value);
}

// ✅ REQUIRED
use SpacePlatform\Utils\Functional\Monad\Scalar\IString;

if (IString::of($value)->isString()) {
    $length = IString::of($value)->length();
    $upper = IString::of($value)->toUpper();
}
```

#### Array Operations
```php
// ❌ PROHIBITED
$result = array_map(fn($x) => $x * 2, $array);
$filtered = array_filter($array, fn($x) => $x > 0);

// ✅ REQUIRED
use SpacePlatform\Utils\Structure\Collection;

$result = Collection::from($array)->map(fn($x) => $x * 2)->all();
$filtered = Collection::from($array)->filter(fn($x) => $x > 0)->all();
```

#### File Operations
```php
// ❌ PROHIBITED
$content = file_get_contents($path);
file_put_contents($path, $data);

// ✅ REQUIRED
use SpacePlatform\Utils\FileSystem\Component\File\File;

$content = File::of($path)->readFile();
File::of($path)->writeFile($data);
```

### Design Pattern Violations

#### Factory Pattern
```php
// ❌ PROHIBITED - Factory with <3 variants
class UserFactory {
    public function createAdmin() { }
    public function createUser() { }
    // Only 2 variants - VIOLATION!
}

// ✅ REQUIRED - 3+ variants or use simple constructor
class UserFactory {
    public function createAdmin() { }
    public function createUser() { }
    public function createModerator() { }
    // 3+ variants - OK
}
```

### Type Safety Requirements

#### Strict Types Declaration
```php
// ❌ PROHIBITED
<?php
namespace App;

// ✅ REQUIRED
<?php
declare(strict_types=1);

namespace App;
```

#### Mixed Types
```php
// ❌ PROHIBITED
public function process(mixed $data): mixed

// ✅ REQUIRED - Use specific types or add justification
public function process(string|array $data): string
// OR
/** @phpstan-ignore-next-line @justified Complex union type */
public function process(mixed $data): mixed
```

## 🔧 Installation

### Step 1: Copy Templates
```bash
# Copy hooks
cp -r templates/hooks/php-paradigm/* .claude/hooks/php-paradigm/

# Copy config
cp templates/config/space-utils-mandatory.json .claude/config/space-utils.json

# Copy settings
cp templates/hooks/settings-template-mandatory.json .claude/settings.local.json
```

### Step 2: Make Scripts Executable
```bash
chmod +x .claude/hooks/php-paradigm/*.sh
```

### Step 3: Configure Paths
```bash
# Edit .claude/config/space-utils.json
# Update SPACE_UTILS_PATH to your installation
```

### Step 4: Test Installation
```bash
# Create a test file with violations
echo '<?php
class Test {
    public function test($value) {
        if (is_string($value)) {
            return strlen($value);
        }
    }
}' > /tmp/test.php

# Run enforcer
.claude/hooks/php-paradigm/space-utils-enforcer.sh /tmp/test.php validate

# Should show violations
```

## 📊 Monitoring Compliance

### Check Current Mode
```bash
# Via config
jq '.space_utils.enforcement.mode' .claude/config/space-utils.json

# Via environment
echo $SPACE_UTILS_ENFORCEMENT
```

### View Violations
```bash
# Latest violations
cat .claude/logs/violations.json

# Enforcement log
tail -f .claude/logs/enforcement.log
```

### Compliance Dashboard
```bash
# Create compliance monitoring script
cat > .claude/hooks/check-compliance.sh << 'EOF'
#!/bin/bash
find src -name "*.php" -exec .claude/hooks/php-paradigm/space-utils-enforcer.sh {} check \; 2>&1 | 
    grep -c "VIOLATION" | 
    xargs -I {} echo "Total violations: {}"
EOF
chmod +x .claude/hooks/check-compliance.sh

# Run compliance check
.claude/hooks/check-compliance.sh
```

## 🚫 How Enforcement Works

### Pre-Edit Phase
1. Checks if file already has violations
2. Blocks edit if existing violations (mandatory mode)
3. Logs attempt in enforcement log

### Post-Edit Phase
1. Attempts automatic transformation
2. Validates transformed code
3. If still non-compliant:
   - **optional**: Shows warnings
   - **mandatory**: Rolls back changes
   - **strict**: Blocks and reports detailed violations

### Git Commit Phase
1. Pre-commit hook validates all PHP files
2. Blocks commit if violations found
3. Reports specific files and violations

## 🛠️ Configuration Options

### Full Configuration Example
```json
{
  "space_utils": {
    "enabled": true,
    "enforcement": {
      "mode": "mandatory",
      "block_on_violation": true,
      "auto_rollback": true,
      "git_hook_integration": true,
      "validation_level": "strict",
      "rollback_on_failure": true,
      "violations_threshold": 0,
      "reporting": {
        "log_violations": true,
        "violations_file": ".claude/logs/violations.json",
        "enforcement_log": ".claude/logs/enforcement.log"
      }
    },
    "validation_rules": {
      "native_functions": {
        "string_functions": {
          "prohibited": ["is_string", "strlen", "substr"],
          "replacement": "IString::of()",
          "severity": "error"
        },
        "array_functions": {
          "prohibited": ["array_map", "array_filter"],
          "replacement": "Collection::from()",
          "severity": "error"
        }
      },
      "patterns": {
        "factory_pattern": {
          "min_variants": 3,
          "severity": "error"
        },
        "mixed_types": {
          "require_justification": true,
          "severity": "warning"
        }
      }
    }
  }
}
```

## 🔄 Switching Between Modes

### Temporary Override
```bash
# For single command
SPACE_UTILS_ENFORCEMENT=optional php script.php

# For session
export SPACE_UTILS_ENFORCEMENT=optional
```

### Permanent Change
```bash
# Update config
jq '.space_utils.enforcement.mode = "optional"' .claude/config/space-utils.json > tmp && mv tmp .claude/config/space-utils.json
```

### Disable Completely
```bash
# Set to disabled
jq '.space_utils.enabled = false' .claude/config/space-utils.json > tmp && mv tmp .claude/config/space-utils.json
```

## 🧪 Testing Your Setup

### Test File with All Violations
```php
<?php
// test-violations.php - Should trigger all violation types

class TestFactory {  // Factory with <3 variants
    public function createOne() {}
    public function createTwo() {}
}

class TestClass {
    public function testMethod($value) {  // Missing type hints
        // String function violations
        if (is_string($value)) {
            $length = strlen($value);
        }
        
        // Array function violations
        $result = array_map(fn($x) => $x * 2, [1, 2, 3]);
        
        // File function violations
        $content = file_get_contents('file.txt');
        
        // Mixed type violation
        return $result;
    }
}
```

### Run Validation
```bash
# Should show all violations
.claude/hooks/php-paradigm/space-utils-enforcer.sh test-violations.php validate

# Try transformation
.claude/hooks/php-paradigm/space-utils-enforcer.sh test-violations.php transform

# Check if violations remain
.claude/hooks/php-paradigm/space-utils-enforcer.sh test-violations.php validate
```

## ⚠️ Troubleshooting

### Enforcer Not Found
```bash
# Check if file exists
ls -la .claude/hooks/php-paradigm/space-utils-enforcer.sh

# Make executable
chmod +x .claude/hooks/php-paradigm/space-utils-enforcer.sh
```

### Config Not Loading
```bash
# Validate JSON
jq . .claude/config/space-utils.json

# Check permissions
ls -la .claude/config/
```

### Hooks Not Running
```bash
# Check settings
cat .claude/settings.local.json | jq '.hooks'

# Check permissions in settings
cat .claude/settings.local.json | jq '.permissions.allow'
```

### Too Many False Positives
```bash
# Adjust validation rules
jq '.space_utils.validation_rules.native_functions.string_functions.prohibited = ["is_string"]' \
    .claude/config/space-utils.json > tmp && mv tmp .claude/config/space-utils.json
```

## 📈 Benefits of Mandatory Mode

1. **Consistency**: All code follows Space-Utils patterns
2. **Performance**: 2-5x faster operations with Space-Utils
3. **Type Safety**: Strong typing throughout codebase
4. **Quality Gates**: Prevents technical debt accumulation
5. **Team Alignment**: Everyone follows same standards
6. **Automation**: Auto-transformation reduces manual work

## 🔗 Related Documentation

- [Space-Utils Patterns](../shared/space-utils-patterns.md)
- [PHP Paradigm Standards](./README.md)
- [Claude Code Hooks](../README.md)
- [Function Registry](../../mappings/function-registry.json)

## 📝 Migration Guide

### From Optional to Mandatory

1. **Audit Current Code**
   ```bash
   find src -name "*.php" -exec .claude/hooks/php-paradigm/space-utils-enforcer.sh {} check \;
   ```

2. **Fix Critical Violations**
   ```bash
   # Auto-transform where possible
   find src -name "*.php" -exec .claude/hooks/php-paradigm/space-utils-enforcer.sh {} transform \;
   ```

3. **Enable Mandatory Mode**
   ```bash
   export SPACE_UTILS_ENFORCEMENT=mandatory
   ```

4. **Monitor and Adjust**
   - Review enforcement logs
   - Adjust validation rules as needed
   - Train team on Space-Utils patterns

## 🤝 Contributing

To improve the enforcement system:

1. Edit validation rules in `space-utils-enforcer.sh`
2. Add new patterns to config schema
3. Test thoroughly with sample files
4. Document new validations
5. Submit improvements back to template repository