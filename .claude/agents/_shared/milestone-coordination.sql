-- Milestone Agent Coordination Database Schema
-- SQLite database for persistent agent state management and coordination
-- Replaces temporary file-based agent communication

PRAGMA foreign_keys = ON;
PRAGMA journal_mode = WAL;
PRAGMA synchronous = NORMAL;

-- Agent Registry: Track all spawned milestone agents
CREATE TABLE IF NOT EXISTS agent_registry (
    agent_id TEXT PRIMARY KEY,
    agent_type TEXT NOT NULL CHECK (agent_type IN (
        'milestone-coordinator', 'milestone-executor', 'milestone-planner',
        'scope-analyzer', 'estimation-agent', 'risk-assessor',
        'kiro-strategy', 'planning-aggregator', 'design-phase',
        'spec-phase', 'task-phase', 'execute-phase', 'git-operations',
        'progress-tracker', 'validation', 'feature-implementation',
        'retry-handler'
    )),
    milestone_id TEXT,
    session_id TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'spawning' CHECK (status IN (
        'spawning', 'active', 'paused', 'completed', 'failed', 'terminated'
    )),
    capabilities TEXT NOT NULL, -- JSON array of agent capabilities
    current_task TEXT,
    current_phase TEXT CHECK (current_phase IN ('design', 'spec', 'task', 'execute')),
    resource_usage TEXT, -- JSON: {cpu_percent, memory_mb, active_threads}
    spawn_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_heartbeat DATETIME DEFAULT CURRENT_TIMESTAMP,
    completion_time DATETIME,
    error_info TEXT, -- JSON: {error_type, message, stack_trace}
    
    -- Foreign key relationships
    FOREIGN KEY (milestone_id) REFERENCES milestones(id),
    FOREIGN KEY (session_id) REFERENCES coordination_sessions(session_id)
);

-- Inter-Agent Messages: Replace /tmp file-based messaging
CREATE TABLE IF NOT EXISTS agent_messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id TEXT NOT NULL,
    from_agent TEXT NOT NULL,
    to_agent TEXT, -- NULL for broadcast messages
    message_type TEXT NOT NULL CHECK (message_type IN (
        'task_assignment', 'progress_update', 'coordination_request',
        'validation_result', 'error_report', 'completion_notification',
        'resource_request', 'phase_transition', 'checkpoint_created'
    )),
    payload TEXT NOT NULL, -- JSON message payload
    priority INTEGER DEFAULT 5 CHECK (priority BETWEEN 1 AND 10),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    delivered_at DATETIME,
    processed_at DATETIME,
    acknowledged_at DATETIME,
    retry_count INTEGER DEFAULT 0,
    
    FOREIGN KEY (session_id) REFERENCES coordination_sessions(session_id),
    FOREIGN KEY (from_agent) REFERENCES agent_registry(agent_id),
    FOREIGN KEY (to_agent) REFERENCES agent_registry(agent_id)
);

-- Agent State: Persistent state storage replacing /tmp files
CREATE TABLE IF NOT EXISTS agent_state (
    agent_id TEXT NOT NULL,
    state_key TEXT NOT NULL,
    state_value TEXT NOT NULL, -- JSON serialized state
    state_type TEXT DEFAULT 'user_data' CHECK (state_type IN (
        'checkpoint', 'progress', 'user_data', 'system_data', 'temp_data'
    )),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME, -- NULL for persistent data, timestamp for temporary
    
    PRIMARY KEY (agent_id, state_key),
    FOREIGN KEY (agent_id) REFERENCES agent_registry(agent_id)
);

-- Coordination Sessions: Session management and recovery
CREATE TABLE IF NOT EXISTS coordination_sessions (
    session_id TEXT PRIMARY KEY,
    milestone_id TEXT NOT NULL,
    operation_type TEXT NOT NULL CHECK (operation_type IN (
        'planning', 'execution', 'validation', 'recovery', 'cleanup'
    )),
    phase TEXT CHECK (phase IN ('design', 'spec', 'task', 'execute')),
    coordinator_id TEXT,
    agent_count INTEGER DEFAULT 0,
    progress INTEGER DEFAULT 0 CHECK (progress BETWEEN 0 AND 100),
    status TEXT DEFAULT 'active' CHECK (status IN (
        'active', 'paused', 'completed', 'failed', 'abandoned'
    )),
    configuration TEXT, -- JSON session configuration
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_activity DATETIME DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME,
    
    FOREIGN KEY (milestone_id) REFERENCES milestones(id),
    FOREIGN KEY (coordinator_id) REFERENCES agent_registry(agent_id)
);

