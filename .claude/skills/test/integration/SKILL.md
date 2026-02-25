---
allowed-tools: all
description: Orchestrate integration tests with service dependency management and cross-system validation
intensity: ⚡⚡⚡⚡
pattern: 🔗🔗🔗🔗
---

# 🔗🔗🔗🔗 CRITICAL INTEGRATION TEST ORCHESTRATION: COMPREHENSIVE SYSTEM VALIDATION! 🔗🔗🔗🔗

**THIS IS NOT A SIMPLE TEST RUN - THIS IS A COMPREHENSIVE INTEGRATION TESTING SYSTEM!**

## 🚨 ZERO TOLERANCE ENFORCEMENT

**MANDATORY - ALL tests must achieve PERFECT execution:**

### Success Criteria (ALL must be met)
- ✅ **0 Failed Tests** - Every single test must pass
- ✅ **0 Errors** - No runtime errors allowed
- ✅ **0 Warnings** - Warnings are treated as failures
- ✅ **0 Deprecations** - Deprecation notices block success
- ✅ **0 Incomplete Tests** - Incomplete tests are failures
- ✅ **0 Risky Tests** - Risky test detection must pass
- ✅ **0 Skipped Tests** - Unless explicitly allowed with justification

### Failure Response Protocol
When ANY issue is detected:
1. **STOP** - Do not proceed to next steps
2. **REPORT** - List all issues with file:line references
3. **FIX** - Resolve ALL issues before continuing
4. **VERIFY** - Re-run tests to confirm 100% clean execution

### Exit Codes
- `0` = Perfect execution (no warnings, no deprecations, no failures)
- `1` = Any failure, warning, deprecation, or incomplete test
- `2` = Configuration or setup error

## ⚠️ SPECIFICATION-FIRST PHILOSOPHY (MANDATORY)

**Integration tests must define service contracts and interaction specifications BEFORE examining existing implementations.**
- Define what the integration SHOULD do from the consumer's perspective
- Test the actual consumer lifecycle (boot, configure, execute) — not internal function calls
- Service contracts are specifications — if the test fails, the integration is wrong
- See `templates/CLAUDE.md` → "MANDATORY: Specification-First Testing" for full mandate

## 📋 TEST SCOPE DEFINITION

**This command executes INTEGRATION TESTS ONLY - completely separate from unit tests:**

### Scope Boundaries
- ✅ **INCLUDES**: All integration tests (`*IntegrationTest.php`, `*.integration.test.ts`, etc.)
- ❌ **EXCLUDES**: Unit tests (those should use `/test unit`)
- ❌ **EXCLUDES**: E2E tests (those should use `/test e2e`)

### What "ALL Tests" Means for Integration Tests
When running `/test integration` with no arguments:
- Execute ALL integration tests in the project
- Do NOT include unit tests
- Do NOT include E2E tests
- Test real integrations between components

### Integration Test Characteristics
- Real external dependencies (database, cache, queues)
- Slower execution (seconds per test)
- May have shared setup (fixtures, seeds)
- Tests actual integration points

### Context Detection
This command automatically detects integration tests by:
- Directory patterns: `tests/Integration/`, `test/integration/`
- File patterns: `*IntegrationTest.php`, `*.integration.test.ts`
- Annotations: `@group integration`, `@integration`
- Configuration: `phpunit.xml` integration suite

When you run `/test integration`, you are REQUIRED to:

1. **ORCHESTRATE** integration tests across multiple services and systems
2. **MANAGE** service dependencies and test environment setup
3. **VALIDATE** cross-system communication and data flow
4. **USE MULTIPLE AGENTS** for parallel integration testing:
   - Spawn one agent per service or integration point
   - Spawn agents for different integration types (API, database, message queue)
   - Say: "I'll spawn multiple agents to orchestrate integration tests across all system boundaries"
5. **COORDINATE** test execution sequence and dependency management
6. **VERIFY** system behavior under realistic conditions

## 🎯 USE MULTIPLE AGENTS

**MANDATORY AGENT SPAWNING FOR INTEGRATION TEST ORCHESTRATION:**
```
"I'll spawn multiple agents to handle integration testing comprehensively:
- Service Orchestration Agent: Manage service startup and dependency coordination
- API Integration Agent: Test REST/GraphQL APIs and service communication
- Database Integration Agent: Validate data persistence and consistency
- Message Queue Agent: Test async communication and event processing
- Environment Agent: Setup and teardown test environments
- Monitoring Agent: Track integration test health and performance"
```

## 🚨 FORBIDDEN BEHAVIORS

**NEVER:**
- ❌ Run integration tests without proper service setup → NO! Environment is critical!
- ❌ "Unit tests cover this" → NO! Integration tests verify system behavior!
- ❌ **"Accept any integration test failures"** → NO! 100% SUCCESS RATE MANDATORY!
- ❌ **"Continue with failing integration tests"** → NO! ALL FAILURES MUST BE FIXED!
- ❌ Skip database/external service tests → NO! Test real integrations!
- ❌ Ignore test data management → NO! Proper test data is essential!
- ❌ Run tests against production systems → NO! Use dedicated test environments!
- ❌ "Mock everything" → NO! Test real service interactions!
- ❌ Analyzing existing infrastructure before defining integration specifications

**MANDATORY WORKFLOW:**
```
1. Environment setup → Start services and dependencies
2. IMMEDIATELY spawn 6 agents for parallel integration testing
3. AGENT RESULT VERIFICATION → Validate all agents completed successfully
4. Service dependency validation → Verify all services are ready
5. Execute integration tests → Run cross-system validation
6. **100% SUCCESS VALIDATION** → BLOCK EXECUTION if any integration test fails
7. Data consistency verification → Check data integrity only after 100% success
8. FINAL SUCCESS VALIDATION → Verify all integration tests pass
```

## TASK TOOL AGENT SPAWNING (MANDATORY)

I'll spawn 6 specialized agents using Task tool for comprehensive integration testing:

### Service Orchestration Agent:
```markdown
<function_calls>
<invoke name="Task">
<parameter name="subagent_type">test-fixer</parameter>
<parameter name="description">Orchestrate service dependencies</parameter>
<parameter name="prompt">You are the Service Orchestration Agent for integration testing.

Your responsibilities:
1. Define service contracts from specifications BEFORE analyzing existing infrastructure
2. Start and manage service dependencies (databases, message queues, external services)
3. Coordinate service startup order and health checks
4. Setup test environment isolation and cleanup
5. Validate service connectivity and readiness
6. Monitor service health during test execution

MANDATORY TEST ENVIRONMENT SETUP:
You MUST actually start services and validate connectivity:
- Start Docker containers or services required for integration tests
- Execute health checks to verify service readiness
- Setup test databases with proper schemas and test data
- Configure message queues and external service mocks

MANDATORY RESULT TRACKING:
- You MUST save orchestration results to /tmp/test-integration-orchestration-results.json
- Include success: true/false, services_started, health_checks_passed
- Document service startup logs and any connectivity issues
- Report services that failed to start or connect

CRITICAL: Integration tests cannot proceed without properly orchestrated services.</parameter>
</invoke>
</function_calls>
```

