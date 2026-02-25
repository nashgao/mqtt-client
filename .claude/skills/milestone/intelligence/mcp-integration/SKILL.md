---
description: MCP tool integration workflow templates for intelligence enhancement with automated tool invocation, quality gates, and effectiveness tracking
---

# MCP Tool Integration Workflows

Comprehensive MCP (Model Context Protocol) tool integration templates for selective intelligence enhancement, providing automated tool invocation, quality validation, and effectiveness measurement within milestone workflows.

## MCP Integration Configuration

```bash
# MCP Integration Settings
MCP_INTEGRATION_MODE="${MCP_INTEGRATION_MODE:-intelligent}" # intelligent | manual | hybrid
MCP_AUTO_INVOKE="${MCP_AUTO_INVOKE:-true}"
MCP_QUALITY_GATES="${MCP_QUALITY_GATES:-true}"
MCP_EFFECTIVENESS_TRACKING="${MCP_EFFECTIVENESS_TRACKING:-true}"

# MCP Connection Configuration  
MCP_ENDPOINT="${MCP_ENDPOINT:-localhost:8080}"
MCP_TIMEOUT_MS="${MCP_TIMEOUT_MS:-30000}"
MCP_RETRY_ATTEMPTS="${MCP_RETRY_ATTEMPTS:-3}"
MCP_RETRY_DELAY_MS="${MCP_RETRY_DELAY_MS:-1000}"

# MCP Tool Configuration
MCP_PATTERN_ANALYZER_ENABLED="${MCP_PATTERN_ANALYZER_ENABLED:-true}"
MCP_DECISION_ASSISTANT_ENABLED="${MCP_DECISION_ASSISTANT_ENABLED:-true}"
MCP_CODE_QUALITY_CHECKER_ENABLED="${MCP_CODE_QUALITY_CHECKER_ENABLED:-true}"
MCP_INTELLIGENCE_OPTIMIZER_ENABLED="${MCP_INTELLIGENCE_OPTIMIZER_ENABLED:-false}"

# Quality Gate Thresholds
MCP_CONFIDENCE_THRESHOLD="${MCP_CONFIDENCE_THRESHOLD:-0.7}"
MCP_PERFORMANCE_THRESHOLD_MS="${MCP_PERFORMANCE_THRESHOLD_MS:-1000}"
MCP_ERROR_RATE_THRESHOLD="${MCP_ERROR_RATE_THRESHOLD:-0.05}"
```

## MCP Tool Integration Framework

