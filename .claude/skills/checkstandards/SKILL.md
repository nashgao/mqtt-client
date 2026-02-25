# Check PHP/Space-Utils Coding Standards

A quick reference command to verify PHP code against Space-Utils coding standards.

## Usage

```bash
/checkstandards [topic]
```

Topics:
- `simplicity` - KISS principle and anti-patterns
- `types` - Strong typing requirements
- `functions` - Space-Utils function usage
- `all` - Complete standards check

## Quick Checks

### 1. Simplicity Check
Before writing any PHP code, ask yourself:
- Can this be done with <10 lines?
- Am I creating abstractions for single use?
- Does this pattern have 3+ real use cases?

### 2. Type Safety Check
Verify all PHP code has:
```php
declare(strict_types=1);
// All parameters typed
function example(string $param, int $count): array
// All properties typed
private string $name;
private ?int $id = null;
```

### 3. Space-Utils Functions
Check if Space-Utils provides helpers for:
- Array operations → use `space_array_*`
- String manipulation → use `space_str_*`
- Type validation → use `space_type_*`

### 4. Anti-Pattern Detection
**RED FLAGS in your code:**
```php
// ❌ Factory for <3 variants
class UserFactory { }

// ❌ Repository for single source
interface UserRepository { }
class MySQLUserRepository implements UserRepository { }

// ❌ Service layer for CRUD
class UserService {
    public function create() { }
    public function update() { }
}
```

### 5. Validation Command
Run after writing PHP:
```bash
php $SPACE_UTILS_PATH/coding-standards/tools/auto-fixer.php <file>
```

## Standards Location
**Hub file**: `$SPACE_UTILS_PATH/coding-standards/claude.md`

> **Setup**: Set the `SPACE_UTILS_PATH` environment variable to your Space-Utils installation directory.

## Key Files to Reference (relative to hub)
- `core-principles/simplicity.md`
- `language-features/strong-typing-standards.md`
- `tools/auto-fixer.php`