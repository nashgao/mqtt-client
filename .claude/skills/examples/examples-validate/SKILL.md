# Command: examples-validate
Automatically test and validate code examples for correctness and quality

## Usage
```
/examples-validate [example-file|directory] --tests [unit|integration|all] --metrics
```

## Description
Validates that code examples work correctly, meet quality standards, and provide expected outputs. Includes syntax checking, execution testing, performance validation, security scanning, and documentation structure compliance.

## Documentation Structure Validation
Validates examples are in correct `docs/` locations based on project type:

### Structure Compliance Checks
- **Production Projects**: See `/templates/resources/documentation-library/core/structure-manager.md`
  - Validates examples in: `docs/user-guides/tutorials/` or `docs/api-reference/examples/`
- **Single Libraries**: See `/templates/resources/documentation-library/core/structure-manager.md`
  - Validates examples in: `docs/examples/` directory structure
- **Aggregated Libraries**: See `/templates/resources/documentation-library/core/structure-manager.md`
  - Validates module examples in: `docs/[module]/examples/`

Validation fails if examples are not in proper documentation structure.

## Implementation

### Validation Pipeline
```xml
<instructions>
Execute comprehensive validation of code examples
</instructions>

<validation_stages>
1. **Syntax Validation** - Parse and check syntax
2. **Import Verification** - Ensure dependencies exist
3. **Execution Testing** - Run the code
4. **Output Verification** - Check expected results
5. **Performance Testing** - Measure execution time
6. **Security Scanning** - Check for vulnerabilities
7. **Quality Metrics** - Complexity and maintainability
</validation_stages>

<requirements>
- All examples must be executable
- Expected outputs must match
- Performance within thresholds
- No security vulnerabilities
- Quality metrics acceptable
</requirements>
```

## Validation Framework

### Stage 1: Syntax and Static Analysis
```python
import ast
import pylint.lint
from typing import Dict, List, Tuple, Any

def validate_syntax(code: str, language: str = "python") -> Tuple[bool, List[str]]:
    """
    Validate code syntax without execution.
    
    Returns:
        (is_valid, error_messages)
    """
    errors = []
    
    if language == "python":
        try:
            ast.parse(code)
            return True, []
        except SyntaxError as e:
            errors.append(f"Syntax error at line {e.lineno}: {e.msg}")
            return False, errors
    
    elif language == "javascript":
        # Use external linter
        result = run_eslint(code)
        return result.is_valid, result.errors
    
    # Add more language validators...
    return True, []

def static_analysis(code: str) -> Dict[str, Any]:
    """Run static analysis checks"""
    
    analysis = {
        'syntax_valid': False,
        'imports_valid': False,
        'type_hints_present': False,
        'docstrings_present': False,
        'complexity_score': 0
    }
    
    # Parse AST
    try:
        tree = ast.parse(code)
        analysis['syntax_valid'] = True
        
        # Check imports
        imports = [node for node in ast.walk(tree) if isinstance(node, ast.Import)]
        analysis['imports_valid'] = validate_imports(imports)
        
        # Check for type hints
        analysis['type_hints_present'] = check_type_hints(tree)
        
        # Check docstrings
        analysis['docstrings_present'] = check_docstrings(tree)
        
        # Calculate complexity
        analysis['complexity_score'] = calculate_complexity(tree)
        
    except SyntaxError:
        pass
    
    return analysis
```

