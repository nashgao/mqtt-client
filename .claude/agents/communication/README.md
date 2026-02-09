# Agent Communication Framework

Advanced inter-agent communication system for the Adaptive Hybrid Orchestration platform, providing robust message passing, coordination patterns, and fault tolerance for distributed agent workflows.

## 🏗️ Architecture Overview

The communication framework consists of four core components:

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Message Bus   │    │  Coordination   │    │ Error Handling  │
│                 │    │   Patterns      │    │                 │
│ • Publishers    │    │ • Request/Reply │    │ • Retry Logic   │
│ • Subscribers   │    │ • Event-Driven  │    │ • Circuit Break │
│ • Queues        │    │ • Map-Reduce    │    │ • Dead Letters  │
│ • Routing       │    │ • Pipelines     │    │ • Escalation    │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         └───────────────────────┼───────────────────────┘
                                 │
                    ┌─────────────────┐
                    │   Directory     │
                    │   Structure     │
                    │                 │
                    │ • Session Mgmt  │
                    │ • State Storage │
                    │ • Health Check  │
                    │ • Cleanup       │
                    └─────────────────┘
```

## 📁 Component Files

| Component | File | Description |
|-----------|------|-------------|
| **Message Bus** | `message-bus.md` | Core message passing infrastructure with JSON schemas |
| **Patterns** | `coordination-patterns.md` | Communication patterns and orchestration strategies |
| **Infrastructure** | `directory-setup.md` | Directory structure and lifecycle management |
| **Reliability** | `error-handling.md` | Error handling, retry mechanisms, and fault tolerance |

## 🚀 Quick Start

### 1. Initialize Communication Session

```bash
# Set up coordination infrastructure
SESSION_ID=$(./templates/agents/communication/setup-coordination-dirs.sh "my-operation")
export CLAUDE_COORDINATION_DIR="/tmp/claude-agents/${SESSION_ID}"
export CLAUDE_SESSION_ID="${SESSION_ID}"

echo "Coordination session initialized: ${SESSION_ID}"
```

### 2. Basic Message Publishing

```javascript
const { MessagePublisher } = require('./message-bus');

const publisher = new MessagePublisher(process.env.CLAUDE_COORDINATION_DIR, 'agent-001');

// Send a task assignment
await publisher.publish({
  to: 'test-fixer-002',
  type: 'task_assignment',
  payload: {
    task: {
      id: 'fix-auth-tests',
      type: 'test_fixing',
      description: 'Fix failing authentication tests',
      parameters: {
        test_files: ['auth.test.js', 'login.test.js'],
        timeout: 300
      }
    }
  },
  headers: {
    priority: 4,
    requires_ack: true
  }
});
```

### 3. Message Subscription

```javascript
const { MessageSubscriber } = require('./message-bus');

const subscriber = new MessageSubscriber(process.env.CLAUDE_COORDINATION_DIR, 'agent-002');

// Handle task assignments
await subscriber.subscribe('task_assignment', async (payload, message) => {
  const { task } = payload;
  
  console.log(`Received task: ${task.id}`);
  
  try {
    // Process the task
    const result = await processTask(task);
    
    // Send result back
    await publisher.publish({
      to: message.from,
      type: 'task_result',
      payload: {
        task_id: task.id,
        status: 'completed',
        result,
        metrics: {
          execution_time: Date.now() - startTime,
          memory_used: '45MB'
        }
      }
    });
    
  } catch (error) {
    // Handle task failure
    await publisher.publish({
      to: message.from,
      type: 'task_result',
      payload: {
        task_id: task.id,
        status: 'failed',
        error: {
          code: error.code,
          message: error.message
        }
      }
    });
  }
});
```

## 🔄 Coordination Patterns

### Request-Response Pattern

```javascript
const { RequestResponseManager } = require('./coordination-patterns');

const requestManager = new RequestResponseManager(
  process.env.CLAUDE_COORDINATION_DIR, 
  'coordinator-001'
);

// Make a request with timeout
try {
  const result = await requestManager.request(
    'data-analyzer-001',
    {
      operation: 'analyze_codebase',
      path: '/project/src'
    },
    { timeout: 60000 }
  );
  
  console.log('Analysis complete:', result);
  
} catch (error) {
  if (error.message.includes('timeout')) {
    console.error('Analysis timed out');
  } else {
    console.error('Analysis failed:', error.message);
  }
}
```

### Event-Driven Coordination

```javascript
const { EventDrivenCoordinator } = require('./coordination-patterns');

const eventCoordinator = new EventDrivenCoordinator(
  process.env.CLAUDE_COORDINATION_DIR, 
  'event-publisher-001'
);

// Subscribe to events
eventCoordinator.subscribe('test_completed', async (data, event) => {
  console.log(`Test completed: ${data.test_file}`);
  
  if (data.success) {
    // Emit success event
    await eventCoordinator.emitEvent('test_success', {
      test_file: data.test_file,
      duration: data.duration
    });
  }
});