### API Integration Agent:
```markdown
<function_calls>
<invoke name="Task">
<parameter name="subagent_type">test-fixer</parameter>
<parameter name="description">Test API integrations</parameter>
<parameter name="prompt">You are the API Integration Agent for cross-service testing.

Your responsibilities:
1. Execute integration tests for REST APIs and GraphQL endpoints
2. Test cross-service communication and data flow
3. Validate API contracts and response formats
4. Test authentication and authorization across services
5. Monitor API performance and response times

MANDATORY API TEST EXECUTION:
You MUST actually execute API integration tests:
- Run integration tests that call real API endpoints
- Test service-to-service communication patterns
- Validate data transformation and error handling
- Execute authentication flows and permission checks

MANDATORY RESULT TRACKING:
- You MUST save API test results to /tmp/test-integration-api-results.json
- Include success: true/false, tests_executed, failures_detected
- Document API response times and any communication failures
- Only execute after Service Orchestration Agent confirms services are ready

CRITICAL: API integration tests must use real service endpoints, not mocks.</parameter>
</invoke>
</function_calls>
```

### Database Integration Agent:
```markdown
<function_calls>
<invoke name="Task">
<parameter name="subagent_type">test-fixer</parameter>
<parameter name="description">Test database integrations</parameter>
<parameter name="prompt">You are the Database Integration Agent for data persistence testing.

Your responsibilities:
1. Execute database integration tests with real database connections
2. Test data persistence, transactions, and consistency
3. Validate database migrations and schema changes
4. Test database performance under realistic loads
5. Verify data integrity across service boundaries

MANDATORY DATABASE TEST EXECUTION:
You MUST actually execute database integration tests:
- Run tests against real test databases, not in-memory mocks
- Test transaction handling and rollback scenarios
- Validate data consistency across multiple services
- Execute migration tests and schema validation

MANDATORY RESULT TRACKING:
- You MUST save database test results to /tmp/test-integration-database-results.json
- Include success: true/false, database_tests_passed, data_consistency_verified
- Document database connection issues and transaction failures
- Only execute after Service Orchestration Agent confirms database is ready

CRITICAL: Database integration tests must use real database connections.</parameter>
</invoke>
</function_calls>
```

### Message Queue Agent:
```markdown
<function_calls>
<invoke name="Task">
<parameter name="subagent_type">test-fixer</parameter>
<parameter name="description">Test message queue integrations</parameter>
<parameter name="prompt">You are the Message Queue Agent for asynchronous communication testing.

Your responsibilities:
1. Test message queue integrations and event processing
2. Validate message serialization and deserialization
3. Test message routing and delivery guarantees
4. Verify error handling and dead letter queues
5. Monitor message processing performance

MANDATORY MESSAGE QUEUE TEST EXECUTION:
You MUST actually execute message queue integration tests:
- Send and receive messages through real message queues
- Test event-driven communication patterns
- Validate message ordering and delivery guarantees
- Test error scenarios and retry mechanisms

MANDATORY RESULT TRACKING:
- You MUST save message queue test results to /tmp/test-integration-messagequeue-results.json
- Include success: true/false, messages_processed, delivery_confirmed
- Document message processing delays and any delivery failures
- Only execute after Service Orchestration Agent confirms message queue is ready

CRITICAL: Message queue tests must use real queues, not in-memory alternatives.</parameter>
</invoke>
</function_calls>
```

### Environment Setup Agent:
```markdown
<function_calls>
<invoke name="Task">
<parameter name="subagent_type">test-fixer</parameter>
<parameter name="description">Setup and teardown test environments</parameter>
<parameter name="prompt">You are the Environment Setup Agent for test environment management.

Your responsibilities:
1. Setup complete test environment with proper isolation
2. Configure test data and environment variables
3. Manage test environment cleanup and teardown
4. Ensure test environment consistency and repeatability
5. Handle environment-specific configuration

MANDATORY ENVIRONMENT OPERATIONS:
You MUST actually setup and manage test environments:
- Create isolated test environment configurations
- Setup test databases with proper schemas and seed data
- Configure environment variables for test execution
- Manage test environment cleanup after execution

MANDATORY RESULT TRACKING:
- You MUST save environment results to /tmp/test-integration-environment-results.json
- Include success: true/false, environment_setup, cleanup_completed
- Document environment setup logs and configuration issues
- Coordinate with other agents for environment readiness

CRITICAL: Test environment must be properly isolated and repeatable.</parameter>
</invoke>
</function_calls>
```

### Integration Test Coordinator:
```markdown
<function_calls>
<invoke name="Task">
<parameter name="subagent_type">test-fixer</parameter>
<parameter name="description">Coordinate integration test execution</parameter>
<parameter name="prompt">You are the Integration Test Coordinator for comprehensive test orchestration.

Your responsibilities:
1. Coordinate execution of all integration test phases
2. Aggregate results from all integration test agents
3. Generate comprehensive integration test reports
4. Monitor test execution progress and dependencies
5. Validate overall integration test success

MANDATORY RESULT AGGREGATION:
- Aggregate results from /tmp/test-integration-*-results.json files
- Validate all integration test agents completed successfully
- Create unified integration test status report
- Coordinate test cleanup and environment teardown

MANDATORY RESULT TRACKING:
- You MUST save coordination results to /tmp/test-integration-coordinator-results.json
- Include success: true/false based on overall integration test success
- Document completion status for all integration test phases
- Report any coordination failures or missing agent results

CRITICAL: Integration tests are only successful if ALL agents report success.</parameter>
</invoke>
</function_calls>
```

## AGENT RESULT VERIFICATION (MANDATORY)

After spawning all 6 integration test agents, you MUST verify their results:

