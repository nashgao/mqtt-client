# Command: examples-tutorial  
Create interactive, step-by-step tutorials with checkpoints and exercises

## Usage
```
/examples-tutorial [topic] --format [notebook|markdown|interactive]
```

## Description
Generates comprehensive tutorials that guide learners through concepts with hands-on exercises, visual checkpoints, and progressive challenges. Each tutorial includes self-assessment, troubleshooting, and next steps.

## Documentation Structure Compliance
Tutorials are organized in `docs/` following educational best practices:

### Tutorial Organization
- **Production Projects**: See `/templates/resources/documentation-library/core/structure-manager.md`
  - Tutorials in: `docs/user-guides/tutorials/` with numbered sections
  - Interactive guides: `docs/getting-started/` for onboarding
- **Single Libraries**: See `/templates/resources/documentation-library/core/structure-manager.md`
  - Tutorial content: `docs/quick-start.md` and `docs/examples/tutorials/`
- **Aggregated Libraries**: See `/templates/resources/documentation-library/core/structure-manager.md`
  - Module tutorials: `docs/[module]/tutorials/` for focused learning
  - Cross-module: `docs/patterns/` for integrated tutorials

Tutorials include progressive learning paths with clear navigation between lessons.

## Implementation

### Tutorial Structure Framework
```xml
<instructions>
Design interactive tutorials with progressive learning
</instructions>

<tutorial_components>
1. **Learning Objectives** - Clear goals
2. **Prerequisites Check** - Ensure readiness
3. **Concept Introduction** - Theory with examples
4. **Hands-On Exercise** - Practice immediately
5. **Visual Checkpoint** - Verify understanding
6. **Challenge Exercise** - Apply knowledge
7. **Troubleshooting** - Common issues
8. **Self-Assessment** - Check comprehension
9. **Next Steps** - Continue learning path
</tutorial_components>

<engagement_features>
- Interactive code cells
- Progress tracking
- Hints system
- Solution reveals
- Achievement badges
- Time estimates
- Difficulty indicators
</engagement_features>
```

## Tutorial Template

### 📚 Complete Tutorial Structure
```markdown
# Tutorial: {{Topic Name}}

## 🎯 What You'll Learn
By the end of this tutorial, you'll be able to:
- ✅ {{Learning objective 1}}
- ✅ {{Learning objective 2}} 
- ✅ {{Learning objective 3}}
- ✅ {{Learning objective 4}}

**Time Required:** {{30-45}} minutes
**Difficulty:** {{Beginner|Intermediate|Advanced}}
**Prerequisites:** {{List prerequisites}}

## 📋 Prerequisites Check
Before starting, make sure you have:

```python
# Run this cell to check prerequisites
import sys

def check_prerequisites():
    checks = {
        'Python 3.7+': sys.version_info >= (3, 7),
        'Required packages': check_packages(['pandas', 'numpy']),
        'Basic Python knowledge': True  # Self-assessed
    }
    
    for requirement, met in checks.items():
        status = "✅" if met else "❌"
        print(f"{status} {requirement}")
    
    return all(checks.values())

if check_prerequisites():
    print("\n🎉 You're ready to start!")
else:
    print("\n⚠️ Please install missing requirements first")
```

## 📖 Part 1: Understanding the Concept

### What is {{concept}}?
{{Concept explanation with real-world analogy}}

**Real-world example:**
Imagine {{relatable scenario}}. This is exactly how {{concept}} works in programming.

### Why is this important?
- **Problem it solves:** {{problem description}}
- **When to use it:** {{use cases}}
- **Benefits:** {{key benefits}}

### Visual Representation
```
{{ASCII diagram or simple visualization}}

Input → [Process] → Output
  ↓         ↓         ↓
Data    Transform  Result
```

## 💻 Part 2: Your First {{Concept}}

Let's start with the simplest possible example:

```python
# Step 1: Basic implementation
def simple_example():
    """Your first {{concept}} - as simple as possible"""
    
    # This is the core idea
    result = {{basic_operation}}
    
    # See what happens
    print(f"Result: {result}")
    
    return result