### Stage 2: Execution Testing
```python
import subprocess
import tempfile
import timeout_decorator
from io import StringIO
import sys

class ExampleExecutor:
    """Safely execute code examples with isolation"""
    
    def __init__(self, timeout: int = 10):
        self.timeout = timeout
        self.results = []
    
    @timeout_decorator.timeout(10)
    def execute_python(self, code: str, inputs: List[str] = None) -> Dict[str, Any]:
        """
        Execute Python code in isolated environment.
        
        Returns execution results with output, errors, and metrics.
        """
        result = {
            'executed': False,
            'output': '',
            'errors': '',
            'return_code': -1,
            'execution_time': 0,
            'memory_usage': 0
        }
        
        # Create temporary file
        with tempfile.NamedTemporaryFile(mode='w', suffix='.py', delete=False) as f:
            f.write(code)
            temp_file = f.name
        
        try:
            # Execute with subprocess for isolation
            start_time = time.time()
            
            process = subprocess.Popen(
                [sys.executable, temp_file],
                stdin=subprocess.PIPE,
                stdout=subprocess.PIPE,
                stderr=subprocess.PIPE,
                text=True
            )
            
            # Provide input if needed
            stdout, stderr = process.communicate(
                input='\n'.join(inputs) if inputs else None,
                timeout=self.timeout
            )
            
            execution_time = time.time() - start_time
            
            result.update({
                'executed': True,
                'output': stdout,
                'errors': stderr,
                'return_code': process.returncode,
                'execution_time': execution_time
            })
            
        except subprocess.TimeoutExpired:
            result['errors'] = f"Execution timeout ({self.timeout}s)"
        except Exception as e:
            result['errors'] = str(e)
        finally:
            # Cleanup
            os.unlink(temp_file)
        
        return result
    
    def validate_output(self, actual: str, expected: str) -> bool:
        """Compare actual output with expected"""
        
        # Normalize whitespace
        actual = actual.strip()
        expected = expected.strip()
        
        # Direct comparison
        if actual == expected:
            return True
        
        # Fuzzy matching for numerical outputs
        if self._fuzzy_match(actual, expected):
            return True
        
        return False
    
    def _fuzzy_match(self, actual: str, expected: str, tolerance: float = 0.01) -> bool:
        """Fuzzy matching for numerical outputs"""
        try:
            # Try to parse as numbers
            actual_nums = re.findall(r'-?\d+\.?\d*', actual)
            expected_nums = re.findall(r'-?\d+\.?\d*', expected)
            
            if len(actual_nums) == len(expected_nums):
                for a, e in zip(actual_nums, expected_nums):
                    if abs(float(a) - float(e)) > tolerance:
                        return False
                return True
        except:
            pass
        
        return False
```

### Stage 3: Test Generation and Execution
```python
def generate_tests(example_code: str) -> str:
    """
    Generate test cases for example code.
    
    Analyzes the example and creates appropriate test cases.
    """
    
    test_template = '''
import unittest
import sys
import io
from contextlib import redirect_stdout, redirect_stderr

{imports}

class TestExample(unittest.TestCase):
    """Auto-generated tests for example validation"""
    
    def setUp(self):
        """Set up test fixtures"""
        self.maxDiff = None
    
    {test_methods}
    
    def test_no_errors(self):
        """Ensure code executes without errors"""
        try:
            # Execute the example code
            exec("""{example_code}""")
        except Exception as e:
            self.fail(f"Example raised an exception: {{e}}")
    
    def test_expected_output(self):
        """Verify output matches expected"""
        captured_output = io.StringIO()
        with redirect_stdout(captured_output):
            exec("""{example_code}""")
        
        output = captured_output.getvalue()
        self.assertIn("{expected_output}", output)

if __name__ == '__main__':
    unittest.main()
'''
    
    # Parse example to extract testable components
    tree = ast.parse(example_code)
    
    # Extract imports
    imports = extract_imports(tree)
    
    # Generate test methods for functions
    test_methods = generate_function_tests(tree)
    
    return test_template.format(
        imports=imports,
        test_methods=test_methods,
        example_code=example_code,
        expected_output="Hello, World!"  # Extract from comments
    )
```

