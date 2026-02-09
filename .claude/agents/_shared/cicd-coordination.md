---
description: CI/CD pipeline coordination utilities for 4-stage parallel execution
---

# CI/CD Pipeline Coordination System

Specialized coordination utilities for CI/CD pipeline execution with true parallelism, stage management, and GitHub integration.

## Pipeline Architecture

```yaml
cicd_pipeline_structure:
  base_directory: /tmp/cicd-pipeline-{timestamp}/
  stages:
    stage-1: "Discovery (parallel file analysis)"
    stage-2: "Classification (issue categorization)"
    stage-3: "Fixing (parallel problem resolution)"
    stage-4: "Validation (comprehensive testing)"
  
  directories:
    registry: "registry/"          # Active agent tracking
    stage-1: "stage-1/"           # Discovery outputs
    stage-2: "stage-2/"           # Classification outputs  
    stage-3: "stage-3/"           # Fixing outputs
    stage-4: "stage-4/"           # Validation outputs
    state: "state/"               # Shared state files
    locks: "locks/"               # Coordination locks
    logs: "logs/"                 # Execution logs
    results: "results/"           # Final aggregated results
    github: "github/"             # GitHub integration data
```

## Session Management

### CI/CD Session Initialization

```bash
# Initialize CI/CD pipeline session
init_cicd_session() {
  local timestamp=$(date +%s)
  local session_id="cicd-pipeline-${timestamp}"
  local session_dir="/tmp/${session_id}"
  
  # Create CI/CD specific structure
  mkdir -p "${session_dir}"/{registry,stage-{1..4},state,locks,logs,results,github}
  
  # Initialize session metadata
  cat > "${session_dir}/session.json" << EOF
{
  "id": "${session_id}",
  "type": "cicd-pipeline",
  "started_at": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
  "status": "initializing",
  "current_stage": 0,
  "stages": {
    "1": {"name": "discovery", "status": "pending", "agents": []},
    "2": {"name": "classification", "status": "pending", "agents": []},
    "3": {"name": "fixing", "status": "pending", "agents": []},
    "4": {"name": "validation", "status": "pending", "agents": []}
  },
  "github": {
    "repository": null,
    "pull_request": null,
    "commit_sha": null
  },
  "coordinator": "$(whoami)@$(hostname)"
}
EOF
  
  # Initialize stage-specific state files
  for stage in {1..4}; do
    echo '{"tasks": [], "results": [], "status": "pending"}' > "${session_dir}/state/stage-${stage}.json"
  done
  
  # Initialize global pipeline state
  cat > "${session_dir}/state/pipeline.json" << EOF
{
  "stage": 1,
  "progress": 0,
  "total_files": 0,
  "processed_files": 0,
  "issues_found": 0,
  "issues_fixed": 0,
  "tests_passing": 0,
  "tests_failing": 0,
  "errors": [],
  "warnings": []
}
EOF
  
  echo "${session_id}"
}
```

### Stage Progression Controls

```javascript
class CICDStageManager {
  constructor(sessionDir) {
    this.sessionDir = sessionDir;
    this.state = new SharedState(sessionDir);
  }
  
  async startStage(stageNumber) {
    const session = await this.state.read('session');
    
    if (session.current_stage >= stageNumber) {
      throw new Error(`Stage ${stageNumber} already started or completed`);
    }
    
    // Update session state
    await this.state.update('session', session => {
      session.current_stage = stageNumber;
      session.stages[stageNumber].status = 'running';
      session.stages[stageNumber].started_at = new Date().toISOString();
      return session;
    });
    
    // Initialize stage state
    await this.state.update(`stage-${stageNumber}`, stage => {
      stage.status = 'running';
      stage.started_at = new Date().toISOString();
      return stage;
    });
    
    this.logStageEvent(stageNumber, 'started');
  }
  
  async completeStage(stageNumber) {
    // Verify all tasks in stage are complete
    const stage = await this.state.read(`stage-${stageNumber}`);
    const pendingTasks = stage.tasks.filter(t => t.status !== 'completed' && t.status !== 'failed');
    
    if (pendingTasks.length > 0) {
      throw new Error(`Cannot complete stage ${stageNumber}: ${pendingTasks.length} tasks still pending`);
    }
    
    // Mark stage as completed
    await this.state.update('session', session => {
      session.stages[stageNumber].status = 'completed';
      session.stages[stageNumber].completed_at = new Date().toISOString();
      return session;
    });
    
    await this.state.update(`stage-${stageNumber}`, stage => {
      stage.status = 'completed';
      stage.completed_at = new Date().toISOString();
      return stage;
    });
    
    this.logStageEvent(stageNumber, 'completed');
    
    // Auto-start next stage if ready
    if (stageNumber < 4) {
      await this.checkStageReadiness(stageNumber + 1);
    }
  }
  
  async checkStageReadiness(stageNumber) {
    const session = await this.state.read('session');
    const previousStage = stageNumber - 1;
    
    // Stage 1 can start immediately
    if (stageNumber === 1) {
      return true;
    }
    
    // Other stages require previous stage completion
    if (session.stages[previousStage].status === 'completed') {
      await this.startStage(stageNumber);
      return true;
    }
    
    return false;
  }
  
  logStageEvent(stageNumber, event) {
    const logEntry = {
      timestamp: new Date().toISOString(),
      stage: stageNumber,
      event: event,
      details: {}
    };
    
    fs.appendFileSync(
      `${this.sessionDir}/logs/pipeline.log`,
      JSON.stringify(logEntry) + '\n'
    );
  }
}
```

## Agent Health Monitoring

### Enhanced Agent Registry for CI/CD

```javascript
class CICDAgentRegistry extends AgentRegistry {
  constructor(sessionDir) {
    super(sessionDir);
    this.healthCheckInterval = 5000; // 5 seconds
    this.startHealthMonitoring();
  }
  
  register(agent) {
    const registration = super.register(agent);
    
    // Add CI/CD specific properties
    registration.stage = agent.stage;
    registration.task_capacity = agent.taskCapacity || 5;
    registration.current_tasks = 0;
    registration.performance = {
      tasks_completed: 0,
      tasks_failed: 0,
      avg_task_time: 0,
      last_heartbeat: new Date().toISOString()
    };
    
    this.persist();
    return registration;
  }
  
  async heartbeat(agentId, metrics = {}) {
    await this.update(agentId, {
      'performance.last_heartbeat': new Date().toISOString(),
      ...metrics
    });
  }
  
  startHealthMonitoring() {
    setInterval(async () => {
      const agents = this.getActiveAgents();
      const now = new Date();
      
      for (const agent of agents) {
        const lastHeartbeat = new Date(agent.performance.last_heartbeat);
        const timeSinceHeartbeat = now - lastHeartbeat;
        
        // Mark as unhealthy if no heartbeat for 30 seconds
        if (timeSinceHeartbeat > 30000) {
          await this.update(agent.id, {
            status: 'unhealthy',
            health_issue: 'No heartbeat received'
          });
          
          this.logHealthEvent(agent.id, 'unhealthy', 'No heartbeat received');
        }
        
        // Check if process is still running
        try {
          process.kill(agent.pid, 0);
        } catch (error) {
          await this.update(agent.id, {
            status: 'failed',
            health_issue: 'Process not found'
          });
          
          this.logHealthEvent(agent.id, 'failed', 'Process not found');
        }
      }
    }, this.healthCheckInterval);
  }
  
  getAgentsByStage(stageNumber, status = 'active') {
    return this.getActiveAgents()
      .filter(a => a.stage === stageNumber && a.status === status);
  }
  
  getAvailableAgent(stageNumber) {
    const agents = this.getAgentsByStage(stageNumber, 'active');
    return agents.find(a => a.current_tasks < a.task_capacity) || null;
  }
  
  logHealthEvent(agentId, status, details) {
    const logEntry = {
      timestamp: new Date().toISOString(),
      type: 'agent_health',
      agent_id: agentId,
      status: status,
      details: details
    };
    
    fs.appendFileSync(
      `${this.sessionDir}/logs/health.log`,
      JSON.stringify(logEntry) + '\n'
    );
  }
}
```

## Inter-Stage Data Passing

### Stage Data Transfer

