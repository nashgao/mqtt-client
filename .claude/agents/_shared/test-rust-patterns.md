# Rust Test Pattern Component

This component provides comprehensive Rust testing patterns, commands, and optimizations for all test-related agents.

## Core Rust Test Commands

```bash
#!/bin/bash

# Generate optimized Rust test command based on context
generate_rust_test_command() {
    local test_type="${1:-all}"
    local release="${2:-false}"
    local parallel="${3:-auto}"
    local verbose="${4:-false}"
    local nocapture="${5:-false}"
    
    local cmd="cargo test"
    
    # Add release mode if requested
    [ "$release" = "true" ] && cmd="$cmd --release"
    
    # Add verbosity
    [ "$verbose" = "true" ] && cmd="$cmd --verbose"
    
    # Test type selection
    case "$test_type" in
        "unit")
            # Run only unit tests (lib tests)
            cmd="$cmd --lib"
            ;;
        "integration")
            # Run integration tests only
            cmd="$cmd --test '*'"
            ;;
        "doc")
            # Run doc tests only
            cmd="$cmd --doc"
            ;;
        "benchmark")
            # Run benchmarks (requires nightly)
            cmd="cargo bench"
            [ "$verbose" = "true" ] && cmd="$cmd --verbose"
            ;;
        "example")
            # Run example tests
            cmd="$cmd --examples"
            ;;
        "specific")
            # Run specific test pattern
            local pattern="${RUST_TEST_PATTERN:-test}"
            cmd="$cmd $pattern"
            ;;
        "ignored")
            # Run ignored tests
            cmd="$cmd -- --ignored"
            ;;
        "all-including-ignored")
            # Run all tests including ignored
            cmd="$cmd -- --include-ignored"
            ;;
        *)
            # Run all tests
            cmd="$cmd --all-targets"
            ;;
    esac
    
    # Add parallel execution control
    if [ "$parallel" != "false" ]; then
        if [ "$parallel" = "auto" ]; then
            # Use all available cores
            cmd="$cmd"  # Cargo uses parallel by default
        elif [ "$parallel" = "1" ]; then
            # Force single-threaded
            cmd="$cmd -- --test-threads=1"
        else
            # Specific thread count
            cmd="$cmd -- --test-threads=$parallel"
        fi
    fi
    
    # Add output capture control
    if [ "$nocapture" = "true" ]; then
        cmd="$cmd -- --nocapture"
    fi
    
    # Add show-output for better debugging
    cmd="$cmd -- --show-output"
    
    echo "$cmd"
}

# Detect Rust test patterns in file
detect_rust_test_patterns() {
    local file="$1"
    local patterns=""
    
    # Check for different test types
    grep -q "#\[test\]" "$file" && patterns="$patterns unit"
    grep -q "#\[bench\]" "$file" && patterns="$patterns benchmark"
    grep -q "#\[should_panic" "$file" && patterns="$patterns panic"
    grep -q "#\[ignore\]" "$file" && patterns="$patterns ignored"
    grep -q "/// ```" "$file" && patterns="$patterns doctest"
    grep -q "#\[cfg(test)\]" "$file" && patterns="$patterns module"
    grep -q "proptest!" "$file" && patterns="$patterns property"
    grep -q "quickcheck!" "$file" && patterns="$patterns quickcheck"
    
    echo "$patterns"
}

# Parse Rust test output for failures
parse_rust_test_failures() {
    local log_file="$1"
    local failure_details=""
    
    # Extract failure information
    while IFS= read -r line; do
        if [[ "$line" == *"test"*"... FAILED"* ]]; then
            local test_name=$(echo "$line" | sed 's/test \(.*\) \.\.\. FAILED/\1/')
            failure_details="$failure_details\nFailed: $test_name"
        elif [[ "$line" == *"---- "*" stdout ----"* ]]; then
            local test_name=$(echo "$line" | sed 's/---- \(.*\) stdout ----/\1/')
            failure_details="$failure_details\nPanic in: $test_name"
        fi
    done < "$log_file"
    
    echo -e "$failure_details"
}

# Count Rust test failures
count_rust_test_failures() {
    local log_file="$1"
    
    # Count lines with "test result: FAILED"
    local fail_count=$(grep -oE 'test result: FAILED. [0-9]+ passed; ([0-9]+) failed' "$log_file" | grep -oE '[0-9]+ failed' | awk '{print $1}')
    
    if [ -n "$fail_count" ]; then
        echo "$fail_count"
    else
        # Fallback: count individual FAILED lines
        grep -c "FAILED" "$log_file" 2>/dev/null || echo "0"
    fi
}

# Validate Rust test success
validate_rust_test_success() {
    local log_file="$1"
    
    # Check for successful test result
    if grep -q "test result: ok" "$log_file"; then
        return 0
    fi
    
    # Check for doc test success
    if grep -q "test result: ok.*doc test" "$log_file"; then
        return 0
    fi
    
    # Check for bench success
    if grep -q "bench:.*ns/iter" "$log_file" && ! grep -q "FAILED" "$log_file"; then
        return 0
    fi
    
    return 1
}

