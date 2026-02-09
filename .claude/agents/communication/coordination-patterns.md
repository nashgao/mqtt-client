---
description: Advanced coordination patterns for inter-agent communication and task orchestration
---

# Agent Coordination Patterns

Comprehensive coordination patterns for robust inter-agent collaboration, featuring synchronous and asynchronous communication patterns, broadcast mechanisms, and result aggregation protocols.

## Coordination Pattern Categories

```yaml
pattern_categories:
  synchronous:
    - Request-Response
    - RPC (Remote Procedure Call)
    - Distributed Transactions
    - Consensus Protocols
    
  asynchronous:
    - Task Delegation
    - Event-Driven
    - Message Queuing
    - Fire-and-Forget
    
  broadcast:
    - Publish-Subscribe
    - Event Broadcasting
    - State Synchronization
    - Command Distribution
    
  aggregation:
    - Map-Reduce
    - Scatter-Gather
    - Pipeline Processing
    - Result Collection
```

## Synchronous Communication Patterns

### Request-Response Pattern

```yaml
request_response:
  description: "Synchronous communication where sender waits for response"
  use_cases:
    - Data queries
    - Configuration requests
    - Status checks
    - Validation requests
  
  characteristics:
    blocking: true
    timeout_required: true
    error_handling: "Exception propagation"
    ordering: "Maintained"
```

#### Request-Response Implementation

```javascript
class RequestResponseCoordinator {
  constructor(sessionDir, agentId) {
    this.messageBus = new MessageBus(sessionDir, agentId);
    this.pendingRequests = new Map();
    this.responseHandlers = new Map();
    
    this.setupResponseHandling();
  }
  
  async request(targetAgent, operation, payload, options = {}) {
    const requestId = this.generateRequestId();
    const timeout = options.timeout || 30000;
    
    const requestMessage = {
      id: requestId,
      to: targetAgent,
      type: 'request',
      payload: {
        operation,
        parameters: payload,
        request_id: requestId
      },
      headers: {
        correlation_id: requestId,
        timeout: timeout,
        requires_response: true
      }
    };
    
    // Create response promise
    const responsePromise = new Promise((resolve, reject) => {
      const timeoutHandle = setTimeout(() => {
        this.pendingRequests.delete(requestId);
        reject(new RequestTimeoutError(`Request ${requestId} timed out`));
      }, timeout);
      
      this.pendingRequests.set(requestId, {
        resolve,
        reject,
        timeoutHandle,
        startTime: Date.now()
      });
    });
    
    // Send request
    await this.messageBus.publish(requestMessage);
    
    return responsePromise;
  }
  
  async respond(request, result, success = true) {
    const response = {
      to: request.from,
      type: 'response',
      payload: {
        request_id: request.payload.request_id,
        success,
        result,
        processed_at: new Date().toISOString()
      },
      headers: {
        correlation_id: request.headers.correlation_id
      }
    };
    
    await this.messageBus.publish(response);
  }
  
  onRequest(operation, handler) {
    this.responseHandlers.set(operation, handler);
  }
  
  async handleIncomingRequest(message) {
    const { operation, parameters, request_id } = message.payload;
    const handler = this.responseHandlers.get(operation);
    
    if (!handler) {
      await this.respond(message, 
        { error: `Unknown operation: ${operation}` }, false);
      return;
    }
    
    try {
      const result = await handler(parameters, message);
      await this.respond(message, result, true);
    } catch (error) {
      await this.respond(message, 
        { error: error.message }, false);
    }
  }
}
```

### Distributed RPC Pattern