```javascript
class StageDataTransfer {
  constructor(sessionDir) {
    this.sessionDir = sessionDir;
    this.state = new SharedState(sessionDir);
  }
  
  // Transfer discovery results to classification stage
  async transferDiscoveryResults() {
    const discoveryResults = await this.collectStageResults(1);
    
    // Prepare classification tasks
    const classificationTasks = discoveryResults.files.map(file => ({
      id: `classify-${file.path.replace(/[^a-zA-Z0-9]/g, '-')}`,
      type: 'classification',
      stage: 2,
      file: file,
      issues: file.issues || [],
      priority: this.calculatePriority(file),
      created_at: new Date().toISOString()
    }));
    
    // Store in stage-2 state
    await this.state.update('stage-2', stage => {
      stage.tasks.push(...classificationTasks);
      stage.input_data = {
        source: 'stage-1',
        files_count: discoveryResults.files.length,
        total_issues: discoveryResults.files.reduce((sum, f) => sum + (f.issues?.length || 0), 0)
      };
      return stage;
    });
    
    return classificationTasks;
  }
  
  // Transfer classification results to fixing stage
  async transferClassificationResults() {
    const classificationResults = await this.collectStageResults(2);
    
    // Group issues by type and priority for parallel fixing
    const fixingTasks = this.groupIssuesForFixing(classificationResults.classified_issues);
    
    await this.state.update('stage-3', stage => {
      stage.tasks.push(...fixingTasks);
      stage.input_data = {
        source: 'stage-2',
        classified_issues: classificationResults.classified_issues.length,
        fix_groups: fixingTasks.length
      };
      return stage;
    });
    
    return fixingTasks;
  }
  
  // Transfer fixing results to validation stage
  async transferFixingResults() {
    const fixingResults = await this.collectStageResults(3);
    
    // Prepare comprehensive validation tasks
    const validationTasks = [
      {
        id: 'validate-syntax',
        type: 'syntax-validation',
        stage: 4,
        files: fixingResults.modified_files,
        priority: 'high'
      },
      {
        id: 'validate-tests',
        type: 'test-validation',
        stage: 4,
        test_files: fixingResults.test_files,
        priority: 'high'
      },
      {
        id: 'validate-integration',
        type: 'integration-validation',
        stage: 4,
        components: fixingResults.affected_components,
        priority: 'medium'
      }
    ];
    
    await this.state.update('stage-4', stage => {
      stage.tasks.push(...validationTasks);
      stage.input_data = {
        source: 'stage-3',
        fixes_applied: fixingResults.fixes_applied,
        files_modified: fixingResults.modified_files.length
      };
      return stage;
    });
    
    return validationTasks;
  }
  
  async collectStageResults(stageNumber) {
    const stageDir = `${this.sessionDir}/stage-${stageNumber}`;
    const resultFiles = await fs.readdir(stageDir);
    
    const results = {
      stage: stageNumber,
      files: [],
      classified_issues: [],
      fixes_applied: [],
      modified_files: [],
      test_results: []
    };
    
    for (const file of resultFiles) {
      if (file.endsWith('.json')) {
        const data = JSON.parse(await fs.readFile(`${stageDir}/${file}`, 'utf8'));
        
        // Merge results based on stage
        switch (stageNumber) {
          case 1: // Discovery
            if (data.files) results.files.push(...data.files);
            break;
          case 2: // Classification
            if (data.classified_issues) results.classified_issues.push(...data.classified_issues);
            break;
          case 3: // Fixing
            if (data.fixes_applied) results.fixes_applied.push(...data.fixes_applied);
            if (data.modified_files) results.modified_files.push(...data.modified_files);
            break;
          case 4: // Validation
            if (data.test_results) results.test_results.push(...data.test_results);
            break;
        }
      }
    }
    
    return results;
  }
  
  calculatePriority(file) {
    const highPriorityPatterns = [/test/, /spec/, /config/, /package\.json/];
    const mediumPriorityPatterns = [/src/, /lib/, /components/];
    
    if (highPriorityPatterns.some(pattern => pattern.test(file.path))) {
      return 'high';
    } else if (mediumPriorityPatterns.some(pattern => pattern.test(file.path))) {
      return 'medium';
    }
    return 'low';
  }
  
  groupIssuesForFixing(classifiedIssues) {
    const groups = new Map();
    
    classifiedIssues.forEach(issue => {
      const groupKey = `${issue.category}-${issue.severity}`;
      if (!groups.has(groupKey)) {
        groups.set(groupKey, {
          id: `fix-${groupKey}`,
          type: 'parallel-fix',
          stage: 3,
          category: issue.category,
          severity: issue.severity,
          issues: [],
          priority: issue.severity === 'high' ? 'high' : 'medium'
        });
      }
      groups.get(groupKey).issues.push(issue);
    });
    
    return Array.from(groups.values());
  }
}
```

## GitHub API Integration

### GitHub Integration Helpers

```javascript
class GitHubIntegration {
  constructor(sessionDir, token) {
    this.sessionDir = sessionDir;
    this.token = token;
    this.apiBase = 'https://api.github.com';
    this.state = new SharedState(sessionDir);
  }
  
  async initializeFromPR(owner, repo, pullNumber) {
    const pr = await this.fetchPullRequest(owner, repo, pullNumber);
    const files = await this.fetchPullRequestFiles(owner, repo, pullNumber);
    
    // Store GitHub context
    await this.state.write('github', {
      repository: `${owner}/${repo}`,
      pull_request: {
        number: pullNumber,
        title: pr.title,
        base_branch: pr.base.ref,
        head_branch: pr.head.ref,
        commit_sha: pr.head.sha
      },
      files: files,
      initialized_at: new Date().toISOString()
    });
    
    return { pr, files };
  }
  
  async fetchPullRequest(owner, repo, pullNumber) {
    const response = await this.apiRequest(
      `GET`,
      `/repos/${owner}/${repo}/pulls/${pullNumber}`
    );
    return response;
  }
  
  async fetchPullRequestFiles(owner, repo, pullNumber) {
    const response = await this.apiRequest(
      `GET`,
      `/repos/${owner}/${repo}/pulls/${pullNumber}/files`
    );
    return response;
  }
  
  async postComment(owner, repo, pullNumber, body) {
    return await this.apiRequest(
      `POST`,
      `/repos/${owner}/${repo}/issues/${pullNumber}/comments`,
      { body }
    );
  }
  
  async updateCommitStatus(owner, repo, sha, state, description, context = 'cicd-pipeline') {
    return await this.apiRequest(
      `POST`,
      `/repos/${owner}/${repo}/statuses/${sha}`,
      {
        state, // pending, success, failure, error
        description,
        context
      }
    );
  }
  
  async createReview(owner, repo, pullNumber, event, body, comments = []) {
    return await this.apiRequest(
      `POST`,
      `/repos/${owner}/${repo}/pulls/${pullNumber}/reviews`,
      {
        event, // APPROVE, REQUEST_CHANGES, COMMENT
        body,
        comments
      }
    );
  }
  
  async apiRequest(method, path, body = null) {
    const url = `${this.apiBase}${path}`;
    const options = {
      method,
      headers: {
        'Authorization': `token ${this.token}`,
        'Accept': 'application/vnd.github.v3+json',
        'User-Agent': 'CICD-Pipeline-Bot'
      }
    };
    
    if (body) {
      options.headers['Content-Type'] = 'application/json';
      options.body = JSON.stringify(body);
    }
    
    const response = await fetch(url, options);
    
    if (!response.ok) {
      throw new Error(`GitHub API error: ${response.status} ${response.statusText}`);
    }
    
    return await response.json();
  }
  
  // Report pipeline progress to GitHub
  async reportProgress(stage, status, details = {}) {
    const github = await this.state.read('github');
    if (!github?.pull_request) return;
    
    const { repository, pull_request } = github;
    const [owner, repo] = repository.split('/');
    
    const statusMap = {
      'running': 'pending',
      'completed': 'success',
      'failed': 'failure'
    };
    
    await this.updateCommitStatus(
      owner,
      repo,
      pull_request.commit_sha,
      statusMap[status] || 'pending',
      `Stage ${stage}: ${details.description || status}`,
      `cicd-pipeline/stage-${stage}`
    );
  }
  
  // Generate final report as PR comment
  async postFinalReport(results) {
    const github = await this.state.read('github');
    if (!github?.pull_request) return;
    
    const { repository, pull_request } = github;
    const [owner, repo] = repository.split('/');
    
    const report = this.generateReportMarkdown(results);
    
    await this.postComment(owner, repo, pull_request.number, report);
  }
  
  generateReportMarkdown(results) {
    const { summary, stages } = results;
    
    return `## 🔄 CI/CD Pipeline Results
    
**Overall Status:** ${summary.success ? '✅ Success' : '❌ Failed'}
**Duration:** ${summary.total_time}s
**Files Processed:** ${summary.files_processed}

### Stage Results