```bash
# MANDATORY: Verify all agents completed successfully
AGENT_RESULTS_DIR="/tmp"
AGENT_FILES=("test-integration-orchestration-results.json" "test-integration-api-results.json" "test-integration-database-results.json" "test-integration-messagequeue-results.json" "test-integration-environment-results.json" "test-integration-coordinator-results.json")

for result_file in "${AGENT_FILES[@]}"; do
    FULL_PATH="$AGENT_RESULTS_DIR/$result_file"
    if [ -f "$FULL_PATH" ]; then
        # Use jq to parse agent results
        AGENT_SUCCESS=$(jq -r '.success // false' "$FULL_PATH" 2>/dev/null || echo 'false')
        if [ "$AGENT_SUCCESS" != "true" ]; then
            echo "❌ CRITICAL: Integration test agent failed to complete successfully"
            echo "   Failed agent result: $result_file"
            echo "   Check agent logs for failure details"
            exit 1
        fi
    else
        echo "❌ CRITICAL: Missing integration test agent result file: $result_file"
        echo "   Agent may have failed to complete or save results"
        exit 1
    fi
done

echo "✅ All integration test agents completed successfully"
```

## FRAMEWORK-SPECIFIC INTEGRATION TEST EXECUTION (MANDATORY)

After agent coordination, you MUST execute actual integration tests:

```bash
# Detect framework and run appropriate integration tests
if [ -f "package.json" ] && grep -q "jest\|mocha\|vitest" package.json; then
    echo "🔗 Executing Jest/Node.js integration tests..."
    npx jest --testPathPattern="integration|e2e" --verbose --runInBand
    INTEGRATION_EXIT_CODE=$?
elif [ -f "requirements.txt" ] || [ -f "setup.py" ] || [ -f "pyproject.toml" ]; then
    echo "🔗 Executing pytest integration tests..."
    python -m pytest tests/integration/ -v --tb=short
    INTEGRATION_EXIT_CODE=$?
elif ls *.go 1> /dev/null 2>&1; then
    echo "🔗 Executing Go integration tests..."
    go test -tags=integration -v ./...
    INTEGRATION_EXIT_CODE=$?
elif [ -f "composer.json" ] && [ -d "vendor/phpunit" ]; then
    echo "🔗 Executing PHPUnit integration tests..."
    ./vendor/bin/phpunit tests/Integration/ --verbose
    INTEGRATION_EXIT_CODE=$?
elif [ -f "Gemfile" ] && grep -q "rspec" Gemfile; then
    echo "🔗 Executing RSpec integration tests..."
    bundle exec rspec spec/integration/ --format documentation
    INTEGRATION_EXIT_CODE=$?
else
    echo "❌ No supported test framework detected for integration tests"
    exit 1
fi

# MANDATORY: Validate integration test execution success
if [ $INTEGRATION_EXIT_CODE -ne 0 ]; then
    echo "❌ CRITICAL: Integration tests failed with exit code $INTEGRATION_EXIT_CODE"
    echo "   All integration tests must pass before proceeding"
    echo "   Check test output above for failure details"
    exit $INTEGRATION_EXIT_CODE
fi

echo "✅ All integration tests executed successfully"
```

**YOU ARE NOT DONE UNTIL:**
- ✅ **100% INTEGRATION TEST SUCCESS RATE ACHIEVED** - NO FAILURES ALLOWED
- ✅ ALL integration tests are passing
- ✅ Service dependencies are properly managed
- ✅ Cross-system communication is validated
- ✅ **ZERO FAILED INTEGRATION TESTS** - Any failure must be fixed before proceeding
- ✅ Data consistency is verified
- ✅ Error handling and recovery is tested
- ✅ Performance under realistic load is acceptable

---

🛑 **MANDATORY INTEGRATION TEST ORCHESTRATION CHECK** 🛑
1. Re-read ~/.claude/CLAUDE.md RIGHT NOW
2. Check current system architecture and service dependencies
3. Verify you understand the integration testing requirements

Execute comprehensive integration test orchestration for: $ARGUMENTS

**Test Output Best Practices:**

⚠️ **CRITICAL: Clean Integration Test Output**
- NO debug console output in integration test code
- Use structured logging for service communication tracking
- For PHP integration tests, use TestDebugHelper trait (templates/shared/test-debug-helper.md)
- Output debugging ONLY via TEST_DEBUG=1 environment variable
- Ensure CI/CD pipelines receive clean, parseable output

**Command-line PHP Control Flags:**
Parse and respect these flags in $ARGUMENTS:
- `--no-php`: Skip all PHP-specific behaviors and structure validation
- `--skip-php-structure-check`: Skip PHP structure validation only

**FORBIDDEN SHORTCUT PATTERNS:**
- "Integration tests are too complex" → NO, they're essential for system quality
- "Services are already tested individually" → NO, test interactions
- "Database tests are optional" → NO, data consistency is critical
- "Test environments are hard to setup" → NO, automate the setup
- "Mocking is easier than real services" → NO, test real integrations

## 🐳 DOCKER CONTAINER CLEANUP ARCHITECTURE

**Three-Layer Cleanup Strategy for Maximum Reliability:**

### Layer 1: Signal-Based Trap Handlers
**Purpose**: Catch unexpected exits, timeouts, and interrupts
```bash
# Automatically registered at test start
trap 'cleanup_docker_containers "$project_dir"' EXIT INT TERM

# Triggers on:
- Normal test completion (EXIT)
- Ctrl+C interruption (INT)
- Kill signal (TERM)
- Script errors (via set -e)
```

### Layer 2: Timeout-Based Forced Cleanup
**Purpose**: Prevent hung containers when tests exceed time limits
```bash
# Default 10-minute timeout (configurable via INTEGRATION_TEST_TIMEOUT)
timeout "$test_timeout" bash -c "$test_command"

# Exit code 124 = timeout
if [ $test_exit_code -eq 124 ]; then
    # Forced cleanup triggered
    cleanup_docker_containers "$project_dir"
fi
```

### Layer 3: Explicit Post-Test Cleanup
**Purpose**: Guaranteed cleanup regardless of exit path
```bash
# Always executed after test command
cleanup_docker_containers "$project_dir"

# Even if tests fail, cleanup still runs
return $test_exit_code
```

### Cleanup Scope (What Gets Removed)

**1. Docker Compose Services:**
- `docker-compose.test.yml` (test-specific)
- `docker-compose.yml` (default fallback)
- Flags: `-v` (volumes), `--remove-orphans`

**2. Labeled Containers:**
- Filter: `label=test.type=integration`
- Force removal with `docker rm -f`

**3. Labeled Volumes:**
- Filter: `label=test.type=integration`
- Prevents disk space accumulation

**4. Orphaned Networks:**
- `docker network prune -f`
- Removes unused test networks

**5. Test Processes:**
- Kills `TEST_SERVER_PID` if set
- Prevents zombie processes

### Container Labeling Strategy

**All integration test containers MUST include:**
```yaml
labels:
  - "test.type=integration"
  - "test.service=<service-name>"  # database, cache, application, etc.
```

**All integration test volumes MUST include:**
```yaml
labels:
  - "test.type=integration"
  - "test.volume=<purpose>"  # database, uploads, etc.
```