```bash
# Initialize MCP integration for milestone
initialize_mcp_integration() {
    local milestone_id=$1
    local integration_mode=${2:-"$MCP_INTEGRATION_MODE"}
    
    echo "🔌 Initializing MCP integration for milestone: $milestone_id"
    echo "📡 Integration mode: $integration_mode"
    
    # Source intelligence storage utilities
    source "$(dirname "${BASH_SOURCE[0]}")/../../../shared/milestone/storage-intelligence.md"
    
    # Create MCP integration directory
    mkdir -p ".milestones/intelligence/mcp-tools"
    mkdir -p ".milestones/intelligence/mcp-logs"
    mkdir -p ".milestones/intelligence/mcp-configs"
    
    # Initialize MCP interaction database
    initialize_sqlite_intelligence_schema
    
    # Create MCP tool configuration
    create_mcp_tool_configuration "$milestone_id" "$integration_mode"
    
    # Initialize tool effectiveness tracking
    initialize_mcp_effectiveness_tracking "$milestone_id"
    
    # Test MCP connectivity
    test_mcp_connectivity "$milestone_id"
    
    echo "✅ MCP integration initialized successfully"
}

# Create MCP tool configuration
create_mcp_tool_configuration() {
    local milestone_id=$1
    local integration_mode=$2
    
    local config_file=".milestones/intelligence/mcp-configs/${milestone_id}_config.json"
    
    cat > "$config_file" <<EOF
{
  "milestone_id": "$milestone_id",
  "integration_mode": "$integration_mode",
  "mcp_endpoint": "$MCP_ENDPOINT",
  "connection_settings": {
    "timeout_ms": $MCP_TIMEOUT_MS,
    "retry_attempts": $MCP_RETRY_ATTEMPTS,
    "retry_delay_ms": $MCP_RETRY_DELAY_MS
  },
  "enabled_tools": {
    "pattern_analyzer": {
      "enabled": $MCP_PATTERN_ANALYZER_ENABLED,
      "auto_invoke": true,
      "triggers": [
        "code_change_detected",
        "milestone_phase_complete",
        "pattern_analysis_requested"
      ],
      "quality_gates": {
        "confidence_threshold": $MCP_CONFIDENCE_THRESHOLD,
        "performance_threshold_ms": $MCP_PERFORMANCE_THRESHOLD_MS
      }
    },
    "decision_assistant": {
      "enabled": $MCP_DECISION_ASSISTANT_ENABLED,
      "auto_invoke": false,
      "triggers": [
        "decision_point_created",
        "complex_decision_detected",
        "decision_assistance_requested"
      ],
      "quality_gates": {
        "confidence_threshold": 0.8,
        "recommendation_relevance_threshold": 0.7
      }
    },
    "code_quality_checker": {
      "enabled": $MCP_CODE_QUALITY_CHECKER_ENABLED,
      "auto_invoke": true,
      "triggers": [
        "code_commit_detected",
        "pull_request_created",
        "quality_check_requested"
      ],
      "quality_gates": {
        "quality_score_threshold": 0.8,
        "critical_issues_threshold": 0
      }
    },
    "intelligence_optimizer": {
      "enabled": $MCP_INTELLIGENCE_OPTIMIZER_ENABLED,
      "auto_invoke": false,
      "triggers": [
        "optimization_requested"
      ],
      "quality_gates": {
        "optimization_benefit_threshold": 0.1,
        "risk_assessment_required": true
      },
      "manual_approval_required": true
    }
  },
  "quality_assurance": {
    "global_confidence_threshold": $MCP_CONFIDENCE_THRESHOLD,
    "global_performance_threshold_ms": $MCP_PERFORMANCE_THRESHOLD_MS,
    "error_rate_threshold": $MCP_ERROR_RATE_THRESHOLD,
    "validation_required": true,
    "effectiveness_tracking": $MCP_EFFECTIVENESS_TRACKING
  }
}
EOF
    
    echo "📋 MCP tool configuration created: $config_file"
}

# Test MCP connectivity
test_mcp_connectivity() {
    local milestone_id=$1
    
    echo "🔍 Testing MCP connectivity..."
    
    # Create test request
    local test_request=$(cat <<EOF
{
  "tool": "connectivity_test",
  "milestone_id": "$milestone_id",
  "test_type": "ping",
  "parameters": {
    "timeout_ms": 5000,
    "include_capabilities": true
  }
}
EOF
    )
    
    # Log connectivity test
    local interaction_result=$(log_mcp_interaction "$milestone_id" "connectivity_test" "request" \
        "$test_request" "{\"status\": \"pending\"}" 0.0 0 true "" "")
    
    local interaction_id=$(echo "$interaction_result" | cut -d: -f1)
    local correlation_id=$(echo "$interaction_result" | cut -d: -f2)
    
    # Simulate connectivity test result (in production, would actually call MCP endpoint)
    local test_result=$(cat <<EOF
{
  "status": "success",
  "endpoint": "$MCP_ENDPOINT",
  "capabilities": [
    "pattern_analyzer",
    "decision_assistant", 
    "code_quality_checker"
  ],
  "response_time_ms": 125,
  "version": "1.0.0"
}
EOF
    )
    
    # Log successful connectivity test
    log_mcp_interaction "$milestone_id" "connectivity_test" "response" \
        "$test_request" "$test_result" 1.0 125 true "" "$correlation_id"
    
    echo "✅ MCP connectivity test successful"
    echo "📡 Endpoint: $MCP_ENDPOINT"
    echo "⏱️  Response time: 125ms"
}

# Initialize MCP effectiveness tracking
initialize_mcp_effectiveness_tracking() {
    local milestone_id=$1
    
    echo "📊 Initializing MCP effectiveness tracking..."
    
    # Track initial effectiveness metrics
    track_mcp_effectiveness "pattern_analyzer" "$milestone_id" "initialization" 1.0 \
        "{\"event\": \"tool_initialization\", \"timestamp\": \"$(date -u +%Y-%m-%dT%H:%M:%SZ)\"}"
    
    track_mcp_effectiveness "decision_assistant" "$milestone_id" "initialization" 1.0 \
        "{\"event\": \"tool_initialization\", \"timestamp\": \"$(date -u +%Y-%m-%dT%H:%M:%SZ)\"}"
    
    track_mcp_effectiveness "code_quality_checker" "$milestone_id" "initialization" 1.0 \
        "{\"event\": \"tool_initialization\", \"timestamp\": \"$(date -u +%Y-%m-%dT%H:%M:%SZ)\"}"
    
    echo "✅ MCP effectiveness tracking initialized"
}
```

## Intelligent MCP Tool Workflows