${Object.entries(stages).map(([stage, data]) => `
**Stage ${stage} (${data.name}):** ${data.status === 'completed' ? '✅' : '❌'}
- Tasks: ${data.tasks_completed}/${data.total_tasks}
- Duration: ${data.duration}s
${data.issues_found ? `- Issues Found: ${data.issues_found}` : ''}
${data.issues_fixed ? `- Issues Fixed: ${data.issues_fixed}` : ''}
`).join('\n')}

### 📊 Performance Metrics
- **Throughput:** ${summary.throughput} tasks/second
- **Success Rate:** ${summary.success_rate}%
- **Agent Utilization:** ${summary.agent_utilization}%

${results.errors.length > 0 ? `
### ⚠️ Errors
${results.errors.map(error => `- ${error}`).join('\n')}
` : ''}

*Generated by CI/CD Pipeline Bot*`;
  }
}
```

## Error Propagation and Rollback

### Error Handling System

```javascript
class CICDErrorHandler {
  constructor(sessionDir) {
    this.sessionDir = sessionDir;
    this.state = new SharedState(sessionDir);
  }
  
  async handleStageError(stageNumber, error, agentId = null) {
    const errorRecord = {
      id: `error-${Date.now()}`,
      stage: stageNumber,
      agent_id: agentId,
      error: {
        message: error.message,
        stack: error.stack,
        type: error.constructor.name
      },
      timestamp: new Date().toISOString(),
      severity: this.categorizeError(error)
    };
    
    // Log error
    await this.logError(errorRecord);
    
    // Update pipeline state
    await this.state.update('pipeline', pipeline => {
      pipeline.errors.push(errorRecord);
      return pipeline;
    });
    
    // Determine if rollback is needed
    if (errorRecord.severity === 'critical') {
      await this.initiateRollback(stageNumber, errorRecord);
    }
    
    return errorRecord;
  }
  
  async initiateRollback(stageNumber, errorRecord) {
    console.log(`Initiating rollback for stage ${stageNumber} due to critical error`);
    
    // Mark current and subsequent stages as failed
    for (let stage = stageNumber; stage <= 4; stage++) {
      await this.state.update('session', session => {
        session.stages[stage].status = 'failed';
        session.stages[stage].failed_at = new Date().toISOString();
        session.stages[stage].failure_reason = errorRecord.id;
        return session;
      });
    }
    
    // Stop all agents in affected stages
    const registry = new CICDAgentRegistry(this.sessionDir);
    for (let stage = stageNumber; stage <= 4; stage++) {
      const agents = registry.getAgentsByStage(stage, 'active');
      for (const agent of agents) {
        await registry.update(agent.id, { status: 'terminated', termination_reason: 'rollback' });
      }
    }
    
    // Create rollback snapshot
    await this.createRollbackSnapshot(stageNumber, errorRecord);
  }
  
  async createRollbackSnapshot(stageNumber, errorRecord) {
    const snapshot = {
      rollback_id: `rollback-${Date.now()}`,
      triggered_by: errorRecord.id,
      stage: stageNumber,
      timestamp: new Date().toISOString(),
      state_backup: {}
    };
    
    // Backup current state
    const stateFiles = ['pipeline', 'session'];
    for (const file of stateFiles) {
      snapshot.state_backup[file] = await this.state.read(file);
    }
    
    // Backup stage states
    for (let stage = 1; stage <= 4; stage++) {
      snapshot.state_backup[`stage-${stage}`] = await this.state.read(`stage-${stage}`);
    }
    
    await fs.writeFile(
      `${this.sessionDir}/rollback-${snapshot.rollback_id}.json`,
      JSON.stringify(snapshot, null, 2)
    );
    
    return snapshot;
  }
  
  categorizeError(error) {
    // Critical errors that require immediate rollback
    const criticalPatterns = [
      /ENOENT.*package\.json/,
      /Cannot resolve module/,
      /Syntax error/,
      /Permission denied/
    ];
    
    // Warning errors that can be retried
    const warningPatterns = [
      /timeout/i,
      /network/i,
      /temporary/i
    ];
    
    if (criticalPatterns.some(pattern => pattern.test(error.message))) {
      return 'critical';
    } else if (warningPatterns.some(pattern => pattern.test(error.message))) {
      return 'warning';
    } else {
      return 'error';
    }
  }
  
  async logError(errorRecord) {
    const logEntry = {
      timestamp: errorRecord.timestamp,
      level: 'ERROR',
      stage: errorRecord.stage,
      agent_id: errorRecord.agent_id,
      message: errorRecord.error.message,
      details: errorRecord
    };
    
    fs.appendFileSync(
      `${this.sessionDir}/logs/errors.log`,
      JSON.stringify(logEntry) + '\n'
    );
  }
}
```

## Progress Tracking and Reporting

### Real-time Progress Tracking

```javascript
class CICDProgressTracker {
  constructor(sessionDir) {
    this.sessionDir = sessionDir;
    this.state = new SharedState(sessionDir);
  }
  
  async updateProgress() {
    const pipeline = await this.state.read('pipeline');
    const session = await this.state.read('session');
    
    // Calculate overall progress
    const stageWeights = { 1: 0.2, 2: 0.2, 3: 0.4, 4: 0.2 }; // Fixing has higher weight
    let totalProgress = 0;
    
    for (const [stageNum, weight] of Object.entries(stageWeights)) {
      const stage = await this.state.read(`stage-${stageNum}`);
      const stageProgress = this.calculateStageProgress(stage);
      totalProgress += stageProgress * weight;
    }
    
    // Update pipeline state
    await this.state.update('pipeline', pipeline => {
      pipeline.progress = Math.round(totalProgress);
      pipeline.updated_at = new Date().toISOString();
      return pipeline;
    });
    
    // Generate progress report
    const report = await this.generateProgressReport();
    
    // Save progress snapshot
    await fs.writeFile(
      `${this.sessionDir}/progress-${Date.now()}.json`,
      JSON.stringify(report, null, 2)
    );
    
    return report;
  }
  
  calculateStageProgress(stage) {
    if (!stage.tasks || stage.tasks.length === 0) return 0;
    
    const completed = stage.tasks.filter(t => t.status === 'completed').length;
    const failed = stage.tasks.filter(t => t.status === 'failed').length;
    const total = stage.tasks.length;
    
    return ((completed + failed) / total) * 100;
  }
  
  async generateProgressReport() {
    const pipeline = await this.state.read('pipeline');
    const session = await this.state.read('session');
    
    const report = {
      session_id: session.id,
      timestamp: new Date().toISOString(),
      overall_progress: pipeline.progress,
      current_stage: session.current_stage,
      stages: {},
      statistics: {
        files_processed: pipeline.processed_files,
        issues_found: pipeline.issues_found,
        issues_fixed: pipeline.issues_fixed,
        tests_passing: pipeline.tests_passing,
        tests_failing: pipeline.tests_failing
      },
      performance: await this.calculatePerformanceMetrics()
    };
    
    // Add stage details
    for (let stageNum = 1; stageNum <= 4; stageNum++) {
      const stage = await this.state.read(`stage-${stageNum}`);
      const stageInfo = session.stages[stageNum];
      
      report.stages[stageNum] = {
        name: stageInfo.name,
        status: stageInfo.status,
        progress: this.calculateStageProgress(stage),
        tasks_total: stage.tasks?.length || 0,
        tasks_completed: stage.tasks?.filter(t => t.status === 'completed').length || 0,
        tasks_failed: stage.tasks?.filter(t => t.status === 'failed').length || 0,
        started_at: stageInfo.started_at,
        completed_at: stageInfo.completed_at
      };
    }
    
    return report;
  }
  
  async calculatePerformanceMetrics() {
    const registry = new CICDAgentRegistry(this.sessionDir);
    const agents = registry.getActiveAgents();
    
    const totalTasks = agents.reduce((sum, a) => sum + a.performance.tasks_completed, 0);
    const totalTime = agents.reduce((sum, a) => sum + (a.performance.avg_task_time * a.performance.tasks_completed), 0);
    const activeAgents = agents.filter(a => a.status === 'active').length;
    const totalAgents = agents.length;
    
    return {
      throughput: totalTime > 0 ? totalTasks / (totalTime / 1000) : 0,
      agent_utilization: totalAgents > 0 ? (activeAgents / totalAgents) * 100 : 0,
      avg_task_time: totalTasks > 0 ? totalTime / totalTasks : 0,
      success_rate: totalTasks > 0 ? (totalTasks - agents.reduce((sum, a) => sum + a.performance.tasks_failed, 0)) / totalTasks * 100 : 0
    };
  }
}
```

## Complete CI/CD Orchestrator

### Main Orchestrator Class