### Timeout Configuration

**Environment Variable:**
```bash
export INTEGRATION_TEST_TIMEOUT=600  # seconds (default: 10 minutes)
```

**Override per test run:**
```bash
INTEGRATION_TEST_TIMEOUT=300 /test integration  # 5 minutes
```

**Recommended timeouts by project size:**
- Small projects: 300s (5 min)
- Medium projects: 600s (10 min)
- Large projects: 1200s (20 min)
- CI/CD pipelines: 900s (15 min)

### Cleanup Verification

**Check cleanup succeeded:**
```bash
# No integration test containers should remain
docker ps -a --filter "label=test.type=integration"

# No integration test volumes should remain
docker volume ls --filter "label=test.type=integration"

# Both should return empty results
```

### Troubleshooting Cleanup Issues

**Problem**: Containers not cleaning up
**Solutions**:
1. Verify labels are present: `docker inspect <container>`
2. Check docker-compose.test.yml exists
3. Ensure Docker daemon is responsive
4. Try manual cleanup: `docker-compose down -v --remove-orphans`

**Problem**: Timeout not triggering cleanup
**Solutions**:
1. Verify `timeout` command is installed: `which timeout`
2. Check `INTEGRATION_TEST_TIMEOUT` is set correctly
3. Ensure trap handlers are registered
4. Review test logs for timeout messages

**Problem**: Volumes persist after cleanup
**Solutions**:
1. Add volume labels to docker-compose.yml
2. Run manual cleanup: `docker volume prune -f`
3. Check for volume mount points in use
4. Stop all containers using volume first

### Best Practices

**DO:**
✅ Always label integration test containers and volumes
✅ Use health checks with reasonable timeouts
✅ Set appropriate `INTEGRATION_TEST_TIMEOUT` values
✅ Monitor Docker disk usage: `docker system df`
✅ Test cleanup in CI/CD environments

