# Docker Environment Variable Configuration Guide

## Table of Contents
1. [Problem Overview](#problem-overview)
2. [Solution Architecture](#solution-architecture)
3. [Quick Start Guide](#quick-start-guide)
4. [Environment Variable Reference](#environment-variable-reference)
5. [Implementation Examples](#implementation-examples)
6. [CI/CD Platform Examples](#cicd-platform-examples)
7. [Docker Compose Configuration](#docker-compose-configuration)
8. [Troubleshooting Guide](#troubleshooting-guide)
9. [Migration Guide](#migration-guide)
10. [Best Practices and Anti-patterns](#best-practices-and-anti-patterns)

## Problem Overview

### The Challenge
Docker containers face networking differences between CI environments and local development, leading to:

- **CI Environments**: Services communicate via service names (e.g., `mysql`, `redis`)
- **Local Development**: Services use `localhost` or `127.0.0.1`
- **Production**: May use external service URLs or internal cluster networking

### Common Issues
```bash
# This fails in CI but works locally
DATABASE_HOST=localhost

# This works in CI but fails locally
DATABASE_HOST=mysql
```

### Why Hardcoded Values Fail
- CI containers can't reach `localhost` services
- Local development can't resolve CI service names
- Different port mappings between environments
- SSL/TLS configuration differences

## Solution Architecture

### Environment-Driven Configuration
Replace hardcoded values with environment variables that adapt to the runtime context:

```yaml
# Instead of hardcoded
services:
  app:
    environment:
      - DATABASE_HOST=mysql  # Fails locally

# Use environment-aware
services:
  app:
    environment:
      - DATABASE_HOST=${DATABASE_HOST:-localhost}
```

### Configuration Layers
1. **Default Values**: Sensible defaults for common scenarios
2. **Environment Detection**: Automatic detection of CI vs local
3. **Override Capability**: Manual override for specific needs
4. **Platform Adaptation**: Platform-specific configurations

## Quick Start Guide

### 5-Minute Implementation

#### Step 1: Create Environment Configuration
```bash
# .env.example
DATABASE_HOST=${DATABASE_HOST:-localhost}
DATABASE_PORT=${DATABASE_PORT:-3306}
REDIS_HOST=${REDIS_HOST:-localhost}
REDIS_PORT=${REDIS_PORT:-6379}

# CI Detection
CI_ENVIRONMENT=${CI:-false}
```

#### Step 2: Update Docker Compose
```yaml
# docker-compose.yml
version: '3.8'
services:
  app:
    build: .
    environment:
      - DATABASE_HOST=${DATABASE_HOST:-mysql}
      - DATABASE_PORT=${DATABASE_PORT:-3306}
      - REDIS_HOST=${REDIS_HOST:-redis}
      - REDIS_PORT=${REDIS_PORT:-6379}
    depends_on:
      - mysql
      - redis

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: password

  redis:
    image: redis:alpine
```

#### Step 3: Create Environment Files
```bash
# .env.local
DATABASE_HOST=localhost
DATABASE_PORT=3306
REDIS_HOST=localhost
REDIS_PORT=6379

# .env.ci
DATABASE_HOST=mysql
DATABASE_PORT=3306
REDIS_HOST=redis
REDIS_PORT=6379
```

#### Step 4: Update Application Configuration
```php
// config/database.php
return [
    'host' => env('DATABASE_HOST', 'localhost'),
    'port' => env('DATABASE_PORT', '3306'),
];
```

#### Step 5: Configure CI Pipeline
```yaml
# .github/workflows/test.yml
env:
  DATABASE_HOST: mysql
  REDIS_HOST: redis
```

## Environment Variable Reference

### Core Database Variables
```bash
# MySQL/MariaDB
DATABASE_HOST=localhost           # Database server hostname
DATABASE_PORT=3306               # Database server port
DATABASE_NAME=app_db             # Database name
DATABASE_USER=root               # Database username
DATABASE_PASSWORD=password       # Database password
DATABASE_CHARSET=utf8mb4         # Character set

# PostgreSQL
POSTGRES_HOST=localhost
POSTGRES_PORT=5432
POSTGRES_DB=app_db
POSTGRES_USER=postgres
POSTGRES_PASSWORD=password

# MongoDB
MONGO_HOST=localhost
MONGO_PORT=27017
MONGO_DATABASE=app_db
MONGO_USERNAME=
MONGO_PASSWORD=
```

### Cache and Session Variables
```bash
# Redis
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DATABASE=0

# Memcached
MEMCACHED_HOST=localhost
MEMCACHED_PORT=11211

# Session Configuration
SESSION_DRIVER=redis
SESSION_REDIS_HOST=${REDIS_HOST}
SESSION_REDIS_PORT=${REDIS_PORT}
```

### Message Queue Variables
```bash
# RabbitMQ
RABBITMQ_HOST=localhost
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=guest
RABBITMQ_VHOST=/

# Apache Kafka
KAFKA_BROKERS=localhost:9092
KAFKA_SECURITY_PROTOCOL=PLAINTEXT

# Redis Queue
QUEUE_CONNECTION=redis
QUEUE_REDIS_HOST=${REDIS_HOST}
QUEUE_REDIS_PORT=${REDIS_PORT}
```

### Microservice Communication
```bash
# Service Discovery
SERVICE_REGISTRY_HOST=localhost
SERVICE_REGISTRY_PORT=8500

# API Gateway
API_GATEWAY_HOST=localhost
API_GATEWAY_PORT=8080

# Load Balancer
LOAD_BALANCER_HOST=localhost
LOAD_BALANCER_PORT=80
```

### Environment Detection
```bash
# CI Detection
CI=false                         # Set by CI systems
CI_ENVIRONMENT=${CI:-false}      # Custom CI detection

# Environment Type
APP_ENV=local                    # local, testing, staging, production
APP_DEBUG=true                   # Debug mode toggle

# Container Detection
DOCKER_ENVIRONMENT=true          # Running in Docker
KUBERNETES_ENVIRONMENT=false     # Running in Kubernetes
```

## Implementation Examples

### Laravel/PHP Framework
```php
// config/database.php
<?php
return [
    'default' => env('DB_CONNECTION', 'mysql'),
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DATABASE_HOST', 'localhost'),
            'port' => env('DATABASE_PORT', '3306'),
            'database' => env('DATABASE_NAME', 'app_db'),
            'username' => env('DATABASE_USER', 'root'),
            'password' => env('DATABASE_PASSWORD', ''),
            'charset' => env('DATABASE_CHARSET', 'utf8mb4'),
        ],
    ],
];

// config/cache.php
return [
    'stores' => [
        'redis' => [
            'driver' => 'redis',
            'connection' => 'cache',
            'host' => env('REDIS_HOST', 'localhost'),
            'port' => env('REDIS_PORT', 6379),
        ],
    ],
];
```

### Node.js/Express
```javascript
// config/database.js
module.exports = {
  development: {
    host: process.env.DATABASE_HOST || 'localhost',
    port: process.env.DATABASE_PORT || 3306,
    database: process.env.DATABASE_NAME || 'app_db',
    username: process.env.DATABASE_USER || 'root',
    password: process.env.DATABASE_PASSWORD || '',
    dialect: 'mysql'
  },
  test: {
    host: process.env.DATABASE_HOST || 'mysql',
    port: process.env.DATABASE_PORT || 3306,
    database: process.env.DATABASE_NAME || 'app_test_db',
    username: process.env.DATABASE_USER || 'root',
    password: process.env.DATABASE_PASSWORD || '',
    dialect: 'mysql'
  }
};

// config/redis.js
const redis = require('redis');
const client = redis.createClient({
  host: process.env.REDIS_HOST || 'localhost',
  port: process.env.REDIS_PORT || 6379,
  password: process.env.REDIS_PASSWORD || undefined
});
```

### Python/Django
```python
# settings.py
import os

DATABASES = {
    'default': {
        'ENGINE': 'django.db.backends.mysql',
        'HOST': os.environ.get('DATABASE_HOST', 'localhost'),
        'PORT': os.environ.get('DATABASE_PORT', '3306'),
        'NAME': os.environ.get('DATABASE_NAME', 'app_db'),
        'USER': os.environ.get('DATABASE_USER', 'root'),
        'PASSWORD': os.environ.get('DATABASE_PASSWORD', ''),
    }
}

CACHES = {
    'default': {
        'BACKEND': 'django_redis.cache.RedisCache',
        'LOCATION': f"redis://{os.environ.get('REDIS_HOST', 'localhost')}:{os.environ.get('REDIS_PORT', '6379')}/1",
    }
}
```

### Java/Spring Boot
```yaml
# application.yml
spring:
  datasource:
    url: jdbc:mysql://${DATABASE_HOST:localhost}:${DATABASE_PORT:3306}/${DATABASE_NAME:app_db}
    username: ${DATABASE_USER:root}
    password: ${DATABASE_PASSWORD:}
  
  redis:
    host: ${REDIS_HOST:localhost}
    port: ${REDIS_PORT:6379}
    password: ${REDIS_PASSWORD:}

# application-test.yml
spring:
  datasource:
    url: jdbc:mysql://${DATABASE_HOST:mysql}:${DATABASE_PORT:3306}/${DATABASE_NAME:app_test_db}
  
  redis:
    host: ${REDIS_HOST:redis}
```

### Go Application
```go
// config/config.go
package config

import (
    "os"
    "strconv"
)

type Config struct {
    DatabaseHost     string
    DatabasePort     int
    DatabaseName     string
    DatabaseUser     string
    DatabasePassword string
    RedisHost        string
    RedisPort        int
}

func Load() *Config {
    port, _ := strconv.Atoi(getEnv("DATABASE_PORT", "3306"))
    redisPort, _ := strconv.Atoi(getEnv("REDIS_PORT", "6379"))
    
    return &Config{
        DatabaseHost:     getEnv("DATABASE_HOST", "localhost"),
        DatabasePort:     port,
        DatabaseName:     getEnv("DATABASE_NAME", "app_db"),
        DatabaseUser:     getEnv("DATABASE_USER", "root"),
        DatabasePassword: getEnv("DATABASE_PASSWORD", ""),
        RedisHost:        getEnv("REDIS_HOST", "localhost"),
        RedisPort:        redisPort,
    }
}

func getEnv(key, defaultValue string) string {
    if value := os.Getenv(key); value != "" {
        return value
    }
    return defaultValue
}
```

## CI/CD Platform Examples

### GitHub Actions
```yaml
# .github/workflows/test.yml
name: Test Suite

on: [push, pull_request]

env:
  # Database Configuration
  DATABASE_HOST: mysql
  DATABASE_PORT: 3306
  DATABASE_NAME: app_test_db
  DATABASE_USER: root
  DATABASE_PASSWORD: password
  
  # Cache Configuration
  REDIS_HOST: redis
  REDIS_PORT: 6379
  
  # Environment Detection
  CI_ENVIRONMENT: true
  APP_ENV: testing

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: app_test_db
        ports:
          - 3306:3306
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3
      
      redis:
        image: redis:alpine
        ports:
          - 6379:6379
        options: >-
          --health-cmd="redis-cli ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Wait for services
        run: |
          sleep 10
          until mysqladmin ping -h mysql -P 3306 -u root -ppassword; do
            echo "Waiting for MySQL..."
            sleep 2
          done
      
      - name: Run tests
        run: |
          docker-compose -f docker-compose.test.yml up --build --abort-on-container-exit
```

### GitLab CI
```yaml
# .gitlab-ci.yml
variables:
  # Database Configuration
  DATABASE_HOST: mysql
  DATABASE_PORT: "3306"
  DATABASE_NAME: app_test_db
  DATABASE_USER: root
  DATABASE_PASSWORD: password
  
  # Cache Configuration
  REDIS_HOST: redis
  REDIS_PORT: "6379"
  
  # Docker Configuration
  DOCKER_DRIVER: overlay2
  DOCKER_TLS_CERTDIR: ""

services:
  - name: mysql:8.0
    alias: mysql
    variables:
      MYSQL_ROOT_PASSWORD: password
      MYSQL_DATABASE: app_test_db
  
  - name: redis:alpine
    alias: redis

stages:
  - test

test:
  stage: test
  image: docker:latest
  services:
    - docker:dind
  before_script:
    - apk add --no-cache docker-compose
    - until nc -z mysql 3306; do echo "Waiting for MySQL..."; sleep 2; done
    - until nc -z redis 6379; do echo "Waiting for Redis..."; sleep 2; done
  script:
    - docker-compose -f docker-compose.test.yml up --build --abort-on-container-exit
```

### Jenkins Pipeline
```groovy
// Jenkinsfile
pipeline {
    agent any
    
    environment {
        // Database Configuration
        DATABASE_HOST = 'mysql'
        DATABASE_PORT = '3306'
        DATABASE_NAME = 'app_test_db'
        DATABASE_USER = 'root'
        DATABASE_PASSWORD = 'password'
        
        // Cache Configuration
        REDIS_HOST = 'redis'
        REDIS_PORT = '6379'
        
        // Environment Detection
        CI_ENVIRONMENT = 'true'
        APP_ENV = 'testing'
    }
    
    stages {
        stage('Setup Services') {
            steps {
                script {
                    sh '''
                        docker network create test-network || true
                        
                        docker run -d --name mysql --network test-network \
                            -e MYSQL_ROOT_PASSWORD=password \
                            -e MYSQL_DATABASE=app_test_db \
                            mysql:8.0
                        
                        docker run -d --name redis --network test-network \
                            redis:alpine
                        
                        # Wait for services
                        sleep 30
                    '''
                }
            }
        }
        
        stage('Test') {
            steps {
                sh '''
                    docker-compose -f docker-compose.test.yml up --build --abort-on-container-exit
                '''
            }
        }
    }
    
    post {
        always {
            sh '''
                docker stop mysql redis || true
                docker rm mysql redis || true
                docker network rm test-network || true
            '''
        }
    }
}
```

### CircleCI
```yaml
# .circleci/config.yml
version: 2.1

executors:
  docker-environment:
    docker:
      - image: cimg/base:stable
    environment:
      DATABASE_HOST: localhost
      DATABASE_PORT: 3306
      DATABASE_NAME: app_test_db
      DATABASE_USER: root
      DATABASE_PASSWORD: password
      REDIS_HOST: localhost
      REDIS_PORT: 6379

jobs:
  test:
    executor: docker-environment
    docker:
      - image: cimg/php:8.1
      - image: mysql:8.0
        environment:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: app_test_db
      - image: redis:alpine
    
    steps:
      - checkout
      - setup_remote_docker:
          version: 20.10.14
      
      - run:
          name: Wait for services
          command: |
            dockerize -wait tcp://localhost:3306 -timeout 1m
            dockerize -wait tcp://localhost:6379 -timeout 1m
      
      - run:
          name: Run tests
          command: |
            docker-compose -f docker-compose.test.yml up --build --abort-on-container-exit

workflows:
  test:
    jobs:
      - test
```

## Docker Compose Configuration

### Multi-Environment Setup
```yaml
# docker-compose.yml (base configuration)
version: '3.8'

services:
  app:
    build: .
    environment:
      - DATABASE_HOST=${DATABASE_HOST}
      - DATABASE_PORT=${DATABASE_PORT}
      - DATABASE_NAME=${DATABASE_NAME}
      - DATABASE_USER=${DATABASE_USER}
      - DATABASE_PASSWORD=${DATABASE_PASSWORD}
      - REDIS_HOST=${REDIS_HOST}
      - REDIS_PORT=${REDIS_PORT}
    depends_on:
      mysql:
        condition: service_healthy
      redis:
        condition: service_healthy

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: ${DATABASE_PASSWORD}
      MYSQL_DATABASE: ${DATABASE_NAME}
    ports:
      - "${DATABASE_PORT}:3306"
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      timeout: 20s
      retries: 10

  redis:
    image: redis:alpine
    ports:
      - "${REDIS_PORT}:6379"
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      timeout: 20s
      retries: 10
```

### Local Development Override
```yaml
# docker-compose.override.yml (loaded automatically)
version: '3.8'

services:
  app:
    volumes:
      - .:/app
    ports:
      - "8000:8000"
    environment:
      - APP_ENV=local
      - APP_DEBUG=true

  mysql:
    volumes:
      - mysql_data:/var/lib/mysql
    ports:
      - "3306:3306"

  redis:
    ports:
      - "6379:6379"

volumes:
  mysql_data:
```

### Testing Environment
```yaml
# docker-compose.test.yml
version: '3.8'

services:
  app:
    build: .
    environment:
      - DATABASE_HOST=mysql
      - DATABASE_PORT=3306
      - DATABASE_NAME=app_test_db
      - DATABASE_USER=root
      - DATABASE_PASSWORD=password
      - REDIS_HOST=redis
      - REDIS_PORT=6379
      - APP_ENV=testing
      - APP_DEBUG=false
    command: |
      sh -c "
        echo 'Waiting for services...'
        sleep 10
        php artisan migrate:fresh --seed
        php artisan test
      "
    depends_on:
      mysql:
        condition: service_healthy
      redis:
        condition: service_healthy

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: password
      MYSQL_DATABASE: app_test_db
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      timeout: 20s
      retries: 10

  redis:
    image: redis:alpine
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      timeout: 20s
      retries: 10
```

### Production Environment
```yaml
# docker-compose.prod.yml
version: '3.8'

services:
  app:
    build:
      context: .
      target: production
    environment:
      - DATABASE_HOST=${DATABASE_HOST}
      - DATABASE_PORT=${DATABASE_PORT}
      - DATABASE_NAME=${DATABASE_NAME}
      - DATABASE_USER=${DATABASE_USER}
      - DATABASE_PASSWORD=${DATABASE_PASSWORD}
      - REDIS_HOST=${REDIS_HOST}
      - REDIS_PORT=${REDIS_PORT}
      - APP_ENV=production
      - APP_DEBUG=false
    restart: unless-stopped
    networks:
      - app-network

networks:
  app-network:
    driver: bridge
```

## Troubleshooting Guide

### Common Issues and Solutions

#### Issue 1: Service Connection Refused
```bash
# Symptom
Connection refused to mysql:3306

# Diagnosis
docker-compose logs mysql
docker-compose ps

# Solutions
1. Check service health:
   docker-compose exec mysql mysqladmin ping

2. Verify environment variables:
   docker-compose config

3. Wait for service startup:
   docker-compose up --wait

4. Check network connectivity:
   docker-compose exec app ping mysql
```

#### Issue 2: Environment Variables Not Loading
```bash
# Symptom
Using default values instead of environment variables

# Diagnosis
docker-compose exec app env | grep DATABASE

# Solutions
1. Check .env file syntax:
   cat .env | grep -E '^[A-Z_]+=.*$'

2. Verify docker-compose.yml syntax:
   docker-compose config

3. Explicit environment setting:
   docker-compose up -e DATABASE_HOST=mysql

4. Debug environment loading:
   docker-compose exec app php -r "echo getenv('DATABASE_HOST');"
```

#### Issue 3: Port Conflicts
```bash
# Symptom
Port 3306 already in use

# Diagnosis
netstat -tulpn | grep 3306
docker ps --filter "publish=3306"

# Solutions
1. Use different ports:
   DATABASE_PORT=3307

2. Stop conflicting services:
   sudo systemctl stop mysql
   docker stop $(docker ps -q --filter "publish=3306")

3. Use host networking:
   network_mode: host
```

#### Issue 4: CI/Local Environment Mismatch
```bash
# Symptom
Tests pass locally but fail in CI

# Diagnosis
1. Compare environment variables:
   # Local
   docker-compose exec app env | sort
   
   # CI logs
   Check CI job output for environment variables

# Solutions
1. Standardize environment files:
   cp .env.ci .env.test

2. Use identical service versions:
   mysql:8.0.33  # Pin exact versions

3. Add debugging output:
   echo "DATABASE_HOST: $DATABASE_HOST"
   echo "Testing connection..."
```

### Debugging Commands

#### Environment Variable Verification
```bash
# Check all environment variables
docker-compose exec app env | grep -E '^(DATABASE|REDIS|CACHE)_'

# Test specific variable
docker-compose exec app sh -c 'echo "DB Host: $DATABASE_HOST"'

# Verify service configuration
docker-compose config
```

#### Service Connectivity Testing
```bash
# Test database connection
docker-compose exec app nc -zv mysql 3306

# Test Redis connection
docker-compose exec app nc -zv redis 6379

# DNS resolution test
docker-compose exec app nslookup mysql
docker-compose exec app ping -c 1 mysql
```

#### Health Check Debugging
```bash
# Check service health status
docker-compose ps

# Manual health check
docker-compose exec mysql mysqladmin ping -h localhost
docker-compose exec redis redis-cli ping

# Service logs
docker-compose logs mysql
docker-compose logs redis
```

### Performance Troubleshooting

#### Slow Service Startup
```yaml
# Add startup delays
services:
  app:
    depends_on:
      mysql:
        condition: service_healthy
    command: |
      sh -c "
        echo 'Waiting for services...'
        sleep 15
        php artisan serve
      "
```

#### Connection Pool Issues
```bash
# Check connection limits
docker-compose exec mysql mysql -e "SHOW VARIABLES LIKE 'max_connections';"

# Monitor active connections
docker-compose exec mysql mysql -e "SHOW PROCESSLIST;"
```

## Migration Guide

### From Hardcoded to Environment-Aware

#### Step 1: Audit Current Configuration
```bash
# Find hardcoded values
grep -r "localhost" config/
grep -r "127.0.0.1" config/
grep -r "3306" config/
grep -r "6379" config/

# Document current settings
echo "Current configuration audit:" > migration.log
grep -rn "host.*=" config/ >> migration.log
```

#### Step 2: Create Environment Variable Mapping
```bash
# Create mapping file
cat > env-mapping.txt << EOF
# Database Configuration
DATABASE_HOST=localhost → ${DATABASE_HOST:-localhost}
DATABASE_PORT=3306 → ${DATABASE_PORT:-3306}
DATABASE_NAME=app_db → ${DATABASE_NAME:-app_db}

# Cache Configuration  
REDIS_HOST=localhost → ${REDIS_HOST:-localhost}
REDIS_PORT=6379 → ${REDIS_PORT:-6379}
EOF
```

#### Step 3: Update Configuration Files
```php
// Before
'host' => 'localhost',
'port' => 3306,

// After
'host' => env('DATABASE_HOST', 'localhost'),
'port' => env('DATABASE_PORT', '3306'),
```

#### Step 4: Create Environment Files
```bash
# Generate environment files
cat > .env.example << EOF
# Database Configuration
DATABASE_HOST=localhost
DATABASE_PORT=3306
DATABASE_NAME=app_db
DATABASE_USER=root
DATABASE_PASSWORD=

# Cache Configuration
REDIS_HOST=localhost
REDIS_PORT=6379
EOF

# Create environment-specific files
cp .env.example .env.local
cp .env.example .env.ci

# Update CI environment
sed -i 's/localhost/mysql/g; s/localhost/redis/g' .env.ci
```

#### Step 5: Update Docker Configuration
```yaml
# Update docker-compose.yml
services:
  app:
    environment:
      - DATABASE_HOST=${DATABASE_HOST:-mysql}
      - DATABASE_PORT=${DATABASE_PORT:-3306}
      - REDIS_HOST=${REDIS_HOST:-redis}
      - REDIS_PORT=${REDIS_PORT:-6379}
```

#### Step 6: Validate Migration
```bash
# Test local environment
docker-compose --env-file .env.local up -d
docker-compose exec app php artisan tinker
# Test: DB::connection()->getPdo()

# Test CI environment
docker-compose --env-file .env.ci up -d
docker-compose exec app php artisan migrate:status

# Cleanup
docker-compose down
```

### Gradual Migration Strategy

#### Phase 1: Add Environment Variables (Non-Breaking)
```php
// Add alongside existing hardcoded values
'host' => env('DATABASE_HOST', 'localhost'),  // New
'port' => 3306,                               // Keep existing
```

#### Phase 2: Validate Environment Variables
```php
// Add validation
if (!env('DATABASE_HOST')) {
    throw new InvalidArgumentException('DATABASE_HOST is required');
}
```

#### Phase 3: Remove Hardcoded Values
```php
// Remove fallbacks after validation
'host' => env('DATABASE_HOST'),  // No fallback
'port' => env('DATABASE_PORT'),  // No fallback
```

## Best Practices and Anti-patterns

### Best Practices

#### 1. Use Descriptive Environment Variable Names
```bash
# Good
DATABASE_HOST=mysql
API_GATEWAY_URL=http://api-gateway:8080
CACHE_REDIS_CLUSTER_NODES=redis-1:6379,redis-2:6379

# Bad
DB_HOST=mysql          # Too abbreviated
HOST=mysql             # Too generic
REDIS=redis-server     # Unclear purpose
```

#### 2. Provide Sensible Defaults
```bash
# Good - Context-aware defaults
DATABASE_HOST=${DATABASE_HOST:-localhost}    # Local dev default
REDIS_HOST=${REDIS_HOST:-localhost}          # Local dev default

# In CI environment file
DATABASE_HOST=mysql     # Override for CI
REDIS_HOST=redis        # Override for CI
```

#### 3. Use Environment-Specific Files
```bash
# Structure
.env.example          # Template with all variables
.env.local           # Local development
.env.ci              # CI environment
.env.staging         # Staging environment
.env.production      # Production environment (secure)
```

#### 4. Validate Required Variables
```php
// Laravel validation
$requiredVars = ['DATABASE_HOST', 'DATABASE_PORT', 'REDIS_HOST'];
foreach ($requiredVars as $var) {
    if (!env($var)) {
        throw new RuntimeException("Required environment variable {$var} is missing");
    }
}
```

#### 5. Use Type-Safe Environment Reading
```php
// Good - Type casting
'port' => (int) env('DATABASE_PORT', 3306),
'debug' => (bool) env('APP_DEBUG', false),
'timeout' => (float) env('CONNECTION_TIMEOUT', 30.0),

// Bad - String values everywhere
'port' => env('DATABASE_PORT', '3306'),  // Should be integer
'debug' => env('APP_DEBUG', 'false'),    // Should be boolean
```

#### 6. Document Environment Variables
```php
/**
 * Environment Variables:
 * - DATABASE_HOST: Database server hostname (default: localhost)
 * - DATABASE_PORT: Database server port (default: 3306)
 * - DATABASE_NAME: Database name (required)
 * - DATABASE_USER: Database username (default: root)
 * - DATABASE_PASSWORD: Database password (required in production)
 */
```

#### 7. Use Health Checks with Environment Awareness
```yaml
services:
  app:
    healthcheck:
      test: ["CMD", "nc", "-z", "${DATABASE_HOST}", "${DATABASE_PORT}"]
      interval: 30s
      timeout: 10s
      retries: 3
```

### Anti-patterns to Avoid

#### 1. Hardcoded Service Names
```yaml
# Bad - Hardcoded
services:
  app:
    environment:
      - DATABASE_URL=mysql://root:password@mysql:3306/app_db

# Good - Environment-aware
services:
  app:
    environment:
      - DATABASE_URL=mysql://${DATABASE_USER}:${DATABASE_PASSWORD}@${DATABASE_HOST}:${DATABASE_PORT}/${DATABASE_NAME}
```

#### 2. Missing Fallback Values
```bash
# Bad - No fallbacks
DATABASE_HOST=${DATABASE_HOST}

# Good - Sensible fallbacks
DATABASE_HOST=${DATABASE_HOST:-localhost}
```

#### 3. Inconsistent Variable Naming
```bash
# Bad - Inconsistent naming
DB_HOST=localhost
DATABASE_PORT=3306
MYSQL_NAME=app_db

# Good - Consistent naming
DATABASE_HOST=localhost
DATABASE_PORT=3306
DATABASE_NAME=app_db
```

#### 4. Environment Leakage
```bash
# Bad - Production secrets in CI
# .env.ci
DATABASE_PASSWORD=production-secret-password

# Good - Test-specific values
# .env.ci
DATABASE_PASSWORD=test-password
```

#### 5. Over-Configuration
```bash
# Bad - Too many variables for simple cases
DATABASE_CONNECTION_POOL_MIN_SIZE=5
DATABASE_CONNECTION_POOL_MAX_SIZE=20
DATABASE_CONNECTION_POOL_ACQUIRE_TIMEOUT=30
DATABASE_CONNECTION_POOL_IDLE_TIMEOUT=300

# Good - Start simple, add complexity when needed
DATABASE_HOST=localhost
DATABASE_PORT=3306
```

#### 6. Ignoring Service Dependencies
```yaml
# Bad - No dependency management
services:
  app:
    build: .
    environment:
      - DATABASE_HOST=mysql

  mysql:
    image: mysql:8.0

# Good - Proper dependencies
services:
  app:
    build: .
    environment:
      - DATABASE_HOST=mysql
    depends_on:
      mysql:
        condition: service_healthy

  mysql:
    image: mysql:8.0
    healthcheck:
      test: ["CMD", "mysqladmin", "ping"]
```

#### 7. Environment Variable Pollution
```yaml
# Bad - Too many environment variables
services:
  app:
    environment:
      - DATABASE_HOST=mysql
      - DATABASE_PORT=3306
      - DATABASE_NAME=app_db
      - DATABASE_USER=root
      - DATABASE_PASSWORD=password
      - DATABASE_CHARSET=utf8mb4
      - DATABASE_COLLATION=utf8mb4_unicode_ci
      - DATABASE_TIMEZONE=UTC
      - DATABASE_SSL_MODE=prefer
      # ... 20 more variables

# Good - Group related configuration
services:
  app:
    environment:
      - DATABASE_URL=mysql://root:password@mysql:3306/app_db?charset=utf8mb4
    env_file:
      - .env.database
```

### Security Best Practices

#### 1. Separate Secrets from Configuration
```bash
# Good - Use separate secret management
# .env (non-sensitive)
DATABASE_HOST=mysql
DATABASE_PORT=3306

# .env.secrets (sensitive, excluded from VCS)
DATABASE_PASSWORD=secret-password
API_KEY=sensitive-api-key
```

#### 2. Use Docker Secrets in Production
```yaml
# docker-compose.prod.yml
version: '3.8'

services:
  app:
    environment:
      - DATABASE_PASSWORD_FILE=/run/secrets/db_password
    secrets:
      - db_password

secrets:
  db_password:
    external: true
```

#### 3. Validate Environment in Production
```php
// Validate production environment
if (env('APP_ENV') === 'production') {
    $requiredSecrets = ['DATABASE_PASSWORD', 'APP_KEY', 'JWT_SECRET'];
    foreach ($requiredSecrets as $secret) {
        if (!env($secret)) {
            throw new RuntimeException("Required secret {$secret} is missing in production");
        }
    }
}
```

This guide provides a comprehensive foundation for implementing Docker environment variable configuration that works reliably across development, CI, and production environments.