```javascript
class CICDOrchestrator {
  constructor(sessionId) {
    this.sessionId = sessionId;
    this.sessionDir = `/tmp/${sessionId}`;
    this.state = new SharedState(this.sessionDir);
    this.stageManager = new CICDStageManager(this.sessionDir);
    this.dataTransfer = new StageDataTransfer(this.sessionDir);
    this.errorHandler = new CICDErrorHandler(this.sessionDir);
    this.progressTracker = new CICDProgressTracker(this.sessionDir);
    this.registry = new CICDAgentRegistry(this.sessionDir);
  }
  
  async executePipeline(config) {
    try {
      // Initialize GitHub integration if configured
      if (config.github) {
        this.github = new GitHubIntegration(this.sessionDir, config.github.token);
        await this.github.initializeFromPR(
          config.github.owner,
          config.github.repo,
          config.github.pullNumber
        );
      }
      
      // Execute stages sequentially with internal parallelism
      for (let stage = 1; stage <= 4; stage++) {
        await this.executeStage(stage, config);
      }
      
      // Generate final report
      const results = await this.generateFinalReport();
      
      // Report to GitHub if configured
      if (this.github) {
        await this.github.postFinalReport(results);
      }
      
      return results;
      
    } catch (error) {
      await this.errorHandler.handleStageError(0, error);
      throw error;
    }
  }
  
  async executeStage(stageNumber, config) {
    console.log(`Starting stage ${stageNumber}`);
    
    await this.stageManager.startStage(stageNumber);
    
    if (this.github) {
      await this.github.reportProgress(stageNumber, 'running', {
        description: `Executing stage ${stageNumber}`
      });
    }
    
    try {
      // Transfer data from previous stage
      if (stageNumber > 1) {
        await this.transferDataToStage(stageNumber);
      }
      
      // Execute stage with parallel agents
      await this.runStageAgents(stageNumber, config);
      
      // Wait for completion
      await this.waitForStageCompletion(stageNumber);
      
      // Mark stage as completed
      await this.stageManager.completeStage(stageNumber);
      
      if (this.github) {
        await this.github.reportProgress(stageNumber, 'completed', {
          description: `Stage ${stageNumber} completed successfully`
        });
      }
      
    } catch (error) {
      await this.errorHandler.handleStageError(stageNumber, error);
      
      if (this.github) {
        await this.github.reportProgress(stageNumber, 'failed', {
          description: `Stage ${stageNumber} failed: ${error.message}`
        });
      }
      
      throw error;
    }
  }
  
  async transferDataToStage(stageNumber) {
    switch (stageNumber) {
      case 2:
        await this.dataTransfer.transferDiscoveryResults();
        break;
      case 3:
        await this.dataTransfer.transferClassificationResults();
        break;
      case 4:
        await this.dataTransfer.transferFixingResults();
        break;
    }
  }
  
  async waitForStageCompletion(stageNumber, timeout = 300000) { // 5 minutes default
    const startTime = Date.now();
    
    while (Date.now() - startTime < timeout) {
      const stage = await this.state.read(`stage-${stageNumber}`);
      const pendingTasks = stage.tasks.filter(t => 
        t.status !== 'completed' && t.status !== 'failed'
      );
      
      if (pendingTasks.length === 0) {
        return true;
      }
      
      // Update progress
      await this.progressTracker.updateProgress();
      
      await new Promise(resolve => setTimeout(resolve, 5000)); // Check every 5 seconds
    }
    
    throw new Error(`Stage ${stageNumber} timeout after ${timeout}ms`);
  }
  
  async generateFinalReport() {
    const results = {
      session_id: this.sessionId,
      completed_at: new Date().toISOString(),
      success: true,
      summary: {},
      stages: {},
      performance: {},
      errors: []
    };
    
    // Collect results from all stages
    for (let stage = 1; stage <= 4; stage++) {
      const stageResults = await this.dataTransfer.collectStageResults(stage);
      const stageState = await this.state.read(`stage-${stage}`);
      
      results.stages[stage] = {
        name: ['discovery', 'classification', 'fixing', 'validation'][stage - 1],
        status: stageState.status,
        results: stageResults,
        tasks_completed: stageState.tasks?.filter(t => t.status === 'completed').length || 0,
        total_tasks: stageState.tasks?.length || 0
      };
    }
    
    // Calculate summary
    const pipeline = await this.state.read('pipeline');
    results.summary = {
      files_processed: pipeline.processed_files,
      issues_found: pipeline.issues_found,
      issues_fixed: pipeline.issues_fixed,
      tests_passing: pipeline.tests_passing,
      tests_failing: pipeline.tests_failing,
      success: pipeline.errors.length === 0
    };
    
    results.performance = await this.progressTracker.calculatePerformanceMetrics();
    results.errors = pipeline.errors;
    results.success = pipeline.errors.filter(e => e.severity === 'critical').length === 0;
    
    // Save final report
    await fs.writeFile(
      `${this.sessionDir}/results/final-report.json`,
      JSON.stringify(results, null, 2)
    );
    
    return results;
  }
}
```

## Usage Example

```javascript
// Initialize and execute CI/CD pipeline
async function runCICDPipeline(config) {
  const sessionId = await bash('init_cicd_session');
  const orchestrator = new CICDOrchestrator(sessionId);
  
  const pipelineConfig = {
    github: {
      token: process.env.GITHUB_TOKEN,
      owner: 'myorg',
      repo: 'myrepo',
      pullNumber: 123
    },
    stages: {
      1: { maxAgents: 5, timeout: 60000 },
      2: { maxAgents: 3, timeout: 30000 },
      3: { maxAgents: 8, timeout: 180000 },
      4: { maxAgents: 4, timeout: 120000 }
    }
  };
  
  try {
    const results = await orchestrator.executePipeline(pipelineConfig);
    console.log('Pipeline completed:', results);
  } catch (error) {
    console.error('Pipeline failed:', error);
  }
}
```

## Quality Gates

**Session Initialization:**
- [ ] CI/CD session directory structure created
- [ ] Stage state files initialized
- [ ] Agent registry configured
- [ ] GitHub integration setup (if configured)

**Stage Execution:**
- [ ] Data transfer between stages successful
- [ ] Agents registered and healthy
- [ ] Progress tracking active
- [ ] Error handling operational

**Completion:**
- [ ] All stages completed successfully
- [ ] Final report generated
- [ ] GitHub status updated (if configured)
- [ ] Session archived properly

This CI/CD coordination system enables robust 4-stage pipeline execution with true parallelism, comprehensive error handling, and seamless GitHub integration.

## Persistent Monitoring Patterns

### Continuous Loop Management

```javascript
class PersistentMonitor {
  constructor(sessionDir, config = {}) {
    this.sessionDir = sessionDir;
    this.state = new SharedState(sessionDir);
    this.isRunning = false;
    this.monitorId = `monitor-${Date.now()}`;

    // Configuration with defaults
    this.config = {
      maxRetries: config.maxRetries || 10,
      baseDelay: config.baseDelay || 1000,
      maxDelay: config.maxDelay || 30000,
      strategy: config.strategy || 'exponential', // fixed, exponential, adaptive
      healthCheck: config.healthCheck || 5000,
      stateBackup: config.stateBackup || 30000,
      communicationInterval: config.communicationInterval || 2000,
      ...config
    };

    this.attempts = 0;
    this.failures = [];
    this.learnings = new Map();
    this.agentCoordination = new Map();
  }

  // Primary persistent monitoring loop
  async startMonitoring(task, conditions = {}) {
    this.isRunning = true;
    await this.initializeMonitoringSession(task, conditions);

    while (this.isRunning && this.attempts < this.config.maxRetries) {
      try {
        this.attempts++;
        await this.logAttempt('started');

        // Execute monitoring cycle
        const result = await this.executeMonitoringCycle(task, conditions);

        if (this.evaluateSuccess(result, conditions)) {
          await this.logAttempt('succeeded', result);
          return result;
        }

        // Handle partial success or continue monitoring
        await this.handlePartialSuccess(result, conditions);

      } catch (error) {
        await this.handleFailure(error);
      }

      // Calculate next attempt delay
      const delay = this.calculateDelay();
      await this.waitWithCoordination(delay);
    }

    // Max retries reached
    await this.handleMaxRetriesReached();
    throw new Error(`Monitoring failed after ${this.config.maxRetries} attempts`);
  }

  async executeMonitoringCycle(task, conditions) {
    // Update agent heartbeat
    await this.sendHeartbeat();

    // Execute the actual monitoring task
    const result = await task.execute();

    // Share state with other agents
    await this.shareState(result);

    // Learn from patterns
    await this.learnFromResult(result);

    return result;
  }

  async initializeMonitoringSession(task, conditions) {
    const session = {
      monitor_id: this.monitorId,
      task: task.name || 'unknown',
      started_at: new Date().toISOString(),
      config: this.config,
      conditions: conditions,
      status: 'running',
      agents: {}
    };

    await this.state.write(`monitoring/${this.monitorId}`, session);
    await this.logAttempt('initialized', session);
  }
}
```

