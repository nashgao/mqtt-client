# Specialist Agent Adapter

Integration adapter that wraps existing specialist agents (php-transformer, perf-sql-optimizer, security-scanner, etc.) to expose standardized service interfaces for generic agents while maintaining full backward compatibility.

## 🎯 ADAPTER MISSION: STANDARDIZED SPECIALIST SERVICES

This adapter enables existing specialist agents to:
1. **Expose capabilities** in standardized format for discovery
2. **Provide service interfaces** for generic agents to call
3. **Handle context passing** seamlessly between agent types  
4. **Maintain specialist autonomy** while enabling collaboration
5. **Preserve existing behavior** - no breaking changes

## 🔧 CAPABILITY EXPOSITION SYSTEM

### Standardized Capability Declaration

Each specialist agent automatically exposes its capabilities in a standard format:

```yaml
specialist_capabilities:
  agent_identity:
    name: "code-php-transformer"
    type: "specialist"  
    domain: "php_development"
    expertise_level: "expert"
    
  core_specializations:
    primary:
      - php_code_transformation
      - space_utils_integration
      - php_modernization
      - type_safety_enforcement
      
    secondary:
      - code_quality_analysis
      - performance_optimization
      - standards_compliance
      - pattern_modernization
      
  service_interfaces:
    transformation_service:
      input_format: "php_files_or_code_snippets"
      output_format: "transformed_code_with_metadata"
      complexity_handling: ["simple", "medium", "complex"]
      estimated_speedup: "3x-5x vs generic"
      
    analysis_service:
      input_format: "php_codebase"
      output_format: "analysis_report_with_recommendations"
      coverage: "comprehensive_space_utils_compliance"
      
  collaboration_protocols:
    accepts_delegation: true
    provides_feedback: true
    coordinates_with: ["generic-coder", "analyst", "quality-enforcer"]
    reporting_format: "standardized_specialist_output"
```

### Dynamic Service Registration

```yaml
service_registry:
  auto_registration:
    on_agent_load:
      - detect_specialist_type
      - extract_capability_metadata
      - register_service_interfaces
      - establish_communication_channels
      
  capability_discovery:
    by_domain:
      php: ["code-php-transformer"]
      sql: ["perf-sql-optimizer"]
      security: ["security-scanner"]
      testing: ["testing-orchestrator"]
      
    by_complexity:
      expert_level: ["code-php-transformer", "perf-sql-optimizer"]
      advanced_level: ["security-scanner", "quality-enforcer"] 
      standard_level: ["pattern-classifier", "file-processor"]
      
  service_matching:
    request_routing:
      - analyze_incoming_request
      - match_to_optimal_specialist
      - check_availability_and_load
      - establish_service_connection
```

## 🚀 SERVICE INTERFACE FRAMEWORK

### Generic-to-Specialist Communication Protocol

```markdown
## Standard Delegation Interface

When a generic agent needs specialist services:

### 1. Service Request Format
```json
{
  "service_request": {
    "requesting_agent": "generic-coder",
    "specialist_needed": "code-php-transformer", 
    "task_context": {
      "original_user_request": "Optimize this PHP code",
      "task_complexity": "medium",
      "expected_output": "modernized_php_with_space_utils"
    },
    "delegation_reason": "php_expertise_required",
    "coordination_data": {
      "session_id": "{{SESSION_ID}}",
      "coordination_files": "/tmp/coordination-{{TIMESTAMP}}/",
      "result_format": "json_with_code_snippets"
    }
  }
}
```

### 2. Specialist Service Response
```json
{
  "service_response": {
    "specialist_agent": "code-php-transformer",
    "request_id": "{{REQUEST_ID}}",
    "service_status": "accepted|rejected|queued", 
    "estimated_completion": "2-5 minutes",
    "capabilities_matched": ["php_transformation", "space_utils_integration"],
    "coordination_established": true
  }
}
```
```

### Context Passing Infrastructure

```yaml
context_passing:
  incoming_context:
    required_fields:
      - original_user_intent
      - task_breakdown
      - expected_integration_point
      - quality_requirements
      - session_coordination_data
      
    optional_fields:  
      - performance_constraints
      - compatibility_requirements
      - user_preferences
      - related_work_context
      
  context_processing:
    - validate_required_context
    - enrich_with_specialist_knowledge
    - adapt_to_specialist_workflow
    - prepare_result_formatting
    
  outgoing_context:
    specialist_results:
      - primary_deliverable
      - execution_metadata
      - quality_metrics
      - integration_recommendations
      
    coordination_data:
      - session_state_updates
      - follow_up_suggestions  
      - cross_specialist_dependencies
      - performance_analytics
```

## 🔄 BACKWARD COMPATIBILITY GUARANTEE

### Existing Behavior Preservation

```yaml
compatibility_matrix:
  direct_invocation:
    behavior: "unchanged"
    performance: "identical" 
    interface: "preserved"
    note: "Specialists work exactly as before when called directly"
    
  orchestrated_invocation:
    behavior: "enhanced"
    performance: "improved_through_coordination"
    interface: "extended_but_compatible"
    note: "Additional coordination capabilities, but existing interface intact"
    
  hybrid_usage:
    behavior: "adaptive"
    performance: "optimal_based_on_context"
    interface: "backward_compatible"
    note: "Can be used both ways seamlessly"
```

### Transparent Enhancement

```markdown
## Enhancement Overlay

**Specialist Agent Without Adapter:**
```
Direct call → Agent processes → Direct response
(Current behavior - unchanged)
```

**Specialist Agent With Adapter:**
```
Direct call → Agent processes → Direct response (unchanged)
     ↓
