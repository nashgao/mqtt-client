# Space-Utils Design Patterns

This document contains the canonical patterns from space-utils that should be used across all projects.

## Core Patterns

### 1. Entity Pattern
**Always use Entity for structured data models**

```php
// ❌ WRONG: Plain array or stdClass
$user = [
    'name' => 'John',
    'email' => 'john@example.com'
];

// ✅ CORRECT: Entity with validation
use SpacePlatform\Utils\Entity\Entity;

class User extends Entity
{
    protected string $name;
    protected string $email;
    
    protected function validate(): void
    {
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email');
        }
    }
}

$user = User::fromArray([
    'name' => 'John',
    'email' => 'john@example.com'
]);
```

### 2. Collection Pattern
**Use Collection for complex array operations**

```php
// ❌ WRONG: Multiple array functions
$activeUserNames = array_map(
    fn($user) => $user->name,
    array_filter(
        $users,
        fn($user) => $user->isActive()
    )
);

// ✅ CORRECT: Collection chain
use SpacePlatform\Utils\Structure\Collection;

$activeUserNames = Collection::from($users)
    ->filter(fn($user) => $user->isActive())
    ->map(fn($user) => $user->name)
    ->toArray();
```

### 3. Pipeline Pattern
**Use Pipeline for data transformation flows**

```php
// ❌ WRONG: Nested function calls
$slug = strtolower(
    str_replace(' ', '-', 
        trim($title)
    )
);

// ✅ CORRECT: Pipeline
use SpacePlatform\Utils\Pipeline\Pipeline;

$slug = Pipeline::create($title)
    ->pipe([Str::class, 'trim'])
    ->pipe([Str::class, 'replace'], ' ', '-')
    ->pipe([Str::class, 'lower'])
    ->execute();
```

### 4. Concurrent Pattern
**Use Concurrent for parallel processing**

```php
// ❌ WRONG: Sequential processing
$results = [];
foreach ($urls as $url) {
    $results[] = fetchData($url);
}

// ✅ CORRECT: Concurrent processing
use SpacePlatform\Utils\Concurrent\Concurrent;

$results = Concurrent::map($urls, fn($url) => fetchData($url));
```

### 5. Result Monad Pattern
**Use Result for error handling**

```php
// ❌ WRONG: Try/catch with mixed returns
function divide($a, $b) {
    try {
        if ($b === 0) {
            throw new \Exception('Division by zero');
        }
        return $a / $b;
    } catch (\Exception $e) {
        return null;
    }
}

// ✅ CORRECT: Result monad
use SpacePlatform\Utils\Functional\Result;

function divide($a, $b): Result {
    return Result::try(function() use ($a, $b) {
        if ($b === 0) {
            throw new \Exception('Division by zero');
        }
        return $a / $b;
    });
}

$result = divide(10, 2)
    ->map(fn($value) => $value * 2)
    ->getOrElse(0);
```

### 6. Maybe Monad Pattern
**Use Maybe for nullable values**

```php
// ❌ WRONG: Null checks everywhere
$user = getUserById($id);
if ($user !== null) {
    $name = $user->getName();
    if ($name !== null) {
        $upperName = strtoupper($name);
    }
}

// ✅ CORRECT: Maybe monad
use SpacePlatform\Utils\Functional\Maybe;

$upperName = Maybe::of(getUserById($id))
    ->map(fn($user) => $user->getName())
    ->map(fn($name) => Str::upper($name))
    ->getOrElse('UNKNOWN');
```

### 7. Either Pattern
**Use Either for success/failure results**

```php
// ❌ WRONG: Array with success flag
function validateInput($data) {
    if (empty($data['email'])) {
        return ['success' => false, 'error' => 'Email required'];
    }
    return ['success' => true, 'data' => $data];
}

// ✅ CORRECT: Either monad
use SpacePlatform\Utils\Functional\Either;

function validateInput($data): Either {
    if (empty($data['email'])) {
        return Either::left('Email required');
    }
    return Either::right($data);
}

$result = validateInput($input)
    ->map(fn($data) => processData($data))
    ->fold(
        fn($error) => "Error: $error",
        fn($data) => "Success: " . json_encode($data)
    );
```

