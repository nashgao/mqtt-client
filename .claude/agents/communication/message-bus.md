---
description: Advanced message bus system for inter-agent communication with protocols and JSON schemas
---

# Agent Message Bus System

Advanced message passing infrastructure for robust inter-agent communication, featuring structured protocols, reliable delivery, and comprehensive error handling.

## Message Bus Architecture

```yaml
message_bus_structure:
  components:
    publisher: "Agent that sends messages"
    subscriber: "Agent that receives messages" 
    broker: "Message routing and delivery system"
    persistence: "Message storage and recovery"
    
  layers:
    transport: "File-based, Redis, or WebSocket transport"
    routing: "Message destination and filtering"
    serialization: "JSON message encoding/decoding"
    delivery: "Reliable delivery guarantees"
    monitoring: "Message flow tracking and metrics"
```

## Core Message Schemas

### Base Message Schema

```json
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "type": "object",
  "required": ["id", "from", "to", "type", "timestamp", "payload"],
  "properties": {
    "id": {
      "type": "string",
      "pattern": "^msg-[0-9]{13}-[a-z0-9]{9}$",
      "description": "Unique message identifier"
    },
    "from": {
      "type": "string",
      "description": "Source agent identifier"
    },
    "to": {
      "type": ["string", "array"],
      "description": "Destination agent(s) or broadcast pattern",
      "oneOf": [
        {"type": "string"},
        {"type": "array", "items": {"type": "string"}}
      ]
    },
    "type": {
      "type": "string",
      "enum": [
        "request", "response", "notification", "error",
        "task_assignment", "task_result", "status_update",
        "heartbeat", "shutdown", "broadcast"
      ]
    },
    "timestamp": {
      "type": "string",
      "format": "date-time",
      "description": "ISO 8601 timestamp"
    },
    "payload": {
      "type": "object",
      "description": "Message content specific to type"
    },
    "headers": {
      "type": "object",
      "properties": {
        "priority": {
          "type": "integer",
          "minimum": 1,
          "maximum": 5,
          "default": 3
        },
        "ttl": {
          "type": "integer",
          "description": "Time to live in milliseconds",
          "default": 30000
        },
        "requires_ack": {
          "type": "boolean",
          "default": false
        },
        "correlation_id": {
          "type": "string",
          "description": "Request-response correlation"
        },
        "retry_count": {
          "type": "integer",
          "default": 0
        }
      }
    }
  }
}
```

### Task Assignment Message

```json
{
  "type": "task_assignment",
  "payload": {
    "task": {
      "id": "string",
      "type": "string",
      "description": "string",
      "parameters": "object",
      "deadline": "string (ISO 8601)",
      "priority": "integer (1-5)",
      "dependencies": ["array of task IDs"],
      "resources": {
        "cpu_limit": "string",
        "memory_limit": "string", 
        "timeout": "integer"
      }
    }
  }
}
```

### Task Result Message

```json
{
  "type": "task_result",
  "payload": {
    "task_id": "string",
    "status": "completed|failed|timeout",
    "result": "object|null",
    "error": {
      "code": "string",
      "message": "string",
      "details": "object"
    },
    "metrics": {
      "execution_time": "integer (ms)",
      "memory_used": "string",
      "cpu_used": "number"
    },
    "artifacts": ["array of file paths"]
  }
}
```

### Status Update Message

```json
{
  "type": "status_update",
  "payload": {
    "agent_status": "idle|busy|error|shutting_down",
    "current_tasks": ["array of task IDs"],
    "progress": {
      "current_task": "string",
      "percentage": "integer (0-100)",
      "eta": "string (ISO 8601)"
    },
    "health": {
      "cpu_usage": "number",
      "memory_usage": "string",
      "load_average": "number"
    }
  }
}
```

## Message Bus Implementation

### Message Publisher