```javascript
class DistributedRPC {
  constructor(sessionDir, agentId) {
    this.coordinator = new RequestResponseCoordinator(sessionDir, agentId);
    this.services = new Map();
    this.clientProxies = new Map();
  }
  
  // Register service
  registerService(serviceName, implementation) {
    this.services.set(serviceName, implementation);
    
    // Handle RPC calls for this service
    this.coordinator.onRequest(`rpc:${serviceName}`, async (params, message) => {
      const { method, args } = params;
      
      if (!implementation[method]) {
        throw new Error(`Method ${method} not found in service ${serviceName}`);
      }
      
      return await implementation[method](...args);
    });
  }
  
  // Create client proxy
  createProxy(serviceName, targetAgent) {
    if (this.clientProxies.has(`${targetAgent}:${serviceName}`)) {
      return this.clientProxies.get(`${targetAgent}:${serviceName}`);
    }
    
    const proxy = new Proxy({}, {
      get: (target, method) => {
        if (typeof method !== 'string') {
          return target[method];
        }
        
        return async (...args) => {
          return await this.coordinator.request(targetAgent, `rpc:${serviceName}`, {
            method,
            args
          });
        };
      }
    });
    
    this.clientProxies.set(`${targetAgent}:${serviceName}`, proxy);
    return proxy;
  }
}
```

## Asynchronous Communication Patterns

### Task Delegation Pattern

```yaml
task_delegation:
  description: "Distribute work to available agents without waiting"
  use_cases:
    - Parallel processing
    - Load distribution
    - Background jobs
    - Independent tasks
  
  characteristics:
    blocking: false
    acknowledgment: "Optional"
    error_handling: "Callback-based"
    ordering: "Not guaranteed"
```

#### Task Delegation Implementation

```javascript
class TaskDelegator {
  constructor(sessionDir, agentId) {
    this.messageBus = new MessageBus(sessionDir, agentId);
    this.agentRegistry = new AgentRegistry(sessionDir);
    this.taskQueue = new TaskQueue(sessionDir);
    
    this.distributionStrategies = {
      round_robin: new RoundRobinStrategy(),
      least_loaded: new LeastLoadedStrategy(),
      capability_match: new CapabilityMatchStrategy(),
      random: new RandomStrategy()
    };
  }
  
  async delegateTask(task, options = {}) {
    const strategy = options.strategy || 'capability_match';
    const requiredCapabilities = options.capabilities || task.capabilities || [];
    
    // Find suitable agents
    const candidates = await this.findCandidateAgents(requiredCapabilities);
    
    if (candidates.length === 0) {
      // Queue task for later
      await this.taskQueue.enqueue(task);
      throw new Error('No suitable agents available');
    }
    
    // Select agent using strategy
    const selectedAgent = this.distributionStrategies[strategy].select(candidates, task);
    
    // Create task assignment
    const assignment = {
      to: selectedAgent.id,
      type: 'task_assignment',
      payload: {
        task_id: task.id,
        task_type: task.type,
        parameters: task.parameters,
        deadline: task.deadline,
        priority: task.priority,
        callback_required: options.callback !== false
      },
      headers: {
        delegation_id: this.generateDelegationId(),
        delegated_at: new Date().toISOString()
      }
    };
    
    // Send assignment
    await this.messageBus.publish(assignment);
    
    // Update agent load
    await this.agentRegistry.updateLoad(selectedAgent.id, +1);
    
    return {
      delegationId: assignment.headers.delegation_id,
      assignedTo: selectedAgent.id,
      taskId: task.id
    };
  }
  
  async delegateTasksBatch(tasks, options = {}) {
    const maxConcurrency = options.maxConcurrency || 10;
    const results = [];
    
    // Process tasks in batches
    for (let i = 0; i < tasks.length; i += maxConcurrency) {
      const batch = tasks.slice(i, i + maxConcurrency);
      
      const batchPromises = batch.map(task => 
        this.delegateTask(task, options).catch(error => ({ error, task }))
      );
      
      const batchResults = await Promise.all(batchPromises);
      results.push(...batchResults);
    }
    
    return results;
  }
  
  async findCandidateAgents(requiredCapabilities) {
    const allAgents = await this.agentRegistry.getActiveAgents();
    
    return allAgents.filter(agent => {
      // Check if agent has required capabilities
      return requiredCapabilities.every(capability => 
        agent.capabilities.includes(capability)
      );
    });
  }
}
```

### Event-Driven Pattern