### 8. Object Pool Pattern
**Use ObjectPool for resource management**

```php
// ❌ WRONG: Creating new connections repeatedly
function query($sql) {
    $connection = new DatabaseConnection();
    $result = $connection->execute($sql);
    $connection->close();
    return $result;
}

// ✅ CORRECT: Object pool
use SpacePlatform\Utils\Entity\Pool\ObjectPool;

class DatabasePool extends ObjectPool {
    protected function createObject(): DatabaseConnection {
        return new DatabaseConnection();
    }
    
    protected function resetObject($connection): void {
        $connection->reset();
    }
}

$pool = new DatabasePool();
$connection = $pool->get();
try {
    $result = $connection->execute($sql);
} finally {
    $pool->put($connection);
}
```

### 9. Batch Processing Pattern
**Use Batch for chunked processing**

```php
// ❌ WRONG: Processing all at once
$results = array_map(fn($item) => process($item), $largeArray);

// ✅ CORRECT: Batch processing
use SpacePlatform\Utils\Batch\Batch;

$results = Batch::create($largeArray)
    ->chunk(100)
    ->process(fn($batch) => array_map(fn($item) => process($item), $batch))
    ->flatten()
    ->toArray();
```

### 10. AsyncIO Pattern
**Use AsyncIO for asynchronous operations**

```php
// ❌ WRONG: Blocking I/O
$file1 = file_get_contents('file1.txt');
$file2 = file_get_contents('file2.txt');
$result = $file1 . $file2;

// ✅ CORRECT: AsyncIO
use SpacePlatform\Utils\Functional\Async\AsyncIO;

$result = AsyncIO::parallel([
    AsyncIO::of(fn() => FileSystem::read('file1.txt')),
    AsyncIO::of(fn() => FileSystem::read('file2.txt'))
])->map(fn($contents) => implode('', $contents))
  ->run();
```

## Pattern Selection Guide

| Scenario | Pattern to Use | Space-Utils Class |
|----------|---------------|-------------------|
| Data models with validation | Entity | `Entity` |
| Array manipulation | Collection | `Collection` |
| Data transformation pipeline | Pipeline | `Pipeline` |
| Parallel processing | Concurrent | `Concurrent` |
| Error handling | Result | `Result` |
| Nullable values | Maybe | `Maybe` |
| Success/failure branching | Either | `Either` |
| Resource pooling | ObjectPool | `ObjectPool` |
| Large dataset processing | Batch | `Batch` |
| Async operations | AsyncIO | `AsyncIO` |

## Anti-Patterns to Avoid

### ❌ Manual Array Manipulation
```php
// AVOID THIS
$filtered = [];
foreach ($items as $item) {
    if ($item->isValid()) {
        $filtered[] = $item;
    }
}
```

### ❌ Nested Try-Catch
```php
// AVOID THIS
try {
    try {
        // code
    } catch (Exception $e) {
        // handle
    }
} catch (Exception $e) {
    // handle
}
```

### ❌ Null Propagation
```php
// AVOID THIS
if ($user !== null && $user->getProfile() !== null && $user->getProfile()->getAvatar() !== null) {
    // use avatar
}
```

### ❌ God Objects
```php
// AVOID THIS
class UserService {
    public function validate() { }
    public function save() { }
    public function sendEmail() { }
    public function generateReport() { }
    // 100 more methods...
}
```

## Best Practices

1. **Always prefer space-utils over native PHP functions**
2. **Use type declarations for all parameters and returns**
3. **Leverage monads for cleaner error handling**
4. **Use Collections instead of array functions**
5. **Apply Pipeline pattern for transformations**
6. **Use Concurrent for I/O-bound operations**
7. **Implement Entity pattern for all models**
8. **Use ObjectPool for expensive resources**
9. **Apply Batch processing for large datasets**
10. **Document with bidirectional annotations**