```javascript
class MessagePublisher {
  constructor(sessionDir, agentId) {
    this.sessionDir = sessionDir;
    this.agentId = agentId;
    this.messageDir = `${sessionDir}/messages`;
    this.outboxDir = `${this.messageDir}/outbox`;
    this.sentDir = `${this.messageDir}/sent`;
    
    this.ensureDirectories();
  }
  
  async publish(message) {
    // Validate message schema
    this.validateMessage(message);
    
    // Generate unique ID if not provided
    if (!message.id) {
      message.id = this.generateMessageId();
    }
    
    // Set sender
    message.from = this.agentId;
    message.timestamp = new Date().toISOString();
    
    // Set default headers
    message.headers = {
      priority: 3,
      ttl: 30000,
      requires_ack: false,
      retry_count: 0,
      ...message.headers
    };
    
    try {
      // Handle different recipient types
      const recipients = Array.isArray(message.to) ? message.to : [message.to];
      
      for (const recipient of recipients) {
        await this.deliverMessage({...message, to: recipient});
      }
      
      // Archive sent message
      await this.archiveSentMessage(message);
      
      return message.id;
      
    } catch (error) {
      await this.handlePublishError(message, error);
      throw error;
    }
  }
  
  async deliverMessage(message) {
    const recipientQueue = `${this.messageDir}/inbox/${message.to}.json`;
    
    // Acquire lock for recipient queue
    const lockFile = `${recipientQueue}.lock`;
    await this.acquireLock(lockFile);
    
    try {
      // Read existing queue
      let queue = [];
      try {
        const data = await fs.readFile(recipientQueue, 'utf8');
        queue = JSON.parse(data);
      } catch (error) {
        // Queue doesn't exist yet
      }
      
      // Add message with metadata
      const queueEntry = {
        ...message,
        queued_at: new Date().toISOString(),
        delivery_attempts: 0
      };
      
      // Insert based on priority (higher priority first)
      const insertIndex = queue.findIndex(m => 
        (m.headers?.priority || 3) < (message.headers?.priority || 3)
      );
      
      if (insertIndex === -1) {
        queue.push(queueEntry);
      } else {
        queue.splice(insertIndex, 0, queueEntry);
      }
      
      // Write queue atomically
      const tempFile = `${recipientQueue}.tmp`;
      await fs.writeFile(tempFile, JSON.stringify(queue, null, 2));
      await fs.rename(tempFile, recipientQueue);
      
    } finally {
      await this.releaseLock(lockFile);
    }
  }
  
  async broadcastMessage(message, pattern = "*") {
    const registry = await this.getAgentRegistry();
    const recipients = this.matchAgents(registry, pattern);
    
    const broadcastMessage = {
      ...message,
      type: "broadcast",
      to: recipients,
      headers: {
        ...message.headers,
        broadcast_pattern: pattern
      }
    };
    
    return await this.publish(broadcastMessage);
  }
  
  generateMessageId() {
    const timestamp = Date.now();
    const random = Math.random().toString(36).substr(2, 9);
    return `msg-${timestamp}-${random}`;
  }
  
  validateMessage(message) {
    const requiredFields = ['to', 'type', 'payload'];
    
    for (const field of requiredFields) {
      if (!message[field]) {
        throw new Error(`Missing required field: ${field}`);
      }
    }
    
    // Validate message type
    const validTypes = [
      'request', 'response', 'notification', 'error',
      'task_assignment', 'task_result', 'status_update',
      'heartbeat', 'shutdown', 'broadcast'
    ];
    
    if (!validTypes.includes(message.type)) {
      throw new Error(`Invalid message type: ${message.type}`);
    }
  }
}
```

### Message Subscriber

