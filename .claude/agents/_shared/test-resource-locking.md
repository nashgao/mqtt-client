# Test Resource Locking System

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

**CRITICAL**: Prevents race conditions when multiple agents modify shared test resources simultaneously.

## 🔐 Resource Locking Protocol

```bash
#!/bin/bash

# Global lock directory
LOCK_DIR="/tmp/test-locks"
mkdir -p "$LOCK_DIR"

# Acquire lock for a resource
acquire_lock() {
    local resource="$1"
    local agent_id="${2:-unknown}"
    local timeout="${3:-30}"  # Default 30 second timeout

    local lock_file="${LOCK_DIR}/${resource//\//_}.lock"
    local start_time=$(date +%s)

    while true; do
        # Try to acquire lock atomically
        if (set -C; echo "$agent_id:$$:$(date +%s)" > "$lock_file") 2>/dev/null; then
            echo "✅ Lock acquired for $resource by $agent_id"
            return 0
        fi

        # Check if lock is stale (older than timeout)
        if [ -f "$lock_file" ]; then
            local lock_time=$(cut -d: -f3 "$lock_file")
            local current_time=$(date +%s)
            local lock_age=$((current_time - lock_time))

            if [ $lock_age -gt $timeout ]; then
                echo "⚠️ Removing stale lock (age: ${lock_age}s)"
                rm -f "$lock_file"
                continue
            fi
        fi

        # Check for timeout
        local elapsed=$(($(date +%s) - start_time))
        if [ $elapsed -gt $timeout ]; then
            echo "❌ Failed to acquire lock after ${timeout}s"
            return 1
        fi

        # Wait before retry
        sleep 0.5
    done
}

# Release lock for a resource
release_lock() {
    local resource="$1"
    local agent_id="${2:-unknown}"

    local lock_file="${LOCK_DIR}/${resource//\//_}.lock"

    if [ -f "$lock_file" ]; then
        local lock_owner=$(cut -d: -f1 "$lock_file")

        if [ "$lock_owner" = "$agent_id" ]; then
            rm -f "$lock_file"
            echo "✅ Lock released for $resource by $agent_id"
            return 0
        else
            echo "❌ Cannot release lock owned by $lock_owner"
            return 1
        fi
    fi

    return 0
}

# Check if resource is locked
is_locked() {
    local resource="$1"
    local lock_file="${LOCK_DIR}/${resource//\//_}.lock"

    [ -f "$lock_file" ]
}

# Get lock owner information
get_lock_owner() {
    local resource="$1"
    local lock_file="${LOCK_DIR}/${resource//\//_}.lock"

    if [ -f "$lock_file" ]; then
        cat "$lock_file"
    else
        echo "none"
    fi
}
```

## 📊 Shared Resource Categories

```bash
# Define lockable resource categories
RESOURCE_CATEGORIES=(
    "base_class:*TestCase"           # Base test classes
    "fixture:*"                       # Test fixtures
    "bootstrap:*"                     # Bootstrap files
    "helper:*"                        # Test helpers
    "mock:*"                          # Mock configurations
    "config:phpunit.xml"              # Test configurations
    "config:jest.config.js"
    "config:pytest.ini"
)

# Lock multiple resources atomically
acquire_multiple_locks() {
    local agent_id="$1"
    shift
    local resources=("$@")
    local acquired=()

    # Try to acquire all locks
    for resource in "${resources[@]}"; do
        if acquire_lock "$resource" "$agent_id"; then
            acquired+=("$resource")
        else
            # Rollback on failure
            echo "Failed to acquire lock for $resource, rolling back..."
            for locked in "${acquired[@]}"; do
                release_lock "$locked" "$agent_id"
            done
            return 1
        fi
    done

    echo "✅ All locks acquired successfully"
    return 0
}
```

## 🎯 Lock-Aware Modification Pattern

```php
<?php
class LockAwareTestModifier {
    private string $agentId;
    private array $heldLocks = [];

    public function __construct(string $agentId) {
        $this->agentId = $agentId;
    }

    public function modifyWithLock(string $testFile, callable $modifier): bool {
        // Determine resources to lock
        $resourcesToLock = $this->identifyResources($testFile);

        // Acquire locks
        foreach ($resourcesToLock as $resource) {
            if (!$this->acquireLock($resource)) {
                $this->releaseAll();
                return false;
            }
        }

        try {
            // Perform modification with locks held
            $result = $modifier($testFile);

            if (!$result) {
                throw new Exception("Modification failed");
            }

            return true;
        } catch (Exception $e) {
            error_log("Modification failed: " . $e->getMessage());
            return false;
        } finally {
            // Always release locks
            $this->releaseAll();
        }
    }

    private function identifyResources(string $testFile): array {
        $resources = [];
        $content = file_get_contents($testFile);

        // Check for base class
        if (preg_match('/extends\s+(\w+TestCase)/', $content, $matches)) {
            $resources[] = "base_class:{$matches[1]}";
        }

        // Check for fixtures
        if (preg_match_all('/use.*Fixture/', $content, $matches)) {
            foreach ($matches[0] as $fixture) {
                $resources[] = "fixture:" . basename($fixture);
            }
        }

        // Check for shared helpers
        if (preg_match_all('/use.*Helper/', $content, $matches)) {
            foreach ($matches[0] as $helper) {
                $resources[] = "helper:" . basename($helper);
            }
        }

        return $resources;
    }

    private function acquireLock(string $resource): bool {
        $lockFile = "/tmp/test-locks/" . str_replace('/', '_', $resource) . ".lock";

        // Atomic lock acquisition
        $fp = @fopen($lockFile, 'x');
        if ($fp === false) {
            return false;
        }

        fwrite($fp, "{$this->agentId}:" . getmypid() . ":" . time());
        fclose($fp);

        $this->heldLocks[] = $resource;
        return true;
    }

    private function releaseAll(): void {
        foreach ($this->heldLocks as $resource) {
            $lockFile = "/tmp/test-locks/" . str_replace('/', '_', $resource) . ".lock";
            @unlink($lockFile);
        }
        $this->heldLocks = [];
    }
}
```