```javascript
class EventDrivenCoordinator {
  constructor(sessionDir, agentId) {
    this.messageBus = new MessageBus(sessionDir, agentId);
    this.eventSubscriptions = new Map();
    this.eventHistory = [];
    this.maxHistorySize = 1000;
    
    this.setupEventHandling();
  }
  
  async emitEvent(eventType, data, options = {}) {
    const event = {
      id: this.generateEventId(),
      type: eventType,
      data,
      emitted_by: this.agentId,
      emitted_at: new Date().toISOString(),
      correlation_id: options.correlationId,
      tags: options.tags || []
    };
    
    // Store in history
    this.eventHistory.push(event);
    if (this.eventHistory.length > this.maxHistorySize) {
      this.eventHistory.shift();
    }
    
    // Create event message
    const eventMessage = {
      type: 'event',
      payload: event,
      headers: {
        event_type: eventType,
        broadcast: true
      }
    };
    
    // Broadcast or send to specific targets
    if (options.targets) {
      eventMessage.to = Array.isArray(options.targets) ? options.targets : [options.targets];
      await this.messageBus.publish(eventMessage);
    } else {
      await this.messageBus.broadcast(eventMessage);
    }
    
    return event;
  }
  
  subscribe(eventType, handler, options = {}) {
    if (!this.eventSubscriptions.has(eventType)) {
      this.eventSubscriptions.set(eventType, []);
    }
    
    const subscription = {
      handler,
      options,
      id: this.generateSubscriptionId()
    };
    
    this.eventSubscriptions.get(eventType).push(subscription);
    
    // Return unsubscribe function
    return () => {
      const subscriptions = this.eventSubscriptions.get(eventType);
      const index = subscriptions.findIndex(s => s.id === subscription.id);
      if (index !== -1) {
        subscriptions.splice(index, 1);
      }
    };
  }
  
  async handleEvent(event, message) {
    const subscriptions = this.eventSubscriptions.get(event.type) || [];
    const wildcardSubscriptions = this.eventSubscriptions.get('*') || [];
    
    const allSubscriptions = [...subscriptions, ...wildcardSubscriptions];
    
    // Execute subscriptions with filtering
    const executions = allSubscriptions.map(async (subscription) => {
      // Apply filters
      if (subscription.options.filter && !subscription.options.filter(event)) {
        return;
      }
      
      // Apply debouncing if configured
      if (subscription.options.debounce) {
        await this.debounce(subscription.id, subscription.options.debounce);
      }
      
      try {
        await subscription.handler(event.data, event, message);
      } catch (error) {
        console.error(`Error in event handler for ${event.type}:`, error);
      }
    });
    
    await Promise.all(executions);
  }
}
```

## Broadcast Communication Patterns

### Publish-Subscribe Pattern