### Retry Strategy Definitions

```javascript
class RetryStrategies {
  static fixed(attempt, baseDelay) {
    return baseDelay;
  }

  static exponential(attempt, baseDelay, maxDelay = 30000) {
    const delay = baseDelay * Math.pow(2, attempt - 1);
    return Math.min(delay, maxDelay);
  }

  static adaptive(attempt, baseDelay, failures, learnings) {
    // Adaptive strategy based on failure patterns and learnings
    let multiplier = 1;

    // Analyze recent failure patterns
    const recentFailures = failures.slice(-5);
    const errorTypes = new Set(recentFailures.map(f => f.type));

    // Adjust based on error types
    if (errorTypes.has('network')) {
      multiplier *= 2; // Network issues need longer delays
    }
    if (errorTypes.has('rate_limit')) {
      multiplier *= 3; // Rate limiting needs much longer delays
    }
    if (errorTypes.has('temporary')) {
      multiplier *= 0.5; // Temporary issues can retry faster
    }

    // Apply learnings from successful patterns
    const successfulDelays = learnings.get('successful_delays') || [];
    if (successfulDelays.length > 0) {
      const avgSuccessDelay = successfulDelays.reduce((a, b) => a + b, 0) / successfulDelays.length;
      multiplier *= (avgSuccessDelay / baseDelay);
    }

    const calculatedDelay = baseDelay * multiplier * attempt;
    return Math.min(calculatedDelay, 60000); // Cap at 1 minute for adaptive
  }

  static progressive(attempt, baseDelay, maxDelay) {
    // Progressive increase: slower ramp-up than exponential
    const delay = baseDelay * (1 + (attempt - 1) * 0.5);
    return Math.min(delay, maxDelay);
  }
}

// Enhanced monitoring with strategy selection
class PersistentMonitor extends PersistentMonitor {
  calculateDelay() {
    const { strategy, baseDelay, maxDelay } = this.config;

    switch (strategy) {
      case 'fixed':
        return RetryStrategies.fixed(this.attempts, baseDelay);
      case 'exponential':
        return RetryStrategies.exponential(this.attempts, baseDelay, maxDelay);
      case 'adaptive':
        return RetryStrategies.adaptive(this.attempts, baseDelay, this.failures, this.learnings);
      case 'progressive':
        return RetryStrategies.progressive(this.attempts, baseDelay, maxDelay);
      default:
        return RetryStrategies.exponential(this.attempts, baseDelay, maxDelay);
    }
  }
}
```

### State Management Protocol

```javascript
class MonitoringStateManager {
  constructor(sessionDir) {
    this.sessionDir = sessionDir;
    this.state = new SharedState(sessionDir);
    this.stateVersion = 0;
    this.pendingUpdates = new Map();
    this.subscribers = new Set();
  }

  // Thread-safe state updates with versioning
  async updateMonitoringState(monitorId, updates, options = {}) {
    const lockKey = `state-lock-${monitorId}`;
    const lock = await this.acquireLock(lockKey, options.timeout || 5000);

    try {
      // Read current state
      const currentState = await this.state.read(`monitoring/${monitorId}`) || {};

      // Apply updates with conflict resolution
      const newState = {
        ...currentState,
        ...updates,
        version: ++this.stateVersion,
        updated_at: new Date().toISOString(),
        updated_by: process.pid
      };

      // Validate state consistency
      await this.validateStateConsistency(newState);

      // Write updated state
      await this.state.write(`monitoring/${monitorId}`, newState);

      // Backup state if configured
      if (options.backup !== false) {
        await this.backupState(monitorId, newState);
      }

      // Notify subscribers
      await this.notifySubscribers(monitorId, newState);

      return newState;

    } finally {
      await this.releaseLock(lockKey);
    }
  }

  async trackAttempt(monitorId, attempt, status, data = {}) {
    const attemptRecord = {
      attempt_number: attempt,
      status: status, // started, succeeded, failed, partial
      timestamp: new Date().toISOString(),
      duration: data.duration,
      result: data.result,
      error: data.error,
      metrics: data.metrics,
      agent_id: process.pid
    };

    await this.updateMonitoringState(monitorId, {
      [`attempts.${attempt}`]: attemptRecord,
      current_attempt: attempt,
      last_status: status
    });

    // Store in attempt history for analysis
    await this.state.append(`monitoring/${monitorId}/attempts`, attemptRecord);
  }

  async shareResultWithAgents(monitorId, result, tags = []) {
    const sharedData = {
      monitor_id: monitorId,
      result: result,
      tags: tags,
      shared_at: new Date().toISOString(),
      agent_id: process.pid,
      version: this.stateVersion
    };

    // Write to shared results for other agents
    await this.state.write(`shared/results/${monitorId}-${Date.now()}`, sharedData);

    // Update coordination map
    await this.updateCoordinationMap(monitorId, sharedData);
  }

  async acquireLock(lockKey, timeout) {
    const lockFile = `${this.sessionDir}/locks/${lockKey}`;
    const startTime = Date.now();

    while (Date.now() - startTime < timeout) {
      try {
        await fs.writeFile(lockFile, JSON.stringify({
          acquired_by: process.pid,
          acquired_at: new Date().toISOString()
        }), { flag: 'wx' }); // Fail if exists

        return lockKey;
      } catch (error) {
        if (error.code !== 'EEXIST') throw error;
        await new Promise(resolve => setTimeout(resolve, 100));
      }
    }

    throw new Error(`Failed to acquire lock ${lockKey} within ${timeout}ms`);
  }

  async releaseLock(lockKey) {
    const lockFile = `${this.sessionDir}/locks/${lockKey}`;
    try {
      await fs.unlink(lockFile);
    } catch (error) {
      // Lock might have been released by timeout or other process
      if (error.code !== 'ENOENT') {
        console.warn(`Warning: Failed to release lock ${lockKey}:`, error.message);
      }
    }
  }

  async backupState(monitorId, state) {
    const backupFile = `${this.sessionDir}/state/backups/${monitorId}-${Date.now()}.json`;
    await fs.writeFile(backupFile, JSON.stringify(state, null, 2));

    // Clean old backups (keep last 10)
    await this.cleanOldBackups(monitorId, 10);
  }

  async validateStateConsistency(state) {
    // Check required fields
    const required = ['monitor_id', 'status', 'started_at'];
    for (const field of required) {
      if (!state[field]) {
        throw new Error(`Missing required state field: ${field}`);
      }
    }

    // Validate status transitions
    if (state.last_status === 'completed' && state.status === 'running') {
      throw new Error('Invalid state transition: completed -> running');
    }

    // Check version consistency
    if (state.version < this.stateVersion) {
      throw new Error('State version conflict detected');
    }
  }
}
```

### Agent Communication Patterns

