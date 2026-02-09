# Test Isolation & Dependency Management Strategy

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

## 🔒 MANDATORY TEST ISOLATION ARCHITECTURE

**CRITICAL: Prevent test cross-contamination through strict isolation boundaries**

### 🎯 Core Isolation Principles

1. **Separate Base Classes**: NEVER share base classes between test types
2. **Independent Bootstraps**: Each test type gets its own bootstrap file
3. **Dependency Tracking**: Track all test modifications and impacts
4. **Fix Impact Analysis**: Analyze ripple effects before modifications
5. **Rollback Capability**: Enable quick rollback of breaking changes

## 📦 Test Type Separation Architecture

### Unit Test Base Class
```php
<?php
namespace App\Test\Unit;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Mockery;

abstract class UnitTestCase extends PHPUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Unit test specific setup - NO real services
        $this->initializeMocks();
        $this->preventDatabaseConnections();
        $this->disableExternalServices();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        $this->clearMockCache();
        parent::tearDown();
    }

    private function preventDatabaseConnections(): void
    {
        // Block any real DB connections
        if (class_exists('\\DB')) {
            \\DB::shouldReceive('connection')->andThrow(
                new \\Exception('Unit tests MUST NOT use real database')
            );
        }
    }
}
```

### Integration Test Base Class
```php
<?php
namespace App\Test\Integration;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use App\Test\Traits\DatabaseTransactions;
use App\Test\Traits\RefreshDatabase;

abstract class IntegrationTestCase extends PHPUnitTestCase
{
    use DatabaseTransactions;
    use RefreshDatabase;

    protected static bool $dbInitialized = false;

    protected function setUp(): void
    {
        parent::setUp();
        // Integration test specific setup - REAL services allowed
        $this->initializeDatabase();
        $this->setupTestEnvironment();
        $this->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->rollbackTransaction();
        $this->cleanupTestData();
        parent::tearDown();
    }
}
```

## 🔍 Test Dependency Tracking System

### Dependency Graph Builder
```bash
#!/bin/bash

build_test_dependency_graph() {
    local session_id="$1"
    local graph_file="/tmp/test-sessions/${session_id}/dependency-graph.json"

    # Initialize dependency graph
    cat > "$graph_file" << 'EOF'
{
    "nodes": {},
    "edges": [],
    "shared_resources": {
        "base_classes": {},
        "fixtures": {},
        "helpers": {},
        "bootstrap_files": {}
    },
    "impact_zones": {}
}
EOF

    # Scan for test dependencies
    find . -name "*Test.php" -type f | while read test_file; do
        local extends=$(grep -oP 'extends\s+\K[^\s]+' "$test_file" | head -1)
        local uses=$(grep -oP '^use\s+\K[^;]+' "$test_file" | grep -E '(Fixture|Helper|Trait)')

        # Record dependencies
        jq --arg file "$test_file" \
           --arg extends "$extends" \
           --argjson uses "$(echo "$uses" | jq -R . | jq -s .)" \
           '.nodes[$file] = {
               "type": (if ($file | contains("/Unit/")) then "unit" else "integration" end),
               "extends": $extends,
               "uses": $uses,
               "modified": false
           }' "$graph_file" > "${graph_file}.tmp" && mv "${graph_file}.tmp" "$graph_file"
    done
}
```

## 🛡️ Fix Impact Analysis Engine

### Pre-Fix Analysis
```bash
analyze_fix_impact() {
    local test_file="$1"
    local modification_type="$2"  # assertion|mock|fixture|base_class
    local session_id="$3"

    local impact_report="/tmp/test-sessions/${session_id}/impact-${test_file##*/}.json"

    # Analyze potential impact
    cat > "$impact_report" << EOF
{
    "target_file": "$test_file",
    "modification_type": "$modification_type",
    "risk_level": "unknown",
    "affected_tests": [],
    "shared_resources_modified": [],
    "recommendations": []
}
EOF

    # Check if modifying shared resources
    if [[ "$modification_type" == "base_class" ]]; then
        # HIGH RISK - affects all tests using this base class
        local base_class=$(grep -oP 'extends\s+\K[^\s]+' "$test_file")
        local affected=$(grep -l "extends $base_class" **/*Test.php)

        jq --arg risk "HIGH" \
           --argjson affected "$(echo "$affected" | jq -R . | jq -s .)" \
           '.risk_level = $risk | .affected_tests = $affected |
            .recommendations += ["Consider creating test-specific base class instead"]' \
           "$impact_report" > "${impact_report}.tmp" && mv "${impact_report}.tmp" "$impact_report"
    fi

    echo "$impact_report"
}
```

