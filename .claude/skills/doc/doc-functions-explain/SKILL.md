# Command: doc-functions-explain
Generate detailed documentation for functions, methods, and classes

## 🚨 CRITICAL OUTPUT PATH CONFIGURATION

**ALL function documentation can be added inline to source code OR saved to `docs/` directory.**

### Documentation Output Options
- **Inline Documentation**: Update source code files with proper docstrings/comments
- **Reference Documentation**: Generate files in `docs/api/` or `docs/reference/`
- **API Documentation**: Save to `docs/api/functions/`, `docs/api/classes/`
- **Integration with Existing Docs**: Link to `docs/api/README.md` structure

Refer to `templates/shared/documentation-patterns.md` for complete path specifications.

## Usage
```
/doc-functions-explain [file|function-name|directory]
```

## Description
Creates comprehensive documentation for code functions, methods, and classes. Explains purpose, parameters, return values, side effects, and provides usage examples. Supports multiple programming languages and documentation formats.

## Implementation

### Multi-Step Function Documentation

#### Phase 1: Code Analysis
```xml
<instructions>
Analyze the function/method/class to understand its behavior
</instructions>

<analysis_targets>
- Function signature and type hints
- Parameter purposes and constraints
- Return value type and meaning
- Side effects and state changes
- Error handling and exceptions
- Dependencies and imports
- Complexity metrics
- Test coverage
</analysis_targets>

<context_gathering>
- Where is this function called?
- What problem does it solve?
- Related functions in the module
- Design patterns used
- Performance characteristics
</context_gathering>

<output>
Structured function metadata and behavior analysis
</output>
```

#### Phase 2: Documentation Structure
```xml
<instructions>
Generate appropriate documentation format based on language
</instructions>

<documentation_formats>
- Python: Docstrings (Google/NumPy/Sphinx style)
- JavaScript: JSDoc
- TypeScript: TSDoc
- Java: Javadoc
- C#: XML Documentation Comments
- Go: Godoc
- Rust: Rustdoc
- PHP: PHPDoc
</documentation_formats>

<sections_to_include>
1. Brief one-line summary
2. Detailed description
3. Parameters documentation
4. Return value documentation
5. Exceptions/errors
6. Usage examples
7. See also/related functions
8. Notes and warnings
9. Version/deprecation info
</sections_to_include>
```

#### Phase 3: Content Generation
```xml
<instructions>
Generate detailed documentation content
</instructions>

<content_requirements>
- Clear, concise descriptions
- Type information for all parameters
- Real-world usage examples
- Edge cases and gotchas
- Performance considerations
- Thread safety (if applicable)
- Memory management notes
</content_requirements>

<example_generation>
- Basic usage example
- Advanced usage with options
- Error handling example
- Integration example
- Performance optimization example
</example_generation>
```

### Documentation Templates by Language

#### Python Documentation (Google Style)
```python
def {{function_name}}({{parameters}}) -> {{return_type}}:
    """{{brief_one_line_summary}}
    
    {{detailed_description}}
    
    Args:
        {{param_1}} ({{type_1}}): {{description_1}}
            {{additional_details_1}}
        {{param_2}} ({{type_2}}, optional): {{description_2}}
            Defaults to {{default_value}}.
        **{{kwargs}} (dict): {{kwargs_description}}
            - {{kwarg_1}} ({{kwarg_type_1}}): {{kwarg_desc_1}}
            - {{kwarg_2}} ({{kwarg_type_2}}): {{kwarg_desc_2}}
    
    Returns:
        {{return_type}}: {{return_description}}
            {{return_structure_details}}
    
    Raises:
        {{exception_1}}: {{when_raised_1}}
        {{exception_2}}: {{when_raised_2}}
    
    Examples:
        Basic usage:
        >>> result = {{function_name}}({{example_args}})
        >>> print(result)
        {{example_output}}
        
        Advanced usage with options:
        >>> config = {{config_setup}}
        >>> result = {{function_name}}(data, **config)
        >>> assert result == {{expected_result}}
    
    Note:
        {{important_note}}
    
    See Also:
        {{related_function_1}}: {{relation_description_1}}
        {{related_function_2}}: {{relation_description_2}}
    
    .. versionadded:: {{version}}
    .. deprecated:: {{version}}
        Use :func:`{{replacement_function}}` instead.
    """
```

