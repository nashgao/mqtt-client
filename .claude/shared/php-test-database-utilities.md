# PHP Database Testing Utilities

## 🚨 MANDATORY: Rule Enforcement Integration

**This shared resource operates under the Rule Enforcement Framework**
**Reference: `/templates/agents/_shared/rule-enforcement-framework.md`**

**ALL USERS OF THIS RESOURCE MUST:**
- ✅ Validate scope before any file modifications
- ✅ Respect unit/integration test separation
- ✅ Execute verification commands before claiming success
- ✅ Never make architectural decisions beyond assigned scope

**VIOLATION CONSEQUENCES:** Immediate halt and escalation to user

---

## Overview

Comprehensive database testing utilities for PHP projects using hyperf/testing with transaction management, data seeding, and test isolation.

## Database Test Trait

```php
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Test\Traits;

use Hyperf\Database\ConnectionInterface;
use Hyperf\Database\Schema\Schema;

/**
 * Database testing trait with transaction management
 */
trait DatabaseTestTrait
{
    /**
     * Database connection
     */
    protected ConnectionInterface $testDb;

    /**
     * Tables to truncate after each test
     */
    protected array $truncateTables = [];

    /**
     * Whether to use transactions for test isolation
     */
    protected bool $useTransactions = true;

    /**
     * Setup database for testing
     */
    protected function setUpDatabase(): void
    {
        $this->testDb = $this->get(ConnectionInterface::class);

        if ($this->useTransactions) {
            $this->testDb->beginTransaction();
        }
    }

    /**
     * Clean up database after testing
     */
    protected function tearDownDatabase(): void
    {
        if ($this->useTransactions) {
            $this->testDb->rollBack();
        } else {
            $this->truncateTestTables();
        }
    }

    /**
     * Truncate specified tables
     */
    protected function truncateTestTables(): void
    {
        foreach ($this->truncateTables as $table) {
            $this->testDb->table($table)->truncate();
        }
    }

    /**
     * Seed table with data
     */
    protected function seedTable(string $table, array $data): void
    {
        if (empty($data)) {
            return;
        }

        // Handle single record vs multiple records
        if (!isset($data[0]) || !is_array($data[0])) {
            $data = [$data];
        }

        $this->testDb->table($table)->insert($data);
    }

    /**
     * Create test record
     */
    protected function createRecord(string $table, array $attributes = []): array
    {
        $record = array_merge($this->getDefaultAttributes($table), $attributes);
        $this->testDb->table($table)->insert($record);
        
        return $record;
    }

    /**
     * Create multiple test records
     */
    protected function createRecords(string $table, int $count, array $attributes = []): array
    {
        $records = [];
        for ($i = 0; $i < $count; $i++) {
            $records[] = $this->createRecord($table, $attributes);
        }
        return $records;
    }

    /**
     * Get default attributes for table
     */
    protected function getDefaultAttributes(string $table): array
    {
        $defaults = [
            'users' => [
                'id' => $this->generateId(),
                'name' => 'Test User',
                'email' => $this->generateEmail(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            'posts' => [
                'id' => $this->generateId(),
                'title' => 'Test Post',
                'content' => 'Test content',
                'user_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        return $defaults[$table] ?? [];
    }

    /**
     * Generate unique ID
     */
    protected function generateId(): int
    {
        return mt_rand(1, 999999);
    }

    /**
     * Generate unique email
     */
    protected function generateEmail(): string
    {
        return 'test' . mt_rand(1000, 9999) . '@example.com';
    }

    /**
     * Assert record exists in database
     */
    protected function assertDatabaseHas(string $table, array $conditions): void
    {
        $count = $this->testDb->table($table)->where($conditions)->count();
        $this->assertGreaterThan(0, $count, "Record not found in table {$table}");
    }

    /**
     * Assert record doesn't exist in database
     */
    protected function assertDatabaseMissing(string $table, array $conditions): void
    {
        $count = $this->testDb->table($table)->where($conditions)->count();
        $this->assertEquals(0, $count, "Unexpected record found in table {$table}");
    }

    /**
     * Assert database count
     */
    protected function assertDatabaseCount(string $table, int $expectedCount, array $conditions = []): void
    {
        $query = $this->testDb->table($table);
        
        if (!empty($conditions)) {
            $query->where($conditions);
        }
        
        $actualCount = $query->count();
        $this->assertEquals($expectedCount, $actualCount, "Expected {$expectedCount} records in {$table}, found {$actualCount}");
    }

    /**
     * Get record from database
     */
    protected function getRecord(string $table, array $conditions): ?array
    {
        $record = $this->testDb->table($table)->where($conditions)->first();
        return $record ? (array) $record : null;
    }

    /**
     * Get multiple records from database
     */
    protected function getRecords(string $table, array $conditions = []): array
    {
        $query = $this->testDb->table($table);
        
        if (!empty($conditions)) {
            $query->where($conditions);
        }
        
        return array_map(fn($record) => (array) $record, $query->get()->toArray());
    }

    /**
     * Execute raw SQL query
     */
    protected function rawQuery(string $sql, array $bindings = []): array
    {
        return array_map(fn($record) => (array) $record, $this->testDb->select($sql, $bindings));
    }

    /**
     * Check if table exists
     */
    protected function tableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }

    /**
     * Check if column exists
     */
    protected function columnExists(string $table, string $column): bool
    {
        return Schema::hasColumn($table, $column);
    }

    /**
     * Get table columns
     */
    protected function getTableColumns(string $table): array
    {
        return Schema::getColumnListing($table);
    }
}
```