```javascript
class AgentCommunicationHub {
  constructor(sessionDir) {
    this.sessionDir = sessionDir;
    this.state = new SharedState(sessionDir);
    this.agentId = `agent-${process.pid}`;
    this.messageQueue = [];
    this.subscriptions = new Map();
    this.coordinationChannels = new Map();
  }

  // Register agent for coordination
  async registerForCoordination(monitorId, capabilities = {}) {
    const registration = {
      agent_id: this.agentId,
      monitor_id: monitorId,
      capabilities: capabilities,
      status: 'active',
      registered_at: new Date().toISOString(),
      last_heartbeat: new Date().toISOString(),
      message_queue: `queue/${this.agentId}`,
      coordination_channel: `coordination/${monitorId}`
    };

    await this.state.write(`agents/${this.agentId}`, registration);

    // Join coordination channel
    this.coordinationChannels.set(monitorId, `coordination/${monitorId}`);

    // Start heartbeat
    this.startHeartbeat(monitorId);

    return registration;
  }

  // Send messages to specific agents or broadcast
  async sendMessage(target, message, priority = 'normal') {
    const envelope = {
      id: `msg-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
      from: this.agentId,
      to: target, // 'broadcast', 'monitor:monitorId', or specific agentId
      priority: priority, // low, normal, high, urgent
      timestamp: new Date().toISOString(),
      message: message,
      requires_ack: priority === 'urgent'
    };

    if (target === 'broadcast') {
      // Send to all active agents
      const agents = await this.getActiveAgents();
      for (const agent of agents) {
        await this.deliverMessage(agent.agent_id, envelope);
      }
    } else if (target.startsWith('monitor:')) {
      // Send to all agents monitoring this ID
      const monitorId = target.split(':')[1];
      const agents = await this.getAgentsByMonitor(monitorId);
      for (const agent of agents) {
        await this.deliverMessage(agent.agent_id, envelope);
      }
    } else {
      // Send to specific agent
      await this.deliverMessage(target, envelope);
    }

    return envelope.id;
  }

  async deliverMessage(agentId, envelope) {
    const queuePath = `queue/${agentId}`;
    await this.state.append(queuePath, envelope);

    // Wake up agent if sleeping
    await this.notifyAgent(agentId, 'message_received');
  }

  // Receive and process messages
  async receiveMessages(filter = {}) {
    const queuePath = `queue/${this.agentId}`;
    const messages = await this.state.readArray(queuePath) || [];

    // Filter messages
    let filteredMessages = messages;
    if (filter.priority) {
      filteredMessages = messages.filter(m => m.priority === filter.priority);
    }
    if (filter.from) {
      filteredMessages = filteredMessages.filter(m => m.from === filter.from);
    }
    if (filter.since) {
      filteredMessages = filteredMessages.filter(m =>
        new Date(m.timestamp) > new Date(filter.since)
      );
    }

    // Mark messages as read
    for (const message of filteredMessages) {
      if (message.requires_ack) {
        await this.sendAcknowledgment(message);
      }
    }

    // Clear processed messages from queue
    const unprocessed = messages.filter(m => !filteredMessages.includes(m));
    await this.state.write(queuePath, unprocessed);

    return filteredMessages;
  }

  // Coordinate sleep intervals between agents
  async coordinatedSleep(monitorId, preferredDuration, options = {}) {
    const coordination = await this.getCoordinationData(monitorId);
    const activeAgents = coordination.agents.filter(a => a.status === 'active');

    if (activeAgents.length <= 1) {
      // Only agent, sleep full duration
      await this.sleep(preferredDuration);
      return;
    }

    // Calculate staggered sleep intervals
    const agentIndex = activeAgents.findIndex(a => a.agent_id === this.agentId);
    const staggerDelay = (preferredDuration / activeAgents.length) * agentIndex;
    const actualSleep = preferredDuration - staggerDelay;

    // Notify other agents of sleep plan
    await this.sendMessage(`monitor:${monitorId}`, {
      type: 'sleep_notification',
      agent_id: this.agentId,
      sleep_duration: actualSleep,
      sleep_start: new Date(Date.now() + staggerDelay).toISOString()
    });

    // Wait for stagger delay, then sleep
    if (staggerDelay > 0) {
      await this.sleep(staggerDelay);
    }
    await this.sleep(actualSleep);
  }

  async sleep(duration) {
    await this.updateStatus('sleeping', { sleep_duration: duration });
    await new Promise(resolve => setTimeout(resolve, duration));
    await this.updateStatus('active');
  }

  startHeartbeat(monitorId) {
    const heartbeatInterval = setInterval(async () => {
      try {
        await this.state.update(`agents/${this.agentId}`, agent => {
          agent.last_heartbeat = new Date().toISOString();
          agent.status = agent.status || 'active';
          return agent;
        });

        // Check for coordination messages
        const messages = await this.receiveMessages({ priority: 'urgent' });
        for (const message of messages) {
          await this.handleCoordinationMessage(message);
        }

      } catch (error) {
        console.error(`Heartbeat failed for ${this.agentId}:`, error);
      }
    }, 5000); // 5-second heartbeat

    // Store interval for cleanup
    this.heartbeatInterval = heartbeatInterval;
  }

  async updateStatus(status, metadata = {}) {
    await this.state.update(`agents/${this.agentId}`, agent => {
      agent.status = status;
      agent.last_status_change = new Date().toISOString();
      agent.metadata = { ...agent.metadata, ...metadata };
      return agent;
    });
  }
}
```

### Failure Pattern Learning

```javascript
class FailurePatternLearner {
  constructor(sessionDir) {
    this.sessionDir = sessionDir;
    this.state = new SharedState(sessionDir);
    this.patterns = new Map();
    this.predictions = new Map();
  }

  async learnFromFailure(monitorId, failure, context = {}) {
    const failureRecord = {
      id: `failure-${Date.now()}`,
      monitor_id: monitorId,
      timestamp: new Date().toISOString(),
      error: {
        type: failure.type || 'unknown',
        message: failure.message,
        stack: failure.stack,
        code: failure.code
      },
      context: {
        attempt: context.attempt,
        delay: context.delay,
        strategy: context.strategy,
        agent_id: context.agent_id,
        environment: context.environment
      },
      conditions: {
        time_of_day: new Date().getHours(),
        day_of_week: new Date().getDay(),
        system_load: context.system_load,
        network_status: context.network_status
      }
    };

    // Store individual failure
    await this.state.append(`failures/${monitorId}`, failureRecord);

    // Update pattern analysis
    await this.updatePatterns(failureRecord);

    // Share learning with other agents
    await this.shareFailurePattern(failureRecord);

    return failureRecord;
  }

  async updatePatterns(failureRecord) {
    const patternKey = this.generatePatternKey(failureRecord);

    // Update pattern frequency
    if (!this.patterns.has(patternKey)) {
      this.patterns.set(patternKey, {
        pattern: patternKey,
        count: 0,
        first_seen: failureRecord.timestamp,
        last_seen: failureRecord.timestamp,
        success_rate: 0,
        recommended_actions: []
      });
    }

    const pattern = this.patterns.get(patternKey);
    pattern.count++;
    pattern.last_seen = failureRecord.timestamp;

    // Analyze for recommendations
    await this.analyzeForRecommendations(pattern, failureRecord);

    // Persist updated patterns
    await this.state.write(`patterns/${patternKey}`, pattern);
  }

  generatePatternKey(failureRecord) {
    // Create a pattern signature from failure characteristics
    const elements = [
      failureRecord.error.type,
      failureRecord.error.code,
      failureRecord.context.strategy,
      failureRecord.conditions.time_of_day >= 9 && failureRecord.conditions.time_of_day <= 17 ? 'business_hours' : 'off_hours'
    ];

    return elements.filter(Boolean).join(':');
  }

  async analyzeForRecommendations(pattern, failureRecord) {
    const recommendations = [];

    // Time-based patterns
    if (failureRecord.conditions.time_of_day >= 9 && failureRecord.conditions.time_of_day <= 17) {
      recommendations.push({
        type: 'timing',
        action: 'increase_delay',
        reason: 'Business hours - higher system load',
        multiplier: 1.5
      });
    }

    // Error type patterns
    switch (failureRecord.error.type) {
      case 'network':
        recommendations.push({
          type: 'strategy',
          action: 'use_exponential_backoff',
          reason: 'Network errors benefit from exponential backoff'
        });
        break;

      case 'rate_limit':
        recommendations.push({
          type: 'delay',
          action: 'increase_base_delay',
          reason: 'Rate limiting requires longer delays',
          minimum_delay: 30000
        });
        break;

      case 'temporary':
        recommendations.push({
          type: 'strategy',
          action: 'use_fixed_delay',
          reason: 'Temporary errors often resolve quickly'
        });
        break;
    }

    pattern.recommended_actions = recommendations;
  }

  async shareFailurePattern(failureRecord) {
    const sharedPattern = {
      pattern_id: this.generatePatternKey(failureRecord),
      shared_by: process.pid,
      shared_at: new Date().toISOString(),
      failure_summary: {
        type: failureRecord.error.type,
        frequency: this.patterns.get(this.generatePatternKey(failureRecord))?.count || 1,
        recommendations: this.patterns.get(this.generatePatternKey(failureRecord))?.recommended_actions || []
      }
    };

    await this.state.write(`shared/patterns/${sharedPattern.pattern_id}`, sharedPattern);
  }

  async getRecommendations(context) {
    const potentialPatterns = [];

    // Check for matching patterns
    for (const [patternKey, pattern] of this.patterns) {
      const score = this.calculatePatternMatch(pattern, context);
      if (score > 0.5) {
        potentialPatterns.push({ pattern, score });
      }
    }

    // Sort by relevance
    potentialPatterns.sort((a, b) => b.score - a.score);

    // Return top recommendations
    return potentialPatterns.slice(0, 3).map(p => p.pattern.recommended_actions).flat();
  }