**DON'T:**
❌ Skip container labels (cleanup won't work)
❌ Set infinite timeouts (prevents cleanup)
❌ Ignore cleanup warnings/errors
❌ Reuse production container names
❌ Mount production volumes in tests

Let me ultrathink about the comprehensive integration testing architecture and orchestration strategy.

🚨 **REMEMBER: Integration tests verify that your system works as a whole!** 🚨

**Comprehensive Integration Test Orchestration Protocol:**

**Step 0: System Architecture Analysis**
- **PHP control validation**: Check PHP structure generation preferences and command flags
- **Structure validation**: Ensure comprehensive test structure exists (respecting PHP opt-out settings)
- Map all service dependencies and integration points
- Identify external systems and third-party services
- Analyze data flow and communication patterns
- Document API contracts and message formats
- Assess service startup order and dependencies

**Integration Test Structure Validation:**
```bash
# Validate integration test structure
validate_integration_structure() {
    local project_dir=${1:-.}
    local command_args=${2:-""}
    
    echo "=== Integration Test Structure Validation ==="
    echo ""
    
    # Check PHP control mechanisms first
    local php_status=$(get_php_status_message "$project_dir" "$command_args")
    echo "$php_status"
    echo ""
    
    # Parse command flags for structure check override
    local flags=$(parse_php_test_flags "$command_args")
    local skip_structure_flag=$(echo "$flags" | sed 's/.*skip_structure_check:\([^ ]*\).*/\1/')
    local php_disabled_flag=$(echo "$flags" | sed 's/.*php_disabled:\([^ ]*\).*/\1/')
    
    # Skip PHP structure validation if disabled or overridden
    if [ "$skip_structure_flag" = "true" ] || [ "$php_disabled_flag" = "true" ] || is_php_structure_disabled "$project_dir"; then
        echo "✅ Integration test structure validation skipped (PHP features disabled)"
        return 0
    fi
    
    # Only enforce PHP structure if PHP project is detected
    if detect_php_framework "$project_dir" >/dev/null 2>&1; then
        local integration_dirs=("tests/Integration" "tests/Integration/Database" "tests/Integration/Api")
        
        # Check for integration test directories
        for dir in "${integration_dirs[@]}"; do
            if [ ! -d "$project_dir/$dir" ]; then
                echo "⚠️  Missing integration test directory: $dir"
                echo ""
                echo "🏗️  You need a comprehensive test structure for integration testing!"
                echo "   Run: /test structure"
                echo "   This will create the complete integration test infrastructure"
                echo ""
                echo "💡 To disable PHP structure validation, use:"
                echo "   --skip-php-structure-check flag OR"
                echo "   export CLAUDE_PHP_TESTS=false OR"
                echo "   touch .claude/no-php-tests"
                echo ""
                return 1
            fi
        done
    else
        # Non-PHP project - check for basic integration test directory
        if [ ! -d "$project_dir/test/integration" ] && [ ! -d "$project_dir/tests/integration" ] && [ ! -d "$project_dir/integration" ]; then
            echo "⚠️  No integration test directory found"
            echo "   Consider creating: test/integration/ or tests/integration/ directory"
            echo ""
        fi
    fi
    
    echo "✅ Integration test structure validation completed"
    return 0
}
```

**Step 1: Test Environment Infrastructure**

**Service Dependency Management:**
```yaml
integration_test_environment:
  services:
    database:
      type: postgresql
      version: "14"
      setup_script: "scripts/setup-test-db.sql"
      cleanup_script: "scripts/cleanup-test-db.sql"
      
    redis:
      type: redis
      version: "7"
      configuration: "config/redis-test.conf"
      
    message_queue:
      type: rabbitmq
      version: "3.11"
      exchanges: ["orders", "notifications", "payments"]
      
    external_services:
      payment_gateway:
        type: mock
        endpoint: "http://${MOCK_PAYMENT_HOST:-localhost}:${MOCK_PAYMENT_PORT:-8080}/mock-payment"

      email_service:
        type: mock
        endpoint: "http://${MOCK_EMAIL_HOST:-localhost}:${MOCK_EMAIL_PORT:-8081}/mock-email"
        
  startup_sequence:
    - database
    - redis
    - message_queue
    - external_service_mocks
    - application_services
```

**Docker Compose Integration Testing:**
```yaml
version: '3.8'
services:
  test-database:
    image: postgres:14
    container_name: integration-test-postgres
    environment:
      POSTGRES_DB: test_db
      POSTGRES_USER: test_user
      POSTGRES_PASSWORD: test_pass
    ports:
      - "5432:5432"
    volumes:
      - ./test-data:/docker-entrypoint-initdb.d
      - integration-test-db-data:/var/lib/postgresql/data
    labels:
      - "test.type=integration"
      - "test.service=database"
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U test_user"]
      interval: 5s
      timeout: 5s
      retries: 5

  test-redis:
    image: redis:7
    container_name: integration-test-redis
    ports:
      - "6379:6379"
    labels:
      - "test.type=integration"
      - "test.service=cache"
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 5s
      timeout: 3s
      retries: 5

  test-app:
    build: .
    container_name: integration-test-app
    depends_on:
      test-database:
        condition: service_healthy
      test-redis:
        condition: service_healthy
    environment:
      # Environment-aware database configuration
      DATABASE_URL: postgres://test_user:test_pass@${DB_HOST:-test-database}:${DB_PORT:-5432}/test_db
      REDIS_URL: redis://${REDIS_HOST:-test-redis}:${REDIS_PORT:-6379}
      # Message queue configuration
      RABBITMQ_URL: amqp://guest:guest@${RABBITMQ_HOST:-localhost}:${RABBITMQ_PORT:-5672}
      TEST_MODE: "true"
    ports:
      - "8000:8000"
    labels:
      - "test.type=integration"
      - "test.service=application"

volumes:
  integration-test-db-data:
    labels:
      - "test.type=integration"
      - "test.volume=database"
```

**Environment Variables for Service Configuration:**

**Core Database & Cache Services:**
- **DB_HOST**: Database hostname (default: test-database for Docker, localhost for local)
- **DB_PORT**: Database port (default: 5432)
- **DATABASE_URL**: Complete database connection string (overrides DB_HOST/DB_PORT if set)
- **REDIS_HOST**: Redis hostname (default: test-redis for Docker, localhost for local)
- **REDIS_PORT**: Redis port (default: 6379)
- **REDIS_URL**: Complete Redis connection string (overrides REDIS_HOST/REDIS_PORT if set)

**Message Queue Services:**
- **RABBITMQ_HOST**: Message queue hostname (default: localhost)
- **RABBITMQ_PORT**: Message queue port (default: 5672)
- **RABBITMQ_URL**: Complete RabbitMQ connection string (overrides RABBITMQ_HOST/RABBITMQ_PORT if set)

**API & GraphQL Services:**
- **GRAPHQL_HOST**: GraphQL server hostname (default: localhost)
- **GRAPHQL_PORT**: GraphQL server port (default: 4000)
- **GRAPHQL_ENDPOINT**: Complete GraphQL endpoint URL (overrides GRAPHQL_HOST/GRAPHQL_PORT if set)

**External Service Mocks:**
- **MOCK_PAYMENT_HOST**: Payment service mock hostname (default: localhost)
- **MOCK_PAYMENT_PORT**: Payment service mock port (default: 8080)
- **MOCK_EMAIL_HOST**: Email service mock hostname (default: localhost)
- **MOCK_EMAIL_PORT**: Email service mock port (default: 8081)

**CI/CD Environment Examples:**
```bash
# Local development
export DB_HOST=localhost
export REDIS_HOST=localhost
export RABBITMQ_HOST=localhost

# Docker Compose
export DB_HOST=test-database
export REDIS_HOST=test-redis
export RABBITMQ_HOST=test-rabbitmq

# CI/CD Pipeline
export DB_HOST=postgres-ci.example.com
export REDIS_HOST=redis-ci.example.com
export RABBITMQ_HOST=rabbitmq-ci.example.com
```

**Step 2: Service Health and Readiness Validation**

**Service Health Monitoring:**
```typescript
interface ServiceHealthCheck {
  service_name: string;
  endpoint: string;
  expected_status: number;
  timeout: number;
  retry_count: number;
  dependencies: string[];
}

const validateServiceHealth = async (checks: ServiceHealthCheck[]): Promise<boolean> => {
  const results = await Promise.all(
    checks.map(async (check) => {
      let attempts = 0;
      while (attempts < check.retry_count) {
        try {
          const response = await fetch(check.endpoint, {
            timeout: check.timeout
          });
          
          if (response.status === check.expected_status) {
            // Service is healthy
            return true;
          }
        } catch (error) {
          // Health check failed
        }
        
        attempts++;
        await sleep(1000 * attempts); // Exponential backoff
      }
      
      // Service failed health checks
      return false;
    })
  );
  
  return results.every(result => result);
};
```

**Database Readiness Validation:**
```typescript
const validateDatabaseReadiness = async (connectionString: string): Promise<boolean> => {
  try {
    const client = new Client(connectionString);
    await client.connect();
    
    // Test basic operations
    await client.query('SELECT 1');
    await client.query('SELECT COUNT(*) FROM pg_tables');
    
    await client.end();
    return true;
  } catch (error) {
    // Database readiness check failed
    return false;
  }
};
```

**Step 3: Parallel Agent Deployment for Integration Testing**

**Agent Spawning Strategy:**
```
"I've identified 6 major integration points in the system. I'll spawn specialized agents:

1. **API Integration Agent**: 'Test REST API endpoints and GraphQL resolvers'
2. **Database Integration Agent**: 'Validate database operations and data consistency'
3. **Message Queue Agent**: 'Test async messaging and event processing'
4. **External Service Agent**: 'Test third-party service integrations'
5. **Authentication Agent**: 'Validate user authentication and authorization flows'
6. **File Storage Agent**: 'Test file upload, storage, and retrieval operations'
7. **Monitoring Agent**: 'Monitor system health and performance during tests'

Each agent will run integration tests in parallel while coordinating shared resources."
```

**Step 4: API Integration Testing**

**REST API Integration Tests:**
```typescript
interface APIIntegrationTest {
  name: string;
  method: 'GET' | 'POST' | 'PUT' | 'DELETE';
  endpoint: string;
  headers?: Record<string, string>;
  body?: any;
  expected_status: number;
  expected_response?: any;
  setup?: () => Promise<void>;
  cleanup?: () => Promise<void>;
  dependencies?: string[];
}

const executeAPIIntegrationTests = async (tests: APIIntegrationTest[]) => {
  const results = [];
  
  for (const test of tests) {
    // Run API integration test
    
    try {
      // Setup test data
      if (test.setup) {
        await test.setup();
      }
      
      // Execute API call
      const response = await fetch(test.endpoint, {
        method: test.method,
        headers: test.headers || {},
        body: test.body ? JSON.stringify(test.body) : undefined
      });
      
      // Validate response
      const isStatusValid = response.status === test.expected_status;
      const responseData = await response.json();
      
      let isResponseValid = true;
      if (test.expected_response) {
        isResponseValid = deepEqual(responseData, test.expected_response);
      }
      
      const success = isStatusValid && isResponseValid;
      
      results.push({
        test_name: test.name,
        success: success,
        actual_status: response.status,
        expected_status: test.expected_status,
        response_data: responseData,
        error: success ? null : 'Status or response validation failed'
      });
      
      // Test result recorded
      
    } catch (error) {
      results.push({
        test_name: test.name,
        success: false,
        error: error.message
      });
      
      // Test failed with error
    } finally {
      // Cleanup test data
      if (test.cleanup) {
        await test.cleanup();
      }
    }
  }
  
  return results;
};
```

**GraphQL Integration Testing:**
```typescript
const executeGraphQLIntegrationTests = async (tests: GraphQLIntegrationTest[]) => {
  const client = new GraphQLClient(process.env.GRAPHQL_ENDPOINT || `http://${process.env.GRAPHQL_HOST || 'localhost'}:${process.env.GRAPHQL_PORT || '4000'}/graphql`);
  
  for (const test of tests) {
    try {
      const result = await client.request(test.query, test.variables);
      
      // Validate response structure and data
      const isValid = validateGraphQLResponse(result, test.expected_response);
      
      // GraphQL test result recorded
      
    } catch (error) {
      // GraphQL test failed
    }
  }
};
```

**Step 5: Database Integration Testing**

**Database Integration Test Framework:**
```typescript
interface DatabaseIntegrationTest {
  name: string;
  setup_queries: string[];
  test_query: string;
  expected_result: any;
  cleanup_queries: string[];
  transaction_test: boolean;
}