```javascript
class MessageSubscriber {
  constructor(sessionDir, agentId) {
    this.sessionDir = sessionDir;
    this.agentId = agentId;
    this.messageDir = `${sessionDir}/messages`;
    this.inboxFile = `${this.messageDir}/inbox/${agentId}.json`;
    
    this.handlers = new Map();
    this.polling = false;
    this.pollInterval = 1000;
  }
  
  async subscribe(messageType, handler) {
    if (!this.handlers.has(messageType)) {
      this.handlers.set(messageType, []);
    }
    
    this.handlers.get(messageType).push(handler);
    
    // Start polling if not already started
    if (!this.polling) {
      this.startPolling();
    }
  }
  
  async startPolling() {
    this.polling = true;
    
    while (this.polling) {
      try {
        await this.processMessages();
      } catch (error) {
        console.error('Error processing messages:', error);
      }
      
      await new Promise(resolve => setTimeout(resolve, this.pollInterval));
    }
  }
  
  async processMessages() {
    const lockFile = `${this.inboxFile}.lock`;
    
    // Try to acquire lock (non-blocking)
    try {
      await this.acquireLock(lockFile, 100);
    } catch (error) {
      // Can't acquire lock, try next time
      return;
    }
    
    try {
      // Read inbox
      let messages = [];
      try {
        const data = await fs.readFile(this.inboxFile, 'utf8');
        messages = JSON.parse(data);
      } catch (error) {
        // No messages
        return;
      }
      
      if (messages.length === 0) {
        return;
      }
      
      // Process messages
      const processedMessages = [];
      const remainingMessages = [];
      
      for (const message of messages) {
        try {
          // Check TTL
          if (this.isExpired(message)) {
            continue; // Skip expired messages
          }
          
          // Process message
          await this.handleMessage(message);
          processedMessages.push(message);
          
        } catch (error) {
          // Handle processing error
          await this.handleMessageError(message, error);
          
          // Retry logic
          if (this.shouldRetry(message, error)) {
            message.delivery_attempts = (message.delivery_attempts || 0) + 1;
            message.last_error = {
              message: error.message,
              timestamp: new Date().toISOString()
            };
            remainingMessages.push(message);
          }
        }
      }
      
      // Update inbox with remaining messages
      await fs.writeFile(this.inboxFile, JSON.stringify(remainingMessages, null, 2));
      
      // Send acknowledgments for processed messages
      for (const message of processedMessages) {
        if (message.headers?.requires_ack) {
          await this.sendAcknowledgment(message);
        }
      }
      
    } finally {
      await this.releaseLock(lockFile);
    }
  }
  
  async handleMessage(message) {
    const handlers = this.handlers.get(message.type) || [];
    const wildcardHandlers = this.handlers.get('*') || [];
    
    const allHandlers = [...handlers, ...wildcardHandlers];
    
    if (allHandlers.length === 0) {
      console.warn(`No handlers for message type: ${message.type}`);
      return;
    }
    
    // Execute handlers in parallel
    await Promise.all(
      allHandlers.map(handler => 
        handler(message.payload, message)
      )
    );
  }
  
  async sendAcknowledgment(message) {
    const ack = {
      to: message.from,
      type: 'notification',
      payload: {
        acknowledgment: true,
        original_message_id: message.id,
        processed_at: new Date().toISOString()
      },
      headers: {
        correlation_id: message.id
      }
    };
    
    const publisher = new MessagePublisher(this.sessionDir, this.agentId);
    await publisher.publish(ack);
  }
  
  isExpired(message) {
    if (!message.headers?.ttl) {
      return false;
    }
    
    const messageTime = new Date(message.timestamp).getTime();
    const now = Date.now();
    const ttl = message.headers.ttl;
    
    return (now - messageTime) > ttl;
  }
  
  shouldRetry(message, error) {
    const maxRetries = 3;
    const currentRetries = message.delivery_attempts || 0;
    
    // Don't retry certain error types
    const nonRetryableErrors = ['ValidationError', 'AuthenticationError'];
    if (nonRetryableErrors.includes(error.constructor.name)) {
      return false;
    }
    
    return currentRetries < maxRetries;
  }
}
```

## Request-Response Protocol

### Request-Response Manager