// Emit an event
await eventCoordinator.emitEvent('test_started', {
  test_file: 'user-auth.test.js',
  agent_id: 'test-runner-002'
});
```

### Map-Reduce Pattern

```javascript
const { MapReduceCoordinator } = require('./coordination-patterns');

const mapReduce = new MapReduceCoordinator(
  process.env.CLAUDE_COORDINATION_DIR,
  'coordinator-001'
);

// Process large dataset in parallel
const data = await loadLargeDataset(); // Array of 10,000 items

const result = await mapReduce.mapReduce(
  data,
  // Map function (runs on workers)
  (chunk) => {
    return chunk.map(item => ({
      id: item.id,
      processed: processItem(item),
      timestamp: Date.now()
    }));
  },
  // Reduce function (combines results)
  (acc, chunk) => {
    return acc.concat(chunk);
  },
  {
    chunkSize: 100,      // Process 100 items per worker
    maxWorkers: 5,       // Use up to 5 workers
    taskTimeout: 30000   // 30 second timeout per task
  }
);

console.log(`Processed ${result.result.length} items in ${result.executionTime}ms`);
```

## 🛡️ Error Handling

### Automatic Retry with Exponential Backoff

```javascript
const { ErrorHandler } = require('./error-handling');

const errorHandler = new ErrorHandler(
  process.env.CLAUDE_COORDINATION_DIR, 
  'agent-001'
);

async function reliableOperation() {
  try {
    return await someFlakyOperation();
    
  } catch (error) {
    // Handle with automatic retry
    return await errorHandler.handleError(error, someFlakyOperation, {
      target: 'remote-service',
      operation: 'data_fetch',
      attempt: 1
    });
  }
}
```

### Circuit Breaker Protection

```javascript
const { CircuitBreaker } = require('./error-handling');

const circuitBreaker = new CircuitBreaker({
  failureThreshold: 5,   // Open after 5 failures
  resetTimeout: 60000,   // Try again after 1 minute
  onStateChange: (state, target) => {
    console.log(`Circuit breaker for ${target} is now ${state}`);
  }
});

async function protectedOperation() {
  return await circuitBreaker.execute(async () => {
    return await unreliableExternalAPI();
  }, 'external-api');
}
```

## 📊 Monitoring and Metrics

### Health Checking

```bash
# Check session health
./templates/agents/communication/health-check.sh "${CLAUDE_COORDINATION_DIR}"

# Continuous monitoring
while true; do
  if ./templates/agents/communication/health-check.sh "${CLAUDE_COORDINATION_DIR}"; then
    echo "✓ All agents healthy"
  else
    echo "⚠ Some agents are unhealthy"
  fi
  sleep 30
done
```

### Performance Metrics

```javascript
const { MessageBusMetrics } = require('./message-bus');

const metrics = new MessageBusMetrics(process.env.CLAUDE_COORDINATION_DIR);

// Get current metrics
const report = await metrics.generateReport();

console.log(`Messages sent: ${report.messages_sent}`);
console.log(`Average delivery time: ${report.average_delivery_time}ms`);
console.log(`Success rate: ${report.success_rate * 100}%`);
console.log(`Throughput: ${report.throughput.per_second} msg/sec`);
```

## 🔧 Configuration

### Environment Variables

```bash
# Core coordination settings
export CLAUDE_COORDINATION_DIR="/tmp/claude-agents/coord-operation-1640995200-abc123"
export CLAUDE_SESSION_ID="coord-operation-1640995200-abc123"
export CLAUDE_AGENT_ID="my-agent-001"

# Message bus settings
export CLAUDE_MESSAGE_POLL_INTERVAL=1000     # 1 second
export CLAUDE_MESSAGE_TTL=30000              # 30 seconds
export CLAUDE_MESSAGE_RETRY_ATTEMPTS=3

# Error handling settings
export CLAUDE_CIRCUIT_BREAKER_THRESHOLD=5
export CLAUDE_CIRCUIT_BREAKER_TIMEOUT=60000
export CLAUDE_MAX_RETRY_ATTEMPTS=5
export CLAUDE_RETRY_BASE_DELAY=1000
```

### Configuration File

```json
{
  "coordination": {
    "base_dir": "/tmp/claude-agents",
    "session_timeout": 3600,
    "health_check_interval": 30,
    "cleanup_retention": 86400
  },
  "message_bus": {
    "poll_interval": 1000,
    "default_ttl": 30000,
    "max_message_size": 1048576,
    "compression_threshold": 1024
  },
  "error_handling": {
    "retry_strategies": {
      "transient": {
        "type": "exponential_backoff",
        "max_retries": 5,
        "base_delay": 1000,
        "max_delay": 30000
      },
      "intermittent": {
        "type": "linear_backoff", 
        "max_retries": 3,
        "base_delay": 2000,
        "increment": 1000
      }
    },
    "circuit_breaker": {
      "failure_threshold": 5,
      "reset_timeout": 60000,
      "monitoring_period": 10000
    }
  },
  "monitoring": {
    "metrics_collection": true,
    "performance_tracking": true,
    "log_level": "info"
  }
}
```

## 🎯 Usage Patterns

### Multi-Agent Test Execution

```javascript
// Coordinator spawns multiple test agents
const testFiles = await findTestFiles('./tests');
const testChunks = chunkArray(testFiles, 10); // 10 files per agent

