# Go Test Pattern Component

This component provides comprehensive Go testing patterns, commands, and optimizations for all test-related agents.

## Core Go Test Commands

```bash
#!/bin/bash

# Generate optimized Go test command based on context
generate_go_test_command() {
    local test_type="${1:-all}"
    local coverage="${2:-false}"
    local parallel="${3:-auto}"
    local verbose="${4:-false}"
    local race="${5:-false}"
    
    local cmd="go test"
    
    # Add verbosity
    [ "$verbose" = "true" ] && cmd="$cmd -v"
    
    # Add race detection
    [ "$race" = "true" ] && cmd="$cmd -race"
    
    # Test type selection
    case "$test_type" in
        "unit")
            # Run only unit tests (exclude integration)
            cmd="$cmd -short ./..."
            ;;
        "integration")
            # Run integration tests
            cmd="$cmd -run Integration ./..."
            ;;
        "benchmark")
            # Run benchmarks
            cmd="$cmd -bench=. -benchmem -run=^$"
            ;;
        "example")
            # Run example tests
            cmd="$cmd -run Example ./..."
            ;;
        "specific")
            # Run specific test pattern
            local pattern="${GO_TEST_PATTERN:-Test}"
            cmd="$cmd -run $pattern ./..."
            ;;
        *)
            # Run all tests
            cmd="$cmd ./..."
            ;;
    esac
    
    # Add coverage if requested
    if [ "$coverage" = "true" ]; then
        cmd="$cmd -cover -coverprofile=coverage.out"
        [ "$verbose" = "true" ] && cmd="$cmd -covermode=atomic"
    fi
    
    # Add parallel execution
    if [ "$parallel" != "false" ] && [ "$parallel" != "1" ]; then
        if [ "$parallel" = "auto" ]; then
            # Use CPU count for parallel execution
            local cpu_count=$(nproc 2>/dev/null || sysctl -n hw.ncpu 2>/dev/null || echo 4)
            cmd="$cmd -parallel $cpu_count"
        else
            cmd="$cmd -parallel $parallel"
        fi
    fi
    
    # Add timeout for safety
    cmd="$cmd -timeout 10m"
    
    echo "$cmd"
}

# Detect Go test patterns in file
detect_go_test_patterns() {
    local file="$1"
    local patterns=""
    
    # Check for different test types
    grep -q "func Test" "$file" && patterns="$patterns unit"
    grep -q "func Benchmark" "$file" && patterns="$patterns benchmark"
    grep -q "func Example" "$file" && patterns="$patterns example"
    grep -q "t.Run(" "$file" && patterns="$patterns subtest"
    grep -q "t.Parallel()" "$file" && patterns="$patterns parallel"
    grep -q "// +build integration" "$file" && patterns="$patterns integration"
    
    echo "$patterns"
}

# Parse Go test output for failures
parse_go_test_failures() {
    local log_file="$1"
    local failure_details=""
    
    # Extract failure information
    while IFS= read -r line; do
        if [[ "$line" == *"--- FAIL:"* ]]; then
            local test_name=$(echo "$line" | sed 's/--- FAIL: \([^ ]*\).*/\1/')
            local duration=$(echo "$line" | grep -oE '\([0-9.]+s\)')
            failure_details="$failure_details\nTest: $test_name Duration: $duration"
        fi
    done < "$log_file"
    
    echo -e "$failure_details"
}

# Count Go test failures
count_go_test_failures() {
    local log_file="$1"
    grep -c "^--- FAIL:" "$log_file" 2>/dev/null || echo "0"
}

# Validate Go test success
validate_go_test_success() {
    local log_file="$1"
    
    # Check for PASS and no FAIL
    if grep -q "^PASS$" "$log_file" && ! grep -q "^FAIL$" "$log_file"; then
        return 0
    fi
    
    # Check for successful benchmark completion
    if grep -q "^PASS$" "$log_file" && grep -q "^ok" "$log_file"; then
        return 0
    fi
    
    return 1
}

# Generate Go test coverage report
generate_go_coverage_report() {
    local coverage_file="${1:-coverage.out}"
    local format="${2:-html}"
    
    if [ ! -f "$coverage_file" ]; then
        echo "Coverage file not found: $coverage_file"
        return 1
    fi
    
    case "$format" in
        "html")
            go tool cover -html="$coverage_file" -o coverage.html
            echo "Coverage report generated: coverage.html"
            ;;
        "func")
            go tool cover -func="$coverage_file"
            ;;
        "text")
            go tool cover -mode=count -o coverage.txt "$coverage_file"
            echo "Coverage report generated: coverage.txt"
            ;;
        *)
            echo "Unknown format: $format"
            return 1
            ;;
    esac
}

# Run Go tests with retry logic
run_go_tests_with_retry() {
    local max_retries="${1:-3}"
    local test_command="${2:-go test ./...}"
    local retry_count=0
    
    while [ $retry_count -lt $max_retries ]; do
        echo "Running Go tests (attempt $((retry_count + 1))/$max_retries)"
        
        if $test_command; then
            echo "✅ Tests passed"
            return 0
        fi
        
        retry_count=$((retry_count + 1))
        [ $retry_count -lt $max_retries ] && echo "⚠️ Tests failed, retrying..."
    done
    
    echo "❌ Tests failed after $max_retries attempts"
    return 1
}
```