```javascript
class PubSubCoordinator {
  constructor(sessionDir, agentId) {
    this.messageBus = new MessageBus(sessionDir, agentId);
    this.topicSubscriptions = new Map();
    this.topicFilters = new Map();
    
    this.setupPubSubHandling();
  }
  
  async publish(topic, message, options = {}) {
    const publication = {
      topic,
      message,
      published_by: this.agentId,
      published_at: new Date().toISOString(),
      message_id: this.generateMessageId(),
      headers: options.headers || {}
    };
    
    const pubMessage = {
      type: 'publication',
      payload: publication,
      headers: {
        topic,
        broadcast: true,
        delivery_mode: options.deliveryMode || 'best_effort'
      }
    };
    
    // Find subscribers
    const subscribers = await this.getTopicSubscribers(topic);
    
    if (subscribers.length === 0 && !options.allowEmpty) {
      throw new Error(`No subscribers for topic: ${topic}`);
    }
    
    // Send to subscribers
    for (const subscriber of subscribers) {
      pubMessage.to = subscriber;
      await this.messageBus.publish({...pubMessage});
    }
    
    return publication;
  }
  
  async subscribe(topic, options = {}) {
    const subscription = {
      topic,
      subscriber: this.agentId,
      subscribed_at: new Date().toISOString(),
      options
    };
    
    // Register subscription
    if (!this.topicSubscriptions.has(topic)) {
      this.topicSubscriptions.set(topic, new Set());
    }
    
    this.topicSubscriptions.get(topic).add(this.agentId);
    
    // Store filters if provided
    if (options.filter) {
      this.topicFilters.set(`${topic}:${this.agentId}`, options.filter);
    }
    
    // Notify other agents of subscription
    await this.messageBus.broadcast({
      type: 'subscription',
      payload: subscription
    });
    
    return subscription;
  }
  
  async unsubscribe(topic) {
    const subscriptions = this.topicSubscriptions.get(topic);
    if (subscriptions) {
      subscriptions.delete(this.agentId);
      
      // Remove filters
      this.topicFilters.delete(`${topic}:${this.agentId}`);
      
      // Clean up empty topic
      if (subscriptions.size === 0) {
        this.topicSubscriptions.delete(topic);
      }
    }
    
    // Notify other agents
    await this.messageBus.broadcast({
      type: 'unsubscription',
      payload: {
        topic,
        subscriber: this.agentId,
        unsubscribed_at: new Date().toISOString()
      }
    });
  }
  
  async getTopicSubscribers(topic) {
    const subscribers = this.topicSubscriptions.get(topic) || new Set();
    const wildcardSubscribers = this.topicSubscriptions.get('*') || new Set();
    
    // Check for pattern matching topics
    const patternSubscribers = new Set();
    for (const [subscribedTopic, subs] of this.topicSubscriptions) {
      if (this.topicMatches(topic, subscribedTopic)) {
        for (const sub of subs) {
          patternSubscribers.add(sub);
        }
      }
    }
    
    return [...new Set([...subscribers, ...wildcardSubscribers, ...patternSubscribers])];
  }
}
```

### State Synchronization Pattern

```javascript
class StateSyncCoordinator {
  constructor(sessionDir, agentId) {
    this.messageBus = new MessageBus(sessionDir, agentId);
    this.state = new Map();
    this.stateVersions = new Map();
    this.syncSubscriptions = new Set();
    
    this.setupStateSyncHandling();
  }
  
  async updateState(key, value, options = {}) {
    const oldValue = this.state.get(key);
    const newVersion = (this.stateVersions.get(key) || 0) + 1;
    
    // Update local state
    this.state.set(key, value);
    this.stateVersions.set(key, newVersion);
    
    // Create state update message
    const stateUpdate = {
      key,
      value,
      old_value: oldValue,
      version: newVersion,
      updated_by: this.agentId,
      updated_at: new Date().toISOString(),
      change_type: oldValue === undefined ? 'create' : 'update'
    };
    
    // Broadcast to subscribed agents
    if (options.broadcast !== false) {
      await this.broadcastStateUpdate(stateUpdate, options);
    }
    
    return stateUpdate;
  }
  
  async broadcastStateUpdate(update, options = {}) {
    const message = {
      type: 'state_update',
      payload: update,
      headers: {
        state_key: update.key,
        version: update.version,
        broadcast: true
      }
    };
    
    if (options.targets) {
      message.to = options.targets;
      await this.messageBus.publish(message);
    } else {
      // Send to all subscribed agents
      const subscribers = Array.from(this.syncSubscriptions);
      if (subscribers.length > 0) {
        message.to = subscribers;
        await this.messageBus.publish(message);
      }
    }
  }
  
  async subscribeToStateSync(keys = ['*']) {
    this.syncSubscriptions.add(this.agentId);
    
    // Request initial state sync
    for (const key of keys) {
      await this.requestStateSync(key);
    }
  }
  
  async requestStateSync(key) {
    const request = {
      type: 'state_sync_request',
      payload: {
        key,
        requested_by: this.agentId,
        requested_at: new Date().toISOString()
      }
    };
    
    await this.messageBus.broadcast(request);
  }
  
  async handleStateSyncRequest(request, message) {
    const { key } = request;
    
    if (key === '*') {
      // Send all state
      for (const [stateKey, value] of this.state) {
        await this.sendStateSnapshot(stateKey, value, message.from);
      }
    } else if (this.state.has(key)) {
      // Send specific key
      await this.sendStateSnapshot(key, this.state.get(key), message.from);
    }
  }
  
  async sendStateSnapshot(key, value, target) {
    const snapshot = {
      type: 'state_snapshot',
      payload: {
        key,
        value,
        version: this.stateVersions.get(key),
        snapshot_at: new Date().toISOString()
      },
      to: target
    };
    
    await this.messageBus.publish(snapshot);
  }
}
```