### Stage 4: Performance Validation
```python
import time
import tracemalloc
import cProfile
import pstats

class PerformanceValidator:
    """Validate performance characteristics of examples"""
    
    def __init__(self):
        self.thresholds = {
            'execution_time': 1.0,  # seconds
            'memory_usage': 100,    # MB
            'cpu_usage': 80,        # percentage
        }
    
    def measure_performance(self, code: str, iterations: int = 100) -> Dict[str, float]:
        """
        Measure performance metrics of code example.
        
        Returns metrics including execution time, memory usage, and CPU profile.
        """
        metrics = {}
        
        # Measure execution time
        times = []
        for _ in range(iterations):
            start = time.perf_counter()
            exec(code)
            times.append(time.perf_counter() - start)
        
        metrics['avg_time'] = sum(times) / len(times)
        metrics['min_time'] = min(times)
        metrics['max_time'] = max(times)
        
        # Measure memory usage
        tracemalloc.start()
        exec(code)
        current, peak = tracemalloc.get_traced_memory()
        tracemalloc.stop()
        
        metrics['memory_current'] = current / 1024 / 1024  # MB
        metrics['memory_peak'] = peak / 1024 / 1024  # MB
        
        # CPU profiling
        profiler = cProfile.Profile()
        profiler.enable()
        exec(code)
        profiler.disable()
        
        stats = pstats.Stats(profiler)
        metrics['function_calls'] = stats.total_calls
        metrics['total_time'] = stats.total_tt
        
        return metrics
    
    def validate_performance(self, metrics: Dict[str, float]) -> Tuple[bool, List[str]]:
        """Check if performance metrics are within acceptable thresholds"""
        
        issues = []
        
        if metrics['avg_time'] > self.thresholds['execution_time']:
            issues.append(f"Execution time {metrics['avg_time']:.2f}s exceeds threshold")
        
        if metrics['memory_peak'] > self.thresholds['memory_usage']:
            issues.append(f"Memory usage {metrics['memory_peak']:.1f}MB exceeds threshold")
        
        return len(issues) == 0, issues
```

### Stage 5: Security Validation
```python
import re
from typing import List, Dict

class SecurityValidator:
    """Check examples for security vulnerabilities"""
    
    def __init__(self):
        self.vulnerability_patterns = {
            'eval': r'\beval\s*\(',
            'exec': r'\bexec\s*\(',
            'sql_injection': r'\".*SELECT.*FROM.*WHERE.*\+',
            'command_injection': r'os\.(system|popen|spawn)',
            'hardcoded_secrets': r'(password|api_key|secret)\s*=\s*["\'][^"\']+["\']',
            'insecure_random': r'random\.\w+\(',  # Should use secrets module
            'pickle': r'pickle\.(loads|load)\(',   # Unsafe deserialization
            'yaml_load': r'yaml\.load\(',          # Should use safe_load
        }
    
    def scan_vulnerabilities(self, code: str) -> Dict[str, List[str]]:
        """Scan code for security vulnerabilities"""
        
        vulnerabilities = {}
        
        for vuln_name, pattern in self.vulnerability_patterns.items():
            matches = re.finditer(pattern, code, re.IGNORECASE)
            
            for match in matches:
                if vuln_name not in vulnerabilities:
                    vulnerabilities[vuln_name] = []
                
                # Get line number
                line_num = code[:match.start()].count('\n') + 1
                vulnerabilities[vuln_name].append(
                    f"Line {line_num}: {match.group()}"
                )
        
        return vulnerabilities
    
    def validate_security(self, code: str) -> Tuple[bool, List[str]]:
        """Validate code security"""
        
        issues = []
        vulnerabilities = self.scan_vulnerabilities(code)
        
        if vulnerabilities:
            for vuln_type, locations in vulnerabilities.items():
                for location in locations:
                    issues.append(f"Security issue ({vuln_type}): {location}")
        
        # Additional checks
        if 'import os' in code and 'input()' in code:
            issues.append("Potential command injection: os module with user input")
        
        return len(issues) == 0, issues
```