  calculatePatternMatch(pattern, context) {
    let score = 0;

    // Time similarity
    if (pattern.pattern.includes('business_hours') ===
        (context.time_of_day >= 9 && context.time_of_day <= 17)) {
      score += 0.3;
    }

    // Strategy similarity
    if (pattern.pattern.includes(context.strategy)) {
      score += 0.4;
    }

    // Error type (if available in context)
    if (context.error_type && pattern.pattern.includes(context.error_type)) {
      score += 0.3;
    }

    return score;
  }
}
```

### Progressive Escalation Coordination

```javascript
class EscalationCoordinator {
  constructor(sessionDir) {
    this.sessionDir = sessionDir;
    this.state = new SharedState(sessionDir);
    this.escalationLevels = new Map();
    this.coordinationState = new Map();
  }

  async initializeEscalation(monitorId, config = {}) {
    const escalationPlan = {
      monitor_id: monitorId,
      levels: config.levels || [
        { level: 1, trigger_after: 3, actions: ['increase_delay', 'notify_team'] },
        { level: 2, trigger_after: 5, actions: ['change_strategy', 'spawn_additional_agents'] },
        { level: 3, trigger_after: 8, actions: ['escalate_to_human', 'rollback_changes'] },
        { level: 4, trigger_after: 10, actions: ['emergency_stop', 'alert_management'] }
      ],
      current_level: 0,
      failure_count: 0,
      escalated_at: {},
      coordination_agents: []
    };

    await this.state.write(`escalation/${monitorId}`, escalationPlan);
    this.escalationLevels.set(monitorId, escalationPlan);

    return escalationPlan;
  }

  async handleFailureEscalation(monitorId, failure) {
    const escalation = await this.state.read(`escalation/${monitorId}`);
    if (!escalation) {
      throw new Error(`No escalation plan found for monitor ${monitorId}`);
    }

    escalation.failure_count++;

    // Check if escalation is needed
    for (const level of escalation.levels) {
      if (escalation.failure_count >= level.trigger_after &&
          escalation.current_level < level.level) {

        await this.triggerEscalationLevel(monitorId, level, failure);
        escalation.current_level = level.level;
        escalation.escalated_at[level.level] = new Date().toISOString();
        break;
      }
    }

    await this.state.write(`escalation/${monitorId}`, escalation);
  }

  async triggerEscalationLevel(monitorId, level, failure) {
    console.log(`Escalating to level ${level.level} for monitor ${monitorId}`);

    for (const action of level.actions) {
      try {
        await this.executeEscalationAction(monitorId, action, level, failure);
      } catch (error) {
        console.error(`Escalation action failed: ${action}`, error);
      }
    }

    // Coordinate with other agents
    await this.coordinateEscalation(monitorId, level);
  }

  async executeEscalationAction(monitorId, action, level, failure) {
    switch (action) {
      case 'increase_delay':
        await this.increaseRetryDelay(monitorId, level.level);
        break;

      case 'change_strategy':
        await this.changeRetryStrategy(monitorId, level.level);
        break;

      case 'spawn_additional_agents':
        await this.spawnAdditionalAgents(monitorId, level.level);
        break;

      case 'notify_team':
        await this.notifyTeam(monitorId, level, failure);
        break;

      case 'escalate_to_human':
        await this.escalateToHuman(monitorId, level, failure);
        break;

      case 'rollback_changes':
        await this.initiateRollback(monitorId, failure);
        break;

      case 'emergency_stop':
        await this.emergencyStop(monitorId, failure);
        break;

      case 'alert_management':
        await this.alertManagement(monitorId, level, failure);
        break;
    }
  }

  async coordinateEscalation(monitorId, level) {
    const coordination = {
      monitor_id: monitorId,
      escalation_level: level.level,
      coordinated_at: new Date().toISOString(),
      coordinator: process.pid,
      required_actions: level.actions,
      participating_agents: []
    };

    // Notify all agents monitoring this ID
    const hub = new AgentCommunicationHub(this.sessionDir);
    await hub.sendMessage(`monitor:${monitorId}`, {
      type: 'escalation_coordination',
      level: level.level,
      actions: level.actions,
      coordination_id: coordination.coordinated_at
    }, 'urgent');

    await this.state.write(`coordination/escalation/${monitorId}-${level.level}`, coordination);
  }

  async increaseRetryDelay(monitorId, level) {
    const multiplier = 1.5 ** level; // Exponential increase per level

    await this.state.update(`monitoring/${monitorId}`, monitor => {
      monitor.config.baseDelay = (monitor.config.baseDelay || 1000) * multiplier;
      monitor.config.maxDelay = Math.min(monitor.config.maxDelay * multiplier, 60000);
      return monitor;
    });
  }

  async changeRetryStrategy(monitorId, level) {
    const strategies = ['fixed', 'exponential', 'adaptive', 'progressive'];
    const currentStrategy = await this.getCurrentStrategy(monitorId);
    const currentIndex = strategies.indexOf(currentStrategy);
    const newStrategy = strategies[(currentIndex + 1) % strategies.length];

    await this.state.update(`monitoring/${monitorId}`, monitor => {
      monitor.config.strategy = newStrategy;
      monitor.strategy_changed_at = new Date().toISOString();
      monitor.strategy_change_reason = `escalation_level_${level}`;
      return monitor;
    });
  }

  async spawnAdditionalAgents(monitorId, level) {
    const additionalAgents = Math.min(level, 3); // Max 3 additional agents

    const spawnConfig = {
      monitor_id: monitorId,
      agents_to_spawn: additionalAgents,
      reason: `escalation_level_${level}`,
      spawn_time: new Date().toISOString()
    };

    await this.state.write(`coordination/spawn/${monitorId}-${Date.now()}`, spawnConfig);

    // This would trigger the agent spawning system
    // Implementation depends on the specific agent management system
  }

  async notifyTeam(monitorId, level, failure) {
    const notification = {
      type: 'escalation_notification',
      monitor_id: monitorId,
      level: level.level,
      failure_count: (await this.state.read(`escalation/${monitorId}`)).failure_count,
      latest_failure: failure,
      timestamp: new Date().toISOString(),
      requires_attention: level.level >= 2
    };

    // Store notification for external systems to pick up
    await this.state.write(`notifications/team/${monitorId}-${Date.now()}`, notification);
  }

  async emergencyStop(monitorId, failure) {
    // Stop all monitoring for this ID
    await this.state.update(`monitoring/${monitorId}`, monitor => {
      monitor.status = 'emergency_stopped';
      monitor.stopped_at = new Date().toISOString();
      monitor.stop_reason = failure;
      return monitor;
    });

    // Notify all agents
    const hub = new AgentCommunicationHub(this.sessionDir);
    await hub.sendMessage(`monitor:${monitorId}`, {
      type: 'emergency_stop',
      reason: failure.message,
      immediate_action_required: true
    }, 'urgent');
  }
}
```

### Sleep Interval Management

```javascript
class SleepIntervalManager {
  constructor(sessionDir) {
    this.sessionDir = sessionDir;
    this.state = new SharedState(sessionDir);
    this.activeIntervals = new Map();
    this.coordinationQueue = [];
  }

  async registerSleepInterval(agentId, monitorId, preferredInterval, options = {}) {
    const registration = {
      agent_id: agentId,
      monitor_id: monitorId,
      preferred_interval: preferredInterval,
      actual_interval: preferredInterval,
      options: {
        allow_stagger: options.allow_stagger !== false,
        priority: options.priority || 'normal',
        can_coordinate: options.can_coordinate !== false,
        min_interval: options.min_interval || 1000,
        max_interval: options.max_interval || 60000
      },
      registered_at: new Date().toISOString(),
      status: 'registered'
    };

    await this.state.write(`sleep/agents/${agentId}`, registration);
    this.activeIntervals.set(agentId, registration);

    return registration;
  }

  async coordinateSleepIntervals(monitorId) {
    const agents = await this.getAgentsForMonitor(monitorId);
    if (agents.length <= 1) {
      return agents; // No coordination needed
    }

    const coordinationPlan = {
      monitor_id: monitorId,
      agents: agents.length,
      plan_created_at: new Date().toISOString(),
      intervals: []
    };

    // Sort agents by priority
    agents.sort((a, b) => {
      const priorities = { high: 3, normal: 2, low: 1 };
      return priorities[b.options.priority] - priorities[a.options.priority];
    });

    // Calculate staggered intervals
    const totalPreferredTime = agents.reduce((sum, a) => sum + a.preferred_interval, 0);
    const avgInterval = totalPreferredTime / agents.length;

    let currentOffset = 0;
    for (let i = 0; i < agents.length; i++) {
      const agent = agents[i];

      if (agent.options.allow_stagger) {
        const staggerDelay = (avgInterval / agents.length) * i;
        const adjustedInterval = agent.preferred_interval - staggerDelay;

        agent.actual_interval = Math.max(
          adjustedInterval,
          agent.options.min_interval
        );
        agent.start_offset = currentOffset + staggerDelay;
      } else {
        agent.actual_interval = agent.preferred_interval;
        agent.start_offset = currentOffset;
      }

      coordinationPlan.intervals.push({
        agent_id: agent.agent_id,
        start_offset: agent.start_offset,
        interval: agent.actual_interval,
        end_time: currentOffset + agent.start_offset + agent.actual_interval
      });

      currentOffset = agent.start_offset + agent.actual_interval;
    }

    // Store coordination plan
    await this.state.write(`sleep/coordination/${monitorId}`, coordinationPlan);

    // Notify agents of their new schedules
    for (const agent of agents) {
      await this.notifyAgentSchedule(agent);
    }

    return coordinationPlan;
  }