## Database Factory

```php
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Test\Database;

use Hyperf\Database\ConnectionInterface;

/**
 * Database factory for creating test data
 */
class DatabaseFactory
{
    private ConnectionInterface $connection;
    private array $sequences = [];

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Create factory for specific model
     */
    public function for(string $table): TableFactory
    {
        return new TableFactory($this->connection, $table, $this->sequences);
    }

    /**
     * Reset sequences
     */
    public function resetSequences(): void
    {
        $this->sequences = [];
    }

    /**
     * Get next sequence number
     */
    public function sequence(string $key): int
    {
        if (!isset($this->sequences[$key])) {
            $this->sequences[$key] = 0;
        }
        
        return ++$this->sequences[$key];
    }
}

/**
 * Table-specific factory
 */
class TableFactory
{
    private ConnectionInterface $connection;
    private string $table;
    private array $sequences;
    private array $attributes = [];

    public function __construct(ConnectionInterface $connection, string $table, array &$sequences)
    {
        $this->connection = $connection;
        $this->table = $table;
        $this->sequences = &$sequences;
    }

    /**
     * Set attributes
     */
    public function with(array $attributes): self
    {
        $this->attributes = array_merge($this->attributes, $attributes);
        return $this;
    }

    /**
     * Create single record
     */
    public function create(array $overrides = []): array
    {
        $record = array_merge(
            $this->getDefaults(),
            $this->attributes,
            $overrides
        );

        $this->connection->table($this->table)->insert($record);
        return $record;
    }

    /**
     * Create multiple records
     */
    public function createMany(int $count, array $overrides = []): array
    {
        $records = [];
        for ($i = 0; $i < $count; $i++) {
            $records[] = $this->create($overrides);
        }
        return $records;
    }

    /**
     * Make record without inserting
     */
    public function make(array $overrides = []): array
    {
        return array_merge(
            $this->getDefaults(),
            $this->attributes,
            $overrides
        );
    }

    /**
     * Make multiple records without inserting
     */
    public function makeMany(int $count, array $overrides = []): array
    {
        $records = [];
        for ($i = 0; $i < $count; $i++) {
            $records[] = $this->make($overrides);
        }
        return $records;
    }

    /**
     * Get default attributes for table
     */
    private function getDefaults(): array
    {
        $sequence = $this->getSequence();
        
        return match ($this->table) {
            'users' => [
                'id' => $sequence,
                'name' => "User {$sequence}",
                'email' => "user{$sequence}@example.com",
                'password' => password_hash('password', PASSWORD_DEFAULT),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            'posts' => [
                'id' => $sequence,
                'title' => "Post {$sequence}",
                'content' => "Content for post {$sequence}",
                'user_id' => 1,
                'status' => 'published',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            'categories' => [
                'id' => $sequence,
                'name' => "Category {$sequence}",
                'slug' => "category-{$sequence}",
                'description' => "Description for category {$sequence}",
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            default => [
                'id' => $sequence,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        };
    }

    /**
     * Get next sequence number
     */
    private function getSequence(): int
    {
        if (!isset($this->sequences[$this->table])) {
            $this->sequences[$this->table] = 0;
        }
        
        return ++$this->sequences[$this->table];
    }
}
```

## Migration Helper

