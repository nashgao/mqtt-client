---
description: Directory structure setup and management for inter-agent coordination
---

# Agent Coordination Directory Setup

Automated setup and management of the coordination directory structure for inter-agent communication, state management, and result aggregation.

## Directory Structure Template

```yaml
coordination_structure:
  base_path: "/tmp/claude-agents/{session-id}/"
  
  directories:
    registry:
      path: "registry/"
      purpose: "Active agent tracking and capabilities"
      files:
        - "agents.json"        # Agent registrations
        - "capabilities.json"  # Capability mapping
        - "health.json"       # Health status
        
    state:
      path: "state/"
      purpose: "Shared state management"
      files:
        - "global.json"       # Global operation state
        - "tasks.json"        # Task queue state
        - "results.json"      # Results aggregation
        - "locks/"           # State locks directory
        
    messages:
      path: "messages/"
      purpose: "Inter-agent message passing"
      directories:
        - "inbox/"           # Agent message inboxes
        - "outbox/"          # Agent message outboxes
        - "sent/"            # Sent message archives
        - "dead-letter/"     # Failed message storage
        - "retry/"           # Retry queue
        
    results:
      path: "results/"
      purpose: "Agent execution results"
      files:
        - "{agent-id}.json"  # Per-agent results
        - "aggregated.json"  # Combined results
        - "final-report.json" # Final execution report
        
    metrics:
      path: "metrics/"
      purpose: "Performance and monitoring data"
      files:
        - "performance.json" # Performance metrics
        - "message-bus.json" # Message bus metrics
        - "agent-activity.json" # Agent activity logs
        
    logs:
      path: "logs/"
      purpose: "Execution and debug logs"
      files:
        - "{agent-id}.log"   # Per-agent logs
        - "coordinator.log"  # Coordinator logs
        - "errors.log"       # Error logs
        
    backups:
      path: "backups/"
      purpose: "State and message backups"
      retention: "1 hour"
```

## Directory Setup Scripts

### Main Setup Script

