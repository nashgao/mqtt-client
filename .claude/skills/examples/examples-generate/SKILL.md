# Command: examples-generate
Generate comprehensive, working code examples with validation and documentation

## Usage
```
/examples-generate [code-snippet|function|class|project]
```

## Description
Creates fully functional, tested code examples that demonstrate best practices. Every example is validated, includes error handling, and provides clear explanations of the "why" behind implementation choices.

## Documentation Structure Compliance
All generated examples are placed in the proper `docs/` folder structure based on project type:

### Project Type Detection and Placement
- **Unified Documentation Library**: See `/templates/resources/documentation-library/`
  - Examples follow Hyperf-style organization in `docs/examples/`
  - Progressive disclosure with basic → advanced → recipes structure
  - Module-specific examples in `docs/features/[module]/examples/`
- **Structure Management**: See `/templates/resources/documentation-library/core/structure-manager.md`
  - Automatic organization based on project type
  - Cross-referenced with main documentation hub

The command automatically detects the project type and places examples in the correct location.

## Implementation

### Phase 1: Context Analysis
```xml
<instructions>
Analyze the code context to determine example requirements
</instructions>

<analysis_targets>
- Programming language and framework
- Complexity level needed
- Target audience skill level
- Domain context (web, CLI, data processing, etc.)
- Required dependencies
- Testing framework availability
</analysis_targets>

<context>
Code: {{provided_code}}
Purpose: {{example_purpose}}
Audience: {{skill_level|intermediate}}
</context>

<output>
Example generation plan with validation requirements
</output>
```

### Phase 2: Progressive Example Structure
```xml
<instructions>
Create examples with progressive complexity layers
</instructions>

<complexity_levels>
1. **Minimal Working Example** (MWE)
   - Simplest possible implementation
   - Core concept demonstration
   - No dependencies if possible

2. **Real-World Example**
   - Error handling included
   - Edge cases covered
   - Production considerations

3. **Advanced Example**
   - Performance optimizations
   - Design patterns applied
   - Full test coverage
</complexity_levels>

<requirements>
- Each example must be runnable
- Include setup instructions
- Provide expected output
- Document common pitfalls
</requirements>
```

### Phase 3: Example Generation
```xml
<instructions>
Generate comprehensive code example
</instructions>

<example_structure>
1. **Setup/Prerequisites**
   - Required installations
   - Environment configuration
   - Import statements

2. **Core Implementation**
   - Well-commented code
   - Clear variable names
   - Logical flow

3. **Usage Demonstration**
   - Multiple use cases
   - Input variations
   - Output examples

4. **Error Handling**
   - Common errors
   - Recovery strategies
   - Debugging tips

5. **Testing**
   - Unit tests
   - Integration tests
   - Performance benchmarks
</example_structure>
```

## Example Templates

### Function Example Template
```{{language}}
"""
Example: {{function_purpose}}
Demonstrates: {{key_concepts}}
Complexity: {{beginner|intermediate|advanced}}
"""

# Setup and imports
{{imports}}

def {{function_name}}({{parameters}}):
    """
    {{brief_description}}
    
    Why this approach:
    - {{reason_1}}
    - {{reason_2}}
    
    Args:
        {{param}}: {{description}} 
            Example: {{example_value}}
    
    Returns:
        {{return_description}}
    
    Raises:
        {{exception}}: {{when_raised}}
    
    Example:
        >>> result = {{function_name}}({{example_input}})
        >>> print(result)
        {{expected_output}}
    """
    
    # Step 1: {{step_description}}
    {{step_1_code}}  # Why: {{explanation}}
    
    # Step 2: {{step_description}}
    {{step_2_code}}  # Why: {{explanation}}
    
    # Error handling
    try:
        {{main_logic}}
    except {{exception}} as e:
        # Handle {{error_scenario}}
        {{error_handling}}
        
    return {{result}}

# Usage examples
if __name__ == "__main__":
    # Example 1: Basic usage
    print("Example 1: Basic usage")
    {{basic_example}}
    
    # Example 2: Edge case
    print("\nExample 2: Edge case")
    {{edge_case_example}}
    
    # Example 3: Error scenario
    print("\nExample 3: Error handling")
    try:
        {{error_example}}
    except Exception as e:
        print(f"Handled error: {e}")
```