## Result Aggregation Patterns

### Map-Reduce Pattern

```javascript
class MapReduceCoordinator {
  constructor(sessionDir, agentId) {
    this.messageBus = new MessageBus(sessionDir, agentId);
    this.taskQueue = new TaskQueue(sessionDir);
    this.activeJobs = new Map();
  }
  
  async mapReduce(data, mapFunction, reduceFunction, options = {}) {
    const jobId = this.generateJobId();
    const chunkSize = options.chunkSize || 100;
    const maxWorkers = options.maxWorkers || 10;
    
    // Split data into chunks
    const chunks = this.chunkData(data, chunkSize);
    
    const job = {
      id: jobId,
      chunks: chunks.length,
      mapResults: [],
      completed: 0,
      startTime: Date.now(),
      options
    };
    
    this.activeJobs.set(jobId, job);
    
    try {
      // Map phase - distribute chunks to workers
      const mapPromises = chunks.map(async (chunk, index) => {
        return await this.delegateMapTask(jobId, index, chunk, mapFunction, options);
      });
      
      // Wait for all map tasks to complete
      job.mapResults = await Promise.all(mapPromises);
      
      // Reduce phase - combine results
      const finalResult = await this.performReduce(job.mapResults, reduceFunction, options);
      
      return {
        jobId,
        result: finalResult,
        executionTime: Date.now() - job.startTime,
        chunksProcessed: job.chunks
      };
      
    } finally {
      this.activeJobs.delete(jobId);
    }
  }
  
  async delegateMapTask(jobId, chunkIndex, chunk, mapFunction, options) {
    const task = {
      id: `${jobId}-map-${chunkIndex}`,
      type: 'map_task',
      payload: {
        job_id: jobId,
        chunk_index: chunkIndex,
        data: chunk,
        map_function: mapFunction.toString(),
        options
      }
    };
    
    // Find available worker
    const worker = await this.findAvailableWorker(['map_reduce']);
    
    const mapMessage = {
      to: worker.id,
      type: 'task_assignment',
      payload: task,
      headers: {
        job_id: jobId,
        task_type: 'map',
        timeout: options.taskTimeout || 30000
      }
    };
    
    // Send and wait for result
    return new Promise((resolve, reject) => {
      const timeout = setTimeout(() => {
        reject(new Error(`Map task ${task.id} timed out`));
      }, options.taskTimeout || 30000);
      
      // Listen for result
      this.messageBus.subscribe('task_result', (result, message) => {
        if (result.task_id === task.id) {
          clearTimeout(timeout);
          if (result.success) {
            resolve(result.data);
          } else {
            reject(new Error(result.error));
          }
        }
      });
      
      // Send task
      this.messageBus.publish(mapMessage);
    });
  }
  
  async performReduce(mapResults, reduceFunction, options) {
    if (options.distributedReduce && mapResults.length > options.reduceThreshold) {
      // Distributed reduce for large datasets
      return await this.distributedReduce(mapResults, reduceFunction, options);
    } else {
      // Local reduce
      return this.localReduce(mapResults, reduceFunction);
    }
  }
  
  localReduce(mapResults, reduceFunction) {
    return mapResults.reduce((acc, result) => {
      return reduceFunction(acc, result);
    });
  }
  
  chunkData(data, chunkSize) {
    const chunks = [];
    for (let i = 0; i < data.length; i += chunkSize) {
      chunks.push(data.slice(i, i + chunkSize));
    }
    return chunks;
  }
}
```

### Scatter-Gather Pattern