const executeDatabaseIntegrationTests = async (tests: DatabaseIntegrationTest[]) => {
  const client = new Client(process.env.DATABASE_URL || `postgres://test_user:test_pass@${process.env.DB_HOST || 'localhost'}:${process.env.DB_PORT || '5432'}/test_db`);
  await client.connect();
  
  for (const test of tests) {
    // Run database integration test
    
    try {
      // Begin transaction for test isolation
      if (test.transaction_test) {
        await client.query('BEGIN');
      }
      
      // Setup test data
      for (const setupQuery of test.setup_queries) {
        await client.query(setupQuery);
      }
      
      // Execute test query
      const result = await client.query(test.test_query);
      
      // Validate result
      const isValid = validateDatabaseResult(result, test.expected_result);
      
      // Test result recorded
      
      // Cleanup
      if (test.transaction_test) {
        await client.query('ROLLBACK');
      } else {
        for (const cleanupQuery of test.cleanup_queries) {
          await client.query(cleanupQuery);
        }
      }
      
    } catch (error) {
      // Database test failed
      
      if (test.transaction_test) {
        await client.query('ROLLBACK');
      }
    }
  }
  
  await client.end();
};
```

**Data Consistency Validation:**
```typescript
const validateDataConsistency = async () => {
  const checks = [
    {
      name: 'User-Order Consistency',
      query: `
        SELECT u.id, u.email, COUNT(o.id) as order_count
        FROM users u
        LEFT JOIN orders o ON u.id = o.user_id
        WHERE u.deleted_at IS NULL
        GROUP BY u.id, u.email
        HAVING COUNT(o.id) != (
          SELECT COUNT(*) FROM orders WHERE user_id = u.id AND deleted_at IS NULL
        )
      `,
      expected_empty: true
    },
    {
      name: 'Order-Payment Consistency',
      query: `
        SELECT o.id, o.status, p.status as payment_status
        FROM orders o
        JOIN payments p ON o.id = p.order_id
        WHERE o.status = 'paid' AND p.status != 'completed'
      `,
      expected_empty: true
    }
  ];
  
  for (const check of checks) {
    const result = await client.query(check.query);
    const isConsistent = check.expected_empty ? result.rows.length === 0 : true;
    
    // Data consistency check result
    
    if (!isConsistent) {
      // Inconsistent data found in database
    }
  }
};
```

**Step 6: Message Queue Integration Testing**

**Message Queue Test Framework:**
```typescript
interface MessageQueueTest {
  name: string;
  exchange: string;
  routing_key: string;
  message: any;
  expected_consumers: string[];
  timeout: number;
  validation: (consumedMessages: any[]) => boolean;
}

const executeMessageQueueTests = async (tests: MessageQueueTest[]) => {
  const connection = await amqp.connect(process.env.RABBITMQ_URL || `amqp://guest:guest@${process.env.RABBITMQ_HOST || 'localhost'}:${process.env.RABBITMQ_PORT || '5672'}`);
  const channel = await connection.createChannel();
  
  for (const test of tests) {
    // Run message queue test
    
    try {
      // Setup message consumers
      const consumedMessages = [];
      const consumerPromises = test.expected_consumers.map(async (consumerQueue) => {
        return new Promise((resolve) => {
          channel.consume(consumerQueue, (msg) => {
            if (msg) {
              consumedMessages.push({
                queue: consumerQueue,
                content: JSON.parse(msg.content.toString()),
                timestamp: new Date()
              });
              channel.ack(msg);
            }
          });
          
          setTimeout(resolve, test.timeout);
        });
      });
      
      // Publish test message
      await channel.publish(
        test.exchange,
        test.routing_key,
        Buffer.from(JSON.stringify(test.message))
      );
      
      // Wait for consumers to process
      await Promise.all(consumerPromises);
      
      // Validate consumed messages
      const isValid = test.validation(consumedMessages);
      
      // Test result recorded
      
    } catch (error) {
      // Message queue test failed
    }
  }
  
  await connection.close();
};
```

**Step 7: External Service Integration Testing**

**External Service Mock Setup:**
```typescript
interface ExternalServiceMock {
  service_name: string;
  base_url: string;
  endpoints: MockEndpoint[];
  authentication?: {
    type: 'api_key' | 'oauth' | 'basic';
    credentials: any;
  };
}

interface MockEndpoint {
  path: string;
  method: string;
  response: any;
  status: number;
  delay?: number;
  failure_rate?: number;
}

const setupExternalServiceMocks = async (mocks: ExternalServiceMock[]) => {
  for (const mock of mocks) {
    // Set up mock for service
    
    // Setup mock server endpoints
    const mockServer = express();
    
    mock.endpoints.forEach(endpoint => {
      mockServer[endpoint.method.toLowerCase()](endpoint.path, (req, res) => {
        // Simulate network delay
        setTimeout(() => {
          // Simulate failure rate
          if (endpoint.failure_rate && Math.random() < endpoint.failure_rate) {
            res.status(500).json({ error: 'Simulated service failure' });
            return;
          }
          
          res.status(endpoint.status).json(endpoint.response);
        }, endpoint.delay || 0);
      });
    });
    
    mockServer.listen(getMockPort(mock.service_name));
  }
};
```

**Third-Party Service Integration Tests:**
```typescript
const executeExternalServiceTests = async (tests: ExternalServiceTest[]) => {
  for (const test of tests) {
    // Run external service test
    
    try {
      // Setup test conditions
      if (test.setup) {
        await test.setup();
      }
      
      // Execute service call through application
      const result = await test.execute();
      
      // Validate integration behavior
      const isValid = test.validate(result);
      
      // Test result recorded
      
    } catch (error) {
      // External service test failed
    }
  }
};
```

**Step 8: End-to-End Workflow Testing**

**Complete User Journey Testing:**
```typescript
interface WorkflowTest {
  name: string;
  steps: WorkflowStep[];
  expected_final_state: any;
  rollback_steps?: WorkflowStep[];
}