#### JavaScript/JSDoc Documentation
```javascript
/**
 * {{brief_one_line_summary}}
 * 
 * {{detailed_description}}
 * 
 * @param {{{type_1}}} {{param_1}} - {{description_1}}
 * @param {{{type_2}}=} {{param_2}} - {{description_2}}
 * @param {Object} options - Configuration options
 * @param {{{option_type_1}}} options.{{option_1}} - {{option_desc_1}}
 * @param {{{option_type_2}}} [options.{{option_2}}={{default}}] - {{option_desc_2}}
 * 
 * @returns {{{return_type}}} {{return_description}}
 * 
 * @throws {{{error_type_1}}} {{error_condition_1}}
 * @throws {{{error_type_2}}} {{error_condition_2}}
 * 
 * @example
 * // Basic usage
 * const result = {{function_name}}({{example_args}});
 * console.log(result); // {{example_output}}
 * 
 * @example
 * // With options
 * const result = {{function_name}}(data, {
 *   {{option_1}}: {{value_1}},
 *   {{option_2}}: {{value_2}}
 * });
 * 
 * @see {@link {{related_function}}}
 * @since {{version}}
 * @deprecated Use {{replacement}} instead
 */
function {{function_name}}({{parameters}}) {
    // Implementation
}
```

#### TypeScript/TSDoc Documentation
```typescript
/**
 * {{brief_one_line_summary}}
 * 
 * @remarks
 * {{detailed_description}}
 * 
 * @param {{param_1}} - {{description_1}}
 * @param {{param_2}} - {{description_2}}
 * @param options - Configuration options
 * 
 * @returns {{return_description}}
 * 
 * @throws {{{error_type}}}
 * {{error_description}}
 * 
 * @example
 * ```typescript
 * // Basic usage
 * const result = {{function_name}}<{{generic_type}}>({{example_args}});
 * ```
 * 
 * @example
 * ```typescript
 * // Advanced usage with type safety
 * interface {{interface_name}} {
 *   {{property}}: {{type}};
 * }
 * 
 * const result = {{function_name}}<{{interface_name}}>(data, {
 *   {{option}}: true
 * });
 * ```
 * 
 * @public
 * @since {{version}}
 * @beta
 */
export function {{function_name}}<T extends {{constraint}}>(
    {{param_1}}: {{type_1}},
    {{param_2}}?: {{type_2}}
): {{return_type}} {
    // Implementation
}
```

### Class Documentation Template
```xml
<instructions>
Document a class comprehensively
</instructions>

<class_documentation>
/**
 * {{class_summary}}
 * 
 * {{detailed_class_description}}
 * 
 * @class {{ClassName}}
 * @extends {{ParentClass}}
 * @implements {{Interface1}}, {{Interface2}}
 * 
 * @example
 * ```{{language}}
 * // Creating an instance
 * const instance = new {{ClassName}}({{constructor_args}});
 * 
 * // Using methods
 * instance.{{method}}({{args}});
 * ```
 */

/**
 * Creates an instance of {{ClassName}}
 * 
 * @constructor
 * @param {{{type}}} {{param}} - {{description}}
 */

/**
 * {{method_summary}}
 * 
 * @method {{methodName}}
 * @memberof {{ClassName}}
 * @instance
 * 
 * @param {{{type}}} {{param}} - {{description}}
 * @returns {{{return_type}}} {{return_description}}
 */

/**
 * {{property_description}}
 * 
 * @property {{{type}}} {{propertyName}}
 * @readonly
 * @memberof {{ClassName}}
 */
</class_documentation>
```

