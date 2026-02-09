# Space-Utils Path Configuration

The space-utils hooks now support portable path detection that works across different machines and setups.

## How Path Detection Works

The hooks automatically detect the space-utils installation using this priority order:

1. **Environment Variable** (highest priority)
   ```bash
   export SPACE_UTILS_PATH="/path/to/your/space-utils"
   ```

2. **Composer Vendor Directory**
   ```
   vendor/space-platform/utils/
   ```

3. **Common Development Paths**
   - `../space-utils`
   - `../../space-utils`
   - `../lib/space-utils`
   - `../../lib/space-utils`
   - `../dependencies/lib/space-utils`
   - `../../dependencies/lib/space-utils`
   - `$HOME/Desktop/project/space/dependencies/lib/space-utils`

## Configuration Options

### Option 1: Environment Variable (Recommended for Teams)
Add to your shell profile (`.bashrc`, `.zshrc`, etc.):
```bash
export SPACE_UTILS_PATH="/your/path/to/space-utils"
```

### Option 2: Composer Installation
Install space-utils via composer in your project:
```bash
composer require space-platform/utils
```

### Option 3: Relative Path
Place space-utils in a standard location relative to your projects:
- One directory up: `../space-utils`
- In a lib folder: `../lib/space-utils`

## Verification

To verify space-utils is detected correctly:
```bash
# Run the pre-edit hook on any PHP file
.claude/hooks/php-paradigm/space-utils-pre-edit.sh yourfile.php
```

If space-utils is not found, you'll see:
```
⚠️  Space-utils not found. To enable space-utils integration:
   • Set environment variable: export SPACE_UTILS_PATH=/path/to/space-utils
   • Or install via composer: composer require space-platform/utils
```

## Benefits

- ✅ **Portable**: Works on any machine without hardcoded paths
- ✅ **Flexible**: Multiple detection methods for different setups
- ✅ **Team-Friendly**: Each developer can have their own path
- ✅ **Zero Config**: Works automatically with standard setups
- ✅ **Clear Feedback**: Helpful messages when path not found

## Troubleshooting

If space-utils is not being detected:

1. **Check the path exists**:
   ```bash
   ls -la $SPACE_UTILS_PATH
   ```

2. **Verify environment variable**:
   ```bash
   echo $SPACE_UTILS_PATH
   ```

3. **Check composer installation**:
   ```bash
   ls -la vendor/space-platform/utils
   ```

4. **Try explicit path**:
   ```bash
   SPACE_UTILS_PATH="/absolute/path" .claude/hooks/php-paradigm/space-utils-pre-edit.sh test.php
   ```