```bash
#!/bin/bash

# setup-coordination-dirs.sh
# Creates and initializes coordination directory structure

set -euo pipefail

# Configuration
DEFAULT_BASE_DIR="/tmp/claude-agents"
DEFAULT_RETENTION="3600"  # 1 hour in seconds
SESSION_ID_PREFIX="coord"

# Functions
generate_session_id() {
    local operation=${1:-"general"}
    local timestamp=$(date +%s)
    local random=$(openssl rand -hex 4 2>/dev/null || echo $(date +%N | cut -c1-8))
    echo "${SESSION_ID_PREFIX}-${operation}-${timestamp}-${random}"
}

create_directory_structure() {
    local session_dir=$1
    
    echo "Creating coordination directory structure: ${session_dir}"
    
    # Create main directories
    mkdir -p "${session_dir}"/{registry,state,messages,results,metrics,logs,backups}
    
    # Create message subdirectories
    mkdir -p "${session_dir}/messages"/{inbox,outbox,sent,dead-letter,retry}
    
    # Create state locks directory
    mkdir -p "${session_dir}/state/locks"
    
    # Set appropriate permissions
    chmod 755 "${session_dir}"
    chmod 755 "${session_dir}"/{registry,state,messages,results,metrics,logs,backups}
    chmod 755 "${session_dir}/messages"/{inbox,outbox,sent,dead-letter,retry}
    chmod 755 "${session_dir}/state/locks"
    
    echo "Directory structure created successfully"
}

initialize_session_metadata() {
    local session_dir=$1
    local session_id=$2
    local operation=$3
    
    local session_file="${session_dir}/session.json"
    
    cat > "${session_file}" << EOF
{
  "id": "${session_id}",
  "operation": "${operation}",
  "created_at": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
  "status": "active",
  "base_dir": "${session_dir}",
  "retention_seconds": ${DEFAULT_RETENTION},
  "cleanup_scheduled": false,
  "coordinator": {
    "user": "$(whoami)",
    "hostname": "$(hostname)",
    "pid": "$$"
  },
  "agents": [],
  "metrics": {
    "messages_sent": 0,
    "messages_received": 0,
    "agents_spawned": 0,
    "tasks_completed": 0
  }
}
EOF

    echo "Session metadata initialized: ${session_file}"
}

initialize_registry() {
    local session_dir=$1
    
    # Initialize agent registry
    cat > "${session_dir}/registry/agents.json" << 'EOF'
{
  "agents": [],
  "last_updated": null,
  "registry_version": 1
}
EOF
    
    # Initialize capabilities registry  
    cat > "${session_dir}/registry/capabilities.json" << 'EOF'
{
  "capability_types": [
    "test_execution",
    "code_analysis", 
    "file_processing",
    "documentation",
    "debugging",
    "performance_optimization"
  ],
  "agent_capabilities": {},
  "last_updated": null
}
EOF
    
    # Initialize health tracking
    cat > "${session_dir}/registry/health.json" << 'EOF'
{
  "agents": {},
  "last_health_check": null,
  "unhealthy_agents": []
}
EOF

    echo "Registry initialized"
}

initialize_state_management() {
    local session_dir=$1
    
    # Initialize global state
    cat > "${session_dir}/state/global.json" << 'EOF'
{
  "operation_progress": 0,
  "phase": "initialization",
  "start_time": null,
  "end_time": null,
  "errors": [],
  "warnings": [],
  "coordinator_status": "active"
}
EOF
    
    # Initialize task queue state
    cat > "${session_dir}/state/tasks.json" << 'EOF'
{
  "pending": [],
  "in_progress": [],
  "completed": [],
  "failed": [],
  "queue_stats": {
    "total_queued": 0,
    "total_processed": 0,
    "average_processing_time": 0
  }
}
EOF
    
    # Initialize results aggregation
    cat > "${session_dir}/state/results.json" << 'EOF'
{
  "by_agent": {},
  "by_task": {},
  "summary": {
    "total_agents": 0,
    "total_tasks": 0,
    "success_rate": 0,
    "total_execution_time": 0
  },
  "last_updated": null
}
EOF

    echo "State management initialized"
}

initialize_metrics() {
    local session_dir=$1
    
    # Initialize performance metrics
    cat > "${session_dir}/metrics/performance.json" << 'EOF'
{
  "start_time": null,
  "end_time": null,
  "total_execution_time": 0,
  "agent_performance": {},
  "throughput": {
    "messages_per_second": 0,
    "tasks_per_second": 0
  },
  "resource_usage": {
    "peak_memory": 0,
    "peak_cpu": 0,
    "peak_agents": 0
  }
}
EOF
    
    # Initialize message bus metrics
    cat > "${session_dir}/metrics/message-bus.json" << 'EOF'
{
  "messages": {
    "sent": 0,
    "received": 0,
    "failed": 0,
    "retried": 0,
    "dead_lettered": 0
  },
  "delivery": {
    "average_time": 0,
    "success_rate": 0,
    "timeout_rate": 0
  },
  "queues": {
    "inbox_sizes": {},
    "outbox_sizes": {},
    "retry_queue_size": 0,
    "dead_letter_size": 0
  }
}
EOF
    
    # Initialize agent activity tracking
    cat > "${session_dir}/metrics/agent-activity.json" << 'EOF'
{
  "agents": {},
  "activity_summary": {
    "most_active": null,
    "least_active": null,
    "average_activity": 0
  },
  "last_updated": null
}
EOF

    echo "Metrics tracking initialized"
}

schedule_cleanup() {
    local session_dir=$1
    local retention_seconds=$2
    
    # Create cleanup script
    local cleanup_script="${session_dir}/cleanup.sh"
    
    cat > "${cleanup_script}" << EOF
#!/bin/bash
# Auto-generated cleanup script for coordination session

SESSION_DIR="${session_dir}"
CLEANUP_TIME=\$(date -u +%Y-%m-%dT%H:%M:%SZ)

echo "[\${CLEANUP_TIME}] Starting cleanup for session: \${SESSION_DIR}"

# Archive session data
if [ -d "\${SESSION_DIR}" ]; then
    echo "[\${CLEANUP_TIME}] Archiving session data..."
    tar -czf "\${SESSION_DIR}.tar.gz" -C "\$(dirname "\${SESSION_DIR}")" "\$(basename "\${SESSION_DIR}")" 2>/dev/null || echo "Archive creation failed"
    
    echo "[\${CLEANUP_TIME}] Removing session directory..."
    rm -rf "\${SESSION_DIR}"
    
    echo "[\${CLEANUP_TIME}] Session cleanup completed"
else
    echo "[\${CLEANUP_TIME}] Session directory not found, cleanup skipped"
fi
EOF
    
    chmod +x "${cleanup_script}"
    
    # Schedule cleanup using at or background process
    if command -v at >/dev/null 2>&1; then
        echo "${cleanup_script}" | at "now + ${retention_seconds} seconds" 2>/dev/null || {
            echo "Failed to schedule cleanup with 'at', using background process"
            (sleep "${retention_seconds}" && "${cleanup_script}") &
        }
    else
        echo "Scheduling cleanup using background process"
        (sleep "${retention_seconds}" && "${cleanup_script}") &
    fi
    
    echo "Cleanup scheduled for ${retention_seconds} seconds"
}

# Main execution
main() {
    local operation=${1:-"general"}
    local base_dir=${2:-$DEFAULT_BASE_DIR}
    local retention=${3:-$DEFAULT_RETENTION}
    
    # Generate session ID
    local session_id=$(generate_session_id "${operation}")
    local session_dir="${base_dir}/${session_id}"
    
    echo "Initializing coordination session: ${session_id}"
    echo "Base directory: ${session_dir}"
    
    # Create and initialize
    create_directory_structure "${session_dir}"
    initialize_session_metadata "${session_dir}" "${session_id}" "${operation}"
    initialize_registry "${session_dir}"
    initialize_state_management "${session_dir}"
    initialize_metrics "${session_dir}"
    
    # Schedule cleanup
    schedule_cleanup "${session_dir}" "${retention}"
    
    # Output session information
    echo "Session ID: ${session_id}"
    echo "Session Directory: ${session_dir}"
    echo "Retention: ${retention} seconds"
    
    # Return session directory for use by calling scripts
    return 0
}

# Execute if called directly
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    main "$@"
fi
```