# Try it yourself!
simple_example()
```

### 🏁 Checkpoint 1: Basic Understanding
**Can you answer these questions?**
1. What does the function do?
2. What would happen if we changed {{parameter}}?
3. Can you modify it to {{simple_modification}}?

<details>
<summary>💡 Need a hint?</summary>

Think about how {{concept_hint}}. The key is to {{key_insight}}.
</details>

<details>
<summary>✅ Show Solution</summary>

```python
# Modified version
def modified_example():
    # Changed to handle {{modification}}
    result = {{modified_operation}}
    return result
```
</details>

## 🔨 Part 3: Building Something Useful

Now let's create something you can actually use:

```python
# Step 2: Practical implementation
class {{ConceptClass}}:
    """A practical {{concept}} you can use in real projects"""
    
    def __init__(self, {{parameters}}):
        """Initialize with validation"""
        
        # Validate inputs (always do this in production!)
        if not {{validation_condition}}:
            raise ValueError("{{error_message}}")
        
        self.{{attribute}} = {{value}}
        print(f"✅ Created {{concept}} with {self.{{attribute}}}")
    
    def process(self, data):
        """Main processing method"""
        
        # Show progress for learning
        print(f"Processing {len(data)} items...")
        
        results = []
        for item in data:
            # Core logic here
            processed = self.{{operation}}(item)
            results.append(processed)
            
            # Visual feedback
            print(f"  • Processed: {item} → {processed}")
        
        print(f"✅ Completed! Processed {len(results)} items")
        return results
    
    def {{operation}}(self, item):
        """Individual item processing"""
        # Your logic here
        return {{transformation}}

# Let's test it!
example = {{ConceptClass}}({{sample_params}})
test_data = {{sample_data}}
results = example.process(test_data)
```

### 🏁 Checkpoint 2: Practical Application
**Try these modifications:**
1. Add error handling for invalid inputs
2. Implement a counter for processed items
3. Add a method to reset the state

## 🚀 Part 4: Advanced Techniques

Ready for more? Let's explore advanced patterns:

```python
# Step 3: Production-ready implementation
import logging
from typing import List, Optional, Dict, Any
from functools import wraps
import time

class Advanced{{Concept}}:
    """Production-ready {{concept}} with all the bells and whistles"""
    
    def __init__(self, config: Optional[Dict[str, Any]] = None):
        self.config = config or {}
        self.logger = logging.getLogger(__name__)
        self.metrics = {'processed': 0, 'errors': 0, 'total_time': 0}
        
    def performance_monitor(self, func):
        """Decorator to monitor performance"""
        @wraps(func)
        def wrapper(*args, **kwargs):
            start = time.time()
            try:
                result = func(*args, **kwargs)
                elapsed = time.time() - start
                self.metrics['total_time'] += elapsed
                self.logger.info(f"{func.__name__} took {elapsed:.3f}s")
                return result
            except Exception as e:
                self.metrics['errors'] += 1
                self.logger.error(f"Error in {func.__name__}: {e}")
                raise
        return wrapper
    
    @performance_monitor
    def batch_process(self, items: List[Any], batch_size: int = 10) -> List[Any]:
        """Process items in batches for efficiency"""
        
        results = []
        total_batches = (len(items) + batch_size - 1) // batch_size
        
        for i in range(0, len(items), batch_size):
            batch = items[i:i + batch_size]
            batch_num = i // batch_size + 1
            
            print(f"Processing batch {batch_num}/{total_batches}")
            
            # Process batch with error recovery
            batch_results = self._process_batch_with_retry(batch)
            results.extend(batch_results)
            
            # Show progress
            progress = (batch_num / total_batches) * 100
            print(f"Progress: [{'=' * int(progress/5)}{' ' * (20-int(progress/5))}] {progress:.1f}%")
        
        self.metrics['processed'] += len(results)
        return results
    
    def _process_batch_with_retry(self, batch: List[Any], max_retries: int = 3) -> List[Any]:
        """Process with retry logic"""
        
        for attempt in range(max_retries):
            try:
                return [self._process_item(item) for item in batch]
            except Exception as e:
                if attempt == max_retries - 1:
                    raise
                self.logger.warning(f"Retry {attempt + 1}/{max_retries}: {e}")
                time.sleep(2 ** attempt)  # Exponential backoff
    
    def _process_item(self, item: Any) -> Any:
        """Process individual item"""
        # Simulate processing
        time.sleep(0.01)
        return item.upper() if isinstance(item, str) else item
    
    def get_metrics(self) -> Dict[str, Any]:
        """Get performance metrics"""
        return {
            **self.metrics,
            'success_rate': (self.metrics['processed'] / 
                           max(self.metrics['processed'] + self.metrics['errors'], 1))
        }