interface WorkflowStep {
  name: string;
  action: () => Promise<any>;
  validation: (result: any) => boolean;
  depends_on?: string[];
}

const executeWorkflowTests = async (tests: WorkflowTest[]) => {
  for (const test of tests) {
    // Run workflow test
    
    try {
      const stepResults = {};
      
      for (const step of test.steps) {
        // Execute workflow step
        
        // Check dependencies
        if (step.depends_on) {
          const dependenciesMet = step.depends_on.every(dep => 
            stepResults[dep] && stepResults[dep].success
          );
          
          if (!dependenciesMet) {
            throw new Error(`Dependencies not met for step: ${step.name}`);
          }
        }
        
        // Execute step
        const result = await step.action();
        const isValid = step.validation(result);
        
        stepResults[step.name] = {
          success: isValid,
          result: result
        };
        
        // Workflow step result
        
        if (!isValid) {
          throw new Error(`Step validation failed: ${step.name}`);
        }
      }
      
      // Validate final state
      const finalStateValid = validateFinalState(test.expected_final_state);
      
      // Workflow test final result
      
    } catch (error) {
      // Workflow test failed
      
      // Execute rollback if defined
      if (test.rollback_steps) {
        // Execute rollback steps
        for (const rollbackStep of test.rollback_steps) {
          try {
            await rollbackStep.action();
          } catch (rollbackError) {
            // Rollback step failed
          }
        }
      }
    }
  }
};
```

**Step 9: Performance and Load Testing**

**Integration Performance Testing:**
```typescript
interface PerformanceTest {
  name: string;
  concurrent_users: number;
  test_duration: number;
  endpoints: PerformanceEndpoint[];
  success_criteria: {
    max_response_time: number;
    min_throughput: number;
    max_error_rate: number;
  };
}

const executePerformanceTests = async (tests: PerformanceTest[]) => {
  for (const test of tests) {
    // Run performance test
    
    const metrics = {
      total_requests: 0,
      successful_requests: 0,
      failed_requests: 0,
      response_times: [],
      start_time: Date.now()
    };
    
    // Create concurrent user simulation
    const userPromises = Array.from({ length: test.concurrent_users }, 
      () => simulateUserLoad(test.endpoints, test.test_duration, metrics)
    );
    
    await Promise.all(userPromises);
    
    // Analyze performance metrics
    const results = analyzePerformanceMetrics(metrics, test.success_criteria);
    
    // Performance test result
    // Response time recorded
    // Throughput recorded
    // Error rate recorded
  }
};
```

**Step 10: Integration Test Reporting**

**Comprehensive Test Report Generation:**
```typescript
interface IntegrationTestReport {
  summary: {
    total_tests: number;
    passed: number;
    failed: number;
    duration: number;
    environment: string;
  };
  service_health: ServiceHealthReport[];
  api_tests: APITestResult[];
  database_tests: DatabaseTestResult[];
  message_queue_tests: MessageQueueTestResult[];
  external_service_tests: ExternalServiceTestResult[];
  workflow_tests: WorkflowTestResult[];
  performance_tests: PerformanceTestResult[];
  recommendations: string[];
}

const generateIntegrationTestReport = (results: any): IntegrationTestReport => {
  return {
    summary: compileSummary(results),
    service_health: compileServiceHealth(results),
    api_tests: compileAPIResults(results),
    database_tests: compileDatabaseResults(results),
    message_queue_tests: compileMessageQueueResults(results),
    external_service_tests: compileExternalServiceResults(results),
    workflow_tests: compileWorkflowResults(results),
    performance_tests: compilePerformanceResults(results),
    recommendations: generateRecommendations(results)
  };
};
```

**Integration Test Quality Checklist:**
- [ ] All integration tests are passing
- [ ] Service dependencies are properly managed
- [ ] API endpoints are thoroughly tested
- [ ] Database operations are validated
- [ ] Message queue communication is tested
- [ ] External service integrations are verified
- [ ] Complete user workflows are tested
- [ ] Performance under load is acceptable
- [ ] Error handling and recovery is tested
- [ ] Test environments are properly isolated

**Agent Coordination for Complex Systems:**
```
"For comprehensive integration testing, I'll coordinate multiple specialized agents:

Primary Integration Agent: Overall test orchestration and coordination
├── Environment Agent: Service startup and dependency management
├── API Agent: REST/GraphQL endpoint testing
├── Database Agent: Data persistence and consistency validation
├── Message Queue Agent: Async communication testing
├── External Service Agent: Third-party integration testing
├── Workflow Agent: End-to-end user journey testing
├── Performance Agent: Load and stress testing
└── Report Agent: Comprehensive test reporting and analysis