```javascript
class ScatterGatherCoordinator {
  constructor(sessionDir, agentId) {
    this.messageBus = new MessageBus(sessionDir, agentId);
    this.activeGathers = new Map();
  }
  
  async scatterGather(task, targets, options = {}) {
    const gatherId = this.generateGatherId();
    const timeout = options.timeout || 30000;
    const waitForAll = options.waitForAll !== false;
    const minResponses = options.minResponses || (waitForAll ? targets.length : 1);
    
    const gather = {
      id: gatherId,
      task,
      targets,
      responses: [],
      startTime: Date.now(),
      options
    };
    
    this.activeGathers.set(gatherId, gather);
    
    return new Promise((resolve, reject) => {
      const timeoutHandle = setTimeout(() => {
        this.activeGathers.delete(gatherId);
        
        if (gather.responses.length >= minResponses) {
          resolve(this.processGatherResults(gather));
        } else {
          reject(new Error(`Scatter-gather timeout: only ${gather.responses.length}/${minResponses} responses received`));
        }
      }, timeout);
      
      // Listen for responses
      const responseHandler = (result, message) => {
        if (message.headers?.gather_id === gatherId) {
          gather.responses.push({
            from: message.from,
            result,
            receivedAt: Date.now()
          });
          
          // Check if we have enough responses
          if (gather.responses.length >= minResponses) {
            clearTimeout(timeoutHandle);
            this.activeGathers.delete(gatherId);
            resolve(this.processGatherResults(gather));
          }
        }
      };
      
      this.messageBus.subscribe('scatter_response', responseHandler);
      
      // Scatter task to all targets
      const scatterPromises = targets.map(target => 
        this.scatterTask(gatherId, task, target, options)
      );
      
      Promise.all(scatterPromises).catch(error => {
        clearTimeout(timeoutHandle);
        this.activeGathers.delete(gatherId);
        reject(error);
      });
    });
  }
  
  async scatterTask(gatherId, task, target, options) {
    const scatterMessage = {
      to: target,
      type: 'scatter_task',
      payload: {
        task,
        gather_id: gatherId,
        scattered_at: new Date().toISOString()
      },
      headers: {
        gather_id: gatherId,
        requires_response: true,
        response_type: 'scatter_response'
      }
    };
    
    await this.messageBus.publish(scatterMessage);
  }
  
  processGatherResults(gather) {
    const results = gather.responses.map(r => r.result);
    const executionTime = Date.now() - gather.startTime;
    
    return {
      gatherId: gather.id,
      results,
      responseCount: gather.responses.length,
      executionTime,
      firstResponse: Math.min(...gather.responses.map(r => r.receivedAt - gather.startTime)),
      lastResponse: Math.max(...gather.responses.map(r => r.receivedAt - gather.startTime))
    };
  }
}
```

## Pipeline Processing Pattern