### Health Check Script

```bash
#!/bin/bash

# health-check.sh
# Monitors agent health and coordination infrastructure

check_agent_health() {
    local session_dir=$1
    local registry_file="${session_dir}/registry/agents.json"
    local health_file="${session_dir}/registry/health.json"
    
    if [[ ! -f "${registry_file}" ]]; then
        echo "Registry file not found: ${registry_file}"
        return 1
    fi
    
    local current_time=$(date -u +%Y-%m-%dT%H:%M:%SZ)
    local unhealthy_agents=()
    
    # Check each registered agent
    while IFS=: read -r agent_id pid; do
        if [[ -n "${agent_id}" && -n "${pid}" ]]; then
            if kill -0 "${pid}" 2>/dev/null; then
                echo "✓ Agent ${agent_id} (PID ${pid}) is healthy"
            else
                echo "✗ Agent ${agent_id} (PID ${pid}) is not responding"
                unhealthy_agents+=("${agent_id}")
            fi
        fi
    done < <(jq -r '.agents[] | select(.status == "active") | .id + ":" + (.pid | tostring)' "${registry_file}" 2>/dev/null)
    
    # Update health status
    local health_data
    health_data=$(jq \
        --argjson unhealthy "$(printf '%s\n' "${unhealthy_agents[@]}" | jq -R . | jq -s .)" \
        --arg timestamp "${current_time}" \
        '.unhealthy_agents = $unhealthy | .last_health_check = $timestamp' \
        "${health_file}" 2>/dev/null || echo '{}')
    
    echo "${health_data}" > "${health_file}"
    
    if [[ ${#unhealthy_agents[@]} -gt 0 ]]; then
        echo "Found ${#unhealthy_agents[@]} unhealthy agents"
        return 1
    fi
    
    return 0
}

check_directory_integrity() {
    local session_dir=$1
    
    local required_dirs=(
        "registry" "state" "messages" "results" "metrics" "logs"
        "messages/inbox" "messages/outbox" "messages/sent" 
        "messages/dead-letter" "messages/retry" "state/locks"
    )
    
    for dir in "${required_dirs[@]}"; do
        if [[ ! -d "${session_dir}/${dir}" ]]; then
            echo "✗ Missing directory: ${session_dir}/${dir}"
            return 1
        fi
    done
    
    echo "✓ Directory structure is intact"
    return 0
}

check_file_permissions() {
    local session_dir=$1
    
    # Check write permissions on critical directories
    local write_dirs=(
        "registry" "state" "messages/inbox" "messages/outbox" 
        "results" "metrics" "logs"
    )
    
    for dir in "${write_dirs[@]}"; do
        local full_path="${session_dir}/${dir}"
        if [[ ! -w "${full_path}" ]]; then
            echo "✗ No write permission: ${full_path}"
            return 1
        fi
    done
    
    echo "✓ File permissions are correct"
    return 0
}

main() {
    local session_dir=$1
    
    if [[ -z "${session_dir}" ]]; then
        echo "Usage: $0 <session-directory>"
        exit 1
    fi
    
    if [[ ! -d "${session_dir}" ]]; then
        echo "Session directory not found: ${session_dir}"
        exit 1
    fi
    
    echo "Checking coordination infrastructure health..."
    echo "Session: ${session_dir}"
    echo ""
    
    local overall_health=0
    
    # Run checks
    check_directory_integrity "${session_dir}" || overall_health=1
    check_file_permissions "${session_dir}" || overall_health=1
    check_agent_health "${session_dir}" || overall_health=1
    
    echo ""
    if [[ ${overall_health} -eq 0 ]]; then
        echo "✓ Coordination infrastructure is healthy"
    else
        echo "✗ Coordination infrastructure has issues"
    fi
    
    exit ${overall_health}
}

if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    main "$@"
fi
```

