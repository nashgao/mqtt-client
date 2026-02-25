---
description: Comprehensive error handling and retry mechanisms for inter-agent communication
---

# Agent Communication Error Handling

Advanced error handling, retry mechanisms, and fault tolerance for robust inter-agent communication in distributed environments.

## Error Classification System

```yaml
error_categories:
  transient:
    description: "Temporary errors that may resolve with retry"
    examples:
      - Network timeouts
      - Resource unavailable
      - Rate limiting
      - Temporary file locks
    retry_strategy: "Exponential backoff"
    max_retries: 5
    
  permanent:
    description: "Errors that won't resolve with retry"
    examples:
      - Authentication failures
      - Malformed messages
      - Permission denied
      - Agent not found
    retry_strategy: "No retry"
    action: "Dead letter or escalate"
    
  intermittent:
    description: "Errors that occur sporadically"
    examples:
      - Load balancer failures
      - DNS resolution issues
      - Partial network connectivity
    retry_strategy: "Linear backoff with jitter"
    max_retries: 3
    
  critical:
    description: "Errors requiring immediate attention"
    examples:
      - Message corruption
      - State inconsistency
      - Security violations
      - Data loss
    retry_strategy: "No retry"
    action: "Alert and escalate"
```

## Error Types and Handling

### Base Error Classes

```javascript
class CommunicationError extends Error {
  constructor(message, code, context = {}) {
    super(message);
    this.name = this.constructor.name;
    this.code = code;
    this.context = context;
    this.timestamp = new Date().toISOString();
    this.retryable = false;
  }
  
  toJSON() {
    return {
      name: this.name,
      message: this.message,
      code: this.code,
      context: this.context,
      timestamp: this.timestamp,
      retryable: this.retryable,
      stack: this.stack
    };
  }
}

class TransientError extends CommunicationError {
  constructor(message, code, context = {}) {
    super(message, code, context);
    this.retryable = true;
    this.category = 'transient';
  }
}

class PermanentError extends CommunicationError {
  constructor(message, code, context = {}) {
    super(message, code, context);
    this.retryable = false;
    this.category = 'permanent';
  }
}

class CriticalError extends CommunicationError {
  constructor(message, code, context = {}) {
    super(message, code, context);
    this.retryable = false;
    this.category = 'critical';
    this.requiresEscalation = true;
  }
}
```

### Specific Error Types

```javascript
// Network and Communication Errors
class NetworkTimeoutError extends TransientError {
  constructor(timeout, context = {}) {
    super(`Network timeout after ${timeout}ms`, 'NETWORK_TIMEOUT', context);
    this.timeout = timeout;
  }
}

class ConnectionRefusedError extends TransientError {
  constructor(target, context = {}) {
    super(`Connection refused to ${target}`, 'CONNECTION_REFUSED', context);
    this.target = target;
  }
}

class MessageTooLargeError extends PermanentError {
  constructor(size, maxSize, context = {}) {
    super(`Message size ${size} exceeds maximum ${maxSize}`, 'MESSAGE_TOO_LARGE', context);
    this.size = size;
    this.maxSize = maxSize;
  }
}

// Agent and Registry Errors  
class AgentNotFoundError extends PermanentError {
  constructor(agentId, context = {}) {
    super(`Agent not found: ${agentId}`, 'AGENT_NOT_FOUND', context);
    this.agentId = agentId;
  }
}

class AgentUnavailableError extends TransientError {
  constructor(agentId, context = {}) {
    super(`Agent unavailable: ${agentId}`, 'AGENT_UNAVAILABLE', context);
    this.agentId = agentId;
  }
}

class CapabilityMismatchError extends PermanentError {
  constructor(required, available, context = {}) {
    super(`Required capabilities ${required.join(',')} not available`, 'CAPABILITY_MISMATCH', context);
    this.required = required;
    this.available = available;
  }
}

// State and Data Errors
class StateCorruptionError extends CriticalError {
  constructor(key, expectedVersion, actualVersion, context = {}) {
    super(`State corruption detected for ${key}`, 'STATE_CORRUPTION', context);
    this.key = key;
    this.expectedVersion = expectedVersion;
    this.actualVersion = actualVersion;
  }
}

class DeadlockError extends TransientError {
  constructor(resources, context = {}) {
    super(`Deadlock detected involving resources: ${resources.join(',')}`, 'DEADLOCK', context);
    this.resources = resources;
  }
}

// Message and Protocol Errors
class MessageValidationError extends PermanentError {
  constructor(validationErrors, context = {}) {
    super(`Message validation failed: ${validationErrors.join(', ')}`, 'MESSAGE_VALIDATION', context);
    this.validationErrors = validationErrors;
  }
}

class ProtocolVersionError extends PermanentError {
  constructor(required, received, context = {}) {
    super(`Protocol version mismatch: required ${required}, received ${received}`, 'PROTOCOL_VERSION', context);
    this.required = required;
    this.received = received;
  }
}
```