Service call → Context processing → Enhanced response → Integration data
(New capability - additive only)
```

**Key Principle**: The adapter adds service capabilities WITHOUT changing existing behavior.
```

## 🎛️ SERVICE INTERFACE DEFINITIONS

### PHP Transformation Service

```yaml
service_definition:
  service_name: "php_transformation_service"
  specialist_agent: "code-php-transformer"
  
  interface:
    transform_code:
      input:
        - php_files: "array of file paths or code snippets"
        - transformation_options: "safe|risky|experimental"
        - space_utils_compliance: "boolean"
      output:
        - transformed_code: "modified PHP code"
        - transformation_report: "changes made and rationale"
        - compliance_metrics: "before/after quality scores"
        
    analyze_codebase:
      input:
        - codebase_path: "directory containing PHP files"
        - analysis_depth: "surface|comprehensive|deep"
      output:
        - analysis_report: "detailed findings and recommendations"
        - transformation_opportunities: "prioritized improvement list"
        - compliance_assessment: "space-utils standards evaluation"
```

### SQL Optimization Service  

```yaml
service_definition:
  service_name: "sql_optimization_service"
  specialist_agent: "perf-sql-optimizer"
  
  interface:
    optimize_queries:
      input:
        - sql_queries: "array of SQL statements or files"
        - database_schema: "optional schema information"
        - performance_targets: "latency and throughput goals"
      output:
        - optimized_queries: "improved SQL statements"
        - performance_analysis: "expected improvements"
        - optimization_recommendations: "additional suggestions"
        
    analyze_performance:
      input:
        - database_connection: "optional live DB connection"
        - query_logs: "execution history and metrics"
      output:
        - performance_report: "bottlenecks and improvement areas"
        - optimization_priorities: "ranked improvement opportunities"
```

## 📊 INTER-SPECIALIST COORDINATION

### Specialist-to-Specialist Communication

```yaml
peer_coordination:
  cross_specialist_workflows:
    php_and_security:
      scenario: "Secure PHP code transformation"
      coordination: 
        - php_transformer_handles_modernization
        - security_scanner_validates_security_patterns
        - results_merged_into_comprehensive_output
        
    sql_and_performance:
      scenario: "Database performance optimization"  
      coordination:
        - sql_optimizer_improves_queries
        - performance_analyzer_validates_improvements
        - integrated_performance_report_generated
        
  coordination_protocols:
    result_sharing:
      - shared_session_state
      - cross_specialist_result_files
      - standardized_metadata_format
      - dependency_resolution_system
      
    conflict_resolution:
      - priority_based_decision_making
      - user_preference_consideration
      - automatic_compromise_suggestion
      - escalation_to_orchestrator
```

### Multi-Specialist Orchestration

```markdown
## Complex Workflow Example

User request: "Modernize this PHP application for security and performance"

**Orchestrator coordinates:**
1. **php-transformer**: Modernize PHP code to Space-Utils standards
2. **security-scanner**: Validate security patterns in modernized code  
3. **perf-sql-optimizer**: Optimize database queries found in code
4. **quality-enforcer**: Ensure overall code quality standards

**Coordination flow:**
```
php-transformer → modernized_code → security-scanner
                                 ↓
quality-enforcer ← integrated_result ← perf-sql-optimizer
```

**Each specialist:**
- Receives proper context from orchestrator
- Processes their domain expertise
- Provides results in standardized format
- Includes integration metadata for other specialists
```

## 🔍 PERFORMANCE OPTIMIZATION

### Service Call Optimization

```yaml
performance_optimization:
  call_routing:
    direct_specialist_calls:
      overhead: "minimal - same as current"
      use_case: "single specialist needed"
      
    coordinated_specialist_calls:  
      overhead: "5-10% for coordination benefits"
      use_case: "multiple specialists or complex workflows"
      
    cached_specialist_results:
      overhead: "near zero for repeated patterns"
      use_case: "similar tasks or incremental work"
      
  resource_management:
    specialist_pooling:
      - maintain_warm_specialist_instances
      - reuse_specialists_for_similar_tasks  
      - load_balance_across_available_specialists
      - graceful_degradation_under_load
      
    context_optimization:
      - compress_context_data_for_transfer
      - cache_frequently_used_context_patterns
      - minimize_context_serialization_overhead
      - optimize_result_aggregation_processing
```

## ✅ QUALITY GATES

### Adapter Validation

Before deploying this adapter:
- [ ] All existing specialist functionality preserved exactly
- [ ] Service interfaces expose capabilities accurately  
- [ ] Context passing maintains all necessary information
- [ ] Performance overhead < 10% for coordinated calls
- [ ] Backward compatibility verified with existing workflows
- [ ] Integration with generic agents tested successfully

### Service Interface Testing

- [ ] Each specialist service interface tested independently
- [ ] Cross-specialist coordination protocols validated
- [ ] Context passing accuracy verified
- [ ] Performance benchmarks meet targets
- [ ] Error handling and recovery mechanisms tested
- [ ] Load testing under concurrent specialist usage

## 🚨 CONSTRAINTS  

### NEVER:
- Change existing specialist agent external behavior
- Break direct invocation patterns
- Add dependencies that affect standalone specialist usage
- Modify specialist internal logic or decision-making
- Create tight coupling between specialists

### ALWAYS: 
- Preserve exact existing specialist interfaces
- Make service capabilities additive only
- Ensure specialists can still work independently
- Maintain specialist autonomy and expertise focus
- Provide clear performance benefits through coordination

This adapter enables existing specialist agents to participate in collaborative workflows while maintaining their independence and specialized expertise. Generic agents can leverage specialist capabilities through standardized service interfaces, creating a powerful hybrid system that combines the best of both generalist and specialist approaches.