# Generate Rust code coverage report
generate_rust_coverage_report() {
    local format="${1:-html}"
    
    # Install tarpaulin if not present
    if ! command -v cargo-tarpaulin &> /dev/null; then
        echo "Installing cargo-tarpaulin for coverage..."
        cargo install cargo-tarpaulin
    fi
    
    case "$format" in
        "html")
            cargo tarpaulin --out Html --output-dir coverage
            echo "Coverage report generated: coverage/index.html"
            ;;
        "xml")
            cargo tarpaulin --out Xml --output-dir coverage
            echo "Coverage report generated: coverage/cobertura.xml"
            ;;
        "lcov")
            cargo tarpaulin --out Lcov --output-dir coverage
            echo "Coverage report generated: coverage/lcov.info"
            ;;
        *)
            cargo tarpaulin --print-summary
            ;;
    esac
}

# Run Rust tests with retry logic
run_rust_tests_with_retry() {
    local max_retries="${1:-3}"
    local test_command="${2:-cargo test}"
    local retry_count=0
    
    while [ $retry_count -lt $max_retries ]; do
        echo "Running Rust tests (attempt $((retry_count + 1))/$max_retries)"
        
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

# Clean Rust test artifacts
clean_rust_test_artifacts() {
    echo "Cleaning Rust test artifacts..."
    cargo clean
    rm -rf target/debug/deps/*test*
    rm -rf target/release/deps/*test*
    rm -rf coverage/
    echo "✅ Test artifacts cleaned"
}
```

## Rust Test Patterns and Best Practices

### Unit Tests
```rust
#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn test_addition() {
        assert_eq!(add(2, 2), 4);
    }

    #[test]
    #[should_panic(expected = "divide by zero")]
    fn test_division_by_zero() {
        divide(10, 0);
    }

    #[test]
    #[ignore]
    fn expensive_test() {
        // This test is ignored by default
        perform_expensive_operation();
    }
}
```

### Integration Tests
```rust
// tests/integration_test.rs
use my_crate;

#[test]
fn test_full_workflow() {
    let result = my_crate::complete_workflow();
    assert!(result.is_ok());
}
```

### Doc Tests
```rust
/// Adds two numbers together.
/// 
/// # Examples
/// 
/// ```
/// use my_crate::add;
/// assert_eq!(add(2, 3), 5);
/// ```
pub fn add(a: i32, b: i32) -> i32 {
    a + b
}
```

### Property-Based Tests
```rust
#[cfg(test)]
mod property_tests {
    use proptest::prelude::*;

    proptest! {
        #[test]
        fn test_addition_commutative(a: i32, b: i32) {
            assert_eq!(add(a, b), add(b, a));
        }
    }
}
```

### Benchmark Tests
```rust
#![feature(test)]
extern crate test;

#[cfg(test)]
mod benches {
    use super::*;
    use test::Bencher;

    #[bench]
    fn bench_addition(b: &mut Bencher) {
        b.iter(|| add(2, 2));
    }
}
```

## Rust Test Command Examples

### Unit Tests
```bash
# Run library unit tests only
cargo test --lib

# Run with verbose output
cargo test --lib --verbose

# Run single-threaded for debugging
cargo test --lib -- --test-threads=1

# Run with output capture disabled
cargo test --lib -- --nocapture
```

### Integration Tests
```bash
# Run all integration tests
cargo test --test '*'

# Run specific integration test file
cargo test --test integration_test

# Run integration tests with release optimizations
cargo test --test '*' --release
```

### Doc Tests
```bash
# Run documentation tests only
cargo test --doc

# Run doc tests for specific crate
cargo test --doc -p my_crate

# Run doc tests with verbose output
cargo test --doc --verbose
```

### Benchmarks
```bash
# Run all benchmarks (requires nightly)
cargo +nightly bench

# Run specific benchmark
cargo +nightly bench bench_name

# Run benchmarks with specific features
cargo +nightly bench --features "performance"
```

### Test Organization
```bash
# Run all tests including ignored
cargo test -- --include-ignored

# Run only ignored tests
cargo test -- --ignored

# Run tests matching pattern
cargo test test_pattern

# Run tests in specific module
cargo test module_name::
```

### Coverage Generation
```bash
# Generate HTML coverage report
cargo tarpaulin --out Html

# Generate coverage with specific test types
cargo tarpaulin --lib --test '*' --doc

# Generate coverage excluding certain paths
cargo tarpaulin --exclude-files "*/generated/*"

# Generate coverage with line-by-line output
cargo tarpaulin --print-summary --print-line-coverage
```

## Rust Test Failure Patterns

### Common Failure Types
```text
# Assertion failure
thread 'tests::test_addition' panicked at 'assertion failed: `(left == right)`
  left: `4`,
 right: `5`'

# Panic in test
thread 'tests::test_panic' panicked at 'explicit panic'

# Compile error
error[E0425]: cannot find value `undefined` in this scope

# Doc test failure
---- src/lib.rs - add (line 5) stdout ----
Test failed: expected 5, got 4
```

### Failure Detection Patterns
```bash
# Count total failures
grep -oE 'test result: FAILED. [0-9]+ passed; ([0-9]+) failed' | grep -oE '[0-9]+ failed' | awk '{print $1}'

# Extract failed test names
grep "test.*FAILED" test_output.log | sed 's/test \(.*\) \.\.\. FAILED/\1/'

# Check for panics
grep -c "panicked at" test_output.log

# Check for compilation errors
grep -c "error\[E[0-9]\+\]" test_output.log
```

## Rust Test Optimization Strategies

### 1. Parallel Execution
```bash
# Use all CPU cores (default)
cargo test

# Limit to specific thread count
cargo test -- --test-threads=4

# Single-threaded for debugging
cargo test -- --test-threads=1
```

### 2. Selective Testing
```bash
# Test only changed code
cargo test --lib

# Skip expensive tests
cargo test -- --skip expensive

# Run quick tests first
cargo test --lib && cargo test --test '*'
```

### 3. Feature-Based Testing
```bash
# Test with specific features
cargo test --features "feature1,feature2"

# Test all feature combinations
cargo test --all-features

# Test without default features
cargo test --no-default-features
```

### 4. Build Optimization
```bash
# Use release mode for performance tests
cargo test --release

# Share build artifacts
cargo test --workspace

# Incremental compilation
CARGO_INCREMENTAL=1 cargo test
```

## Integration with CI/CD

### GitHub Actions Example
```yaml
- name: Run Rust Tests
  run: |
    cargo test --all-targets --verbose
    cargo test --doc
    
- name: Run Coverage
  run: |
    cargo install cargo-tarpaulin
    cargo tarpaulin --out Xml
    
- name: Run Clippy
  run: cargo clippy -- -D warnings
```

### Makefile Integration
```makefile
.PHONY: test
test:
	cargo test --all-targets

.PHONY: test-coverage
test-coverage:
	cargo tarpaulin --out Html --output-dir coverage

.PHONY: test-bench
test-bench:
	cargo +nightly bench

.PHONY: test-all
test-all: test test-coverage
	cargo test --doc
	cargo test -- --ignored
```

## Advanced Testing Features

### Custom Test Frameworks
```toml
# Cargo.toml
[dev-dependencies]
criterion = "0.5"
proptest = "1.0"
quickcheck = "1.0"

[[bench]]
name = "my_benchmark"
harness = false
```

### Test Organization
```text
project/
├── src/
│   ├── lib.rs          # Library code with unit tests
│   └── bin/
│       └── main.rs     # Binary with tests
├── tests/
│   ├── integration/    # Integration test modules
│   └── common/         # Shared test utilities
└── benches/
    └── benchmarks.rs   # Benchmark tests
```

## 🚨 ZERO TOLERANCE ENFORCEMENT

**ALL Rust test utilities MUST enforce PERFECT execution:**

### Success Criteria (ALL must be met)
- ✅ **0 Failed Tests** - Every single test must pass
- ✅ **0 Errors** - No runtime errors allowed
- ✅ **0 Warnings** - Warnings are treated as failures
- ✅ **0 Deprecations** - Deprecation notices block success
- ✅ **0 Incomplete Tests** - Incomplete tests are failures
- ✅ **0 Risky Tests** - Risky test detection must pass
- ✅ **0 Skipped Tests** - Unless explicitly allowed with justification

### Rust-Specific Enforcement
- **MANDATORY**: Always run with `-- --test-threads=1` for sequential execution when debugging
- **MANDATORY**: Always use `-- --show-output` for comprehensive output
- **MANDATORY**: Reject builds with clippy warnings using `-- -D warnings`
- **MANDATORY**: Check all feature combinations with `--all-features`

### Integration Requirements
- All test detection must flag warnings as errors
- All completion gates must reject warnings/deprecations
- All coordination must enforce zero tolerance across agents

## Best Practices

1. **Organize tests by type** - Separate unit, integration, and doc tests
2. **Use `#[should_panic]`** for expected failures
3. **Mark slow tests** with `#[ignore]` attribute
4. **Write doc tests** for all public APIs
5. **Use property-based testing** for invariants
6. **Benchmark critical paths** with criterion
7. **Enable parallel execution** by default
8. **Use test fixtures** for complex data setup
9. **Mock external dependencies** in unit tests
10. **Generate coverage reports** in CI/CD pipeline
11. **Run clippy and fmt** before tests
12. **Test all feature combinations** in CI
13. **Use workspace testing** for multi-crate projects
14. **Cache dependencies** in CI for faster runs
15. **Test error paths** explicitly with Result/Option