Each agent will coordinate with others to ensure proper test isolation and comprehensive coverage."
```

**Anti-Patterns to Avoid:**
- ❌ Testing against production systems (data corruption risk)
- ❌ Ignoring service startup order (test environment failures)
- ❌ Poor test data management (test interference)
- ❌ Mocking critical integrations (missing real-world issues)
- ❌ Sequential test execution (slow feedback loops)
- ❌ Incomplete error scenario testing (production failures)

## 🚨 **MANDATORY 100% SUCCESS VALIDATION FOR INTEGRATION TESTS**

**Docker Container Cleanup Function:**
```bash
# Cleanup Docker containers and volumes for integration tests
cleanup_docker_containers() {
    local project_dir=${1:-.}

    echo "🐳 Cleaning up Docker containers and volumes..."

    # Stop and remove containers from docker-compose.test.yml
    if [ -f "$project_dir/docker-compose.test.yml" ]; then
        echo "   Stopping test compose services..."
        docker-compose -f "$project_dir/docker-compose.test.yml" down -v --remove-orphans 2>/dev/null || \
            echo "   ⚠️  Warning: docker-compose.test.yml cleanup failed"
    fi

    # Stop and remove containers from docker-compose.yml
    if [ -f "$project_dir/docker-compose.yml" ]; then
        echo "   Stopping default compose services..."
        docker-compose -f "$project_dir/docker-compose.yml" down -v --remove-orphans 2>/dev/null || \
            echo "   ⚠️  Warning: docker-compose.yml cleanup failed"
    fi

    # Remove containers with integration test labels
    local integration_containers=$(docker ps -a --filter "label=test.type=integration" -q 2>/dev/null)
    if [ -n "$integration_containers" ]; then
        echo "   Removing labeled integration test containers..."
        echo "$integration_containers" | xargs docker rm -f 2>/dev/null || true
    fi

    # Remove test volumes with labels
    local integration_volumes=$(docker volume ls --filter "label=test.type=integration" -q 2>/dev/null)
    if [ -n "$integration_volumes" ]; then
        echo "   Removing labeled integration test volumes..."
        echo "$integration_volumes" | xargs docker volume rm 2>/dev/null || true
    fi

    # Remove orphaned networks
    docker network prune -f 2>/dev/null || true

    echo "✅ Docker cleanup completed"
}
```

**Integration Test Execution with Success Rate Enforcement:**
```bash
# Execute integration tests with mandatory 100% success validation
execute_integration_tests_with_validation() {
    local project_dir=${1:-.}
    local command_args=${2:-""}

    echo "🚨 **CRITICAL: 100% INTEGRATION TEST SUCCESS RATE REQUIRED** 🚨"
    echo "   ANY FAILING INTEGRATION TESTS WILL BLOCK EXECUTION"
    echo ""

    # Setup cleanup trap for Docker containers (ensures cleanup on ANY exit)
    trap 'cleanup_docker_containers "$project_dir"' EXIT INT TERM

    # Validate integration test structure before proceeding (respecting PHP opt-out)
    if ! validate_integration_structure "$project_dir" "$command_args"; then
        echo ""
        echo "❌ Integration test structure validation failed"
        echo "   Please run '/test structure' first or use --skip-php-structure-check"
        return 1
    fi

    local test_exit_code=0
    local framework=$(detect_test_framework "$project_dir")
    local test_timeout=${INTEGRATION_TEST_TIMEOUT:-600}  # Default 10 minutes

    echo "⏰ Integration test timeout: ${test_timeout}s"
    echo ""
    
    case "$framework" in
        "jest"|"mocha")
            # JavaScript integration tests with timeout
            timeout "$test_timeout" bash -c "npm run test:integration 2>/dev/null || npx jest --testMatch='**/integration/**/*.test.{js,ts}'"
            test_exit_code=$?
            ;;
        "pytest")
            # Python integration tests with timeout
            timeout "$test_timeout" bash -c "python -m pytest tests/integration/ -v --tb=short"
            test_exit_code=$?
            ;;
        "go-test")
            # Go integration tests with timeout
            timeout "$test_timeout" bash -c "go test -v -tags=integration ./..."
            test_exit_code=$?
            ;;
        "rspec")
            # Ruby integration tests with timeout
            timeout "$test_timeout" bash -c "bundle exec rspec spec/integration/"
            test_exit_code=$?
            ;;
        "phpunit")
            # PHP integration tests (if not disabled) with timeout
            local flags=$(parse_php_test_flags "$command_args")
            local php_disabled=$(echo "$flags" | sed 's/.*php_disabled:\([^ ]*\).*/\1/')

            if [ "$php_disabled" = "true" ]; then
                echo "PHPUnit integration tests skipped (--no-php flag specified)"
                return 0
            else
                timeout "$test_timeout" bash -c "./vendor/bin/phpunit tests/Integration/ 2>/dev/null || phpunit tests/Integration/"
                test_exit_code=$?
            fi
            ;;
        *)
            # Generic integration test execution with timeout
            if [ -f "docker-compose.test.yml" ]; then
                timeout "$test_timeout" docker-compose -f docker-compose.test.yml up --build --abort-on-container-exit
                test_exit_code=$?
            elif [ -f "Makefile" ] && grep -q "test-integration" Makefile; then
                timeout "$test_timeout" make test-integration
                test_exit_code=$?
            else
                echo "No integration test framework detected"
                return 1
            fi
            ;;
    esac

    # Check for timeout condition
    if [ $test_exit_code -eq 124 ]; then
        echo ""
        echo "⏰⏰⏰ **INTEGRATION TEST TIMEOUT** ⏰⏰⏰"
        echo "❌ Tests exceeded ${test_timeout}s timeout"
        echo "   Forcing Docker container cleanup..."
        cleanup_docker_containers "$project_dir"
        return 124
    fi
    
    # MANDATORY 100% SUCCESS VALIDATION
    if [ $test_exit_code -ne 0 ]; then
        echo ""
        echo "🚨🚨🚨 **INTEGRATION TEST EXECUTION BLOCKED** 🚨🚨🚨"
        echo "❌ INTEGRATION TEST SUCCESS RATE: LESS THAN 100%"
        echo "❌ EXIT CODE: $test_exit_code (NON-ZERO = FAILURE)"
        echo ""
        echo "🛑 **EXECUTION HALTED - ALL INTEGRATION TEST FAILURES MUST BE FIXED**"
        echo ""
        echo "Required Actions:"
        echo "1. Fix all failing integration tests"
        echo "2. Verify service dependencies are properly configured"
        echo "3. Check database connectivity and test data setup"
        echo "4. Ensure all external service mocks are working"
        echo "5. Re-run integration test execution"
        echo ""
        echo "🚨 **NO FURTHER STEPS UNTIL 100% INTEGRATION TEST SUCCESS**"
        return $test_exit_code
    fi
    
    echo ""
    echo "✅✅✅ **100% INTEGRATION TEST SUCCESS ACHIEVED** ✅✅✅"
    echo "✅ All integration tests passed successfully"
    echo "✅ Cross-system communication validated"
    echo "✅ Service dependencies verified"
    echo "✅ Proceeding with data consistency verification and performance testing"
    echo ""
    
    return 0
}
```

**Final Verification:**
Before completing integration test orchestration:
- **Have ALL integration tests achieved 100% success rate?**
- Are all integration tests passing successfully?
- Are service dependencies properly managed?
- Is cross-system communication validated?
- Are performance requirements met?
- Are error scenarios thoroughly tested?
- Are test environments properly isolated?

**Final Commitment:**
- **I will**: Orchestrate comprehensive integration tests with proper service management
- **I will**: Use multiple agents for parallel integration testing
- **I will**: Validate all system integrations and data consistency
- **I will**: Test performance and error scenarios thoroughly
- **I will NOT**: Skip complex integration scenarios
- **I will NOT**: Test against production systems
- **I will NOT**: Ignore service dependency management

**REMEMBER:**
This is INTEGRATION TEST ORCHESTRATION mode - comprehensive cross-system validation, proper service management, and thorough integration testing. The goal is to ensure all system components work together correctly.

Executing comprehensive integration test orchestration protocol for complete system validation...