### Class Example Template
```{{language}}
class {{ClassName}}:
    """
    {{class_description}}
    
    Design decisions:
    - {{design_choice_1}}: {{rationale}}
    - {{design_choice_2}}: {{rationale}}
    
    Usage:
        >>> obj = {{ClassName}}({{init_params}})
        >>> result = obj.{{method}}({{args}})
        >>> assert result == {{expected}}
    """
    
    def __init__(self, {{parameters}}):
        """Initialize with validation"""
        # Validate inputs
        if not {{validation_condition}}:
            raise ValueError({{error_message}})
        
        # Initialize state
        self.{{attribute}} = {{value}}
        
        # Why: {{initialization_reasoning}}
    
    def {{method_name}}(self, {{parameters}}):
        """
        {{method_description}}
        
        Implementation notes:
        - {{implementation_note_1}}
        - {{implementation_note_2}}
        """
        # Implementation with comments
        {{method_implementation}}
        
        return {{result}}
    
    # Additional methods...
```

## Validation Framework

### Automatic Validation
```python
def validate_example(code_example):
    """Validates that example code works correctly"""
    
    validations = {
        'syntax': check_syntax(code_example),
        'imports': verify_imports(code_example),
        'execution': test_execution(code_example),
        'output': verify_output(code_example),
        'performance': check_performance(code_example)
    }
    
    return all(validations.values()), validations

# Validation checklist
validation_requirements = {
    'runnable': True,           # Code executes without errors
    'testable': True,          # Includes test cases
    'documented': True,        # Has clear documentation
    'error_handled': True,     # Handles common errors
    'performant': True,        # Meets performance criteria
    'secure': True            # No security vulnerabilities
}
```

### Testing Template
```{{test_framework}}
def test_{{function_name}}():
    """Test {{function_name}} with various inputs"""
    
    # Test case 1: Normal operation
    result = {{function_name}}({{normal_input}})
    assert result == {{expected_output}}
    
    # Test case 2: Edge case
    result = {{function_name}}({{edge_input}})
    assert result == {{edge_output}}
    
    # Test case 3: Error condition
    with pytest.raises({{exception}}):
        {{function_name}}({{invalid_input}})
    
    # Test case 4: Performance
    import time
    start = time.time()
    {{function_name}}({{performance_input}})
    elapsed = time.time() - start
    assert elapsed < {{max_time}}  # Performance requirement
```

## Visual Checkpoints

### Progress Indicators
```markdown
## Example Progress Checkpoints

✅ **Checkpoint 1**: Basic setup complete
- Environment configured
- Dependencies installed
- Initial code runs

✅ **Checkpoint 2**: Core functionality working
- Main function operates
- Expected output produced
- No runtime errors

✅ **Checkpoint 3**: Error handling added
- Edge cases covered
- Exceptions handled gracefully
- Recovery mechanisms in place

✅ **Checkpoint 4**: Tests passing
- All unit tests pass
- Integration tests complete
- Performance benchmarks met

✅ **Checkpoint 5**: Production ready
- Documentation complete
- Security validated
- Deployment tested
```

## Real-World Scenarios