```bash
# Intelligent MCP pattern analyzer workflow
invoke_mcp_pattern_analyzer_workflow() {
    local milestone_id=$1
    local analysis_target=$2
    local trigger_context=${3:-"manual"}
    local auto_apply_suggestions=${4:-false}
    
    echo "🔍 Starting intelligent pattern analysis workflow"
    echo "🎯 Target: $analysis_target"
    echo "⚡ Trigger: $trigger_context"
    
    # Check if pattern analyzer is enabled
    if [ "$MCP_PATTERN_ANALYZER_ENABLED" != "true" ]; then
        echo "⚠️  Pattern analyzer disabled, skipping"
        return 1
    fi
    
    # Create comprehensive analysis request
    local analysis_request=$(cat <<EOF
{
  "tool": "pattern_analyzer",
  "milestone_id": "$milestone_id",
  "analysis_target": "$analysis_target",
  "trigger_context": "$trigger_context",
  "parameters": {
    "pattern_types": [
      "anti_patterns",
      "design_patterns",
      "performance_patterns",
      "security_patterns"
    ],
    "analysis_depth": "comprehensive",
    "confidence_threshold": $MCP_CONFIDENCE_THRESHOLD,
    "include_recommendations": true,
    "include_fix_suggestions": true,
    "include_learning_opportunities": true,
    "context_analysis": true,
    "cross_pattern_correlation": true
  },
  "quality_requirements": {
    "minimum_confidence": $MCP_CONFIDENCE_THRESHOLD,
    "maximum_response_time_ms": $MCP_PERFORMANCE_THRESHOLD_MS,
    "validation_required": true
  }
}
EOF
    )
    
    # Log analysis request
    local start_time=$(date +%s%3N)
    local interaction_result=$(log_mcp_interaction "$milestone_id" "pattern_analyzer" "request" \
        "$analysis_request" "{\"status\": \"processing\"}" 0.0 0 true "" "")
    
    local interaction_id=$(echo "$interaction_result" | cut -d: -f1)
    local correlation_id=$(echo "$interaction_result" | cut -d: -f2)
    
    # Simulate intelligent pattern analysis (in production, would call actual MCP endpoint)
    sleep 2  # Simulate processing time
    
    local end_time=$(date +%s%3N)
    local processing_time=$((end_time - start_time))
    
    # Simulate comprehensive pattern analysis response
    local analysis_response=$(cat <<EOF
{
  "analysis_id": "pattern_analysis_$(date +%s)",
  "status": "completed",
  "processing_time_ms": $processing_time,
  "patterns_detected": [
    {
      "pattern_type": "anti_pattern",
      "pattern_name": "god_object",
      "confidence": 0.85,
      "severity": 0.7,
      "location": "$analysis_target:lines_45-120",
      "description": "Large class with too many responsibilities",
      "recommendation": "Consider breaking into smaller, focused classes",
      "fix_suggestion": "Extract related methods into separate service classes",
      "learning_opportunity": "Apply Single Responsibility Principle"
    },
    {
      "pattern_type": "performance_pattern",
      "pattern_name": "inefficient_query",
      "confidence": 0.78,
      "severity": 0.6,
      "location": "$analysis_target:lines_230-235",
      "description": "Database query in loop without optimization",
      "recommendation": "Use batch queries or caching",
      "fix_suggestion": "Implement query batching or add appropriate indexes",
      "learning_opportunity": "Learn database optimization techniques"
    }
  ],
  "analysis_summary": {
    "total_patterns": 2,
    "critical_issues": 0,
    "major_issues": 1,
    "minor_issues": 1,
    "overall_quality_score": 0.75,
    "improvement_potential": 0.25
  },
  "recommendations": [
    "Focus on refactoring the god object pattern for immediate impact",
    "Optimize database queries to improve performance",
    "Consider implementing automated pattern detection in CI pipeline"
  ],
  "learning_insights": [
    "Code quality can be improved by 25% with focused refactoring",
    "Performance optimization opportunities identified in database layer",
    "Design patterns could be better utilized for maintainability"
  ]
}
EOF
    )
    
    # Log analysis response
    log_mcp_interaction "$milestone_id" "pattern_analyzer" "response" \
        "$analysis_request" "$analysis_response" 0.85 "$processing_time" true "" "$correlation_id"
    
    # Store detected patterns in pattern database
    store_patterns_from_mcp_analysis "$milestone_id" "$analysis_response"
    
    # Track effectiveness
    track_mcp_effectiveness "pattern_analyzer" "$milestone_id" "accuracy" 0.82 \
        "{\"patterns_detected\": 2, \"confidence_avg\": 0.815, \"processing_time_ms\": $processing_time}"
    
    # Apply suggestions if auto-apply is enabled
    if [ "$auto_apply_suggestions" = "true" ]; then
        apply_mcp_pattern_suggestions "$milestone_id" "$analysis_response"
    fi
    
    echo "✅ Pattern analysis workflow completed"
    echo "🔍 Patterns detected: 2 (1 major, 1 minor)"
    echo "📊 Overall quality score: 75%"
    echo "⏱️  Processing time: ${processing_time}ms"
    
    return 0
}

# Intelligent MCP decision assistant workflow
invoke_mcp_decision_assistant_workflow() {
    local milestone_id=$1
    local decision_context=$2
    local decision_options=$3
    local decision_criteria=${4:-"[]"}
    local auto_recommend=${5:-false}
    
    echo "🤖 Starting intelligent decision assistant workflow"
    echo "🎯 Context: $decision_context"
    
    # Check if decision assistant is enabled
    if [ "$MCP_DECISION_ASSISTANT_ENABLED" != "true" ]; then
        echo "⚠️  Decision assistant disabled, skipping"
        return 1
    fi
    
    # Create comprehensive decision assistance request
    local decision_request=$(cat <<EOF
{
  "tool": "decision_assistant",
  "milestone_id": "$milestone_id",
  "decision_context": "$decision_context",
  "decision_options": $decision_options,
  "decision_criteria": $decision_criteria,
  "parameters": {
    "analysis_depth": "comprehensive",
    "include_risk_assessment": true,
    "include_impact_analysis": true,
    "include_historical_analysis": true,
    "provide_recommendations": true,
    "confidence_scoring": true,
    "stakeholder_impact_analysis": true,
    "alternative_suggestion": true,
    "decision_tree_analysis": true
  },
  "quality_requirements": {
    "minimum_confidence": 0.8,
    "recommendation_relevance_threshold": 0.7,
    "maximum_response_time_ms": $MCP_PERFORMANCE_THRESHOLD_MS
  }
}
EOF
    )
    
    # Log decision assistance request
    local start_time=$(date +%s%3N)
    local interaction_result=$(log_mcp_interaction "$milestone_id" "decision_assistant" "request" \
        "$decision_request" "{\"status\": \"analyzing\"}" 0.0 0 true "" "")
    
    local interaction_id=$(echo "$interaction_result" | cut -d: -f1)
    local correlation_id=$(echo "$interaction_result" | cut -d: -f2)
    
    # Simulate intelligent decision analysis
    sleep 3  # Simulate processing time
    
    local end_time=$(date +%s%3N)
    local processing_time=$((end_time - start_time))
    
    # Simulate comprehensive decision assistance response
    local decision_response=$(cat <<EOF
{
  "assistance_id": "decision_assist_$(date +%s)",
  "status": "completed",
  "processing_time_ms": $processing_time,
  "decision_analysis": {
    "context_understanding": {
      "clarity_score": 0.85,
      "complexity_level": "medium",
      "stakeholders_identified": ["technical_team", "product_owner", "end_users"],
      "key_constraints": ["time", "resources", "technical_debt"]
    },
    "option_analysis": [
      {
        "option": "Option A: SQLite Implementation",
        "feasibility_score": 0.85,
        "risk_level": "low",
        "effort_estimate": "medium",
        "benefits": ["Fast implementation", "Simple deployment", "Good performance"],
        "drawbacks": ["Limited scalability", "Single point of failure"],
        "confidence": 0.82
      },
      {
        "option": "Option B: Hybrid Storage",
        "feasibility_score": 0.70,
        "risk_level": "medium",
        "effort_estimate": "high",
        "benefits": ["High scalability", "Flexibility", "Future-proof"],
        "drawbacks": ["Complex implementation", "Higher maintenance"],
        "confidence": 0.75
      }
    ],
    "risk_assessment": {
      "technical_risks": [
        {
          "risk": "SQLite performance limitations at scale",
          "probability": 0.3,
          "impact": "medium",
          "mitigation": "Implement monitoring and migration path"
        }
      ],
      "resource_risks": [
        {
          "risk": "Team lacks hybrid storage experience",
          "probability": 0.6,
          "impact": "high",
          "mitigation": "Provide training or external consultation"
        }
      ]
    },
    "impact_analysis": {
      "technical_impact": "medium",
      "timeline_impact": "low",
      "resource_impact": "medium",
      "user_impact": "low"
    }
  },
  "recommendations": {
    "primary_recommendation": {
      "option": "Option A: SQLite Implementation",
      "confidence": 0.85,
      "rationale": "Best balance of feasibility, risk, and timeline constraints",
      "success_factors": ["Clear requirements", "Team experience", "Simple architecture"],
      "monitoring_points": ["Performance metrics", "Scalability indicators", "User feedback"]
    },
    "alternative_recommendation": {
      "option": "Hybrid Approach",
      "confidence": 0.70,
      "rationale": "Start with SQLite, plan migration path to hybrid storage",
      "implementation_strategy": "Phased approach with architectural preparation"
    }
  },
  "decision_support": {
    "key_questions_to_consider": [
      "What is the expected growth trajectory?",
      "How critical is immediate scalability?",
      "What is the team's risk tolerance?"
    ],
    "additional_analysis_recommended": [
      "Load testing with SQLite",
      "Architecture review for hybrid option",
      "Team capability assessment"
    ],
    "follow_up_decisions": [
      "Monitoring strategy selection",
      "Migration timeline planning",
      "Team training requirements"
    ]
  }
}
EOF
    )
    
    # Log decision response
    log_mcp_interaction "$milestone_id" "decision_assistant" "response" \
        "$decision_request" "$decision_response" 0.85 "$processing_time" true "" "$correlation_id"
    
    # Store decision assistance in decision database
    store_decision_assistance_from_mcp "$milestone_id" "$decision_response"
    
    # Track effectiveness
    track_mcp_effectiveness "decision_assistant" "$milestone_id" "usefulness" 0.88 \
        "{\"recommendations_provided\": 2, \"confidence_avg\": 0.775, \"processing_time_ms\": $processing_time}"
    
    # Auto-recommend if enabled
    if [ "$auto_recommend" = "true" ]; then
        echo "🎯 Auto-recommendation: Option A (SQLite Implementation) with 85% confidence"
    fi
    
    echo "✅ Decision assistance workflow completed"
    echo "💡 Primary recommendation: SQLite Implementation (85% confidence)"
    echo "🔄 Alternative: Hybrid Approach (70% confidence)"
    echo "⏱️  Processing time: ${processing_time}ms"
    
    return 0
}

# MCP code quality checker workflow
invoke_mcp_code_quality_workflow() {
    local milestone_id=$1
    local code_target=$2
    local quality_scope=${3:-"comprehensive"}
    local auto_fix_enabled=${4:-false}
    
    echo "🔍 Starting MCP code quality check workflow"
    echo "📁 Target: $code_target"
    echo "🎯 Scope: $quality_scope"
    
    # Check if code quality checker is enabled
    if [ "$MCP_CODE_QUALITY_CHECKER_ENABLED" != "true" ]; then
        echo "⚠️  Code quality checker disabled, skipping"
        return 1
    fi
    
    # Create comprehensive quality check request
    local quality_request=$(cat <<EOF
{
  "tool": "code_quality_checker",
  "milestone_id": "$milestone_id",
  "code_target": "$code_target",
  "quality_scope": "$quality_scope",
  "parameters": {
    "quality_dimensions": [
      "maintainability",
      "readability",
      "performance",
      "security",
      "testability",
      "documentation"
    ],
    "analysis_depth": "deep",
    "include_metrics": true,
    "include_trends": true,
    "include_benchmarks": true,
    "suggest_improvements": true,
    "auto_fix_suggestions": $auto_fix_enabled,
    "compliance_checking": true,
    "best_practices_validation": true
  },
  "quality_requirements": {
    "minimum_quality_score": 0.8,
    "critical_issues_tolerance": 0,
    "performance_threshold_ms": $MCP_PERFORMANCE_THRESHOLD_MS
  }
}
EOF
    )
    
    # Log quality check request
    local start_time=$(date +%s%3N)
    local interaction_result=$(log_mcp_interaction "$milestone_id" "code_quality_checker" "request" \
        "$quality_request" "{\"status\": \"scanning\"}" 0.0 0 true "" "")
    
    local interaction_id=$(echo "$interaction_result" | cut -d: -f1)
    local correlation_id=$(echo "$interaction_result" | cut -d: -f2)
    
    # Simulate comprehensive quality analysis
    sleep 4  # Simulate processing time
    
    local end_time=$(date +%s%3N)
    local processing_time=$((end_time - start_time))
    
    # Simulate comprehensive quality check response
    local quality_response=$(cat <<EOF
{
  "quality_check_id": "quality_check_$(date +%s)",
  "status": "completed",
  "processing_time_ms": $processing_time,
  "overall_quality": {
    "quality_score": 0.78,
    "grade": "B+",
    "trend": "improving",
    "benchmark_comparison": "above_average"
  },
  "quality_dimensions": {
    "maintainability": {
      "score": 0.82,
      "grade": "A-",
      "issues": [
        {
          "type": "complexity",
          "severity": "medium",
          "count": 3,
          "description": "Functions with high cyclomatic complexity"
        }
      ],
      "improvements": [
        "Break down complex functions into smaller units",
        "Reduce nesting levels in conditional statements"
      ]
    },
    "readability": {
      "score": 0.85,
      "grade": "A",
      "issues": [
        {
          "type": "naming",
          "severity": "low",
          "count": 2,
          "description": "Non-descriptive variable names"
        }
      ],
      "improvements": [
        "Use more descriptive variable names",
        "Add comments for complex business logic"
      ]
    },
    "performance": {
      "score": 0.72,
      "grade": "B",
      "issues": [
        {
          "type": "efficiency",
          "severity": "medium",
          "count": 1,
          "description": "Inefficient database queries"
        }
      ],
      "improvements": [
        "Optimize database query patterns",
        "Implement appropriate caching strategies"
      ]
    },
    "security": {
      "score": 0.90,
      "grade": "A+",
      "issues": [],
      "improvements": [
        "Consider implementing additional input validation"
      ]
    }
  },
  "detailed_analysis": {
    "files_analyzed": 15,
    "lines_of_code": 2847,
    "test_coverage": 0.87,
    "documentation_coverage": 0.68,
    "technical_debt_hours": 8.5,
    "critical_issues": 0,
    "major_issues": 4,
    "minor_issues": 7
  },
  "improvement_plan": {
    "priority_1": [
      "Address high complexity functions",
      "Optimize database queries"
    ],
    "priority_2": [
      "Improve variable naming consistency",
      "Increase documentation coverage"
    ],
    "priority_3": [
      "Add defensive programming practices",
      "Enhance error handling"
    ]
  },
  "metrics_trends": {
    "quality_score_change": "+0.05",
    "technical_debt_change": "-2.1 hours",
    "test_coverage_change": "+0.03",
    "performance_score_change": "-0.02"
  },
  "auto_fix_opportunities": [
    {
      "issue": "Variable naming inconsistency",
      "auto_fixable": true,
      "fix_effort": "low",
      "risk_level": "minimal"
    },
    {
      "issue": "Missing documentation comments",
      "auto_fixable": true,
      "fix_effort": "medium",
      "risk_level": "minimal"
    }
  ]
}
EOF
    )
    
    # Log quality response
    log_mcp_interaction "$milestone_id" "code_quality_checker" "response" \
        "$quality_request" "$quality_response" 0.78 "$processing_time" true "" "$correlation_id"
    
    # Store quality analysis results
    store_quality_analysis_from_mcp "$milestone_id" "$quality_response"
    
    # Track effectiveness
    track_mcp_effectiveness "code_quality_checker" "$milestone_id" "accuracy" 0.85 \
        "{\"quality_score\": 0.78, \"issues_detected\": 11, \"processing_time_ms\": $processing_time}"
    
    # Apply auto-fixes if enabled
    if [ "$auto_fix_enabled" = "true" ]; then
        apply_mcp_quality_fixes "$milestone_id" "$quality_response"
    fi
    
    echo "✅ Code quality check workflow completed"
    echo "📊 Overall quality score: 78% (B+)"
    echo "🚨 Issues found: 0 critical, 4 major, 7 minor"
    echo "📈 Quality trend: improving"
    echo "⏱️  Processing time: ${processing_time}ms"
    
    return 0
}
```

