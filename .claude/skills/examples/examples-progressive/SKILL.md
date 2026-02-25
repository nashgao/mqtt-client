# Command: examples-progressive
Generate skill-appropriate examples from beginner to advanced with smooth progression

## Usage
```
/examples-progressive [concept|feature|pattern] --levels [beginner|intermediate|advanced|all]
```

## Description
Creates a progressive learning path through code examples, starting with minimal concepts and building to production-ready implementations. Each level introduces new concepts while reinforcing previous learning.

## Documentation Structure Compliance
Progressive examples are organized in `docs/` following standardized structures:

### Project Type Organization
- **Production Projects**: See `/templates/resources/documentation-library/core/structure-manager.md`
  - Beginner examples: `docs/user-guides/tutorials/01-basics/`
  - Intermediate: `docs/user-guides/tutorials/02-intermediate/`
  - Advanced: `docs/user-guides/tutorials/03-advanced/`
- **Single Libraries**: See `/templates/resources/documentation-library/core/structure-manager.md`
  - All levels in: `docs/examples/` with sections for each level
- **Aggregated Libraries**: See `/templates/resources/documentation-library/core/structure-manager.md`
  - Module-specific: `docs/[module]/examples/` with progressive complexity

Examples automatically organized by detected project type and complexity level.

## Implementation

### Progressive Complexity Framework
```xml
<instructions>
Design examples with progressive skill development
</instructions>

<progression_strategy>
1. **Foundation Level** - Core concept only
2. **Building Level** - Add basic features
3. **Expanding Level** - Include error handling
4. **Optimizing Level** - Performance improvements
5. **Production Level** - Full implementation
</progression_strategy>

<learning_principles>
- Each level builds on the previous
- New concepts introduced gradually
- Repetition reinforces understanding
- Clear transitions between levels
- Success checkpoints at each stage
</learning_principles>
```

## Level Definitions

### 🟢 Beginner Level
**Focus**: Understanding the basic concept
```xml
<beginner_requirements>
- Minimal code (< 20 lines)
- No external dependencies
- Single responsibility
- Clear, verbose variable names
- Extensive comments
- Simple data types only
- Synchronous execution
- Happy path only
</beginner_requirements>

<beginner_example>
# BEGINNER: Simple function that adds two numbers
# Learning goals: Functions, parameters, return values

def add_numbers(first_number, second_number):
    """
    Adds two numbers together and returns the result.
    
    This is a basic function that demonstrates:
    - How to define a function
    - How to accept parameters
    - How to return a value
    """
    # Calculate the sum
    result = first_number + second_number
    
    # Return the result to whoever called this function
    return result

# Let's test our function
number1 = 5
number2 = 3
answer = add_numbers(number1, number2)
print(f"The sum of {number1} and {number2} is {answer}")
# Output: The sum of 5 and 3 is 8
</beginner_example>
```

### 🟡 Intermediate Level
**Focus**: Real-world usage patterns
```xml
<intermediate_requirements>
- Moderate complexity (50-100 lines)
- Common libraries allowed
- Multiple functions/methods
- Type hints added
- Error handling basics
- Working with collections
- File I/O operations
- Basic validation
</intermediate_requirements>

<intermediate_example>
from typing import List, Optional, Union
import json

def calculate_sum(numbers: List[Union[int, float]]) -> float:
    """
    Calculate sum with validation and error handling.
    
    Intermediate concepts:
    - Type hints for clarity
    - Input validation
    - Error handling
    - Working with lists
    """
    # Validate input
    if not numbers:
        raise ValueError("Cannot calculate sum of empty list")
    
    # Check for invalid values
    for num in numbers:
        if not isinstance(num, (int, float)):
            raise TypeError(f"Invalid type: {type(num)}. Expected int or float")
    
    # Calculate and return sum
    total = sum(numbers)
    return total

def save_result(result: float, filename: str = "result.json") -> bool:
    """Save calculation result to file"""
    try:
        data = {
            "result": result,
            "timestamp": datetime.now().isoformat()
        }
        
        with open(filename, 'w') as f:
            json.dump(data, f, indent=2)
        
        return True
    except IOError as e:
        print(f"Failed to save result: {e}")
        return False

# Usage with error handling
try:
    numbers_list = [10, 20, 30.5, 40]
    result = calculate_sum(numbers_list)
    print(f"Sum: {result}")
    
    if save_result(result):
        print("Result saved successfully")
        
except ValueError as e:
    print(f"Validation error: {e}")
except TypeError as e:
    print(f"Type error: {e}")
</intermediate_example>
```