```javascript
class RequestResponseManager {
  constructor(sessionDir, agentId) {
    this.publisher = new MessagePublisher(sessionDir, agentId);
    this.subscriber = new MessageSubscriber(sessionDir, agentId);
    this.pendingRequests = new Map();
    
    // Subscribe to responses
    this.subscriber.subscribe('response', (payload, message) => {
      this.handleResponse(message);
    });
  }
  
  async request(to, payload, options = {}) {
    const requestId = this.publisher.generateMessageId();
    const timeout = options.timeout || 30000;
    
    const requestMessage = {
      to,
      type: 'request',
      payload,
      headers: {
        correlation_id: requestId,
        requires_ack: false,
        ttl: timeout
      }
    };
    
    // Create promise for response
    const responsePromise = new Promise((resolve, reject) => {
      const timer = setTimeout(() => {
        this.pendingRequests.delete(requestId);
        reject(new Error(`Request timeout: ${requestId}`));
      }, timeout);
      
      this.pendingRequests.set(requestId, { resolve, reject, timer });
    });
    
    // Send request
    await this.publisher.publish(requestMessage);
    
    return responsePromise;
  }
  
  async respond(originalMessage, payload, success = true) {
    const response = {
      to: originalMessage.from,
      type: 'response',
      payload: {
        success,
        data: payload,
        request_id: originalMessage.id
      },
      headers: {
        correlation_id: originalMessage.headers?.correlation_id || originalMessage.id
      }
    };
    
    await this.publisher.publish(response);
  }
  
  handleResponse(message) {
    const correlationId = message.headers?.correlation_id;
    
    if (!correlationId || !this.pendingRequests.has(correlationId)) {
      return;
    }
    
    const { resolve, reject, timer } = this.pendingRequests.get(correlationId);
    clearTimeout(timer);
    this.pendingRequests.delete(correlationId);
    
    if (message.payload.success) {
      resolve(message.payload.data);
    } else {
      reject(new Error(message.payload.error || 'Request failed'));
    }
  }
}
```

## Shared State Management

### Distributed State Manager

```javascript
class DistributedState {
  constructor(sessionDir, agentId) {
    this.sessionDir = sessionDir;
    this.agentId = agentId;
    this.stateDir = `${sessionDir}/state`;
    this.publisher = new MessagePublisher(sessionDir, agentId);
    this.subscriber = new MessageSubscriber(sessionDir, agentId);
    
    this.setupStateSync();
  }
  
  setupStateSync() {
    // Listen for state change notifications
    this.subscriber.subscribe('state_change', (payload, message) => {
      this.handleStateChange(payload, message);
    });
    
    // Listen for state synchronization requests
    this.subscriber.subscribe('state_sync_request', (payload, message) => {
      this.handleStateSyncRequest(payload, message);
    });
  }
  
  async set(key, value, options = {}) {
    const stateFile = `${this.stateDir}/${key}.json`;
    const lockFile = `${stateFile}.lock`;
    
    await this.acquireLock(lockFile);
    
    try {
      const stateEntry = {
        value,
        updated_at: new Date().toISOString(),
        updated_by: this.agentId,
        version: await this.getNextVersion(key)
      };
      
      // Write state atomically
      const tempFile = `${stateFile}.tmp`;
      await fs.writeFile(tempFile, JSON.stringify(stateEntry, null, 2));
      await fs.rename(tempFile, stateFile);
      
      // Notify other agents if configured
      if (options.notify !== false) {
        await this.notifyStateChange(key, stateEntry, options);
      }
      
      return stateEntry.version;
      
    } finally {
      await this.releaseLock(lockFile);
    }
  }
  
  async get(key, options = {}) {
    const stateFile = `${this.stateDir}/${key}.json`;
    
    try {
      const data = await fs.readFile(stateFile, 'utf8');
      const stateEntry = JSON.parse(data);
      
      // Check if we need the latest version
      if (options.latest && this.isStale(stateEntry)) {
        await this.requestStateSync(key);
        // Re-read after sync
        const updatedData = await fs.readFile(stateFile, 'utf8');
        return JSON.parse(updatedData);
      }
      
      return stateEntry;
      
    } catch (error) {
      if (error.code === 'ENOENT') {
        return null;
      }
      throw error;
    }
  }
  
  async update(key, updater, options = {}) {
    const lockFile = `${this.stateDir}/${key}.json.lock`;
    
    await this.acquireLock(lockFile);
    
    try {
      const current = await this.get(key) || { value: null, version: 0 };
      const updated = updater(current.value);
      
      return await this.set(key, updated, options);
      
    } finally {
      await this.releaseLock(lockFile);
    }
  }
  
  async notifyStateChange(key, stateEntry, options) {
    const notification = {
      type: 'state_change',
      payload: {
        key,
        version: stateEntry.version,
        updated_by: stateEntry.updated_by,
        updated_at: stateEntry.updated_at,
        full_value: options.include_value ? stateEntry.value : null
      }
    };
    
    // Broadcast to interested agents
    if (options.subscribers) {
      notification.to = options.subscribers;
    } else {
      await this.publisher.broadcastMessage(notification, "*");
    }
  }
  
  async handleStateChange(payload, message) {
    // Update local cache or trigger refresh
    // Implementation depends on caching strategy
  }
  
  async requestStateSync(key) {
    const request = {
      type: 'state_sync_request',
      payload: { key }
    };
    
    await this.publisher.broadcastMessage(request, "*");
  }
}
```