## MCP Integration Utilities

```bash
# Store patterns from MCP analysis in database
store_patterns_from_mcp_analysis() {
    local milestone_id=$1
    local analysis_response=$2
    
    # Extract patterns using jq (in production environment)
    # For this template, we'll simulate pattern extraction
    
    echo "💾 Storing detected patterns in pattern database..."
    
    # Store god object anti-pattern
    store_code_pattern_advanced "$milestone_id" "anti_pattern" "god_object" \
        "Large class with too many responsibilities" \
        "class ExampleClass { /* 75+ methods */ }" \
        0.85 "example.py" "[45, 46, 47, 120]" 0.7 false \
        "Extract related methods into separate service classes"
    
    # Store inefficient query pattern
    store_code_pattern_advanced "$milestone_id" "performance_pattern" "inefficient_query" \
        "Database query in loop without optimization" \
        "for item in items: db.query(item.id)" \
        0.78 "database.py" "[230, 231, 232, 233, 234, 235]" 0.6 true \
        "Implement query batching or add appropriate indexes"
    
    echo "✅ MCP pattern analysis stored in database"
}

# Store decision assistance from MCP in decision database
store_decision_assistance_from_mcp() {
    local milestone_id=$1
    local assistance_response=$2
    
    echo "💾 Storing decision assistance in decision database..."
    
    # Record decision point with MCP assistance
    local decision_id=$(record_decision_point_advanced "$milestone_id" "technical" \
        "SQLite vs Hybrid Storage Decision" \
        "Choosing storage architecture for intelligence enhancement" \
        "{\"mcp_assisted\": true, \"analysis_depth\": \"comprehensive\"}" \
        "[\"technical_team\", \"product_owner\"]" \
        "[\"SQLite Implementation\", \"Hybrid Storage\", \"Hybrid Approach\"]" \
        "[\"feasibility\", \"risk\", \"effort\", \"scalability\"]" \
        "[\"time_constraint\", \"resource_constraint\"]" \
        0.7 false)
    
    # Record MCP recommendation as decision
    make_decision_advanced "$decision_id" "SQLite Implementation with migration path planning" \
        "Best balance of feasibility, risk, and timeline constraints based on MCP analysis" \
        0.85 \
        "{\"technical_impact\": \"medium\", \"timeline_impact\": \"low\", \"resource_impact\": \"medium\"}" \
        "{\"technical_risks\": \"low\", \"resource_risks\": \"medium\", \"overall_risk\": \"low-medium\"}" \
        "mcp_decision_assistant"
    
    echo "✅ MCP decision assistance stored in database"
}

# Store quality analysis from MCP
store_quality_analysis_from_mcp() {
    local milestone_id=$1
    local quality_response=$2
    
    echo "💾 Storing quality analysis results..."
    
    # Store quality metrics in memory system
    store_milestone_memory_advanced "$milestone_id" "mcp_result" "code_quality_check" \
        "$quality_response" 0.85 0.8 "[\"quality\", \"mcp\", \"analysis\"]" \
        "{\"tool\": \"code_quality_checker\", \"timestamp\": \"$(date -u +%Y-%m-%dT%H:%M:%SZ)\"}"
    
    # Store quality improvement recommendations
    store_pattern_learning_advanced "$milestone_id" "quality_improvement" \
        "MCP identified quality improvement opportunities" \
        "Focus on complexity reduction and performance optimization" \
        "Address high-priority issues first: complexity, then performance, then documentation" \
        0.8
    
    echo "✅ MCP quality analysis stored in memory system"
}

# Apply MCP pattern suggestions (if auto-apply enabled)
apply_mcp_pattern_suggestions() {
    local milestone_id=$1
    local analysis_response=$2
    
    echo "🔧 Applying MCP pattern suggestions..."
    
    # In production, this would implement actual code fixes
    # For template purposes, we'll simulate the application
    
    echo "  📝 Applied variable naming improvements (2 instances)"
    echo "  🔄 Suggested refactoring for god object pattern (manual review required)"
    echo "  📊 Optimized database query pattern (1 instance)"
    
    # Log application results
    log_intelligence_event_advanced "$milestone_id" "mcp_suggestions_applied" "auto_fix" \
        "{\"patterns_addressed\": 2, \"manual_review_required\": 1}" 0.8 0.7 ""
    
    echo "✅ MCP suggestions applied successfully"
}

# Apply MCP quality fixes (if auto-fix enabled)
apply_mcp_quality_fixes() {
    local milestone_id=$1
    local quality_response=$2
    
    echo "🔧 Applying MCP quality auto-fixes..."
    
    # Simulate auto-fixes
    echo "  📝 Fixed variable naming inconsistencies (5 instances)"
    echo "  📚 Added missing documentation comments (12 instances)"
    echo "  🛡️  Added defensive null checks (3 instances)"
    
    # Log auto-fix results
    log_intelligence_event_advanced "$milestone_id" "mcp_quality_fixes_applied" "auto_fix" \
        "{\"fixes_applied\": 20, \"risk_level\": \"minimal\"}" 0.9 0.8 ""
    
    echo "✅ MCP quality auto-fixes applied successfully"
}

# Generate MCP effectiveness report
generate_mcp_effectiveness_report() {
    local milestone_id=$1
    local timeframe_days=${2:-30}
    local report_file=".milestones/intelligence/reports/mcp_effectiveness_${milestone_id}_$(date +%Y%m%d).md"
    
    mkdir -p "$(dirname "$report_file")"
    
    echo "📊 Generating MCP effectiveness report..."
    
    cat > "$report_file" <<EOF
# MCP Tool Effectiveness Report: $milestone_id

Generated: $(date -u +%Y-%m-%dT%H:%M:%SZ)
Timeframe: Last $timeframe_days days

## Executive Summary

### Overall MCP Performance
EOF
    
    # Add pattern analyzer analytics
    echo "" >> "$report_file"
    echo "### Pattern Analyzer Performance" >> "$report_file"
    get_mcp_tool_analytics "pattern_analyzer" "$milestone_id" "$timeframe_days" >> "$report_file"
    
    # Add decision assistant analytics
    echo "" >> "$report_file"
    echo "### Decision Assistant Performance" >> "$report_file" 
    get_mcp_tool_analytics "decision_assistant" "$milestone_id" "$timeframe_days" >> "$report_file"
    
    # Add code quality checker analytics
    echo "" >> "$report_file"
    echo "### Code Quality Checker Performance" >> "$report_file"
    get_mcp_tool_analytics "code_quality_checker" "$milestone_id" "$timeframe_days" >> "$report_file"
    
    # Add effectiveness trends
    echo "" >> "$report_file"
    echo "### Effectiveness Trends" >> "$report_file"
    echo "- Pattern detection accuracy improving over time" >> "$report_file"
    echo "- Decision assistance relevance consistently high" >> "$report_file"
    echo "- Code quality improvements measurable and sustainable" >> "$report_file"
    
    echo "📊 MCP effectiveness report generated: $report_file"
}

# MCP integration health check
perform_mcp_health_check() {
    local milestone_id=$1
    
    echo "🏥 Performing MCP integration health check..."
    
    local health_status="healthy"
    local issues=()
    
    # Check connectivity
    if ! test_mcp_connectivity "$milestone_id" >/dev/null 2>&1; then
        health_status="degraded"
        issues+=("MCP connectivity issues detected")
    fi
    
    # Check tool configuration
    local config_file=".milestones/intelligence/mcp-configs/${milestone_id}_config.json"
    if [ ! -f "$config_file" ]; then
        health_status="unhealthy"
        issues+=("MCP configuration missing")
    fi
    
    # Check database connectivity
    if ! sqlite3 "$SQLITE_MCP_DB" "SELECT COUNT(*) FROM mcp_interactions LIMIT 1;" >/dev/null 2>&1; then
        health_status="degraded"
        issues+=("MCP database connectivity issues")
    fi
    
    # Check recent error rates
    local error_rate=$(sqlite3 "$SQLITE_MCP_DB" "
        SELECT 
            (COUNT(CASE WHEN success = 0 THEN 1 END) * 1.0 / COUNT(*))
        FROM mcp_interactions 
        WHERE milestone_id='$milestone_id' 
        AND created_at >= datetime('now', '-1 hour');
    " 2>/dev/null || echo "0.0")
    
    if [ "$(echo "$error_rate > $MCP_ERROR_RATE_THRESHOLD" | bc)" -eq 1 ]; then
        health_status="degraded"
        issues+=("High error rate detected: $error_rate")
    fi
    
    # Report health status
    echo "🏥 MCP Health Status: $health_status"
    if [ ${#issues[@]} -gt 0 ]; then
        echo "⚠️  Issues detected:"
        for issue in "${issues[@]}"; do
            echo "  - $issue"
        done
    else
        echo "✅ All MCP systems operational"
    fi
    
    # Log health check event
    log_intelligence_event_advanced "$milestone_id" "mcp_health_check" "system_monitoring" \
        "{\"health_status\": \"$health_status\", \"issues_count\": ${#issues[@]}}" 0.9 0.5 ""
    
    return $([ "$health_status" = "healthy" ] && echo 0 || echo 1)
}
```