### Complexity Analysis Documentation
```markdown
## Complexity Analysis

### Time Complexity
- **Best Case:** O({{best_case}})
  - Occurs when {{best_case_condition}}
- **Average Case:** O({{average_case}})
  - Typical scenario: {{average_scenario}}
- **Worst Case:** O({{worst_case}})
  - Occurs when {{worst_case_condition}}

### Space Complexity
- **Memory Usage:** O({{space_complexity}})
  - {{memory_explanation}}
- **Auxiliary Space:** O({{auxiliary_space}})
  - {{auxiliary_explanation}}

### Performance Notes
- {{performance_consideration_1}}
- {{performance_consideration_2}}
- Optimization opportunity: {{optimization_suggestion}}
```

### Edge Cases Documentation
```markdown
## Edge Cases and Gotchas

### Input Validation
- **Empty Input:** {{empty_behavior}}
- **Null/Undefined:** {{null_behavior}}
- **Type Mismatch:** {{type_mismatch_behavior}}
- **Out of Range:** {{range_behavior}}

### Special Cases
1. **{{edge_case_1}}**
   - Condition: {{condition_1}}
   - Behavior: {{behavior_1}}
   - Example: `{{example_1}}`

2. **{{edge_case_2}}**
   - Condition: {{condition_2}}
   - Behavior: {{behavior_2}}
   - Workaround: {{workaround_2}}

### Common Mistakes
❌ **Don't:** {{common_mistake_1}}
✅ **Do:** {{correct_approach_1}}

❌ **Don't:** {{common_mistake_2}}
✅ **Do:** {{correct_approach_2}}
```

### Testing Documentation
```markdown
## Testing Guide

### Unit Tests
```{{test_language}}
describe('{{function_name}}', () => {
    it('should {{test_description_1}}', () => {
        // Arrange
        const input = {{test_input_1}};
        const expected = {{expected_output_1}};
        
        // Act
        const result = {{function_name}}(input);
        
        // Assert
        expect(result).toEqual(expected);
    });
    
    it('should handle {{edge_case}}', () => {
        // Test edge case handling
    });
    
    it('should throw error when {{error_condition}}', () => {
        // Test error handling
    });
});
```

### Integration Tests
- Test with {{integration_point_1}}
- Verify {{integration_behavior}}
- Check {{side_effect_verification}}

### Performance Tests
```{{language}}
// Benchmark test
{{benchmark_code}}
// Expected: < {{performance_threshold}}ms
```
```

## Advanced Features

### Auto-Detection Capabilities
- Language detection from file extension
- Documentation style detection from project
- Framework-specific patterns recognition
- Test framework integration

### Batch Documentation
```bash
# Document all functions in a file (inline + docs/api/)
/doc-functions-explain ./src/utils.js

# Document all public methods in a class
/doc-functions-explain MyClass --public-only

# Document entire module with docs/ integration
/doc-functions-explain ./src/modules/auth/ --output docs/api/auth/

# Update API documentation index
/doc-functions-explain ./src/ --update-index docs/api/README.md
```

### Documentation Formats
- **Inline**: Update documentation in source files (recommended)
- **Markdown**: Generate separate .md files in `docs/api/`
- **HTML**: Generate browsable HTML documentation in `docs/api/`
- **JSON**: Machine-readable documentation for `docs/api/schemas/`

### Output Path Examples
```bash
# Inline documentation (default)
/doc-functions-explain ./src/UserService.js
# Result: Updates UserService.js with JSDoc comments

# Generate API reference files
/doc-functions-explain ./src/UserService.js --output docs/api/classes/UserService.md
# Result: Creates docs/api/classes/UserService.md

# Update API index automatically
/doc-functions-explain ./src/ --batch --update-api-index
# Result: Updates docs/api/README.md with new function links
```

## Quality Metrics
✅ Every parameter documented with type
✅ Return value clearly described
✅ At least one usage example
✅ Error conditions documented
✅ Complexity analysis for algorithms
✅ Related functions cross-referenced
✅ Integration with docs/api/ structure maintained
✅ Links to main documentation in docs/README.md updated

## Integration with Documentation Structure

This command automatically integrates with the centralized documentation structure:

- Updates `docs/api/README.md` with new function references
- Creates appropriate subdirectories in `docs/api/`
- Maintains cross-references to related documentation
- Links back to main documentation hub at `docs/README.md`