## Go Test Patterns and Best Practices

### Table-Driven Tests
```go
func TestAdd(t *testing.T) {
    tests := []struct {
        name string
        a, b int
        want int
    }{
        {"positive", 2, 3, 5},
        {"negative", -1, -2, -3},
        {"zero", 0, 0, 0},
    }
    
    for _, tt := range tests {
        t.Run(tt.name, func(t *testing.T) {
            if got := Add(tt.a, tt.b); got != tt.want {
                t.Errorf("Add(%d, %d) = %d, want %d", tt.a, tt.b, got, tt.want)
            }
        })
    }
}
```

### Subtests with Parallel Execution
```go
func TestFeature(t *testing.T) {
    t.Run("Subtest1", func(t *testing.T) {
        t.Parallel()
        // Test code
    })
    
    t.Run("Subtest2", func(t *testing.T) {
        t.Parallel()
        // Test code
    })
}
```

### Benchmark Tests
```go
func BenchmarkOperation(b *testing.B) {
    for i := 0; i < b.N; i++ {
        // Operation to benchmark
    }
}
```

### Example Tests
```go
func ExampleFunction() {
    result := Function()
    fmt.Println(result)
    // Output: expected output
}
```

## Go Test Command Examples

### Unit Tests Only
```bash
# Run unit tests only (exclude integration)
go test -short -v ./...

# Run with coverage
go test -short -cover -coverprofile=coverage.out ./...

# Run with race detection
go test -short -race ./...
```

### Integration Tests
```bash
# Run integration tests only
go test -run Integration -v ./...

# Run with build tags
go test -tags=integration ./...
```

### Benchmarks
```bash
# Run all benchmarks
go test -bench=. -benchmem -run=^$ ./...

# Run specific benchmark
go test -bench=BenchmarkSpecific -benchmem ./...

# Run benchmarks with CPU profiling
go test -bench=. -cpuprofile=cpu.prof ./...
```

### Coverage Analysis
```bash
# Generate coverage report
go test -cover -coverprofile=coverage.out ./...
go tool cover -html=coverage.out -o coverage.html

# Function-level coverage
go tool cover -func=coverage.out

# Coverage with multiple packages
go test -coverpkg=./... -coverprofile=coverage.out ./...
```

### Parallel Execution
```bash
# Run tests in parallel (auto-detect CPUs)
go test -parallel $(nproc) ./...

# Limit parallel execution
go test -parallel 4 ./...

# Disable parallel execution
go test -parallel 1 ./...
```