-- Agent Checkpoints: Recovery points for agent state
CREATE TABLE IF NOT EXISTS agent_checkpoints (
    checkpoint_id TEXT PRIMARY KEY,
    agent_id TEXT NOT NULL,
    session_id TEXT NOT NULL,
    checkpoint_type TEXT DEFAULT 'automatic' CHECK (checkpoint_type IN (
        'automatic', 'manual', 'phase_completion', 'error_recovery'
    )),
    state_snapshot TEXT NOT NULL, -- Complete JSON state snapshot
    file_references TEXT, -- JSON array of file paths referenced in state
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    retention_until DATETIME, -- Automatic cleanup date
    
    FOREIGN KEY (agent_id) REFERENCES agent_registry(agent_id),
    FOREIGN KEY (session_id) REFERENCES coordination_sessions(session_id)
);

-- Agent Health Monitoring
CREATE TABLE IF NOT EXISTS agent_health_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    agent_id TEXT NOT NULL,
    health_status TEXT NOT NULL CHECK (health_status IN (
        'healthy', 'degraded', 'failing', 'unresponsive'
    )),
    cpu_usage REAL,
    memory_usage REAL,
    response_time REAL, -- milliseconds
    error_count INTEGER DEFAULT 0,
    warning_count INTEGER DEFAULT 0,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (agent_id) REFERENCES agent_registry(agent_id)
);

-- Performance Indexes
CREATE INDEX IF NOT EXISTS idx_agents_session_status ON agent_registry(session_id, status);
CREATE INDEX IF NOT EXISTS idx_agents_milestone ON agent_registry(milestone_id);
CREATE INDEX IF NOT EXISTS idx_agents_heartbeat ON agent_registry(last_heartbeat, status);
CREATE INDEX IF NOT EXISTS idx_agents_type_status ON agent_registry(agent_type, status);

CREATE INDEX IF NOT EXISTS idx_messages_recipient ON agent_messages(to_agent, processed_at);
CREATE INDEX IF NOT EXISTS idx_messages_session ON agent_messages(session_id, created_at);
CREATE INDEX IF NOT EXISTS idx_messages_priority ON agent_messages(priority DESC, created_at);
CREATE INDEX IF NOT EXISTS idx_messages_unprocessed ON agent_messages(processed_at) WHERE processed_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_state_agent_updated ON agent_state(agent_id, updated_at);
CREATE INDEX IF NOT EXISTS idx_state_expires ON agent_state(expires_at) WHERE expires_at IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_sessions_milestone ON coordination_sessions(milestone_id, status);
CREATE INDEX IF NOT EXISTS idx_sessions_active ON coordination_sessions(last_activity) WHERE status = 'active';

CREATE INDEX IF NOT EXISTS idx_checkpoints_agent ON agent_checkpoints(agent_id, created_at);
CREATE INDEX IF NOT EXISTS idx_checkpoints_retention ON agent_checkpoints(retention_until) WHERE retention_until IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_health_agent_time ON agent_health_log(agent_id, timestamp);

-- Views for Common Queries
CREATE VIEW IF NOT EXISTS active_agents AS
SELECT 
    ar.agent_id,
    ar.agent_type,
    ar.milestone_id,
    ar.session_id,
    ar.current_task,
    ar.current_phase,
    ar.spawn_time,
    ar.last_heartbeat,
    cs.operation_type,
    (julianday('now') - julianday(ar.last_heartbeat)) * 24 * 60 AS minutes_since_heartbeat
FROM agent_registry ar
JOIN coordination_sessions cs ON ar.session_id = cs.session_id
WHERE ar.status = 'active'
  AND cs.status = 'active';

CREATE VIEW IF NOT EXISTS agent_message_queue AS
SELECT 
    am.id,
    am.to_agent,
    am.message_type,
    am.priority,
    am.created_at,
    (julianday('now') - julianday(am.created_at)) * 24 * 60 AS age_minutes
FROM agent_messages am
WHERE am.processed_at IS NULL
ORDER BY am.priority DESC, am.created_at ASC;