### Web API Example
```python
# Real-world example: REST API endpoint with rate limiting
from flask import Flask, request, jsonify
from functools import wraps
import time

app = Flask(__name__)

def rate_limit(max_calls=10, period=60):
    """Rate limiting decorator - real production pattern"""
    def decorator(f):
        calls = []
        
        @wraps(f)
        def wrapper(*args, **kwargs):
            now = time.time()
            # Remove old calls outside the period
            calls[:] = [c for c in calls if c > now - period]
            
            if len(calls) >= max_calls:
                return jsonify({'error': 'Rate limit exceeded'}), 429
            
            calls.append(now)
            return f(*args, **kwargs)
        return wrapper
    return decorator

@app.route('/api/data')
@rate_limit(max_calls=100, period=60)
def get_data():
    """Production-ready API endpoint with rate limiting"""
    try:
        # Validate request
        if not request.args.get('id'):
            return jsonify({'error': 'Missing required parameter: id'}), 400
        
        # Process request
        result = process_data(request.args.get('id'))
        
        # Return with proper headers
        response = jsonify(result)
        response.headers['X-RateLimit-Remaining'] = str(remaining_calls())
        return response
        
    except Exception as e:
        # Log error (in production, use proper logging)
        app.logger.error(f"Error processing request: {e}")
        return jsonify({'error': 'Internal server error'}), 500
```

### Data Processing Example
```python
# Real-world example: Efficient data processing with pandas
import pandas as pd
import numpy as np
from typing import Optional, Dict, Any

def process_large_dataset(
    file_path: str,
    chunk_size: int = 10000,
    filters: Optional[Dict[str, Any]] = None
) -> pd.DataFrame:
    """
    Process large datasets efficiently using chunking
    
    Real-world considerations:
    - Memory efficiency through chunking
    - Type hints for clarity
    - Progress tracking for UX
    - Error recovery mechanisms
    """
    
    processed_chunks = []
    total_rows = 0
    
    try:
        # Process in chunks to handle large files
        for i, chunk in enumerate(pd.read_csv(file_path, chunksize=chunk_size)):
            # Apply filters if provided
            if filters:
                for column, value in filters.items():
                    if column in chunk.columns:
                        chunk = chunk[chunk[column] == value]
            
            # Data cleaning
            chunk = chunk.dropna(subset=['critical_column'])
            chunk['processed_date'] = pd.Timestamp.now()
            
            processed_chunks.append(chunk)
            total_rows += len(chunk)
            
            # Progress indicator for large files
            if i % 10 == 0:
                print(f"Processed {total_rows:,} rows...")
        
        # Combine all chunks
        result = pd.concat(processed_chunks, ignore_index=True)
        
        print(f"Successfully processed {total_rows:,} total rows")
        return result
        
    except FileNotFoundError:
        raise FileNotFoundError(f"Data file not found: {file_path}")
    except pd.errors.EmptyDataError:
        raise ValueError("The data file is empty")
    except Exception as e:
        # Log error and attempt recovery
        print(f"Error processing data: {e}")
        if processed_chunks:
            # Return partial results if available
            print("Returning partial results")
            return pd.concat(processed_chunks, ignore_index=True)
        raise
```

## Quality Metrics

### Code Quality Checklist
- ✅ **Readability**: Clear variable names and structure
- ✅ **Maintainability**: Modular design, low complexity
- ✅ **Testability**: Easy to test, mockable dependencies
- ✅ **Performance**: Meets timing requirements
- ✅ **Security**: No vulnerable patterns
- ✅ **Documentation**: Complete and accurate

### Complexity Metrics
```python
def analyze_complexity(code):
    """Analyze code complexity metrics"""
    
    metrics = {
        'cyclomatic_complexity': calculate_cyclomatic(code),
        'cognitive_complexity': calculate_cognitive(code),
        'lines_of_code': count_lines(code),
        'test_coverage': measure_coverage(code),
        'documentation_ratio': calc_doc_ratio(code)
    }
    
    # Thresholds for good examples
    thresholds = {
        'cyclomatic_complexity': 10,
        'cognitive_complexity': 15,
        'test_coverage': 80,
        'documentation_ratio': 0.3
    }
    
    return metrics, all(
        metrics[key] <= thresholds[key] 
        for key in ['cyclomatic_complexity', 'cognitive_complexity']
    )
```

## Success Criteria
✅ Example runs without errors
✅ All tests pass
✅ Documentation explains "why"
✅ Error handling demonstrated
✅ Performance acceptable
✅ Security validated
✅ Visual checkpoints clear