### 🔴 Advanced Level
**Focus**: Production-ready implementation
```xml
<advanced_requirements>
- Full implementation (200+ lines)
- Design patterns applied
- Async/concurrent operations
- Comprehensive error handling
- Logging and monitoring
- Configuration management
- Performance optimization
- Security considerations
- Full test coverage
</advanced_requirements>

<advanced_example>
import asyncio
import logging
from typing import List, Dict, Any, Optional, Protocol
from dataclasses import dataclass
from concurrent.futures import ThreadPoolExecutor
from functools import lru_cache, wraps
import time

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

class Calculator(Protocol):
    """Protocol for calculator implementations"""
    async def calculate(self, numbers: List[float]) -> float:
        ...

@dataclass
class CalculationResult:
    """Result container with metadata"""
    value: float
    duration_ms: float
    method: str
    cached: bool = False

class AdvancedCalculator:
    """
    Production-ready calculator with advanced features.
    
    Features:
    - Async operations for scalability
    - Caching for performance
    - Multiple calculation strategies
    - Comprehensive error handling
    - Metrics and logging
    - Thread pool for CPU-bound operations
    """
    
    def __init__(self, max_workers: int = 4, cache_size: int = 128):
        self.executor = ThreadPoolExecutor(max_workers=max_workers)
        self.cache_size = cache_size
        self._metrics: Dict[str, Any] = {
            'total_calculations': 0,
            'cache_hits': 0,
            'errors': 0
        }
    
    def performance_monitor(self, func):
        """Decorator to monitor performance"""
        @wraps(func)
        async def wrapper(*args, **kwargs):
            start = time.perf_counter()
            try:
                result = await func(*args, **kwargs)
                duration = (time.perf_counter() - start) * 1000
                logger.info(f"{func.__name__} completed in {duration:.2f}ms")
                return result
            except Exception as e:
                self._metrics['errors'] += 1
                logger.error(f"Error in {func.__name__}: {e}")
                raise
        return wrapper
    
    @lru_cache(maxsize=128)
    def _cached_sum(self, numbers_tuple: tuple) -> float:
        """Cached synchronous sum calculation"""
        return sum(numbers_tuple)
    
    @performance_monitor
    async def calculate_sum(
        self, 
        numbers: List[float],
        use_cache: bool = True,
        parallel: bool = False
    ) -> CalculationResult:
        """
        Advanced sum calculation with multiple strategies.
        
        Args:
            numbers: List of numbers to sum
            use_cache: Whether to use caching
            parallel: Whether to use parallel processing
            
        Returns:
            CalculationResult with metadata
        """
        start = time.perf_counter()
        self._metrics['total_calculations'] += 1
        
        # Validation
        if not numbers:
            raise ValueError("Empty input list")
        
        if len(numbers) > 10000:
            logger.warning(f"Large input size: {len(numbers)} elements")
        
        # Try cache first
        if use_cache:
            numbers_tuple = tuple(numbers)
            if numbers_tuple in self._cached_sum.cache_info().currsize:
                self._metrics['cache_hits'] += 1
                cached_result = self._cached_sum(numbers_tuple)
                return CalculationResult(
                    value=cached_result,
                    duration_ms=0.1,
                    method="cached",
                    cached=True
                )
        
        # Calculate based on strategy
        if parallel and len(numbers) > 1000:
            result = await self._parallel_sum(numbers)
            method = "parallel"
        else:
            result = await self._async_sum(numbers)
            method = "async"
        
        # Cache result
        if use_cache:
            self._cached_sum(tuple(numbers))
        
        duration = (time.perf_counter() - start) * 1000
        
        return CalculationResult(
            value=result,
            duration_ms=duration,
            method=method,
            cached=False
        )
    
    async def _async_sum(self, numbers: List[float]) -> float:
        """Async sum calculation"""
        await asyncio.sleep(0)  # Yield control
        return sum(numbers)
    
    async def _parallel_sum(self, numbers: List[float]) -> float:
        """Parallel sum using thread pool"""
        loop = asyncio.get_event_loop()
        
        # Split work into chunks
        chunk_size = len(numbers) // self.executor._max_workers
        chunks = [
            numbers[i:i + chunk_size] 
            for i in range(0, len(numbers), chunk_size)
        ]
        
        # Process chunks in parallel
        futures = [
            loop.run_in_executor(self.executor, sum, chunk)
            for chunk in chunks
        ]
        
        results = await asyncio.gather(*futures)
        return sum(results)
    
    def get_metrics(self) -> Dict[str, Any]:
        """Return performance metrics"""
        cache_info = self._cached_sum.cache_info()
        return {
            **self._metrics,
            'cache_info': {
                'hits': cache_info.hits,
                'misses': cache_info.misses,
                'size': cache_info.currsize,
                'maxsize': cache_info.maxsize
            }
        }
    
    async def close(self):
        """Cleanup resources"""
        self.executor.shutdown(wait=True)

# Advanced usage example
async def main():
    calculator = AdvancedCalculator(max_workers=4)
    
    try:
        # Small dataset - use cache
        small_numbers = list(range(100))
        result1 = await calculator.calculate_sum(small_numbers)
        print(f"Small dataset: {result1}")
        
        # Large dataset - use parallel processing
        large_numbers = list(range(10000))
        result2 = await calculator.calculate_sum(
            large_numbers, 
            parallel=True
        )
        print(f"Large dataset: {result2}")
        
        # Check metrics
        metrics = calculator.get_metrics()
        print(f"Performance metrics: {metrics}")
        
    finally:
        await calculator.close()

if __name__ == "__main__":
    asyncio.run(main())
</advanced_example>
```