CREATE VIEW IF NOT EXISTS stale_sessions AS
SELECT 
    cs.session_id,
    cs.milestone_id,
    cs.operation_type,
    cs.agent_count,
    cs.last_activity,
    (julianday('now') - julianday(cs.last_activity)) * 24 * 60 AS minutes_inactive
FROM coordination_sessions cs
WHERE cs.status = 'active'
  AND (julianday('now') - julianday(cs.last_activity)) * 24 * 60 > 30; -- 30+ minutes inactive

-- Triggers for Automatic Maintenance
CREATE TRIGGER IF NOT EXISTS update_session_activity
    AFTER UPDATE ON agent_registry
    WHEN NEW.last_heartbeat > OLD.last_heartbeat
BEGIN
    UPDATE coordination_sessions 
    SET last_activity = NEW.last_heartbeat
    WHERE session_id = NEW.session_id;
END;

CREATE TRIGGER IF NOT EXISTS cleanup_expired_state
    AFTER INSERT ON agent_state
    WHEN NEW.expires_at IS NOT NULL
BEGIN
    DELETE FROM agent_state 
    WHERE expires_at < datetime('now');
END;

CREATE TRIGGER IF NOT EXISTS cleanup_old_checkpoints
    AFTER INSERT ON agent_checkpoints
BEGIN
    DELETE FROM agent_checkpoints 
    WHERE retention_until < datetime('now');
END;