## Retry Strategies

### Retry Strategy Interface

```javascript
class RetryStrategy {
  constructor(maxRetries = 3, baseDelay = 1000) {
    this.maxRetries = maxRetries;
    this.baseDelay = baseDelay;
  }
  
  shouldRetry(error, attempt) {
    return error.retryable && attempt <= this.maxRetries;
  }
  
  calculateDelay(attempt, error) {
    throw new Error('calculateDelay must be implemented by subclass');
  }
  
  onRetry(attempt, error, delay) {
    console.log(`Retry attempt ${attempt} for ${error.code} in ${delay}ms`);
  }
}
```

### Exponential Backoff Strategy

```javascript
class ExponentialBackoffStrategy extends RetryStrategy {
  constructor(maxRetries = 5, baseDelay = 1000, maxDelay = 30000, multiplier = 2) {
    super(maxRetries, baseDelay);
    this.maxDelay = maxDelay;
    this.multiplier = multiplier;
  }
  
  calculateDelay(attempt, error) {
    const exponentialDelay = this.baseDelay * Math.pow(this.multiplier, attempt - 1);
    const cappedDelay = Math.min(exponentialDelay, this.maxDelay);
    
    // Add jitter to avoid thundering herd
    const jitter = Math.random() * 0.1 * cappedDelay;
    
    return Math.floor(cappedDelay + jitter);
  }
}
```

### Linear Backoff Strategy

```javascript
class LinearBackoffStrategy extends RetryStrategy {
  constructor(maxRetries = 3, baseDelay = 1000, increment = 1000, maxDelay = 10000) {
    super(maxRetries, baseDelay);
    this.increment = increment;
    this.maxDelay = maxDelay;
  }
  
  calculateDelay(attempt, error) {
    const linearDelay = this.baseDelay + (this.increment * (attempt - 1));
    const cappedDelay = Math.min(linearDelay, this.maxDelay);
    
    // Add jitter for intermittent errors
    if (error.category === 'intermittent') {
      const jitter = Math.random() * 0.2 * cappedDelay;
      return Math.floor(cappedDelay + jitter);
    }
    
    return cappedDelay;
  }
}
```

### Adaptive Strategy

```javascript
class AdaptiveRetryStrategy extends RetryStrategy {
  constructor(maxRetries = 5, baseDelay = 1000) {
    super(maxRetries, baseDelay);
    this.errorHistory = new Map();
    this.successHistory = new Map();
  }
  
  calculateDelay(attempt, error) {
    const errorKey = `${error.code}:${error.context?.target || 'unknown'}`;
    const history = this.errorHistory.get(errorKey) || { attempts: 0, successes: 0 };
    
    // Calculate success rate
    const totalAttempts = history.attempts + 1;
    const successRate = totalAttempts > 0 ? history.successes / totalAttempts : 0;
    
    // Adjust delay based on historical success rate
    let delayMultiplier = 1;
    if (successRate < 0.3) {
      delayMultiplier = 3; // Poor success rate, wait longer
    } else if (successRate > 0.8) {
      delayMultiplier = 0.5; // Good success rate, retry faster
    }
    
    // Use exponential backoff with adaptive multiplier
    const baseDelay = this.baseDelay * delayMultiplier;
    const exponentialDelay = baseDelay * Math.pow(2, attempt - 1);
    
    // Update history
    history.attempts = totalAttempts;
    this.errorHistory.set(errorKey, history);
    
    return Math.min(exponentialDelay, 60000); // Cap at 1 minute
  }
  
  recordSuccess(error) {
    const errorKey = `${error.code}:${error.context?.target || 'unknown'}`;
    const history = this.errorHistory.get(errorKey) || { attempts: 0, successes: 0 };
    history.successes++;
    this.errorHistory.set(errorKey, history);
  }
}
```

## Circuit Breaker Pattern