```javascript
class PipelineCoordinator {
  constructor(sessionDir, agentId) {
    this.messageBus = new MessageBus(sessionDir, agentId);
    this.activePipelines = new Map();
    this.pipelineDefinitions = new Map();
  }
  
  definePipeline(name, stages, options = {}) {
    const pipeline = {
      name,
      stages,
      options: {
        parallelism: options.parallelism || 1,
        errorHandling: options.errorHandling || 'stop',
        timeout: options.timeout || 300000,
        ...options
      }
    };
    
    this.pipelineDefinitions.set(name, pipeline);
    return pipeline;
  }
  
  async executePipeline(pipelineName, initialData, options = {}) {
    const pipelineDefinition = this.pipelineDefinitions.get(pipelineName);
    if (!pipelineDefinition) {
      throw new Error(`Pipeline not found: ${pipelineName}`);
    }
    
    const executionId = this.generateExecutionId();
    const execution = {
      id: executionId,
      pipeline: pipelineDefinition,
      data: initialData,
      currentStage: 0,
      stageResults: [],
      startTime: Date.now(),
      status: 'running'
    };
    
    this.activePipelines.set(executionId, execution);
    
    try {
      let stageData = initialData;
      
      for (let i = 0; i < pipelineDefinition.stages.length; i++) {
        const stage = pipelineDefinition.stages[i];
        execution.currentStage = i;
        
        const stageResult = await this.executeStage(execution, stage, stageData);
        execution.stageResults.push(stageResult);
        
        // Pass result to next stage
        stageData = stageResult.output;
      }
      
      execution.status = 'completed';
      execution.finalResult = stageData;
      execution.executionTime = Date.now() - execution.startTime;
      
      return execution;
      
    } catch (error) {
      execution.status = 'failed';
      execution.error = error;
      throw error;
      
    } finally {
      // Keep execution record for a while for monitoring
      setTimeout(() => {
        this.activePipelines.delete(executionId);
      }, 60000);
    }
  }
  
  async executeStage(execution, stage, inputData) {
    const stageExecution = {
      stage: stage.name,
      startTime: Date.now(),
      agentAssignments: []
    };
    
    if (stage.parallel && Array.isArray(inputData)) {
      // Parallel processing of array data
      const parallelResults = await this.executeStageParallel(execution, stage, inputData);
      stageExecution.output = parallelResults;
    } else {
      // Sequential processing
      const sequentialResult = await this.executeStageSequential(execution, stage, inputData);
      stageExecution.output = sequentialResult;
    }
    
    stageExecution.executionTime = Date.now() - stageExecution.startTime;
    return stageExecution;
  }
  
  async executeStageParallel(execution, stage, dataArray) {
    const maxParallelism = stage.parallelism || execution.pipeline.options.parallelism;
    const results = [];
    
    // Process in parallel batches
    for (let i = 0; i < dataArray.length; i += maxParallelism) {
      const batch = dataArray.slice(i, i + maxParallelism);
      
      const batchPromises = batch.map(async (item, index) => {
        const worker = await this.findStageWorker(stage);
        return await this.delegateStageTask(execution, stage, item, worker);
      });
      
      const batchResults = await Promise.all(batchPromises);
      results.push(...batchResults);
    }
    
    return results;
  }
  
  async executeStageSequential(execution, stage, inputData) {
    const worker = await this.findStageWorker(stage);
    return await this.delegateStageTask(execution, stage, inputData, worker);
  }
  
  async delegateStageTask(execution, stage, data, worker) {
    const stageTask = {
      execution_id: execution.id,
      stage_name: stage.name,
      data,
      stage_config: stage.config || {}
    };
    
    const taskMessage = {
      to: worker.id,
      type: 'pipeline_stage_task',
      payload: stageTask,
      headers: {
        execution_id: execution.id,
        stage_name: stage.name,
        timeout: stage.timeout || 30000
      }
    };
    
    return new Promise((resolve, reject) => {
      const timeout = setTimeout(() => {
        reject(new Error(`Stage task timeout: ${stage.name}`));
      }, stage.timeout || 30000);
      
      this.messageBus.subscribe('pipeline_stage_result', (result, message) => {
        if (message.headers?.execution_id === execution.id && 
            message.payload.stage_name === stage.name) {
          clearTimeout(timeout);
          
          if (result.success) {
            resolve(result.data);
          } else {
            reject(new Error(result.error));
          }
        }
      });
      
      this.messageBus.publish(taskMessage);
    });
  }
}
```

## Quality Gates

**Pattern Implementation:**
- [ ] Synchronous patterns handle timeouts properly
- [ ] Asynchronous patterns have proper error callbacks
- [ ] Broadcast patterns filter recipients correctly
- [ ] Aggregation patterns handle partial failures

**Message Flow:**
- [ ] Request-response correlation IDs working
- [ ] Event subscriptions properly filtered
- [ ] State synchronization conflict resolution
- [ ] Pipeline data flow validated

**Error Handling:**
- [ ] Timeout handling implemented
- [ ] Retry mechanisms configured
- [ ] Dead letter handling active
- [ ] Circuit breakers functional

**Performance:**
- [ ] Parallelism limits enforced
- [ ] Memory usage bounded
- [ ] Network efficiency optimized
- [ ] Monitoring metrics collected

These coordination patterns provide a comprehensive foundation for robust inter-agent communication and task orchestration in distributed systems.