-- Initialize configuration table
CREATE TABLE IF NOT EXISTS agent_coordination_config (
    key TEXT PRIMARY KEY,
    value TEXT NOT NULL,
    description TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

INSERT OR REPLACE INTO agent_coordination_config VALUES
    ('heartbeat_interval', '30', 'Agent heartbeat interval in seconds', datetime('now')),
    ('message_retry_limit', '3', 'Maximum retry attempts for agent messages', datetime('now')),
    ('session_timeout', '1800', 'Session timeout in seconds (30 minutes)', datetime('now')),
    ('checkpoint_interval', '300', 'Automatic checkpoint interval in seconds', datetime('now')),
    ('health_check_interval', '60', 'Health check interval in seconds', datetime('now')),
    ('cleanup_retention_days', '7', 'Number of days to retain completed sessions', datetime('now'));

-- ========================================
-- MEMORY FOUNDATION INTELLIGENCE TABLES
-- ========================================
-- Enhanced schema for persistent memory system and agent intelligence
-- Milestone 001: Memory System Foundation
-- Added: 2025-08-18 for selective intelligence capabilities

-- Pattern Memory: Store learned patterns and decision contexts
CREATE TABLE IF NOT EXISTS pattern_memory (
    pattern_id TEXT PRIMARY KEY,
    pattern_type TEXT NOT NULL CHECK (pattern_type IN (
        'command_sequence', 'error_resolution', 'optimization_strategy',
        'user_preference', 'context_pattern', 'success_pattern', 'failure_pattern'
    )),
    context_hash TEXT NOT NULL, -- SHA256 of normalized context
    pattern_data TEXT NOT NULL, -- JSON: detailed pattern information
    confidence_score REAL NOT NULL CHECK (confidence_score BETWEEN 0.0 AND 1.0),
    success_count INTEGER DEFAULT 0,
    failure_count INTEGER DEFAULT 0,
    last_used_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    retention_policy TEXT DEFAULT 'adaptive' CHECK (retention_policy IN ('persistent', 'adaptive', 'temporary'))
);

-- Decision Context: Store context for intelligent decision making
CREATE TABLE IF NOT EXISTS decision_contexts (
    context_id TEXT PRIMARY KEY,
    milestone_id TEXT,
    session_id TEXT,
    operation_type TEXT NOT NULL,
    context_data TEXT NOT NULL, -- JSON: complete context snapshot
    decisions_made TEXT NOT NULL, -- JSON array: decisions and outcomes
    effectiveness_score REAL CHECK (effectiveness_score BETWEEN 0.0 AND 1.0),
    learning_value TEXT, -- JSON: extracted learning insights
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (milestone_id) REFERENCES milestones(id),
    FOREIGN KEY (session_id) REFERENCES coordination_sessions(session_id)
);

-- Agent Learning: Track agent behavior and improvement over time
CREATE TABLE IF NOT EXISTS agent_learning (
    learning_id INTEGER PRIMARY KEY AUTOINCREMENT,
    agent_id TEXT NOT NULL,
    agent_type TEXT NOT NULL,
    learning_category TEXT NOT NULL CHECK (learning_category IN (
        'task_optimization', 'error_prevention', 'resource_usage',
        'collaboration_pattern', 'user_interaction', 'performance_tuning'
    )),
    before_state TEXT NOT NULL, -- JSON: state before learning
    after_state TEXT NOT NULL,  -- JSON: state after learning
    improvement_metric REAL,
    learning_source TEXT NOT NULL CHECK (learning_source IN (
        'success_analysis', 'failure_analysis', 'pattern_recognition',
        'user_feedback', 'performance_monitoring', 'collaborative_insight'
    )),
    confidence_level REAL CHECK (confidence_level BETWEEN 0.0 AND 1.0),
    applied_count INTEGER DEFAULT 0,
    validation_status TEXT DEFAULT 'pending' CHECK (validation_status IN (
        'pending', 'validated', 'invalidated', 'requires_revalidation'
    )),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_applied_at DATETIME,
    
    FOREIGN KEY (agent_id) REFERENCES agent_registry(agent_id)
);

-- Memory Patterns: Store frequently accessed patterns for quick retrieval
CREATE TABLE IF NOT EXISTS memory_patterns (
    memory_id INTEGER PRIMARY KEY AUTOINCREMENT,
    pattern_category TEXT NOT NULL CHECK (pattern_category IN (
        'workflow_optimization', 'error_handling', 'resource_allocation',
        'timing_strategies', 'coordination_patterns', 'user_preferences'
    )),
    trigger_conditions TEXT NOT NULL, -- JSON: when to apply this pattern
    pattern_implementation TEXT NOT NULL, -- JSON: how to apply pattern
    success_indicators TEXT NOT NULL, -- JSON: metrics for success
    usage_frequency INTEGER DEFAULT 0,
    average_impact REAL DEFAULT 0.0,
    last_effectiveness_check DATETIME,
    pattern_version INTEGER DEFAULT 1,
    superseded_by INTEGER, -- References newer version of pattern
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (superseded_by) REFERENCES memory_patterns(memory_id)
);

-- Context Intelligence: Advanced context analysis and storage
CREATE TABLE IF NOT EXISTS context_intelligence (
    context_id TEXT PRIMARY KEY,
    milestone_id TEXT,
    operation_phase TEXT CHECK (operation_phase IN ('design', 'spec', 'task', 'execute')),
    context_type TEXT NOT NULL CHECK (context_type IN (
        'project_state', 'user_behavior', 'system_performance',
        'error_context', 'success_context', 'environmental_factors'
    )),
    context_snapshot TEXT NOT NULL, -- JSON: comprehensive context data
    intelligence_metadata TEXT NOT NULL, -- JSON: derived insights
    similarity_vectors TEXT, -- JSON: vector representation for similarity matching
    correlation_strength REAL CHECK (correlation_strength BETWEEN 0.0 AND 1.0),
    predictive_value REAL CHECK (predictive_value BETWEEN 0.0 AND 1.0),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_referenced_at DATETIME,
    
    FOREIGN KEY (milestone_id) REFERENCES milestones(id)
);

-- Memory Performance Indexes for Intelligence Operations
CREATE INDEX IF NOT EXISTS idx_patterns_type_confidence ON pattern_memory(pattern_type, confidence_score DESC);
CREATE INDEX IF NOT EXISTS idx_patterns_context_hash ON pattern_memory(context_hash);
CREATE INDEX IF NOT EXISTS idx_patterns_usage ON pattern_memory(last_used_at DESC, success_count DESC);

CREATE INDEX IF NOT EXISTS idx_decisions_milestone ON decision_contexts(milestone_id, created_at);
CREATE INDEX IF NOT EXISTS idx_decisions_operation ON decision_contexts(operation_type, effectiveness_score DESC);
CREATE INDEX IF NOT EXISTS idx_decisions_session ON decision_contexts(session_id);

CREATE INDEX IF NOT EXISTS idx_learning_agent_category ON agent_learning(agent_id, learning_category);
CREATE INDEX IF NOT EXISTS idx_learning_validation ON agent_learning(validation_status, confidence_level DESC);
CREATE INDEX IF NOT EXISTS idx_learning_applied ON agent_learning(applied_count DESC, improvement_metric DESC);

CREATE INDEX IF NOT EXISTS idx_memory_patterns_category ON memory_patterns(pattern_category, usage_frequency DESC);
CREATE INDEX IF NOT EXISTS idx_memory_patterns_effectiveness ON memory_patterns(average_impact DESC, usage_frequency DESC);
CREATE INDEX IF NOT EXISTS idx_memory_patterns_version ON memory_patterns(pattern_version) WHERE superseded_by IS NULL;

CREATE INDEX IF NOT EXISTS idx_context_milestone_phase ON context_intelligence(milestone_id, operation_phase);
CREATE INDEX IF NOT EXISTS idx_context_type_correlation ON context_intelligence(context_type, correlation_strength DESC);
CREATE INDEX IF NOT EXISTS idx_context_predictive ON context_intelligence(predictive_value DESC, last_referenced_at DESC);

-- Intelligence Views for Common Memory Operations
CREATE VIEW IF NOT EXISTS active_patterns AS
SELECT 
    pm.pattern_id,
    pm.pattern_type,
    pm.confidence_score,
    pm.success_count,
    pm.failure_count,
    CASE 
        WHEN pm.failure_count = 0 THEN 1.0
        ELSE CAST(pm.success_count AS REAL) / (pm.success_count + pm.failure_count)
    END as success_rate,
    (julianday('now') - julianday(pm.last_used_at)) * 24 * 60 AS minutes_since_used
FROM pattern_memory pm
WHERE pm.confidence_score > 0.6
  AND (pm.retention_policy != 'temporary' OR pm.last_used_at > datetime('now', '-7 days'))
ORDER BY pm.confidence_score DESC, success_rate DESC;

CREATE VIEW IF NOT EXISTS learning_summary AS
SELECT 
    al.agent_type,
    al.learning_category,
    COUNT(*) as learning_count,
    AVG(al.improvement_metric) as avg_improvement,
    AVG(al.confidence_level) as avg_confidence,
    COUNT(CASE WHEN al.validation_status = 'validated' THEN 1 END) as validated_learnings
FROM agent_learning al
WHERE al.created_at > datetime('now', '-30 days')
GROUP BY al.agent_type, al.learning_category
ORDER BY learning_count DESC, avg_improvement DESC;

CREATE VIEW IF NOT EXISTS memory_effectiveness AS
SELECT 
    mp.pattern_category,
    COUNT(*) as pattern_count,
    AVG(mp.usage_frequency) as avg_usage,
    AVG(mp.average_impact) as avg_impact,
    COUNT(CASE WHEN mp.superseded_by IS NULL THEN 1 END) as active_patterns
FROM memory_patterns mp
GROUP BY mp.pattern_category
ORDER BY avg_impact DESC, avg_usage DESC;

-- Triggers for Memory Intelligence Maintenance
CREATE TRIGGER IF NOT EXISTS update_pattern_usage
    AFTER UPDATE ON pattern_memory
    WHEN NEW.last_used_at > OLD.last_used_at
BEGIN
    UPDATE pattern_memory 
    SET updated_at = CURRENT_TIMESTAMP
    WHERE pattern_id = NEW.pattern_id;
END;

CREATE TRIGGER IF NOT EXISTS cleanup_temporary_patterns
    AFTER INSERT ON pattern_memory
    WHEN NEW.retention_policy = 'temporary'
BEGIN
    DELETE FROM pattern_memory 
    WHERE retention_policy = 'temporary' 
      AND last_used_at < datetime('now', '-24 hours')
      AND confidence_score < 0.3;
END;

-- Memory system configuration
INSERT OR REPLACE INTO agent_coordination_config VALUES
    ('memory_pattern_threshold', '0.6', 'Minimum confidence score for pattern activation', datetime('now')),
    ('learning_validation_threshold', '0.7', 'Confidence threshold for auto-validation of learnings', datetime('now')),
    ('context_similarity_threshold', '0.8', 'Similarity threshold for context matching', datetime('now')),
    ('memory_cleanup_interval', '3600', 'Memory cleanup interval in seconds (1 hour)', datetime('now')),
    ('pattern_retention_days', '30', 'Days to retain low-confidence patterns', datetime('now')),
    ('learning_batch_size', '100', 'Batch size for learning operations', datetime('now'));