# Advanced usage
advanced = Advanced{{Concept}}()
data = ['item1', 'item2', 'item3'] * 10  # 30 items

# Process with monitoring
results = advanced.batch_process(data, batch_size=5)

# Check metrics
print(f"\n📊 Performance Metrics:")
for key, value in advanced.get_metrics().items():
    print(f"  • {key}: {value}")
```

## 🎯 Exercise: Build Your Own

Now it's your turn! Complete this exercise:

```python
# TODO: Implement your own version
class Your{{Concept}}:
    """
    Requirements:
    1. Initialize with a configuration parameter
    2. Implement a process method
    3. Add input validation
    4. Include error handling
    5. Track metrics
    """
    
    def __init__(self, {{your_params}}):
        # TODO: Your initialization
        pass
    
    def process(self, data):
        # TODO: Your processing logic
        pass
    
    # TODO: Add at least one more method
    
# Test your implementation
# your_instance = Your{{Concept}}(...)
# result = your_instance.process(...)
```

<details>
<summary>💡 Hints</summary>

1. Start with the simplest version that works
2. Add validation one check at a time
3. Use try/except for error handling
4. Keep a counter for metrics

</details>

<details>
<summary>✅ Solution</summary>

```python
class Your{{Concept}}:
    def __init__(self, name: str, threshold: float = 0.5):
        if not name:
            raise ValueError("Name cannot be empty")
        if not 0 <= threshold <= 1:
            raise ValueError("Threshold must be between 0 and 1")
        
        self.name = name
        self.threshold = threshold
        self.processed_count = 0
    
    def process(self, data: List[float]) -> List[bool]:
        if not data:
            return []
        
        results = []
        for value in data:
            try:
                result = self.evaluate(value)
                results.append(result)
                self.processed_count += 1
            except Exception as e:
                print(f"Error processing {value}: {e}")
                results.append(None)
        
        return results
    
    def evaluate(self, value: float) -> bool:
        return value > self.threshold
    
    def get_stats(self) -> Dict[str, Any]:
        return {
            'name': self.name,
            'processed': self.processed_count,
            'threshold': self.threshold
        }
```
</details>

## 🐛 Troubleshooting Guide

### Common Issue 1: {{Error Type}}
**Symptom:** {{error_description}}
**Cause:** {{root_cause}}
**Solution:**
```python
# Instead of this (wrong):
{{wrong_code}}

# Do this (correct):
{{correct_code}}
```

### Common Issue 2: {{Performance Issue}}
**Symptom:** Code runs slowly with large datasets
**Solution:** Use batch processing and caching:
```python
# Optimize with batching
results = process_in_batches(data, batch_size=1000)
```

## 📝 Self-Assessment Quiz

Test your understanding:

1. **What is the main purpose of {{concept}}?**
   <details>
   <summary>Show Answer</summary>
   {{answer_1}}
   </details>

2. **When would you use {{concept}} instead of {{alternative}}?**
   <details>
   <summary>Show Answer</summary>
   {{answer_2}}
   </details>

3. **Write code to {{specific_task}}:**
   <details>
   <summary>Show Answer</summary>
   
   ```python
   {{answer_code}}
   ```
   </details>

## 🎉 Congratulations!

You've completed the {{concept}} tutorial! You've learned:
- ✅ The fundamentals of {{concept}}
- ✅ How to implement it from scratch
- ✅ Production-ready patterns
- ✅ Performance optimization techniques
- ✅ Error handling and recovery

### 📈 Your Progress
```
Beginner ████████░░ 80% Complete
         ↑ You are here