## Go Test Failure Patterns

### Common Failure Types
```bash
# Test failure
--- FAIL: TestFunction (0.01s)
    main_test.go:10: Expected 5, got 3

# Panic in test
--- FAIL: TestPanic (0.00s)
panic: runtime error: index out of range [recovered]

# Timeout
panic: test timed out after 10m0s

# Build failure
# command-line-arguments
./main_test.go:5:2: undefined: someFunction
```

### Failure Detection Patterns
```bash
# Count failures
grep -c "^--- FAIL:" test_output.log

# Extract failed test names
grep "^--- FAIL:" test_output.log | awk '{print $3}'

# Check for panics
grep -c "panic:" test_output.log

# Check for build errors
grep -c "^#" test_output.log
```

## Go Test Optimization Strategies

### 1. Test Caching
```bash
# Clear test cache
go clean -testcache

# Run tests without cache
go test -count=1 ./...
```

### 2. Test Isolation
```bash
# Run each package's tests in separate process
for pkg in $(go list ./...); do
    go test -v $pkg || exit 1
done
```

### 3. Focused Testing
```bash
# Run specific test by name
go test -run TestSpecificFunction ./...

# Run tests matching pattern
go test -run "Test.*Database" ./...

# Exclude tests matching pattern
go test -run "Test[^Integration]" ./...
```

### 4. Performance Testing
```bash
# Memory profiling
go test -memprofile=mem.prof ./...
go tool pprof mem.prof

# CPU profiling
go test -cpuprofile=cpu.prof ./...
go tool pprof cpu.prof

# Trace generation
go test -trace=trace.out ./...
go tool trace trace.out
```

## Integration with CI/CD

### GitHub Actions Example
```yaml
- name: Run Go Tests
  run: |
    go test -v -race -coverprofile=coverage.out ./...
    go tool cover -func=coverage.out

- name: Run Benchmarks
  run: go test -bench=. -benchmem -run=^$ ./...
```

### Makefile Integration
```makefile
.PHONY: test
test:
	go test -v -race ./...

.PHONY: test-coverage
test-coverage:
	go test -cover -coverprofile=coverage.out ./...
	go tool cover -html=coverage.out -o coverage.html

.PHONY: test-bench
test-bench:
	go test -bench=. -benchmem -run=^$ ./...
```

## 🚨 ZERO TOLERANCE ENFORCEMENT

**ALL Go test utilities MUST enforce PERFECT execution:**

### Success Criteria (ALL must be met)
- ✅ **0 Failed Tests** - Every single test must pass
- ✅ **0 Errors** - No runtime errors allowed
- ✅ **0 Warnings** - Warnings are treated as failures
- ✅ **0 Deprecations** - Deprecation notices block success
- ✅ **0 Incomplete Tests** - Incomplete tests are failures
- ✅ **0 Risky Tests** - Risky test detection must pass
- ✅ **0 Skipped Tests** - Unless explicitly allowed with justification

### Go-Specific Enforcement
- **MANDATORY**: Always run with `-race` flag for race detection
- **MANDATORY**: Always run with `-v` flag for verbose output
- **MANDATORY**: Set strict error checking on all test outputs
- **MANDATORY**: Reject builds with vet warnings

### Integration Requirements
- All test detection must flag warnings as errors
- All completion gates must reject warnings/deprecations
- All coordination must enforce zero tolerance across agents

## Best Practices

1. **Always use `-race` flag in CI/CD** to detect race conditions
2. **Set appropriate timeouts** to prevent hanging tests
3. **Use parallel execution** for faster test runs
4. **Separate unit and integration tests** with build tags or naming
5. **Generate coverage reports** to track test completeness
6. **Use table-driven tests** for comprehensive test cases
7. **Leverage subtests** for better organization and parallel execution
8. **Cache dependencies** but clear test cache when needed
9. **Profile tests** to identify performance bottlenecks
10. **Mock external dependencies** for reliable unit tests