```javascript
class CircuitBreaker {
  constructor(options = {}) {
    this.failureThreshold = options.failureThreshold || 5;
    this.resetTimeout = options.resetTimeout || 60000;
    this.monitoringPeriod = options.monitoringPeriod || 10000;
    
    this.state = 'CLOSED'; // CLOSED, OPEN, HALF_OPEN
    this.failures = 0;
    this.successes = 0;
    this.lastFailureTime = null;
    this.nextAttemptTime = null;
    
    this.onStateChange = options.onStateChange || (() => {});
  }
  
  async execute(operation, target) {
    if (this.state === 'OPEN') {
      if (Date.now() < this.nextAttemptTime) {
        throw new TransientError('Circuit breaker is OPEN', 'CIRCUIT_BREAKER_OPEN', {
          target,
          nextAttemptTime: this.nextAttemptTime
        });
      }
      
      // Transition to HALF_OPEN
      this.state = 'HALF_OPEN';
      this.onStateChange(this.state, target);
    }
    
    try {
      const result = await operation();
      this.onSuccess(target);
      return result;
      
    } catch (error) {
      this.onFailure(target, error);
      throw error;
    }
  }
  
  onSuccess(target) {
    this.failures = 0;
    
    if (this.state === 'HALF_OPEN') {
      this.state = 'CLOSED';
      this.onStateChange(this.state, target);
    }
    
    this.successes++;
  }
  
  onFailure(target, error) {
    this.failures++;
    this.lastFailureTime = Date.now();
    
    if (this.state === 'HALF_OPEN' || this.failures >= this.failureThreshold) {
      this.state = 'OPEN';
      this.nextAttemptTime = Date.now() + this.resetTimeout;
      this.onStateChange(this.state, target);
    }
  }
  
  getMetrics() {
    return {
      state: this.state,
      failures: this.failures,
      successes: this.successes,
      lastFailureTime: this.lastFailureTime,
      nextAttemptTime: this.nextAttemptTime
    };
  }
}
```

## Error Handler