Next Level: Intermediate {{related_concept}}
```

## 🚀 Next Steps

Ready to continue learning? Here's what to explore next:

1. **Immediate Next:** [{{Next Tutorial}}](link)
   - Builds directly on what you learned
   - Introduces {{next_concept}}

2. **Practice Projects:**
   - Build a {{project_idea_1}}
   - Create a {{project_idea_2}}
   - Implement {{project_idea_3}}

3. **Advanced Topics:**
   - {{advanced_topic_1}}
   - {{advanced_topic_2}}
   - {{advanced_topic_3}}

4. **Resources:**
   - 📖 [Official Documentation](link)
   - 🎥 [Video Tutorial](link)
   - 💬 [Community Forum](link)
   - 📚 [Recommended Book](link)

## 💡 Final Tips

Remember:
- **Practice regularly** - Even 15 minutes daily makes a difference
- **Build projects** - Apply what you learn immediately
- **Join communities** - Learn from others' experiences
- **Teach others** - The best way to solidify knowledge

Happy coding! 🚀
```

## Interactive Features

### Progress Tracking
```python
class TutorialProgress:
    """Track learner progress through tutorial"""
    
    def __init__(self):
        self.checkpoints = {
            'prerequisites': False,
            'basic_understanding': False,
            'practical_application': False,
            'advanced_techniques': False,
            'exercise_completed': False,
            'quiz_passed': False
        }
        self.start_time = time.time()
        self.attempts = {}
    
    def complete_checkpoint(self, name: str):
        """Mark checkpoint as complete"""
        if name in self.checkpoints:
            self.checkpoints[name] = True
            print(f"✅ Checkpoint completed: {name}")
            self.show_progress()
    
    def show_progress(self):
        """Display progress bar"""
        completed = sum(self.checkpoints.values())
        total = len(self.checkpoints)
        percentage = (completed / total) * 100
        
        bar = '█' * int(percentage / 10) + '░' * (10 - int(percentage / 10))
        print(f"Progress: {bar} {percentage:.0f}%")
        
        if percentage == 100:
            elapsed = int(time.time() - self.start_time)
            print(f"🎉 Tutorial completed in {elapsed // 60} minutes!")
            self.award_achievement()
    
    def award_achievement(self):
        """Award completion badge"""
        print("""
        🏆 Achievement Unlocked!
        ========================
        {{Concept}} Master
        Completed all checkpoints
        """)
```

### Hint System
```python
class HintSystem:
    """Progressive hint system"""
    
    def __init__(self):
        self.hints = {
            'exercise_1': [
                "Think about the input type",
                "Check if validation is needed",
                "The solution uses a list comprehension"
            ],
            'exercise_2': [
                "Start with error handling",
                "Use a try/except block",
                "Don't forget to return a value"
            ]
        }
        self.hints_used = {}
    
    def get_hint(self, exercise: str) -> str:
        """Get next hint for exercise"""
        if exercise not in self.hints:
            return "No hints available for this exercise"
        
        used = self.hints_used.get(exercise, 0)
        available_hints = self.hints[exercise]
        
        if used >= len(available_hints):
            return "You've seen all hints. Try reviewing the examples above!"
        
        hint = available_hints[used]
        self.hints_used[exercise] = used + 1
        
        return f"💡 Hint {used + 1}/{len(available_hints)}: {hint}"
```

## Success Criteria
✅ Clear learning objectives
✅ Progressive difficulty
✅ Interactive exercises
✅ Visual checkpoints
✅ Self-assessment included
✅ Troubleshooting guide
✅ Next steps provided