```php
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Test\Database;

use Hyperf\Database\ConnectionInterface;
use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;

/**
 * Migration helper for test database setup
 */
class MigrationHelper
{
    private ConnectionInterface $connection;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Create test tables
     */
    public function createTestTables(): void
    {
        $this->createUsersTable();
        $this->createPostsTable();
        $this->createCategoriesTable();
        // Add more tables as needed
    }

    /**
     * Drop test tables
     */
    public function dropTestTables(): void
    {
        Schema::dropIfExists('posts');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('users');
        // Drop in reverse order due to foreign keys
    }

    /**
     * Create users table
     */
    private function createUsersTable(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Create posts table
     */
    private function createPostsTable(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->string('status')->default('draft');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('category_id')->references('id')->on('categories');
        });
    }

    /**
     * Create categories table
     */
    private function createCategoriesTable(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Seed test data
     */
    public function seedTestData(): void
    {
        $this->connection->table('users')->insert([
            [
                'id' => 1,
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => password_hash('password', PASSWORD_DEFAULT),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->connection->table('categories')->insert([
            [
                'id' => 1,
                'name' => 'Technology',
                'slug' => 'technology',
                'description' => 'Technology related posts',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->connection->table('posts')->insert([
            [
                'id' => 1,
                'title' => 'Test Post',
                'content' => 'This is a test post content',
                'status' => 'published',
                'user_id' => 1,
                'category_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
```

## Database Seeder

```php
<?php

declare(strict_types=1);

namespace {{NAMESPACE}}\Test\Database;

/**
 * Database seeder for test data
 */
class DatabaseSeeder
{
    private DatabaseFactory $factory;

    public function __construct(DatabaseFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Run all seeders
     */
    public function run(): void
    {
        $this->seedUsers();
        $this->seedCategories();
        $this->seedPosts();
    }

    /**
     * Seed users
     */
    public function seedUsers(int $count = 10): array
    {
        return $this->factory->for('users')->createMany($count);
    }

    /**
     * Seed categories
     */
    public function seedCategories(int $count = 5): array
    {
        return $this->factory->for('categories')->createMany($count);
    }

    /**
     * Seed posts
     */
    public function seedPosts(int $count = 20): array
    {
        return $this->factory->for('posts')->createMany($count);
    }

    /**
     * Seed specific scenario
     */
    public function seedScenario(string $scenario): void
    {
        match ($scenario) {
            'empty' => $this->seedEmpty(),
            'basic' => $this->seedBasic(),
            'full' => $this->seedFull(),
            default => throw new \InvalidArgumentException("Unknown scenario: {$scenario}")
        };
    }

    /**
     * Empty scenario - minimal data
     */
    private function seedEmpty(): void
    {
        $this->factory->for('users')->create(['email' => 'admin@example.com']);
    }

    /**
     * Basic scenario - typical test data
     */
    private function seedBasic(): void
    {
        $users = $this->seedUsers(3);
        $categories = $this->seedCategories(2);
        $this->seedPosts(5);
    }

    /**
     * Full scenario - comprehensive test data
     */
    private function seedFull(): void
    {
        $this->run();
    }
}
```

## 🚨 ZERO TOLERANCE ENFORCEMENT

**MANDATORY - ALL PHP tests must achieve PERFECT execution:**

### Success Criteria (ALL must be met)
- ✅ **0 Failed Tests** - Every single test must pass
- ✅ **0 Errors** - No runtime errors allowed
- ✅ **0 Warnings** - Warnings are treated as failures
- ✅ **0 Deprecations** - Deprecation notices block success
- ✅ **0 Incomplete Tests** - Incomplete tests are failures
- ✅ **0 Risky Tests** - Risky test detection must pass
- ✅ **0 Skipped Tests** - Unless explicitly allowed with justification

### PHPUnit Strict Mode Flags (MANDATORY)
```bash
phpunit --fail-on-warning --fail-on-risky --fail-on-incomplete --fail-on-skipped --stop-on-failure
```

### PHP Error Handling
```php
error_reporting(E_ALL);
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
```

## Usage Example

```php
<?php

declare(strict_types=1);

namespace App\Test\Cases\Integration;

use App\Test\TestCase;
use App\Test\Traits\DatabaseTestTrait;
use App\Test\Database\DatabaseFactory;
use PHPUnit\Framework\Attributes\Group;

#[Group('integration')]
class UserServiceTest extends TestCase
{
    use DatabaseTestTrait;

    private DatabaseFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
        $this->factory = new DatabaseFactory($this->testDb);
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    public function testCreateUser(): void
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        $user = $this->factory->for('users')->create($userData);

        $this->assertDatabaseHas('users', $userData);
        $this->assertEquals('John Doe', $user['name']);
    }

    public function testUserWithPosts(): void
    {
        // Create user with factory
        $user = $this->factory->for('users')->create();

        // Create posts for user
        $posts = $this->factory->for('posts')
            ->with(['user_id' => $user['id']])
            ->createMany(3);

        $this->assertDatabaseCount('posts', 3, ['user_id' => $user['id']]);
    }
}
```

These database utilities provide comprehensive support for database testing with proper isolation, factories, and migration management for hyperf/testing environments.