```javascript
class ErrorHandler {
  constructor(sessionDir, agentId) {
    this.sessionDir = sessionDir;
    this.agentId = agentId;
    this.deadLetterQueue = new DeadLetterQueue(sessionDir);
    this.retryQueue = new RetryQueue(sessionDir);
    this.circuitBreakers = new Map();
    
    // Default strategies
    this.strategies = {
      transient: new ExponentialBackoffStrategy(5, 1000, 30000),
      intermittent: new LinearBackoffStrategy(3, 2000, 1000),
      adaptive: new AdaptiveRetryStrategy(5, 1000)
    };
    
    this.errorMetrics = {
      totalErrors: 0,
      errorsByType: {},
      retriedErrors: 0,
      deadLetteredErrors: 0
    };
  }
  
  async handleError(error, operation, context = {}) {
    this.errorMetrics.totalErrors++;
    
    // Update error type metrics
    const errorType = error.constructor.name;
    this.errorMetrics.errorsByType[errorType] = 
      (this.errorMetrics.errorsByType[errorType] || 0) + 1;
    
    // Log error
    await this.logError(error, operation, context);
    
    // Handle based on error category
    switch (error.category) {
      case 'critical':
        return await this.handleCriticalError(error, operation, context);
        
      case 'permanent':
        return await this.handlePermanentError(error, operation, context);
        
      case 'transient':
      case 'intermittent':
        return await this.handleRetryableError(error, operation, context);
        
      default:
        return await this.handleUnknownError(error, operation, context);
    }
  }
  
  async handleCriticalError(error, operation, context) {
    // Critical errors require immediate escalation
    await this.escalateError(error, operation, context);
    
    // Add to dead letter queue
    await this.deadLetterQueue.add({
      error,
      operation,
      context,
      reason: 'critical_error',
      timestamp: new Date().toISOString()
    });
    
    throw error; // Re-throw for immediate handling
  }
  
  async handlePermanentError(error, operation, context) {
    // No retry for permanent errors
    await this.deadLetterQueue.add({
      error,
      operation,
      context,
      reason: 'permanent_error',
      timestamp: new Date().toISOString()
    });
    
    this.errorMetrics.deadLetteredErrors++;
    throw error;
  }
  
  async handleRetryableError(error, operation, context) {
    const attempt = context.attempt || 1;
    const strategy = this.getRetryStrategy(error);
    
    if (!strategy.shouldRetry(error, attempt)) {
      // Max retries exceeded
      await this.deadLetterQueue.add({
        error,
        operation,
        context,
        reason: 'max_retries_exceeded',
        attempts: attempt,
        timestamp: new Date().toISOString()
      });
      
      this.errorMetrics.deadLetteredErrors++;
      throw error;
    }
    
    // Check circuit breaker
    const target = context.target || 'unknown';
    const circuitBreaker = this.getCircuitBreaker(target);
    
    try {
      await circuitBreaker.execute(async () => {
        // Circuit breaker allows execution
        const delay = strategy.calculateDelay(attempt, error);
        
        // Add to retry queue
        await this.retryQueue.add({
          operation,
          context: { ...context, attempt: attempt + 1 },
          retryAt: Date.now() + delay,
          delay
        });
        
        this.errorMetrics.retriedErrors++;
        strategy.onRetry(attempt, error, delay);
      }, target);
      
    } catch (circuitError) {
      // Circuit breaker is open
      await this.deadLetterQueue.add({
        error: circuitError,
        originalError: error,
        operation,
        context,
        reason: 'circuit_breaker_open',
        timestamp: new Date().toISOString()
      });
      
      throw circuitError;
    }
  }
  
  async handleUnknownError(error, operation, context) {
    // Treat unknown errors as transient with limited retries
    const transientError = new TransientError(error.message, 'UNKNOWN_ERROR', {
      originalError: error.constructor.name
    });
    
    return await this.handleRetryableError(transientError, operation, context);
  }
  
  getRetryStrategy(error) {
    switch (error.category) {
      case 'transient':
        return this.strategies.transient;
      case 'intermittent':
        return this.strategies.intermittent;
      default:
        return this.strategies.adaptive;
    }
  }
  
  getCircuitBreaker(target) {
    if (!this.circuitBreakers.has(target)) {
      const breaker = new CircuitBreaker({
        failureThreshold: 5,
        resetTimeout: 60000,
        onStateChange: (state, target) => {
          this.logCircuitBreakerState(state, target);
        }
      });
      
      this.circuitBreakers.set(target, breaker);
    }
    
    return this.circuitBreakers.get(target);
  }
  
  async logError(error, operation, context) {
    const logEntry = {
      timestamp: new Date().toISOString(),
      agent_id: this.agentId,
      error: error.toJSON(),
      operation,
      context,
      stack_trace: error.stack
    };
    
    const logFile = `${this.sessionDir}/logs/errors.log`;
    await fs.appendFile(logFile, JSON.stringify(logEntry) + '\n');
  }
  
  async escalateError(error, operation, context) {
    // Send escalation notification
    const escalation = {
      type: 'error_escalation',
      payload: {
        error: error.toJSON(),
        operation,
        context,
        agent_id: this.agentId,
        escalated_at: new Date().toISOString()
      },
      priority: 'urgent'
    };
    
    // Broadcast to monitoring agents
    const messageBus = new MessageBus(this.sessionDir, this.agentId);
    await messageBus.broadcast(escalation, 'monitoring-*');
  }
  
  getMetrics() {
    return {
      ...this.errorMetrics,
      circuitBreakers: Array.from(this.circuitBreakers.entries()).map(([target, breaker]) => ({
        target,
        metrics: breaker.getMetrics()
      }))
    };
  }
}
```

## Retry Queue Implementation