### Cleanup and Archival Script

```bash
#!/bin/bash

# cleanup-coordination.sh
# Cleans up coordination sessions and archives data

archive_session() {
    local session_dir=$1
    local archive_dir=${2:-"/tmp/claude-agents-archive"}
    
    local session_id=$(basename "${session_dir}")
    local archive_file="${archive_dir}/${session_id}-$(date +%Y%m%d-%H%M%S).tar.gz"
    
    echo "Archiving session: ${session_id}"
    
    # Create archive directory
    mkdir -p "${archive_dir}"
    
    # Create archive with compression
    if tar -czf "${archive_file}" -C "$(dirname "${session_dir}")" "${session_id}"; then
        echo "Session archived: ${archive_file}"
        return 0
    else
        echo "Failed to create archive: ${archive_file}"
        return 1
    fi
}

cleanup_session() {
    local session_dir=$1
    local force=${2:-false}
    
    if [[ ! -d "${session_dir}" ]]; then
        echo "Session directory not found: ${session_dir}"
        return 1
    fi
    
    local session_file="${session_dir}/session.json"
    
    # Check if session is still active
    if [[ -f "${session_file}" ]] && [[ "${force}" != "true" ]]; then
        local status
        status=$(jq -r '.status // "unknown"' "${session_file}" 2>/dev/null)
        
        if [[ "${status}" == "active" ]]; then
            echo "Session is still active, use --force to cleanup"
            return 1
        fi
    fi
    
    echo "Removing session directory: ${session_dir}"
    rm -rf "${session_dir}"
    
    return 0
}

cleanup_old_sessions() {
    local base_dir=${1:-"/tmp/claude-agents"}
    local max_age_hours=${2:-24}
    
    if [[ ! -d "${base_dir}" ]]; then
        echo "Base directory not found: ${base_dir}"
        return 0
    fi
    
    echo "Cleaning up sessions older than ${max_age_hours} hours..."
    
    find "${base_dir}" -maxdepth 1 -type d -name "coord-*" -mtime +"${max_age_hours}h" -print0 | \
    while IFS= read -r -d '' session_dir; do
        local session_id=$(basename "${session_dir}")
        echo "Found old session: ${session_id}"
        
        # Archive before cleanup
        if archive_session "${session_dir}"; then
            cleanup_session "${session_dir}" true
            echo "Cleaned up: ${session_id}"
        else
            echo "Failed to cleanup: ${session_id}"
        fi
    done
}

main() {
    local command=${1:-"help"}
    
    case "${command}" in
        "archive")
            archive_session "$2" "$3"
            ;;
        "cleanup")
            cleanup_session "$2" "$3"
            ;;
        "cleanup-old")
            cleanup_old_sessions "$2" "$3"
            ;;
        "help"|*)
            echo "Usage: $0 {archive|cleanup|cleanup-old} [options]"
            echo ""
            echo "Commands:"
            echo "  archive <session-dir> [archive-dir]"
            echo "  cleanup <session-dir> [force]"  
            echo "  cleanup-old [base-dir] [max-age-hours]"
            echo ""
            exit 1
            ;;
    esac
}

if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    main "$@"
fi
```