## 🔄 Deadlock Prevention

```bash
# Deadlock detection and resolution
detect_deadlocks() {
    local agent_id="$1"
    local waiting_for="$2"

    # Build dependency graph
    local dep_graph="/tmp/test-locks/dependency-graph.txt"

    echo "$agent_id -> $waiting_for" >> "$dep_graph"

    # Check for cycles using topological sort
    if ! tsort "$dep_graph" 2>/dev/null; then
        echo "⚠️ DEADLOCK DETECTED!"

        # Break deadlock by releasing oldest lock
        local oldest_lock=$(ls -t "$LOCK_DIR"/*.lock 2>/dev/null | tail -1)
        if [ -n "$oldest_lock" ]; then
            echo "Breaking deadlock by releasing: $oldest_lock"
            rm -f "$oldest_lock"
        fi

        return 1
    fi

    return 0
}

# Priority-based lock acquisition
acquire_lock_with_priority() {
    local resource="$1"
    local agent_id="$2"
    local priority="${3:-5}"  # 1-10, higher is more important

    local lock_file="${LOCK_DIR}/${resource//\//_}.lock"
    local queue_file="${lock_file}.queue"

    # Add to priority queue
    echo "$priority:$agent_id:$(date +%s)" >> "$queue_file"

    # Sort queue by priority
    sort -t: -k1 -rn "$queue_file" > "${queue_file}.sorted"
    mv "${queue_file}.sorted" "$queue_file"

    # Check if we're next in line
    local next_agent=$(head -1 "$queue_file" | cut -d: -f2)

    if [ "$next_agent" = "$agent_id" ]; then
        # Try to acquire lock
        if acquire_lock "$resource" "$agent_id"; then
            # Remove from queue
            grep -v "$agent_id" "$queue_file" > "${queue_file}.tmp"
            mv "${queue_file}.tmp" "$queue_file"
            return 0
        fi
    fi

    return 1
}
```

## 🚦 Integration with Test Agents

```bash
# Use in test fixer agents
fix_test_with_locking() {
    local test_file="$1"
    local agent_id="${AGENT_ID:-test-fixer-$$}"

    # Identify required locks
    local base_class=$(grep -oP 'extends\s+\K\w+TestCase' "$test_file")
    local fixtures=$(grep -oP 'use.*Fixture' "$test_file")

    # Acquire all necessary locks
    local resources=("base_class:$base_class")
    for fixture in $fixtures; do
        resources+=("fixture:$fixture")
    done

    if ! acquire_multiple_locks "$agent_id" "${resources[@]}"; then
        echo "Failed to acquire necessary locks"
        return 1
    fi

    # Perform fix with locks held
    apply_test_fix "$test_file"
    local result=$?

    # Release all locks
    for resource in "${resources[@]}"; do
        release_lock "$resource" "$agent_id"
    done

    return $result
}
```

## 📈 Lock Monitoring

```bash
# Monitor lock status
monitor_locks() {
    while true; do
        clear
        echo "=== TEST RESOURCE LOCKS ==="
        echo "Time: $(date)"
        echo ""

        for lock_file in "$LOCK_DIR"/*.lock 2>/dev/null; do
            if [ -f "$lock_file" ]; then
                local resource=$(basename "$lock_file" .lock | tr '_' '/')
                local info=$(cat "$lock_file")
                local agent=$(echo "$info" | cut -d: -f1)
                local pid=$(echo "$info" | cut -d: -f2)
                local time=$(echo "$info" | cut -d: -f3)
                local age=$(($(date +%s) - time))

                printf "%-30s %-20s PID:%-8s Age:%ds\n" \
                    "$resource" "$agent" "$pid" "$age"
            fi
        done

        sleep 2
    done
}
```

## 🔐 Enforcement Rules

1. **MANDATORY**: Acquire locks before modifying shared resources
2. **MANDATORY**: Release locks in finally blocks
3. **MANDATORY**: Use atomic lock operations
4. **MANDATORY**: Implement timeout for lock acquisition
5. **MANDATORY**: Clean up stale locks automatically

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