```javascript
class RetryQueue {
  constructor(sessionDir) {
    this.sessionDir = sessionDir;
    this.retryDir = `${sessionDir}/messages/retry`;
    this.processing = false;
    
    this.ensureDirectory();
    this.startProcessing();
  }
  
  async add(retryItem) {
    const retryId = this.generateRetryId();
    const retryFile = `${this.retryDir}/${retryId}.json`;
    
    const retryRecord = {
      id: retryId,
      ...retryItem,
      addedAt: new Date().toISOString()
    };
    
    await fs.writeFile(retryFile, JSON.stringify(retryRecord, null, 2));
  }
  
  async processRetries() {
    if (this.processing) return;
    this.processing = true;
    
    try {
      const files = await fs.readdir(this.retryDir);
      const now = Date.now();
      
      for (const file of files) {
        if (!file.endsWith('.json')) continue;
        
        const retryFile = `${this.retryDir}/${file}`;
        
        try {
          const retryData = JSON.parse(await fs.readFile(retryFile, 'utf8'));
          
          if (now >= retryData.retryAt) {
            // Execute retry
            await this.executeRetry(retryData);
            
            // Remove from retry queue
            await fs.unlink(retryFile);
          }
          
        } catch (error) {
          console.error(`Error processing retry ${file}:`, error);
        }
      }
      
    } finally {
      this.processing = false;
    }
  }
  
  async executeRetry(retryData) {
    const { operation, context } = retryData;
    
    try {
      // Execute the operation with retry context
      await operation(context);
      
    } catch (error) {
      // Handle retry failure
      const errorHandler = new ErrorHandler(this.sessionDir, context.agentId);
      await errorHandler.handleError(error, operation, context);
    }
  }
  
  startProcessing() {
    // Process retries every 5 seconds
    setInterval(() => {
      this.processRetries().catch(console.error);
    }, 5000);
  }
  
  generateRetryId() {
    return `retry-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
  }
  
  ensureDirectory() {
    if (!fs.existsSync(this.retryDir)) {
      fs.mkdirSync(this.retryDir, { recursive: true });
    }
  }
}
```

## Dead Letter Queue Implementation

```javascript
class DeadLetterQueue {
  constructor(sessionDir) {
    this.sessionDir = sessionDir;
    this.deadLetterDir = `${sessionDir}/messages/dead-letter`;
    this.maxSize = 1000; // Maximum dead letters to keep
    
    this.ensureDirectory();
  }
  
  async add(deadLetter) {
    const deadLetterId = this.generateDeadLetterId();
    const deadLetterFile = `${this.deadLetterDir}/${deadLetterId}.json`;
    
    const deadLetterRecord = {
      id: deadLetterId,
      ...deadLetter,
      deadLetteredAt: new Date().toISOString()
    };
    
    await fs.writeFile(deadLetterFile, JSON.stringify(deadLetterRecord, null, 2));
    
    // Cleanup if too many dead letters
    await this.cleanup();
  }
  
  async getAll() {
    const files = await fs.readdir(this.deadLetterDir);
    const deadLetters = [];
    
    for (const file of files) {
      if (file.endsWith('.json')) {
        try {
          const data = await fs.readFile(`${this.deadLetterDir}/${file}`, 'utf8');
          deadLetters.push(JSON.parse(data));
        } catch (error) {
          console.error(`Error reading dead letter ${file}:`, error);
        }
      }
    }
    
    return deadLetters.sort((a, b) => new Date(b.deadLetteredAt) - new Date(a.deadLetteredAt));
  }
  
  async cleanup() {
    const files = await fs.readdir(this.deadLetterDir);
    const jsonFiles = files.filter(f => f.endsWith('.json'));
    
    if (jsonFiles.length > this.maxSize) {
      // Sort by modification time and remove oldest
      const fileStats = await Promise.all(
        jsonFiles.map(async (file) => {
          const stats = await fs.stat(`${this.deadLetterDir}/${file}`);
          return { file, mtime: stats.mtime };
        })
      );
      
      fileStats.sort((a, b) => a.mtime - b.mtime);
      
      const toRemove = fileStats.slice(0, jsonFiles.length - this.maxSize);
      
      for (const { file } of toRemove) {
        await fs.unlink(`${this.deadLetterDir}/${file}`);
      }
    }
  }
  
  generateDeadLetterId() {
    return `dead-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
  }
  
  ensureDirectory() {
    if (!fs.existsSync(this.deadLetterDir)) {
      fs.mkdirSync(this.deadLetterDir, { recursive: true });
    }
  }
}
```

## Quality Gates

**Error Classification:**
- [ ] All error types properly categorized
- [ ] Retry logic matches error category
- [ ] Critical errors escalate immediately
- [ ] Permanent errors skip retry

**Retry Mechanisms:**
- [ ] Backoff strategies working correctly
- [ ] Circuit breakers prevent cascading failures
- [ ] Max retry limits enforced
- [ ] Jitter prevents thundering herd

**Fault Tolerance:**
- [ ] Dead letter queue captures failures
- [ ] Retry queue processes items correctly
- [ ] Error metrics tracked accurately
- [ ] State remains consistent during failures

**Recovery:**
- [ ] Failed operations can be retried
- [ ] State corruption detected and handled
- [ ] Circuit breakers reset properly
- [ ] Dead letters can be inspected and reprocessed

This error handling system provides comprehensive fault tolerance and recovery mechanisms for robust inter-agent communication in distributed environments.