## Event-Driven Coordination

### Event Bus

```javascript
class EventBus {
  constructor(sessionDir, agentId) {
    this.sessionDir = sessionDir;
    this.agentId = agentId;
    this.publisher = new MessagePublisher(sessionDir, agentId);
    this.subscriber = new MessageSubscriber(sessionDir, agentId);
    
    this.eventHandlers = new Map();
    this.setupEventHandling();
  }
  
  setupEventHandling() {
    this.subscriber.subscribe('event', (payload, message) => {
      this.handleEvent(payload, message);
    });
  }
  
  async emit(eventName, data, options = {}) {
    const event = {
      name: eventName,
      data,
      emitted_at: new Date().toISOString(),
      emitted_by: this.agentId,
      correlation_id: options.correlationId
    };
    
    const eventMessage = {
      type: 'event',
      payload: event,
      to: options.targets || '*'
    };
    
    if (options.targets) {
      eventMessage.to = Array.isArray(options.targets) ? options.targets : [options.targets];
      await this.publisher.publish(eventMessage);
    } else {
      await this.publisher.broadcastMessage(eventMessage, "*");
    }
    
    return event;
  }
  
  on(eventName, handler) {
    if (!this.eventHandlers.has(eventName)) {
      this.eventHandlers.set(eventName, []);
    }
    
    this.eventHandlers.get(eventName).push(handler);
  }
  
  off(eventName, handler) {
    if (this.eventHandlers.has(eventName)) {
      const handlers = this.eventHandlers.get(eventName);
      const index = handlers.indexOf(handler);
      if (index !== -1) {
        handlers.splice(index, 1);
      }
    }
  }
  
  async handleEvent(eventPayload, message) {
    const { name, data, emitted_by } = eventPayload;
    
    // Don't handle our own events
    if (emitted_by === this.agentId) {
      return;
    }
    
    const handlers = this.eventHandlers.get(name) || [];
    const wildcardHandlers = this.eventHandlers.get('*') || [];
    
    const allHandlers = [...handlers, ...wildcardHandlers];
    
    // Execute handlers
    await Promise.all(
      allHandlers.map(handler =>
        handler(data, eventPayload, message)
      )
    );
  }
}
```

## Error Handling and Recovery

### Message Recovery System