## Progressive Learning Path

### Concept Introduction Strategy
```markdown
## Learning Path: API Development

### Level 1: Hello World Endpoint
```python
# Simplest possible API
def hello():
    return "Hello, World!"
```

### Level 2: Accept Parameters
```python
# Add input handling
def hello(name):
    return f"Hello, {name}!"
```

### Level 3: Validation
```python
# Add input validation
def hello(name):
    if not name:
        return "Error: Name required"
    return f"Hello, {name}!"
```

### Level 4: HTTP Methods
```python
# Full REST endpoint
@app.route('/hello', methods=['GET', 'POST'])
def hello():
    if request.method == 'POST':
        name = request.json.get('name')
        if not name:
            return {'error': 'Name required'}, 400
        return {'message': f'Hello, {name}!'}, 200
    return {'message': 'Send POST with name'}, 200
```

### Level 5: Production Features
```python
# Production-ready with all features
@app.route('/api/v1/hello', methods=['GET', 'POST'])
@rate_limit(100, per=60)
@require_auth
@validate_schema(HelloSchema)
@monitor_performance
async def hello():
    """Full production endpoint with:
    - Rate limiting
    - Authentication
    - Schema validation
    - Performance monitoring
    - Async processing
    - Error handling
    - Logging
    """
    # Full implementation...
```
```

## Skills Matrix

### Progressive Skill Development
| Level | Concepts | Skills | Prerequisites |
|-------|----------|--------|---------------|
| **Beginner** | Variables, Functions, Returns | Basic syntax, Simple logic | None |
| **Intermediate** | Types, Collections, Files | Error handling, Validation | Beginner |
| **Advanced** | Async, Patterns, Optimization | Architecture, Performance | Intermediate |

### Learning Objectives by Level

#### Beginner Learning Objectives
- [ ] Understand basic syntax
- [ ] Write simple functions
- [ ] Use variables correctly
- [ ] Handle simple inputs/outputs
- [ ] Follow code flow

#### Intermediate Learning Objectives
- [ ] Handle errors gracefully
- [ ] Work with data structures
- [ ] Implement validation
- [ ] Use external libraries
- [ ] Write reusable code

#### Advanced Learning Objectives
- [ ] Design scalable systems
- [ ] Optimize performance
- [ ] Implement design patterns
- [ ] Handle concurrency
- [ ] Ensure security

## Transition Guides

### Beginner → Intermediate
```markdown
## Transitioning to Intermediate

You're ready for intermediate when you can:
✅ Write functions without help
✅ Understand variable scope
✅ Debug simple errors
✅ Read basic documentation

New concepts to learn:
- Error handling with try/except
- Working with files and JSON
- Using type hints
- Creating classes
- Using external libraries
```

### Intermediate → Advanced
```markdown
## Transitioning to Advanced

You're ready for advanced when you can:
✅ Handle errors appropriately
✅ Design multi-function programs
✅ Use common design patterns
✅ Write maintainable code

New concepts to learn:
- Asynchronous programming
- Performance optimization
- Security best practices
- System architecture
- Production deployment
```

## Assessment Checkpoints

### Self-Assessment Questions

#### After Beginner Examples
1. Can you explain what each line does?
2. Can you modify the example for a different use case?
3. Can you identify potential problems?

#### After Intermediate Examples
1. Can you add error handling for edge cases?
2. Can you refactor for better organization?
3. Can you write tests for the code?

#### After Advanced Examples
1. Can you identify performance bottlenecks?
2. Can you suggest architectural improvements?
3. Can you implement additional features?

## Success Metrics
✅ Clear progression path defined
✅ Each level builds on previous
✅ Smooth skill transitions
✅ Self-assessment included
✅ Real-world applications shown
✅ Production readiness achieved