## 🔄 Safe Fix Application Protocol

### Staged Fix Application
```bash
apply_fix_with_rollback() {
    local test_file="$1"
    local fix_content="$2"
    local session_id="$3"

    # Create rollback point
    local rollback_dir="/tmp/test-sessions/${session_id}/rollback"
    mkdir -p "$rollback_dir"
    cp "$test_file" "$rollback_dir/${test_file##*/}.$(date +%s)"

    # Apply fix in stages
    echo "$fix_content" > "$test_file"

    # Run ONLY the fixed test first
    if ! run_single_test "$test_file"; then
        echo "Fix failed for $test_file - rolling back"
        cp "$rollback_dir/${test_file##*/}"* "$test_file"
        return 1
    fi

    # Run related tests to check for breakage
    local related_tests=$(get_related_tests "$test_file" "$session_id")
    for related in $related_tests; do
        if ! run_single_test "$related"; then
            echo "Fix broke related test $related - rolling back"
            cp "$rollback_dir/${test_file##*/}"* "$test_file"
            return 1
        fi
    done

    return 0
}
```

## 📊 Bootstrap Isolation Strategy

### Separate Bootstrap Files
```
test/
├── bootstrap/
│   ├── unit.php          # Unit test bootstrap (mocks only)
│   ├── integration.php    # Integration bootstrap (real services)
│   └── e2e.php           # E2E bootstrap (full stack)
```

### Unit Test Bootstrap
```php
<?php
// test/bootstrap/unit.php

// Prevent any real service initialization
define('UNIT_TEST_MODE', true);

// Load composer autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// Initialize mock container
$container = new \Mockery\Container();

// Register mock services
$container->singleton('db', function() {
    return \Mockery::mock('Database');
});

// Block real service connections
if (function_exists('preventRealConnections')) {
    preventRealConnections();
}
```

### Integration Test Bootstrap
```php
<?php
// test/bootstrap/integration.php

// Allow real service connections
define('INTEGRATION_TEST_MODE', true);

// Load composer autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// Initialize test database
$testDb = new TestDatabaseManager();
$testDb->migrate();
$testDb->seed();

// Start test transaction
DB::beginTransaction();

// Register cleanup handler
register_shutdown_function(function() {
    DB::rollback();
    TestDataCleaner::cleanup();
});
```

## 🚦 Test Categorization Enforcement

### Automatic Test Type Detection
```php
<?php
namespace App\Test\Analyzer;

class TestTypeDetector
{
    public static function detectType(string $testFile): string
    {
        $content = file_get_contents($testFile);

        // Check path convention
        if (strpos($testFile, '/Unit/') !== false) {
            return 'unit';
        }
        if (strpos($testFile, '/Integration/') !== false) {
            return 'integration';
        }

        // Check for real service usage indicators
        $integrationIndicators = [
            'DB::',
            'database',
            'real',
            'actual',
            '->connect',
            'Http::',
            'external'
        ];

        foreach ($integrationIndicators as $indicator) {
            if (stripos($content, $indicator) !== false) {
                return 'integration';
            }
        }

        // Check for mock usage indicators
        $unitIndicators = [
            'Mockery',
            'createMock',
            'shouldReceive',
            'getMock',
            'prophesize'
        ];

        foreach ($unitIndicators as $indicator) {
            if (stripos($content, $indicator) !== false) {
                return 'unit';
            }
        }

        // Default to unit for safety
        return 'unit';
    }

    public static function enforceTypeRequirements(string $testFile, string $type): array
    {
        $violations = [];
        $content = file_get_contents($testFile);

        if ($type === 'unit') {
            // Check for forbidden real connections
            if (preg_match('/DB::|->connect\(|Http::/i', $content)) {
                $violations[] = 'Unit test using real services - MUST use mocks';
            }

            // Check for skipped tests
            if (preg_match('/@skip|test\.skip|xit\(/i', $content)) {
                $violations[] = 'Unit test has skipped tests - NOT ALLOWED';
            }
        }

        if ($type === 'integration') {
            // Check for proper cleanup
            if (!preg_match('/tearDown|@after|cleanup/i', $content)) {
                $violations[] = 'Integration test missing cleanup - REQUIRED';
            }
        }

        return $violations;
    }
}
```