  async executeSleepWithCoordination(agentId, monitorId) {
    const registration = await this.state.read(`sleep/agents/${agentId}`);
    if (!registration) {
      throw new Error(`No sleep registration found for agent ${agentId}`);
    }

    const coordination = await this.state.read(`sleep/coordination/${monitorId}`);

    if (coordination && registration.options.can_coordinate) {
      // Use coordinated schedule
      const schedule = coordination.intervals.find(i => i.agent_id === agentId);
      if (schedule) {
        await this.executeScheduledSleep(schedule, registration);
        return;
      }
    }

    // Fall back to simple sleep
    await this.simpleSleep(registration.actual_interval);
  }

  async executeScheduledSleep(schedule, registration) {
    // Wait for start offset
    if (schedule.start_offset > 0) {
      await this.state.update(`sleep/agents/${registration.agent_id}`, agent => {
        agent.status = 'waiting_for_offset';
        agent.offset_wait_until = new Date(Date.now() + schedule.start_offset).toISOString();
        return agent;
      });

      await this.simpleSleep(schedule.start_offset);
    }

    // Execute main sleep
    await this.state.update(`sleep/agents/${registration.agent_id}`, agent => {
      agent.status = 'sleeping';
      agent.sleep_started = new Date().toISOString();
      agent.sleep_duration = schedule.interval;
      return agent;
    });

    await this.simpleSleep(schedule.interval);

    // Mark as completed
    await this.state.update(`sleep/agents/${registration.agent_id}`, agent => {
      agent.status = 'completed';
      agent.sleep_completed = new Date().toISOString();
      return agent;
    });
  }

  async simpleSleep(duration) {
    await new Promise(resolve => setTimeout(resolve, duration));
  }

  async getAgentsForMonitor(monitorId) {
    const allAgents = await this.state.readPattern(`sleep/agents/*`);
    return allAgents.filter(agent => agent.monitor_id === monitorId);
  }

  async notifyAgentSchedule(agent) {
    const hub = new AgentCommunicationHub(this.sessionDir);
    await hub.sendMessage(agent.agent_id, {
      type: 'sleep_schedule_update',
      actual_interval: agent.actual_interval,
      start_offset: agent.start_offset,
      coordination_enabled: true
    });
  }

  // Adaptive interval adjustment based on success patterns
  async adaptIntervalBasedOnSuccess(agentId, monitorId, success) {
    const registration = await this.state.read(`sleep/agents/${agentId}`);
    if (!registration) return;

    const history = registration.success_history || [];
    history.push({
      timestamp: new Date().toISOString(),
      success: success,
      interval_used: registration.actual_interval
    });

    // Keep last 10 entries
    if (history.length > 10) {
      history.shift();
    }

    // Calculate success rate for current interval
    const recentSuccess = history.slice(-5);
    const successRate = recentSuccess.filter(h => h.success).length / recentSuccess.length;

    // Adjust interval based on success rate
    let newInterval = registration.preferred_interval;
    if (successRate < 0.3) {
      // Low success rate - increase interval
      newInterval = Math.min(registration.actual_interval * 1.5, registration.options.max_interval);
    } else if (successRate > 0.8) {
      // High success rate - decrease interval
      newInterval = Math.max(registration.actual_interval * 0.8, registration.options.min_interval);
    }

    await this.state.update(`sleep/agents/${agentId}`, agent => {
      agent.success_history = history;
      agent.preferred_interval = newInterval;
      agent.last_adaptation = new Date().toISOString();
      agent.adaptation_reason = `success_rate_${successRate.toFixed(2)}`;
      return agent;
    });
  }
}
```

### Complete Persistent Monitoring System Integration

```javascript
// Enhanced PersistentMonitor with all coordination features
class CompletePersistentMonitor extends PersistentMonitor {
  constructor(sessionDir, config = {}) {
    super(sessionDir, config);

    // Initialize all coordination components
    this.stateManager = new MonitoringStateManager(sessionDir);
    this.communicationHub = new AgentCommunicationHub(sessionDir);
    this.failureLearner = new FailurePatternLearner(sessionDir);
    this.escalationCoordinator = new EscalationCoordinator(sessionDir);
    this.sleepManager = new SleepIntervalManager(sessionDir);
  }

  async startMonitoring(task, conditions = {}) {
    // Register for coordination
    await this.communicationHub.registerForCoordination(this.monitorId, {
      task_type: task.type,
      capabilities: task.capabilities || []
    });

    // Initialize escalation plan
    await this.escalationCoordinator.initializeEscalation(this.monitorId, this.config.escalation);

    // Register sleep intervals
    await this.sleepManager.registerSleepInterval(
      this.communicationHub.agentId,
      this.monitorId,
      this.config.baseDelay,
      this.config.sleep_options
    );

    // Start the enhanced monitoring loop
    return await super.startMonitoring(task, conditions);
  }

  async handleFailure(error) {
    // Learn from failure
    const failure = await this.failureLearner.learnFromFailure(this.monitorId, error, {
      attempt: this.attempts,
      delay: this.calculateDelay(),
      strategy: this.config.strategy,
      agent_id: this.communicationHub.agentId
    });

    // Handle escalation
    await this.escalationCoordinator.handleFailureEscalation(this.monitorId, failure);

    // Share failure with other agents
    await this.communicationHub.sendMessage(`monitor:${this.monitorId}`, {
      type: 'failure_notification',
      failure: failure,
      recommendations: await this.failureLearner.getRecommendations({
        strategy: this.config.strategy,
        time_of_day: new Date().getHours(),
        error_type: error.type
      })
    });

    // Record in state
    await this.stateManager.trackAttempt(this.monitorId, this.attempts, 'failed', {
      error: error,
      failure_id: failure.id
    });

    this.failures.push(failure);
  }

  async waitWithCoordination(delay) {
    // Use coordinated sleep
    await this.sleepManager.executeSleepWithCoordination(
      this.communicationHub.agentId,
      this.monitorId
    );

    // Check for coordination messages during wait
    const messages = await this.communicationHub.receiveMessages({
      since: new Date(Date.now() - delay).toISOString()
    });

    for (const message of messages) {
      await this.handleCoordinationMessage(message);
    }
  }

  async handleCoordinationMessage(message) {
    switch (message.message.type) {
      case 'escalation_coordination':
        await this.participateInEscalation(message.message);
        break;

      case 'failure_notification':
        await this.processSharedFailure(message.message.failure);
        break;

      case 'sleep_schedule_update':
        await this.updateSleepSchedule(message.message);
        break;

      case 'emergency_stop':
        this.isRunning = false;
        throw new Error(`Emergency stop: ${message.message.reason}`);
    }
  }

  async shareState(result) {
    await this.stateManager.shareResultWithAgents(this.monitorId, result, ['monitoring_result']);

    // Adapt sleep interval based on success
    await this.sleepManager.adaptIntervalBasedOnSuccess(
      this.communicationHub.agentId,
      this.monitorId,
      this.evaluateSuccess(result, {})
    );
  }
}
```

This comprehensive persistent monitoring system provides:

1. **Persistent Loop Patterns**: Robust monitoring loops with proper state management and coordination
2. **Retry Strategy Definitions**: Multiple strategies (fixed, exponential, adaptive, progressive) with intelligent selection
3. **State Management Protocol**: Thread-safe state updates with versioning and conflict resolution
4. **Agent Communication Patterns**: Multi-agent coordination with message queues and heartbeats
5. **Failure Pattern Learning**: Machine learning from failures to improve future attempts
6. **Progressive Escalation**: Automatic escalation with coordinated responses
7. **Sleep Interval Management**: Coordinated sleep intervals across multiple agents to optimize resource usage

The system enables agents to persistently monitor CI/CD pipelines while learning from failures, coordinating with other agents, and progressively escalating issues when needed.