### Stage 6: Quality Metrics
```python
class QualityValidator:
    """Validate code quality metrics"""
    
    def __init__(self):
        self.thresholds = {
            'cyclomatic_complexity': 10,
            'cognitive_complexity': 15,
            'maintainability_index': 20,
            'lines_of_code': 500,
            'documentation_ratio': 0.2
        }
    
    def calculate_metrics(self, code: str) -> Dict[str, float]:
        """Calculate various code quality metrics"""
        
        metrics = {}
        tree = ast.parse(code)
        
        # Cyclomatic complexity
        metrics['cyclomatic_complexity'] = self._calculate_cyclomatic(tree)
        
        # Lines of code
        lines = code.split('\n')
        metrics['lines_of_code'] = len([l for l in lines if l.strip()])
        
        # Documentation ratio
        docstring_lines = len([l for l in lines if '"""' in l or "'''" in l])
        metrics['documentation_ratio'] = docstring_lines / max(len(lines), 1)
        
        # Function count
        functions = [n for n in ast.walk(tree) if isinstance(n, ast.FunctionDef)]
        metrics['function_count'] = len(functions)
        
        # Average function length
        if functions:
            func_lengths = [
                n.end_lineno - n.lineno 
                for n in functions 
                if n.end_lineno
            ]
            metrics['avg_function_length'] = sum(func_lengths) / len(func_lengths)
        
        return metrics
    
    def _calculate_cyclomatic(self, tree: ast.AST) -> int:
        """Calculate cyclomatic complexity"""
        
        complexity = 1  # Base complexity
        
        for node in ast.walk(tree):
            # Decision points
            if isinstance(node, (ast.If, ast.While, ast.For)):
                complexity += 1
            elif isinstance(node, ast.BoolOp):
                complexity += len(node.values) - 1
            elif isinstance(node, ast.ExceptHandler):
                complexity += 1
        
        return complexity
```

## Validation Report Template

```markdown
# Validation Report

**Example**: {{filename}}
**Date**: {{timestamp}}
**Overall Status**: {{PASS|FAIL}}

## Summary
- ✅ Syntax Valid: {{yes/no}}
- ✅ Executes Successfully: {{yes/no}}
- ✅ Output Correct: {{yes/no}}
- ✅ Performance Acceptable: {{yes/no}}
- ✅ Security Validated: {{yes/no}}
- ✅ Quality Standards Met: {{yes/no}}

## Detailed Results

### Syntax Validation
{{syntax_results}}

### Execution Testing
```
Return Code: {{return_code}}
Execution Time: {{time}}ms
Memory Usage: {{memory}}MB
```

### Output Validation
**Expected Output:**
```
{{expected_output}}
```

**Actual Output:**
```
{{actual_output}}
```

**Match**: {{match_percentage}}%

### Performance Metrics
| Metric | Value | Threshold | Status |
|--------|-------|-----------|--------|
| Execution Time | {{time}} | 1000ms | {{status}} |
| Memory Usage | {{memory}} | 100MB | {{status}} |
| CPU Usage | {{cpu}}% | 80% | {{status}} |

### Security Scan
{{security_issues}}

### Quality Metrics
| Metric | Value | Threshold | Status |
|--------|-------|-----------|--------|
| Cyclomatic Complexity | {{cc}} | 10 | {{status}} |
| Lines of Code | {{loc}} | 500 | {{status}} |
| Documentation Ratio | {{doc}}% | 20% | {{status}} |

## Recommendations
{{recommendations}}
```

## Automated Fix Generation

```python
def auto_fix_issues(code: str, issues: List[Dict]) -> str:
    """Automatically fix common issues in examples"""
    
    fixed_code = code
    
    for issue in issues:
        if issue['type'] == 'missing_imports':
            # Add missing imports
            imports = generate_imports(issue['missing'])
            fixed_code = imports + '\n' + fixed_code
            
        elif issue['type'] == 'syntax_error':
            # Attempt to fix common syntax errors
            fixed_code = fix_syntax(fixed_code, issue['line'])
            
        elif issue['type'] == 'security_vulnerability':
            # Replace insecure patterns
            fixed_code = fix_security_issue(fixed_code, issue['pattern'])
    
    return fixed_code
```

## Success Criteria
✅ All syntax valid
✅ Examples execute without errors
✅ Output matches expected
✅ Performance within limits
✅ No security vulnerabilities
✅ Quality metrics acceptable
✅ Tests auto-generated and passing