## 🎯 Conflict Resolution Strategy

### Smart Conflict Detection
```php
<?php
namespace App\Test\ConflictResolver;

class TestConflictResolver
{
    private array $modificationHistory = [];

    public function recordModification(string $file, string $changeType, array $details): void
    {
        $this->modificationHistory[] = [
            'file' => $file,
            'type' => $changeType,
            'details' => $details,
            'timestamp' => time(),
            'affects' => $this->calculateAffectedTests($file, $changeType)
        ];
    }

    public function detectPotentialConflict(string $targetFile, string $plannedChange): ?array
    {
        // Check if recent modifications might conflict
        foreach ($this->modificationHistory as $history) {
            if ($this->isRelated($targetFile, $history['file'])) {
                if ($this->changesConflict($plannedChange, $history['type'])) {
                    return [
                        'conflict_type' => 'modification_overlap',
                        'previous_change' => $history,
                        'recommendation' => $this->getResolutionStrategy($plannedChange, $history['type'])
                    ];
                }
            }
        }

        return null;
    }

    private function getResolutionStrategy(string $newChange, string $existingChange): string
    {
        $strategies = [
            'assertion:mock' => 'Update mocks before assertions',
            'mock:fixture' => 'Ensure fixtures are loaded before mocks',
            'base_class:assertion' => 'Modify base class in separate commit',
            'fixture:fixture' => 'Consolidate fixture changes'
        ];

        $key = "$existingChange:$newChange";
        return $strategies[$key] ?? 'Apply changes sequentially with validation';
    }
}
```

## 🔐 Enforcement Rules

### Mandatory Rules (ZERO TOLERANCE)
1. **NEVER** modify base classes without impact analysis
2. **ALWAYS** run affected tests after each fix
3. **MUST** use separate bootstrap files per test type
4. **REQUIRE** rollback capability for all modifications
5. **ENFORCE** test type requirements before execution

### Quality Gates
- [ ] All unit tests use UnitTestCase base
- [ ] All integration tests use IntegrationTestCase base
- [ ] No shared fixtures between test types
- [ ] Each fix validated against related tests
- [ ] Dependency graph updated after modifications

## 🚨 ZERO TOLERANCE ENFORCEMENT

**ALL shared test utilities MUST enforce PERFECT execution:**

### Success Criteria (ALL must be met)
- ✅ **0 Failed Tests** - Every single test must pass
- ✅ **0 Errors** - No runtime errors allowed
- ✅ **0 Warnings** - Warnings are treated as failures
- ✅ **0 Deprecations** - Deprecation notices block success
- ✅ **0 Incomplete Tests** - Incomplete tests are failures
- ✅ **0 Risky Tests** - Risky test detection must pass
- ✅ **0 Skipped Tests** - Unless explicitly allowed with justification

### Integration Requirements
- All test detection must flag warnings as errors
- All completion gates must reject warnings/deprecations
- All coordination must enforce zero tolerance across agents

## 🔒 UNIT/INTEGRATION SEPARATION REQUIREMENTS

**Mandatory separation between unit and integration test execution:**

### Isolation Boundaries
```
┌─────────────────────────────────────────────────────────┐
│                    TEST EXECUTION                        │
├─────────────────────┬───────────────────────────────────┤
│    UNIT TESTS       │      INTEGRATION TESTS            │
│  (Isolated Domain)  │     (Isolated Domain)             │
├─────────────────────┼───────────────────────────────────┤
│ • Mocked deps       │ • Real deps                       │
│ • In-memory only    │ • Real database/cache             │
│ • Parallel safe     │ • Sequential or careful parallel  │
│ • Milliseconds      │ • Seconds                         │
│ • No cleanup needed │ • Cleanup required                │
└─────────────────────┴───────────────────────────────────┘
          ↑                         ↑
          │    NEVER MIX THESE      │
          └─────────────────────────┘
```

### Separation Enforcement
1. **Execution Separation** - Run unit and integration in separate processes
2. **Environment Separation** - Different environment variables if needed
3. **Resource Separation** - Unit tests never touch real databases
4. **Reporting Separation** - Separate results for unit vs integration