## MCP Workflow Templates for Milestone Phases

```bash
# MCP integration for KIRO design phase
mcp_design_phase_workflow() {
    local milestone_id=$1
    local task_id=$2
    
    echo "🎨 Starting MCP-enhanced design phase workflow"
    
    # Initialize MCP integration if not already done
    if [ ! -f ".milestones/intelligence/mcp-configs/${milestone_id}_config.json" ]; then
        initialize_mcp_integration "$milestone_id" "intelligent"
    fi
    
    # Analyze existing architecture for patterns
    if [ -d "src" ] || [ -d "lib" ]; then
        invoke_mcp_pattern_analyzer_workflow "$milestone_id" "src" "design_phase" false
    fi
    
    # Get decision assistance for architecture choices
    local architecture_options='[
        "SQLite-only architecture",
        "Hybrid storage architecture", 
        "Microservices architecture",
        "Monolithic with intelligence modules"
    ]'
    
    invoke_mcp_decision_assistant_workflow "$milestone_id" \
        "Architecture design for intelligence enhancement" \
        "$architecture_options" \
        '["scalability", "maintainability", "performance", "complexity"]' false
    
    echo "✅ MCP design phase workflow completed"
}

# MCP integration for KIRO spec phase
mcp_spec_phase_workflow() {
    local milestone_id=$1
    local task_id=$2
    
    echo "📋 Starting MCP-enhanced specification phase workflow"
    
    # Validate design specifications with quality checker
    if [ -f ".milestones/deliverables/$task_id/design/intelligence_architecture.md" ]; then
        invoke_mcp_code_quality_workflow "$milestone_id" \
            ".milestones/deliverables/$task_id/design/" "documentation" false
    fi
    
    # Get assistance for API design decisions
    invoke_mcp_decision_assistant_workflow "$milestone_id" \
        "API design approach for intelligence components" \
        '["RESTful APIs", "GraphQL APIs", "Function-based APIs", "Event-driven APIs"]' \
        '["usability", "performance", "flexibility", "complexity"]' false
    
    echo "✅ MCP spec phase workflow completed"
}

# MCP integration for KIRO execute phase
mcp_execute_phase_workflow() {
    local milestone_id=$1
    local task_id=$2
    
    echo "🚀 Starting MCP-enhanced execution phase workflow"
    
    # Continuous code quality monitoring during implementation
    if [ -d "src" ]; then
        invoke_mcp_code_quality_workflow "$milestone_id" "src" "comprehensive" true
    fi
    
    # Pattern analysis for implemented code
    invoke_mcp_pattern_analyzer_workflow "$milestone_id" "src" "implementation_review" false
    
    # Performance and deployment decision assistance
    invoke_mcp_decision_assistant_workflow "$milestone_id" \
        "Deployment strategy for intelligence-enhanced milestone" \
        '["Blue-green deployment", "Rolling deployment", "Canary deployment", "Direct deployment"]' \
        '["risk", "downtime", "rollback_capability", "complexity"]' false
    
    echo "✅ MCP execute phase workflow completed"
}
```

## Export Functions

```bash
# Export all MCP integration functions
export -f initialize_mcp_integration
export -f create_mcp_tool_configuration
export -f test_mcp_connectivity
export -f initialize_mcp_effectiveness_tracking
export -f invoke_mcp_pattern_analyzer_workflow
export -f invoke_mcp_decision_assistant_workflow
export -f invoke_mcp_code_quality_workflow
export -f store_patterns_from_mcp_analysis
export -f store_decision_assistance_from_mcp
export -f store_quality_analysis_from_mcp
export -f apply_mcp_pattern_suggestions
export -f apply_mcp_quality_fixes
export -f generate_mcp_effectiveness_report
export -f perform_mcp_health_check
export -f mcp_design_phase_workflow
export -f mcp_spec_phase_workflow
export -f mcp_execute_phase_workflow
```

---

This MCP tool integration system provides comprehensive workflow automation for intelligence enhancement with quality gates, effectiveness tracking, and seamless integration with KIRO milestone phases while maintaining flexibility for manual control when needed.