```javascript
class MessageRecovery {
  constructor(sessionDir) {
    this.sessionDir = sessionDir;
    this.deadLetterDir = `${sessionDir}/messages/dead-letter`;
    this.retryDir = `${sessionDir}/messages/retry`;
    
    this.ensureDirectories();
  }
  
  async handleFailedMessage(message, error, context = {}) {
    const failureRecord = {
      message,
      error: {
        name: error.name,
        message: error.message,
        stack: error.stack
      },
      context,
      failed_at: new Date().toISOString(),
      retry_count: message.delivery_attempts || 0
    };
    
    if (this.shouldDeadLetter(message, error)) {
      await this.deadLetter(failureRecord);
    } else if (this.shouldRetry(message, error)) {
      await this.scheduleRetry(failureRecord);
    }
  }
  
  async deadLetter(failureRecord) {
    const deadLetterFile = `${this.deadLetterDir}/${failureRecord.message.id}.json`;
    await fs.writeFile(deadLetterFile, JSON.stringify(failureRecord, null, 2));
  }
  
  async scheduleRetry(failureRecord) {
    const retryDelay = this.calculateRetryDelay(failureRecord.retry_count);
    const retryAt = new Date(Date.now() + retryDelay);
    
    const retryRecord = {
      ...failureRecord,
      retry_at: retryAt.toISOString(),
      retry_delay: retryDelay
    };
    
    const retryFile = `${this.retryDir}/${failureRecord.message.id}.json`;
    await fs.writeFile(retryFile, JSON.stringify(retryRecord, null, 2));
  }
  
  calculateRetryDelay(retryCount) {
    // Exponential backoff: 1s, 2s, 4s, 8s, 16s
    return Math.min(1000 * Math.pow(2, retryCount), 16000);
  }
  
  shouldDeadLetter(message, error) {
    const maxRetries = 5;
    const retryCount = message.delivery_attempts || 0;
    
    // Dead letter if max retries exceeded
    if (retryCount >= maxRetries) {
      return true;
    }
    
    // Dead letter for certain error types
    const deadLetterErrors = [
      'ValidationError',
      'AuthenticationError',
      'PermissionError'
    ];
    
    return deadLetterErrors.includes(error.constructor.name);
  }
  
  shouldRetry(message, error) {
    return !this.shouldDeadLetter(message, error);
  }
}
```

## Performance Monitoring

### Message Bus Metrics

```javascript
class MessageBusMetrics {
  constructor(sessionDir) {
    this.sessionDir = sessionDir;
    this.metricsFile = `${sessionDir}/metrics/message-bus.json`;
    
    this.metrics = {
      messages_sent: 0,
      messages_received: 0,
      messages_failed: 0,
      messages_dead_lettered: 0,
      average_delivery_time: 0,
      throughput: {
        per_second: 0,
        per_minute: 0
      },
      error_rates: {},
      agent_activity: {}
    };
    
    this.startMetricsCollection();
  }
  
  recordMessageSent(message) {
    this.metrics.messages_sent++;
    this.updateAgentActivity(message.from, 'sent');
    this.persistMetrics();
  }
  
  recordMessageReceived(message, processingTime) {
    this.metrics.messages_received++;
    this.updateAverageDeliveryTime(processingTime);
    this.updateAgentActivity(message.to, 'received');
    this.persistMetrics();
  }
  
  recordMessageFailed(message, error) {
    this.metrics.messages_failed++;
    this.updateErrorRates(error.constructor.name);
    this.persistMetrics();
  }
  
  updateAverageDeliveryTime(deliveryTime) {
    const currentAvg = this.metrics.average_delivery_time;
    const totalMessages = this.metrics.messages_received;
    
    this.metrics.average_delivery_time = 
      (currentAvg * (totalMessages - 1) + deliveryTime) / totalMessages;
  }
  
  updateErrorRates(errorType) {
    if (!this.metrics.error_rates[errorType]) {
      this.metrics.error_rates[errorType] = 0;
    }
    this.metrics.error_rates[errorType]++;
  }
  
  async generateReport() {
    const report = {
      ...this.metrics,
      success_rate: this.calculateSuccessRate(),
      timestamp: new Date().toISOString()
    };
    
    await fs.writeFile(
      `${this.sessionDir}/metrics/message-bus-report.json`,
      JSON.stringify(report, null, 2)
    );
    
    return report;
  }
}
```

## Quality Gates

**Message Bus Setup:**
- [ ] Session directory structure created
- [ ] Message schemas validated
- [ ] Publisher and subscriber initialized
- [ ] Error handling configured

**Message Flow:**
- [ ] Messages properly formatted
- [ ] Delivery guarantees working
- [ ] Priority queuing functional
- [ ] TTL enforcement active

**Error Handling:**
- [ ] Retry logic operational
- [ ] Dead letter queue working
- [ ] Recovery mechanisms tested
- [ ] Monitoring collecting metrics

**Performance:**
- [ ] Throughput meets requirements
- [ ] Latency within acceptable limits
- [ ] Memory usage stable
- [ ] No message leaks detected

The message bus system provides reliable, efficient inter-agent communication with comprehensive error handling, monitoring, and recovery capabilities.