## Directory Usage Examples

### Setting Up a Coordination Session

```bash
# Basic setup
./setup-coordination-dirs.sh "test-execution"

# Custom location and retention
./setup-coordination-dirs.sh "refactoring" "/custom/path" 7200

# Extract session info
SESSION_ID=$(./setup-coordination-dirs.sh "debugging" | grep "Session ID:" | cut -d' ' -f3)
echo "Created session: ${SESSION_ID}"
```

### Health Monitoring

```bash
# Check specific session
./health-check.sh "/tmp/claude-agents/coord-test-1640995200-abc12345"

# Monitor continuously
while true; do
    ./health-check.sh "/tmp/claude-agents/${SESSION_ID}"
    sleep 30
done
```

### Cleanup Management

```bash
# Archive and cleanup specific session
./cleanup-coordination.sh archive "/tmp/claude-agents/coord-test-1640995200-abc12345"
./cleanup-coordination.sh cleanup "/tmp/claude-agents/coord-test-1640995200-abc12345"

# Cleanup old sessions (older than 24 hours)
./cleanup-coordination.sh cleanup-old "/tmp/claude-agents" 24
```

## Directory Integration

### Environment Variables

```bash
# Set in agent environment
export CLAUDE_COORDINATION_DIR="/tmp/claude-agents/coord-test-1640995200-abc12345"
export CLAUDE_AGENT_ID="test-fixer-001"
export CLAUDE_SESSION_ID="coord-test-1640995200-abc12345"

# Use in agent scripts
INBOX_DIR="${CLAUDE_COORDINATION_DIR}/messages/inbox"
RESULTS_DIR="${CLAUDE_COORDINATION_DIR}/results"
STATE_DIR="${CLAUDE_COORDINATION_DIR}/state"
```

### Configuration Template

```json
{
  "coordination": {
    "base_dir": "/tmp/claude-agents",
    "session_timeout": 3600,
    "health_check_interval": 30,
    "cleanup_retention": 86400,
    "archive_location": "/tmp/claude-agents-archive"
  },
  "message_bus": {
    "poll_interval": 1000,
    "retry_attempts": 3,
    "dead_letter_threshold": 5
  },
  "monitoring": {
    "metrics_interval": 10,
    "log_rotation": true,
    "performance_tracking": true
  }
}
```

## Quality Gates

**Directory Setup:**
- [ ] All required directories created
- [ ] Permissions set correctly
- [ ] Initial files populated
- [ ] Session metadata complete

**Health Monitoring:**
- [ ] Agent health checks functional
- [ ] Directory integrity verified
- [ ] Permission validation working
- [ ] Unhealthy agents detected

**Cleanup Management:**
- [ ] Archive creation successful
- [ ] Session cleanup complete
- [ ] Old sessions removed
- [ ] Data retention policies enforced

**Integration:**
- [ ] Environment variables set
- [ ] Configuration loaded
- [ ] Scripts executable
- [ ] Error handling robust

The directory setup system provides a robust foundation for inter-agent coordination with proper lifecycle management, health monitoring, and cleanup procedures.