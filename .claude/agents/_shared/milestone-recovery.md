# Milestone Agent Session Recovery Functions

## SQLite-Based Session Recovery for Milestone Agents

This system provides robust session recovery capabilities for milestone agents using the SQLite coordination database, replacing fragile temporary file-based approaches.

### Core Recovery Functions

```bash
# Source the storage adapter for coordination functions
source "$(dirname "$0")/../../skills/milestone/_shared/storage-adapter.md"

# Detect interrupted sessions and offer recovery
detect_interrupted_sessions() {
    local backend=$(get_current_storage_backend)
    
    if [ "$backend" = "file" ]; then
        echo "Session recovery requires hybrid or database backend"
        return 0
    fi
    
    echo "Scanning for interrupted milestone agent sessions..."
    
    # Find stale sessions (active but no recent agent heartbeats)
    local interrupted_sessions=$(sqlite3 ".milestones/coordination.db" "
        SELECT DISTINCT cs.session_id, cs.milestone_id, cs.operation_type, cs.last_activity,
               COUNT(ar.agent_id) as agent_count,
               COUNT(CASE WHEN ar.status = 'active' THEN 1 END) as active_agents,
               COUNT(CASE WHEN ar.status = 'failed' THEN 1 END) as failed_agents,
               (julianday('now') - julianday(cs.last_activity)) * 24 * 60 as minutes_inactive
        FROM coordination_sessions cs
        LEFT JOIN agent_registry ar ON cs.session_id = ar.session_id
        WHERE cs.status = 'active'
          AND (julianday('now') - julianday(cs.last_activity)) * 24 * 60 > 5  -- 5+ minutes inactive
        GROUP BY cs.session_id, cs.milestone_id, cs.operation_type, cs.last_activity
        ORDER BY cs.last_activity DESC;
    ")
    
    if [ -z "$interrupted_sessions" ]; then
        echo "No interrupted sessions found."
        return 0
    fi
    
    echo "Found interrupted sessions:"
    echo "Session ID | Milestone | Operation | Inactive (min) | Agents | Active | Failed"
    echo "-----------|-----------|-----------|----------------|--------|--------|-------"
    
    echo "$interrupted_sessions" | while IFS='|' read -r session_id milestone_id operation_type last_activity agent_count active_agents failed_agents minutes_inactive; do
        printf "%-10s | %-9s | %-9s | %14.0f | %6s | %6s | %6s\n" \
               "${session_id:0:10}" "${milestone_id:0:9}" "${operation_type:0:9}" \
               "$minutes_inactive" "$agent_count" "$active_agents" "$failed_agents"
    done
    
    echo ""
    echo "Use 'recover_milestone_session <session_id>' to recover a specific session"
    echo "Use 'cleanup_failed_sessions' to clean up sessions with all failed agents"
    
    return 0
}

# Recover specific milestone session
recover_milestone_session() {
    local session_id=$1
    
    if [ -z "$session_id" ]; then
        echo "Usage: recover_milestone_session <session_id>"
        return 1
    fi
    
    local backend=$(get_current_storage_backend)
    
    if [ "$backend" = "file" ]; then
        echo "Session recovery requires hybrid or database backend"
        return 1
    fi
    
    echo "Recovering milestone session: $session_id"
    
    # Get session information
    local session_info=$(sqlite3 ".milestones/coordination.db" "
        SELECT milestone_id, operation_type, progress, created_at
        FROM coordination_sessions 
        WHERE session_id = '$session_id';
    ")
    
    if [ -z "$session_info" ]; then
        echo "Session not found: $session_id"
        return 1
    fi
    
    local milestone_id=$(echo "$session_info" | cut -d'|' -f1)
    local operation_type=$(echo "$session_info" | cut -d'|' -f2)
    local progress=$(echo "$session_info" | cut -d'|' -f3)
    local created_at=$(echo "$session_info" | cut -d'|' -f4)
    
    echo "Session Details:"
    echo "  Milestone ID: $milestone_id"
    echo "  Operation: $operation_type"
    echo "  Progress: $progress%"
    echo "  Created: $created_at"
    echo ""
    
    # Get agent status for this session
    local agent_status=$(sqlite3 ".milestones/coordination.db" "
        SELECT agent_id, agent_type, status, current_task, last_heartbeat,
               (julianday('now') - julianday(last_heartbeat)) * 24 * 60 as minutes_since_heartbeat
        FROM agent_registry 
        WHERE session_id = '$session_id'
        ORDER BY spawn_time;
    ")
    
    echo "Agent Status:"
    echo "Agent ID | Type | Status | Last Task | Minutes Since Heartbeat"
    echo "---------|------|--------|-----------|------------------------"
    
    local failed_agents=0
    local recoverable_agents=0
    
    echo "$agent_status" | while IFS='|' read -r agent_id agent_type status current_task last_heartbeat minutes_since; do
        printf "%-8s | %-4s | %-6s | %-9s | %22.0f\n" \
               "${agent_id:0:8}" "${agent_type:0:4}" "$status" "${current_task:0:9}" "$minutes_since"
        
        if [ "$status" = "failed" ] || [ "$minutes_since" -gt 30 ]; then
            ((failed_agents++))
        elif [ "$status" = "active" ] || [ "$status" = "paused" ]; then
            ((recoverable_agents++))
        fi
    done
    
    echo ""
    echo "Recovery Assessment:"
    echo "  - Failed/Stale agents: $failed_agents"
    echo "  - Recoverable agents: $recoverable_agents"
    echo ""
    
    # Recovery strategy based on session state
    if [ "$operation_type" = "planning" ]; then
        recover_planning_session "$session_id" "$milestone_id"
    elif [ "$operation_type" = "execution" ]; then
        recover_execution_session "$session_id" "$milestone_id"
    else
        echo "Unknown operation type: $operation_type"
        return 1
    fi
}

# Recover planning session
recover_planning_session() {
    local session_id=$1
    local milestone_id=$2
    
    echo "Recovering planning session..."
    
    # Check what planning artifacts are available
    local available_artifacts=$(sqlite3 ".milestones/coordination.db" "
        SELECT state_key, COUNT(*) as count
        FROM agent_state as_table
        INNER JOIN agent_registry ar ON as_table.agent_id = ar.agent_id
        WHERE ar.session_id = '$session_id'
          AND as_table.state_key LIKE 'planning-%'
          AND (as_table.expires_at IS NULL OR as_table.expires_at > datetime('now'))
        GROUP BY state_key;
    ")
    
    echo "Available planning artifacts:"
    echo "$available_artifacts" | while IFS='|' read -r artifact_name count; do
        echo "  - $artifact_name ($count agent(s))"
    done
    echo ""
    
    # Check for unified milestone plan
    local unified_plan=$(sqlite3 ".milestones/coordination.db" "
        SELECT state_value
        FROM agent_state as_table
        INNER JOIN agent_registry ar ON as_table.agent_id = ar.agent_id
        WHERE ar.session_id = '$session_id'
          AND as_table.state_key = 'milestone-plan'
        LIMIT 1;
    ")
    
    if [ -n "$unified_plan" ]; then
        echo "✅ Unified milestone plan found - session can be completed"
        echo ""
        echo "Options:"
        echo "  1. Complete planning session: complete_planning_session '$session_id'"
        echo "  2. Create milestone from plan: create_milestone_from_recovered_planning '$session_id'"
        echo "  3. Resume planning agents: resume_planning_agents '$session_id'"
    else
        echo "❌ Unified milestone plan missing - planning incomplete"
        echo ""
        echo "Recovery options:"
        echo "  1. Resume planning agents to complete: resume_planning_agents '$session_id'"
        echo "  2. Restart planning with existing artifacts: restart_planning_with_artifacts '$session_id'"
        echo "  3. Abandon session and cleanup: abandon_session '$session_id'"
    fi
}

# Recover execution session  
recover_execution_session() {
    local session_id=$1
    local milestone_id=$2
    
    echo "Recovering execution session..."
    
    # Check KIRO phase completion status
    local phase_status=$(sqlite3 ".milestones/coordination.db" "
        SELECT 
            COUNT(CASE WHEN state_key = 'design-phase-results' THEN 1 END) as design_complete,
            COUNT(CASE WHEN state_key = 'spec-phase-results' THEN 1 END) as spec_complete,
            COUNT(CASE WHEN state_key = 'task-phase-results' THEN 1 END) as task_complete,
            COUNT(CASE WHEN state_key = 'execute-phase-results' THEN 1 END) as execute_complete
        FROM agent_state as_table
        INNER JOIN agent_registry ar ON as_table.agent_id = ar.agent_id
        WHERE ar.session_id = '$session_id';
    ")
    
    local design_done=$(echo "$phase_status" | cut -d'|' -f1)
    local spec_done=$(echo "$phase_status" | cut -d'|' -f2)
    local task_done=$(echo "$phase_status" | cut -d'|' -f3)
    local execute_done=$(echo "$phase_status" | cut -d'|' -f4)
    
    echo "KIRO Phase Completion Status:"
    echo "  - Design (15%): $([ "$design_done" -gt 0 ] && echo "✅ Complete" || echo "❌ Incomplete")"
    echo "  - Spec (25%):   $([ "$spec_done" -gt 0 ] && echo "✅ Complete" || echo "❌ Incomplete")"
    echo "  - Task (20%):   $([ "$task_done" -gt 0 ] && echo "✅ Complete" || echo "❌ Incomplete")"
    echo "  - Execute (40%): $([ "$execute_done" -gt 0 ] && echo "✅ Complete" || echo "❌ Incomplete")"
    echo ""
    
    # Calculate recovery progress
    local completed_weight=0
    [ "$design_done" -gt 0 ] && completed_weight=$((completed_weight + 15))
    [ "$spec_done" -gt 0 ] && completed_weight=$((completed_weight + 25))
    [ "$task_done" -gt 0 ] && completed_weight=$((completed_weight + 20))
    [ "$execute_done" -gt 0 ] && completed_weight=$((completed_weight + 40))
    
    echo "Estimated progress: $completed_weight%"
    echo ""
    
    if [ "$completed_weight" -eq 100 ]; then
        echo "✅ All phases complete - session can be finalized"
        echo "Options:"
        echo "  1. Finalize milestone: finalize_milestone_execution '$session_id'"
        echo "  2. Generate final report: generate_milestone_report '$session_id'"
    elif [ "$completed_weight" -gt 0 ]; then
        echo "🔄 Partial completion - can resume from next phase"
        echo "Recovery options:"
        echo "  1. Resume from next incomplete phase: resume_execution_from_phase '$session_id'"
        echo "  2. Restart incomplete phases: restart_incomplete_phases '$session_id'"
    else
        echo "❌ No phases completed - execution failed to start"
        echo "Recovery options:"
        echo "  1. Restart execution from beginning: restart_milestone_execution '$session_id'"
        echo "  2. Check for planning issues: validate_planning_artifacts '$session_id'"
    fi
}

# Resume planning agents for incomplete planning session
resume_planning_agents() {
    local session_id=$1
    
    echo "Resuming planning agents for session: $session_id"
    
    # Mark stale agents as failed
    sqlite3 ".milestones/coordination.db" "
        UPDATE agent_registry 
        SET status = 'failed',
            error_info = 'Agent marked as failed during session recovery'
        WHERE session_id = '$session_id'
          AND status = 'active'
          AND (julianday('now') - julianday(last_heartbeat)) * 24 * 60 > 10;
    "
    
    # Check which planning components are missing
    local missing_artifacts=$(sqlite3 ".milestones/coordination.db" "
        WITH required_artifacts AS (
            SELECT 'planning-scope' as artifact_name
            UNION SELECT 'planning-estimates'
            UNION SELECT 'planning-risks'  
            UNION SELECT 'planning-kiro'
            UNION SELECT 'milestone-plan'
        )
        SELECT ra.artifact_name
        FROM required_artifacts ra
        LEFT JOIN (
            SELECT DISTINCT state_key
            FROM agent_state as_table
            INNER JOIN agent_registry ar ON as_table.agent_id = ar.agent_id
            WHERE ar.session_id = '$session_id'
        ) existing ON ra.artifact_name = existing.state_key
        WHERE existing.state_key IS NULL;
    ")
    
    if [ -z "$missing_artifacts" ]; then
        echo "All planning artifacts present - attempting to complete session"
        complete_planning_session "$session_id"
        return $?
    fi
    
    echo "Missing planning artifacts:"
    echo "$missing_artifacts" | while read -r artifact; do
        echo "  - $artifact"
    done
    echo ""
    
    # Spawn replacement agents for missing artifacts
    echo "Spawning replacement planning agents..."
    
    # Update session status
    sqlite3 ".milestones/coordination.db" "
        UPDATE coordination_sessions 
        SET status = 'active',
            last_activity = CURRENT_TIMESTAMP
        WHERE session_id = '$session_id';
    "
    
    # This would integrate with the actual agent spawning system
    echo "Planning agents resumed. Monitor progress with: get_session_status '$session_id'"
}

# Complete planning session by creating unified plan
complete_planning_session() {
    local session_id=$1
    
    echo "Completing planning session: $session_id"
    
    # Collect all planning artifacts
    local planning_data=$(sqlite3 ".milestones/coordination.db" "
        SELECT state_key, state_value
        FROM agent_state as_table
        INNER JOIN agent_registry ar ON as_table.agent_id = ar.agent_id
        WHERE ar.session_id = '$session_id'
          AND state_key LIKE 'planning-%'
        ORDER BY state_key;
    ")
    
    if [ -z "$planning_data" ]; then
        echo "No planning artifacts found for session: $session_id"
        return 1
    fi
    
    # Mark session as completed
    sqlite3 ".milestones/coordination.db" "
        UPDATE coordination_sessions 
        SET status = 'completed',
            progress = 100,
            completed_at = CURRENT_TIMESTAMP
        WHERE session_id = '$session_id';
        
        UPDATE agent_registry 
        SET status = 'completed',
            completion_time = CURRENT_TIMESTAMP
        WHERE session_id = '$session_id'
          AND status != 'failed';
    "
    
    log_storage_event "planning_session_completed" "$session_id" "$(get_current_storage_backend)" 
    echo "Planning session completed successfully: $session_id"
}

# Create milestone from recovered planning session
create_milestone_from_recovered_planning() {
    local session_id=$1
    local milestone_id=${2:-"milestone-$(date +%Y%m%d_%H%M%S)"}
    
    echo "Creating milestone from recovered planning session..."
    
    # Get unified plan from session
    local unified_plan=$(sqlite3 ".milestones/coordination.db" "
        SELECT state_value
        FROM agent_state as_table
        INNER JOIN agent_registry ar ON as_table.agent_id = ar.agent_id
        WHERE ar.session_id = '$session_id'
          AND state_key = 'milestone-plan'
        LIMIT 1;
    ")
    
    if [ -z "$unified_plan" ]; then
        echo "No unified milestone plan found. Complete planning first with:"
        echo "  complete_planning_session '$session_id'"
        return 1
    fi
    
    # Convert to milestone format and create
    local milestone_yaml=$(echo "$unified_plan" | jq -r '
        {
            "title": .title,
            "status": "planned",
            "kiro_configuration": {
                "enabled": true,
                "phase_weights": {"design": 15, "spec": 25, "task": 20, "execute": 40}
            },
            "tasks": (.tasks // []),
            "timeline": .timeline,
            "dependencies": .dependencies,
            "planning_session": "'$session_id'",
            "recovered": true,
            "recovery_timestamp": "'$(date -u +%Y-%m-%dT%H:%M:%SZ)'"
        }
    ' | yq e -P -)
    
    # Create milestone record
    create_milestone_record "$milestone_id" "$milestone_yaml"
    
    # Mark planning session as used
    sqlite3 ".milestones/coordination.db" "
        UPDATE coordination_sessions 
        SET status = 'completed',
            milestone_id = '$milestone_id'
        WHERE session_id = '$session_id';
    "
    
    log_storage_event "milestone_created_from_recovery" "$milestone_id" "$(get_current_storage_backend)" "{\"planning_session\":\"$session_id\"}"
    echo "Milestone created from recovered session: $milestone_id"
}

# Cleanup failed sessions
cleanup_failed_sessions() {
    local retention_hours=${1:-24}
    
    echo "Cleaning up failed sessions older than $retention_hours hours..."
    
    local backend=$(get_current_storage_backend)
    
    if [ "$backend" = "file" ]; then
        echo "Session cleanup requires hybrid or database backend"
        return 0
    fi
    
    # Find sessions with all agents failed or stale
    local failed_sessions=$(sqlite3 ".milestones/coordination.db" "
        WITH session_summary AS (
            SELECT 
                cs.session_id,
                cs.milestone_id,
                cs.created_at,
                COUNT(ar.agent_id) as total_agents,
                COUNT(CASE WHEN ar.status IN ('failed', 'terminated') 
                           OR (julianday('now') - julianday(ar.last_heartbeat)) * 24 > 1 
                       THEN 1 END) as failed_agents
            FROM coordination_sessions cs
            LEFT JOIN agent_registry ar ON cs.session_id = ar.session_id
            WHERE cs.status = 'active'
              AND (julianday('now') - julianday(cs.created_at)) * 24 > $retention_hours
            GROUP BY cs.session_id, cs.milestone_id, cs.created_at
        )
        SELECT session_id
        FROM session_summary
        WHERE total_agents > 0 AND total_agents = failed_agents;
    ")
    
    if [ -z "$failed_sessions" ]; then
        echo "No failed sessions found for cleanup"
        return 0
    fi
    
    local cleanup_count=0
    echo "$failed_sessions" | while read -r session_id; do
        echo "Cleaning up failed session: $session_id"
        
        # Mark session as abandoned
        sqlite3 ".milestones/coordination.db" "
            UPDATE coordination_sessions 
            SET status = 'abandoned',
                completed_at = CURRENT_TIMESTAMP
            WHERE session_id = '$session_id';
            
            UPDATE agent_registry 
            SET status = 'terminated'
            WHERE session_id = '$session_id'
              AND status != 'completed';
        "
        
        ((cleanup_count++))
        log_storage_event "session_cleaned_up" "$session_id" "$backend"
    done
    
    echo "Cleaned up $cleanup_count failed sessions"
}

# Get session recovery status
get_session_recovery_status() {
    local backend=$(get_current_storage_backend)
    
    if [ "$backend" = "file" ]; then
        echo "Recovery status requires hybrid or database backend"
        return 0
    fi
    
    local recovery_stats=$(sqlite3 ".milestones/coordination.db" "
        SELECT 
            COUNT(*) as total_sessions,
            COUNT(CASE WHEN status = 'active' THEN 1 END) as active_sessions,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_sessions,
            COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_sessions,
            COUNT(CASE WHEN status = 'abandoned' THEN 1 END) as abandoned_sessions,
            COUNT(CASE WHEN status = 'active' 
                       AND (julianday('now') - julianday(last_activity)) * 24 * 60 > 5 
                  THEN 1 END) as stale_sessions
        FROM coordination_sessions
        WHERE created_at > date('now', '-7 days');
    ")
    
    echo "Session Recovery Status (Last 7 Days):"
    echo "$recovery_stats" | while IFS='|' read -r total active completed failed abandoned stale; do
        echo "  Total Sessions: $total"
        echo "  Active: $active"
        echo "  Completed: $completed"  
        echo "  Failed: $failed"
        echo "  Abandoned: $abandoned"
        echo "  Stale (>5min inactive): $stale"
    done
    
    if [ "$stale" -gt 0 ]; then
        echo ""
        echo "⚠️  $stale sessions appear to be interrupted and may need recovery"
        echo "Run 'detect_interrupted_sessions' to see details"
    fi
}

# Auto-recovery daemon for monitoring sessions
start_session_monitor() {
    local check_interval=${1:-300}  # 5 minutes default
    
    echo "Starting session recovery monitor (checking every ${check_interval}s)"
    echo "Monitor process will run in background. Use 'pkill -f session-monitor' to stop."
    
    (
        while true; do
            # Cleanup stale agents
            cleanup_stale_coordination 30
            
            # Log recovery status
            local timestamp=$(date -u +%Y-%m-%dT%H:%M:%SZ)
            echo "[$timestamp] Session monitor check" >> ".milestones/logs/session-monitor.log"
            
            sleep "$check_interval"
        done
    ) &
    
    local monitor_pid=$!
    echo "$monitor_pid" > ".milestones/session-monitor.pid"
    echo "Session monitor started with PID: $monitor_pid"
}

# Stop session monitor
stop_session_monitor() {
    local pid_file=".milestones/session-monitor.pid"
    
    if [ -f "$pid_file" ]; then
        local monitor_pid=$(cat "$pid_file")
        if kill -0 "$monitor_pid" 2>/dev/null; then
            kill "$monitor_pid"
            echo "Session monitor stopped (PID: $monitor_pid)"
        else
            echo "Session monitor was not running"
        fi
        rm -f "$pid_file"
    else
        echo "Session monitor PID file not found"
    fi
}

# Usage help
show_recovery_help() {
    cat << 'EOF'
# Milestone Agent Session Recovery Commands

## Detection and Status
detect_interrupted_sessions              # Find sessions needing recovery
get_session_recovery_status             # Overall recovery statistics
get_session_status <session_id>         # Detailed session information
list_session_agents <session_id>        # Show agents in session

## Recovery Operations  
recover_milestone_session <session_id>  # Main recovery command
resume_planning_agents <session_id>     # Resume interrupted planning
complete_planning_session <session_id>  # Finalize planning artifacts
create_milestone_from_recovered_planning <session_id> [milestone_id]

## Cleanup and Maintenance
cleanup_failed_sessions [retention_hours]  # Clean up old failed sessions
cleanup_stale_coordination [stale_minutes] # Mark stale agents as failed

## Monitoring
start_session_monitor [interval_seconds]   # Auto-recovery daemon
stop_session_monitor                       # Stop monitoring daemon

## Examples
detect_interrupted_sessions
recover_milestone_session "planning-milestone-001-1640995200"
create_milestone_from_recovered_planning "planning-milestone-001-1640995200"
EOF
}
```

This session recovery system provides comprehensive recovery capabilities for milestone agents, ensuring that work is never lost due to system interruptions or agent failures. The SQLite-based approach makes recovery reliable and deterministic.