const testPromises = testChunks.map(async (chunk, index) => {
  const agentId = `test-runner-${index}`;
  
  return await requestManager.request(agentId, {
    operation: 'run_tests',
    test_files: chunk,
    parallel: true
  }, { timeout: 300000 });
});

const results = await Promise.all(testPromises);
const aggregatedResults = aggregateTestResults(results);

console.log(`Executed ${aggregatedResults.totalTests} tests`);
console.log(`Success rate: ${aggregatedResults.successRate * 100}%`);
```

### Code Analysis Pipeline

```javascript
// Pipeline: Discovery → Analysis → Transformation → Validation
const pipeline = pipelineCoordinator.definePipeline('code-analysis', [
  {
    name: 'discovery',
    agent_type: 'file-processor',
    config: { pattern: '**/*.js' }
  },
  {
    name: 'analysis',
    agent_type: 'code-analyzer',
    parallel: true,
    parallelism: 3
  },
  {
    name: 'transformation', 
    agent_type: 'code-transformer',
    config: { rules: ['es6-modules', 'async-await'] }
  },
  {
    name: 'validation',
    agent_type: 'quality-enforcer',
    config: { strict: true }
  }
]);

const result = await pipelineCoordinator.executePipeline(
  'code-analysis',
  { projectPath: '/project/src' }
);

console.log(`Pipeline completed in ${result.executionTime}ms`);
console.log(`Files processed: ${result.finalResult.filesProcessed}`);
```

## 🧹 Cleanup and Maintenance

### Session Cleanup

```bash
# Archive and cleanup specific session
./templates/agents/communication/cleanup-coordination.sh archive "${CLAUDE_COORDINATION_DIR}"
./templates/agents/communication/cleanup-coordination.sh cleanup "${CLAUDE_COORDINATION_DIR}"

# Cleanup old sessions (older than 24 hours)
./templates/agents/communication/cleanup-coordination.sh cleanup-old "/tmp/claude-agents" 24
```

### Dead Letter Inspection

```javascript
const { DeadLetterQueue } = require('./error-handling');

const deadLetterQueue = new DeadLetterQueue(process.env.CLAUDE_COORDINATION_DIR);
const deadLetters = await deadLetterQueue.getAll();

console.log(`Found ${deadLetters.length} dead letters:`);

deadLetters.forEach(letter => {
  console.log(`- ${letter.id}: ${letter.reason} at ${letter.deadLetteredAt}`);
  console.log(`  Error: ${letter.error.message}`);
});

// Reprocess specific dead letters if needed
```

## 🏁 Best Practices

### Agent Design

1. **Stateless Agents**: Design agents to be stateless for better scalability
2. **Idempotent Operations**: Make operations idempotent to handle retries safely
3. **Resource Limits**: Set appropriate timeouts and resource limits
4. **Health Reporting**: Implement health check endpoints

### Message Design

1. **Schema Validation**: Always validate message schemas
2. **Size Limits**: Keep messages under 1MB for performance
3. **Correlation IDs**: Use correlation IDs for request tracing
4. **Semantic Versioning**: Version your message schemas

### Error Handling

1. **Classify Errors**: Properly categorize errors (transient/permanent/critical)
2. **Implement Backoff**: Use exponential backoff for retries
3. **Circuit Breakers**: Protect against cascading failures
4. **Dead Letter Queues**: Capture failed messages for investigation

### Performance

1. **Parallel Execution**: Leverage parallelism where possible
2. **Batch Operations**: Batch small operations for efficiency
3. **Connection Pooling**: Reuse connections and resources
4. **Monitor Metrics**: Track performance and error metrics

## 📚 API Reference

For detailed API documentation, see the individual component files:

- [Message Bus API](./message-bus.md)
- [Coordination Patterns API](./coordination-patterns.md)  
- [Directory Setup API](./directory-setup.md)
- [Error Handling API](./error-handling.md)

## 🔍 Troubleshooting

### Common Issues

**Messages not being delivered:**
- Check agent registration in registry
- Verify inbox directory permissions
- Check message TTL and expiration
- Look for errors in dead letter queue

**High retry rates:**
- Review circuit breaker metrics
- Check network connectivity
- Verify agent health status
- Examine error logs for patterns

**Memory leaks:**
- Monitor message queue sizes
- Check cleanup job status
- Verify session lifecycle management
- Review agent resource usage

**Performance degradation:**
- Check parallelism settings
- Monitor agent load distribution
- Review message size and frequency
- Verify no agents are blocking

This communication framework provides a robust foundation for inter-agent coordination with comprehensive error handling, monitoring, and performance optimization capabilities.