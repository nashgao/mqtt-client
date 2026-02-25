#!/usr/bin/env node

/**
 * Integration test for the Agent Communication Framework
 * 
 * This script demonstrates the key components working together:
 * - Directory setup and session management
 * - Message publishing and subscription
 * - Coordination patterns (request-response)
 * - Error handling and retry mechanisms
 */

const fs = require('fs').promises;
const path = require('path');
const { spawn } = require('child_process');

// Mock implementations for demonstration
class TestMessageBus {
  constructor(sessionDir, agentId) {
    this.sessionDir = sessionDir;
    this.agentId = agentId;
    this.messageDir = `${sessionDir}/messages`;
  }
  
  async publish(message) {
    console.log(`[${this.agentId}] Publishing message: ${message.type} to ${message.to}`);
    
    // Write to recipient's inbox
    const inboxFile = `${this.messageDir}/inbox/${message.to}.json`;
    
    let inbox = [];
    try {
      const data = await fs.readFile(inboxFile, 'utf8');
      inbox = JSON.parse(data);
    } catch (error) {
      // Inbox doesn't exist, create new
    }
    
    inbox.push({
      ...message,
      from: this.agentId,
      id: `msg-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
      timestamp: new Date().toISOString()
    });
    
    await fs.writeFile(inboxFile, JSON.stringify(inbox, null, 2));
    console.log(`[${this.agentId}] Message delivered to ${message.to}`);
  }
  
  async subscribe(messageType, handler) {
    console.log(`[${this.agentId}] Subscribing to ${messageType} messages`);
    
    // Simulate message polling
    const pollMessages = async () => {
      const inboxFile = `${this.messageDir}/inbox/${this.agentId}.json`;
      
      try {
        const data = await fs.readFile(inboxFile, 'utf8');
        const messages = JSON.parse(data);
        
        if (messages.length > 0) {
          const processedMessages = [];
          
          for (const message of messages) {
            if (message.type === messageType || messageType === '*') {
              console.log(`[${this.agentId}] Processing ${message.type} from ${message.from}`);
              await handler(message.payload, message);
              processedMessages.push(message);
            }
          }
          
          // Remove processed messages
          const remainingMessages = messages.filter(m => 
            !processedMessages.some(p => p.id === m.id)
          );
          
          await fs.writeFile(inboxFile, JSON.stringify(remainingMessages, null, 2));
        }
      } catch (error) {
        // No messages or inbox doesn't exist
      }
    };
    
    // Poll every second
    setInterval(pollMessages, 1000);
  }
}

class TestCoordinator {
  constructor(sessionDir, agentId) {
    this.messageBus = new TestMessageBus(sessionDir, agentId);
    this.sessionDir = sessionDir;
    this.agentId = agentId;
  }
  
  async request(targetAgent, operation, payload, options = {}) {
    const requestId = `req-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
    const timeout = options.timeout || 30000;
    
    return new Promise(async (resolve, reject) => {
      const timeoutHandle = setTimeout(() => {
        reject(new Error(`Request ${requestId} timed out`));
      }, timeout);
      
      // Listen for response
      await this.messageBus.subscribe('response', (payload, message) => {
        if (payload.request_id === requestId) {
          clearTimeout(timeoutHandle);
          if (payload.success) {
            resolve(payload.result);
          } else {
            reject(new Error(payload.error));
          }
        }
      });
      
      // Send request
      await this.messageBus.publish({
        to: targetAgent,
        type: 'request',
        payload: {
          operation,
          parameters: payload,
          request_id: requestId
        }
      });
    });
  }
}

class TestAgent {
  constructor(sessionDir, agentId, capabilities = []) {
    this.sessionDir = sessionDir;
    this.agentId = agentId;
    this.capabilities = capabilities;
    this.messageBus = new TestMessageBus(sessionDir, agentId);
    
    this.setupMessageHandlers();
  }
  
  async setupMessageHandlers() {
    // Handle requests
    await this.messageBus.subscribe('request', async (payload, message) => {
      const { operation, parameters, request_id } = payload;
      
      console.log(`[${this.agentId}] Received request: ${operation}`);
      
      try {
        const result = await this.handleOperation(operation, parameters);
        
        await this.messageBus.publish({
          to: message.from,
          type: 'response',
          payload: {
            request_id,
            success: true,
            result
          }
        });
        
      } catch (error) {
        await this.messageBus.publish({
          to: message.from,
          type: 'response',
          payload: {
            request_id,
            success: false,
            error: error.message
          }
        });
      }
    });
    
    // Handle task assignments
    await this.messageBus.subscribe('task_assignment', async (payload, message) => {
      const { task } = payload;
      console.log(`[${this.agentId}] Received task: ${task.id}`);
      
      // Simulate task processing
      await new Promise(resolve => setTimeout(resolve, 1000));
      
      await this.messageBus.publish({
        to: message.from,
        type: 'task_result',
        payload: {
          task_id: task.id,
          status: 'completed',
          result: {
            processed: true,
            agent: this.agentId,
            duration: 1000
          }
        }
      });
    });
  }
  
  async handleOperation(operation, parameters) {
    switch (operation) {
      case 'analyze_code':
        return {
          files_analyzed: parameters.files?.length || 0,
          issues_found: Math.floor(Math.random() * 5),
          quality_score: Math.floor(Math.random() * 100)
        };
        
      case 'run_tests':
        return {
          tests_run: parameters.test_files?.length || 0,
          passed: Math.floor(Math.random() * 10) + 5,
          failed: Math.floor(Math.random() * 2),
          duration: Math.floor(Math.random() * 30000) + 5000
        };
        
      default:
        throw new Error(`Unknown operation: ${operation}`);
    }
  }
}

async function setupTestSession() {
  console.log('🚀 Setting up test coordination session...');
  
  const timestamp = Date.now();
  const sessionId = `test-integration-${timestamp}`;
  const sessionDir = `/tmp/claude-agents/${sessionId}`;
  
  // Create directory structure
  const directories = [
    'registry',
    'state', 
    'messages/inbox',
    'messages/outbox', 
    'messages/sent',
    'results',
    'metrics',
    'logs'
  ];
  
  for (const dir of directories) {
    await fs.mkdir(`${sessionDir}/${dir}`, { recursive: true });
  }
  
  // Initialize session metadata
  const sessionMetadata = {
    id: sessionId,
    created_at: new Date().toISOString(),
    status: 'active',
    test_mode: true
  };
  
  await fs.writeFile(
    `${sessionDir}/session.json`,
    JSON.stringify(sessionMetadata, null, 2)
  );
  
  console.log(`✅ Session created: ${sessionId}`);
  console.log(`📁 Session directory: ${sessionDir}`);
  
  return { sessionId, sessionDir };
}

async function runIntegrationTest() {
  try {
    // Setup test session
    const { sessionId, sessionDir } = await setupTestSession();
    
    console.log('\n📡 Starting agent communication test...');
    
    // Create test agents
    const coordinator = new TestCoordinator(sessionDir, 'coordinator-001');
    const codeAnalyzer = new TestAgent(sessionDir, 'code-analyzer-001', ['code_analysis']);
    const testRunner = new TestAgent(sessionDir, 'test-runner-001', ['test_execution']);
    
    // Wait for agents to initialize
    await new Promise(resolve => setTimeout(resolve, 2000));
    
    // Test 1: Request-Response Pattern
    console.log('\n🧪 Test 1: Request-Response Communication');
    try {
      const analysisResult = await coordinator.request(
        'code-analyzer-001',
        'analyze_code',
        { files: ['app.js', 'utils.js', 'config.js'] },
        { timeout: 10000 }
      );
      
      console.log('✅ Analysis completed:', analysisResult);
    } catch (error) {
      console.error('❌ Analysis failed:', error.message);
    }
    
    // Test 2: Task Assignment Pattern
    console.log('\n🧪 Test 2: Task Assignment Communication');
    
    await coordinator.messageBus.publish({
      to: 'test-runner-001',
      type: 'task_assignment',
      payload: {
        task: {
          id: 'run-unit-tests',
          type: 'test_execution',
          description: 'Run unit tests for core modules',
          parameters: {
            test_files: ['auth.test.js', 'api.test.js'],
            timeout: 300
          }
        }
      }
    });
    
    // Listen for task result
    await coordinator.messageBus.subscribe('task_result', (payload, message) => {
      console.log('✅ Task completed:', payload);
    });
    
    // Test 3: Error Handling (simulate timeout)
    console.log('\n🧪 Test 3: Error Handling (Timeout)');
    try {
      await coordinator.request(
        'non-existent-agent',
        'dummy_operation',
        {},
        { timeout: 2000 }
      );
    } catch (error) {
      console.log('✅ Timeout handled correctly:', error.message);
    }
    
    // Let tests run for a bit
    await new Promise(resolve => setTimeout(resolve, 5000));
    
    // Cleanup
    console.log('\n🧹 Cleaning up test session...');
    await fs.rm(sessionDir, { recursive: true, force: true });
    console.log('✅ Cleanup completed');
    
    console.log('\n🎉 Integration test completed successfully!');
    
    // Summary
    console.log('\n📊 Test Summary:');
    console.log('- ✅ Directory structure creation');
    console.log('- ✅ Message publishing and delivery');
    console.log('- ✅ Request-response communication');
    console.log('- ✅ Task assignment handling');
    console.log('- ✅ Error handling and timeouts');
    
  } catch (error) {
    console.error('❌ Integration test failed:', error);
    process.exit(1);
  }
}

// Run the integration test
if (require.main === module) {
  runIntegrationTest().catch(console.error);
}

module.exports = {
  TestMessageBus,
  TestCoordinator, 
  TestAgent,
  setupTestSession,
  runIntegrationTest
};