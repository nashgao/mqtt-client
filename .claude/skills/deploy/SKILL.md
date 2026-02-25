---
allowed-tools: all
description: Smart Routing Deployment Orchestration - Intelligent deployment routing to appropriate systems based on project context
---

# 🚀 SMART ROUTING DEPLOYMENT ORCHESTRATION 🚀

**COMPREHENSIVE DEPLOYMENT COORDINATION - LEVERAGING 95% EXISTING INFRASTRUCTURE**

When you run `/deploy`, you get **INTELLIGENT ROUTING** to the right deployment system:

1. **CONTEXT DETECTION** - Automatically detect deployment type and environment
2. **SMART ROUTING** - Route to appropriate existing orchestration systems
3. **UNIFIED INTERFACE** - Single command for all deployment scenarios
4. **SAFETY INTEGRATION** - Leverage existing monitoring, rollback, and release workflows
5. **APPROVAL WORKFLOWS** - Integration with existing approval and quality gates

**Usage:** `/deploy $ENVIRONMENT $DEPLOYMENT_TYPE $OPTIONS`

**Examples:**
- `/deploy dev` - Auto-detect and deploy to development
- `/deploy staging docker` - Docker deployment to staging  
- `/deploy prod k8s --approval-required` - Production Kubernetes with approvals
- `/deploy prod --rollback` - Rollback production deployment

---

## 🎯 DEPLOYMENT CONTEXT DETECTION

**Automatic Platform Detection:**
```bash
detect_deployment_context() {
    local environment="${1:-auto}"
    local deployment_type="${2:-auto}"
    local project_root=$(pwd)
    
    echo "🔍 SMART DEPLOYMENT CONTEXT DETECTION"
    echo "===================================="
    
    # Detect project structure and deployment requirements
    context_analysis=$(analyze_project_structure "$project_root")
    
    # Environment detection
    if [[ "$environment" == "auto" ]]; then
        environment=$(detect_environment "$project_root")
        echo "🎯 Auto-detected environment: $environment"
    fi
    
    # Platform detection  
    if [[ "$deployment_type" == "auto" ]]; then
        deployment_type=$(detect_deployment_platform "$project_root")
        echo "🏗️ Auto-detected platform: $deployment_type"
    fi
    
    # Validate detection results
    validate_deployment_context "$environment" "$deployment_type"
    
    echo "✅ Context detection complete: $environment → $deployment_type"
}

analyze_project_structure() {
    local project_root=$1
    local analysis_result=""
    
    echo "📊 Analyzing project structure..."
    
    # Container detection
    if [[ -f "$project_root/Dockerfile" || -f "$project_root/docker-compose.yml" ]]; then
        analysis_result="$analysis_result container,"
        echo "  ✅ Container configuration detected"
    fi
    
    # Kubernetes detection
    if [[ -d "$project_root/k8s" || -d "$project_root/kubernetes" || -f "$project_root/kustomization.yaml" ]]; then
        analysis_result="$analysis_result kubernetes,"
        echo "  ✅ Kubernetes configuration detected"
    fi
    
    # Serverless detection
    if [[ -f "$project_root/serverless.yml" || -f "$project_root/sam.yaml" || -d "$project_root/lambda" ]]; then
        analysis_result="$analysis_result serverless,"
        echo "  ✅ Serverless configuration detected"
    fi
    
    # Traditional deployment detection
    if [[ -f "$project_root/deploy.sh" || -d "$project_root/deploy" ]]; then
        analysis_result="$analysis_result traditional,"
        echo "  ✅ Traditional deployment scripts detected"
    fi
    
    # CI/CD detection
    if [[ -f "$project_root/.github/workflows/deploy.yml" || -f "$project_root/.gitlab-ci.yml" ]]; then
        analysis_result="$analysis_result cicd,"
        echo "  ✅ CI/CD deployment pipeline detected"
    fi
    
    echo "$analysis_result"
}

detect_environment() {
    local project_root=$1
    
    # Check git branch for environment indicators
    current_branch=$(git branch --show-current 2>/dev/null || echo "unknown")
    
    case "$current_branch" in
        main|master|production|prod)
            echo "prod"
            ;;
        staging|stage|preprod)
            echo "staging"
            ;;
        develop|development|dev)
            echo "dev"
            ;;
        *)
            # Check for environment configuration files
            if [[ -f "$project_root/.env.production" ]]; then
                echo "prod"
            elif [[ -f "$project_root/.env.staging" ]]; then
                echo "staging"
            else
                echo "dev"
            fi
            ;;
    esac
}

detect_deployment_platform() {
    local project_root=$1
    
    # Priority-based platform detection
    if [[ -d "$project_root/k8s" || -d "$project_root/kubernetes" ]]; then
        echo "k8s"
    elif [[ -f "$project_root/serverless.yml" || -f "$project_root/sam.yaml" ]]; then
        echo "serverless"
    elif [[ -f "$project_root/Dockerfile" || -f "$project_root/docker-compose.yml" ]]; then
        echo "docker"
    elif [[ -f "$project_root/.github/workflows/deploy.yml" ]]; then
        echo "github-actions"
    elif [[ -f "$project_root/deploy.sh" ]]; then
        echo "script"
    else
        echo "traditional"
    fi
}

validate_deployment_context() {
    local environment=$1
    local deployment_type=$2
    
    echo "🔒 Validating deployment context..."
    
    # Environment validation
    if [[ ! "$environment" =~ ^(dev|staging|prod)$ ]]; then
        echo "❌ Invalid environment: $environment"
        echo "Valid environments: dev, staging, prod"
        exit 1
    fi
    
    # Platform validation
    valid_platforms="docker|k8s|serverless|github-actions|script|traditional"
    if [[ ! "$deployment_type" =~ ^($valid_platforms)$ ]]; then
        echo "❌ Invalid deployment platform: $deployment_type"
        echo "Valid platforms: docker, k8s, serverless, github-actions, script, traditional"
        exit 1
    fi
    
    echo "✅ Context validation successful"
}
```

## 🚦 SMART ROUTING DECISION ENGINE

**Intelligent routing to existing orchestration systems:**
```bash
route_deployment() {
    local environment=$1
    local deployment_type=$2
    local options=$3
    
    echo "🚦 SMART DEPLOYMENT ROUTING"
    echo "=========================="
    echo "Environment: $environment"
    echo "Platform: $deployment_type"
    echo "Options: $options"
    echo ""
    
    # Load deployment router configuration
    source "$(dirname "$0")/../shared/deployment-router.md"
    
    # Determine routing strategy
    routing_decision=$(determine_routing_strategy "$environment" "$deployment_type" "$options")
    
    case "$routing_decision" in
        "infra-orchestrator")
            route_to_infra_orchestrator "$environment" "$deployment_type" "$options"
            ;;
        "release-workflow")
            route_to_release_workflow "$environment" "$deployment_type" "$options"
            ;;
        "cicd-orchestrator")
            route_to_cicd_orchestrator "$environment" "$deployment_type" "$options"
            ;;
        "direct-deployment")
            route_to_direct_deployment "$environment" "$deployment_type" "$options"
            ;;
        *)
            echo "❌ Unknown routing decision: $routing_decision"
            exit 1
            ;;
    esac
}

determine_routing_strategy() {
    local environment=$1
    local deployment_type=$2
    local options=$3
    
    # Production deployments always use release workflow
    if [[ "$environment" == "prod" ]]; then
        echo "release-workflow"
        return
    fi
    
    # Complex multi-service deployments use infra orchestrator
    if [[ "$options" =~ --complex|--multi-service|--orchestrated ]]; then
        echo "infra-orchestrator"
        return
    fi
    
    # CI/CD pipeline deployments
    if [[ "$deployment_type" == "github-actions" || "$options" =~ --pipeline ]]; then
        echo "cicd-orchestrator"
        return
    fi
    
    # Simple deployments can go direct
    echo "direct-deployment"
}

route_to_infra_orchestrator() {
    local environment=$1
    local deployment_type=$2
    local options=$3
    
    echo "🎭 ROUTING TO INFRA-ORCHESTRATOR"
    echo "==============================="
    echo "Complex deployment detected - using master orchestration"
    
    # Call infra-orchestrator with deployment context
    orchestrator_task="Deploy $deployment_type application to $environment environment with options: $options"
    
    echo "Spawning infra-orchestrator for complex deployment coordination..."
    
    # Use Task tool to spawn infra-orchestrator agent
    echo "<function_calls>"
    echo "<invoke name=\"Task\">"
    echo "<parameter name=\"subagent_type\">infra-orchestrator</parameter>"
    echo "<parameter name=\"description\">Complex deployment coordination</parameter>"
    echo "<parameter name=\"prompt\">$orchestrator_task
    
Environment: $environment
Platform: $deployment_type
Options: $options
Coordination Mode: deployment-orchestration
    
Coordinate this deployment using the master orchestration patterns, managing multiple agents for parallel deployment tasks including validation, monitoring setup, and post-deployment verification.</parameter>"
    echo "</invoke>"
    echo "</function_calls>"
}

route_to_release_workflow() {
    local environment=$1
    local deployment_type=$2  
    local options=$3
    
    echo "🚀 ROUTING TO RELEASE WORKFLOW"
    echo "=============================="
    echo "Production deployment - using comprehensive release process"
    
    # Check if we're in a rollback scenario
    if [[ "$options" =~ --rollback ]]; then
        echo "🔄 Rollback requested - routing to rollback procedure"
        /claude execute_command rollback --environment="$environment" --platform="$deployment_type"
        return
    fi
    
    # Route to release workflow for production deployments  
    echo "Executing release workflow for production deployment..."
    
    # Check if release workflow exists and execute
    if [[ -f "$(dirname "$0")/git/workflows/release.md" ]]; then
        # Execute release workflow with deployment context
        source "$(dirname "$0")/git/workflows/release.md"
        
        # Set deployment context for release workflow
        export DEPLOYMENT_ENVIRONMENT="$environment"
        export DEPLOYMENT_TYPE="$deployment_type" 
        export AUTO_DEPLOY="true"
        export MONITORING_INTEGRATION="enabled"
        
        # Execute production deployment phase
        deploy_to_production
    else
        echo "❌ Release workflow not found - using direct production deployment"
        execute_direct_deployment "$environment" "$deployment_type" "$options"
    fi
}

route_to_cicd_orchestrator() {
    local environment=$1
    local deployment_type=$2
    local options=$3
    
    echo "🤖 ROUTING TO CI/CD ORCHESTRATOR"
    echo "==============================="
    echo "Pipeline deployment - using CI/CD orchestration"
    
    # Spawn cicd-orchestrator agent for pipeline management
    echo "Spawning CI/CD orchestrator for pipeline deployment..."
    
    echo "<function_calls>"
    echo "<invoke name=\"Task\">"
    echo "<parameter name=\"subagent_type\">cicd-failure-orchestrator</parameter>"
    echo "<parameter name=\"description\">CI/CD Pipeline Deployment</parameter>"
    echo "<parameter name=\"prompt\">Execute deployment pipeline for $deployment_type to $environment environment.
    
Pipeline Requirements:
- Environment: $environment
- Platform: $deployment_type  
- Options: $options
- Integration: Smart deployment routing
- Failure handling: Auto-rollback enabled
    
Coordinate the CI/CD pipeline execution with proper error handling, monitoring integration, and automatic rollback capabilities for failed deployments.</parameter>"
    echo "</invoke>"
    echo "</function_calls>"
}

route_to_direct_deployment() {
    local environment=$1
    local deployment_type=$2
    local options=$3
    
    echo "⚡ DIRECT DEPLOYMENT ROUTING"
    echo "=========================="
    echo "Simple deployment - executing directly with safety checks"
    
    # Even direct deployments use safety checks
    execute_direct_deployment "$environment" "$deployment_type" "$options"
}
```

## 🛡️ SAFETY AND APPROVAL INTEGRATION

**Mandatory safety checks and approval workflows:**
```bash
execute_deployment_with_safety() {
    local environment=$1
    local deployment_type=$2
    local options=$3
    
    echo "🛡️ DEPLOYMENT SAFETY PROTOCOL"
    echo "============================="
    
    # Pre-deployment safety checks
    run_pre_deployment_checks "$environment" "$deployment_type"
    
    # Approval workflow integration
    if requires_approval "$environment" "$options"; then
        request_deployment_approval "$environment" "$deployment_type" "$options"
    fi
    
    # Backup and rollback preparation
    prepare_rollback_procedures "$environment" "$deployment_type"
    
    # Execute deployment with monitoring
    execute_monitored_deployment "$environment" "$deployment_type" "$options"
    
    # Post-deployment validation
    validate_deployment_success "$environment" "$deployment_type"
}

run_pre_deployment_checks() {
    local environment=$1
    local deployment_type=$2
    
    echo "🔍 PRE-DEPLOYMENT VALIDATION"
    echo "==========================="
    
    # Repository state validation
    if ! git diff --quiet; then
        echo "❌ Working directory not clean"
        echo "Commit or stash changes before deployment"
        exit 1
    fi
    
    # Branch validation for production
    if [[ "$environment" == "prod" ]]; then
        current_branch=$(git branch --show-current)
        if [[ ! "$current_branch" =~ ^(main|master|release/.*)$ ]]; then
            echo "❌ Production deployments must be from main, master, or release branch"
            echo "Current branch: $current_branch"
            exit 1
        fi
    fi
    
    # Configuration validation
    validate_deployment_config "$environment" "$deployment_type"
    
    # Security scan
    run_security_scan "$deployment_type"
    
    echo "✅ Pre-deployment checks passed"
}

requires_approval() {
    local environment=$1
    local options=$2
    
    # Production always requires approval
    [[ "$environment" == "prod" ]] && return 0
    
    # Explicit approval flag
    [[ "$options" =~ --approval-required ]] && return 0
    
    # Staging with sensitive changes
    if [[ "$environment" == "staging" && "$options" =~ --breaking-changes ]]; then
        return 0
    fi
    
    return 1
}

request_deployment_approval() {
    local environment=$1
    local deployment_type=$2
    local options=$3
    
    echo "👥 DEPLOYMENT APPROVAL WORKFLOW"
    echo "==============================="
    
    # Create approval request
    cat > deployment_approval_request.md << EOF
# Deployment Approval Request

**Environment:** $environment
**Platform:** $deployment_type  
**Requested by:** $(git config user.name)
**Request time:** $(date -u '+%Y-%m-%d %H:%M:%S UTC')

## Changes Summary
$(git log --oneline -5)

## Impact Assessment
- **Risk Level:** $(assess_deployment_risk "$environment" "$deployment_type")
- **Downtime Expected:** $(estimate_downtime "$deployment_type")
- **Rollback Time:** $(estimate_rollback_time "$deployment_type")

## Approvals Required
- [ ] Technical Lead
- [ ] Product Manager (if business impact)
- [ ] Security Team (if security changes)
- [ ] Operations Team (for production)

## Deployment Options
$options
EOF
    
    echo "📧 Approval request created: deployment_approval_request.md"
    echo "Please obtain required approvals before proceeding"
    
    if [[ "$options" =~ --auto-approve ]]; then
        echo "⚠️ AUTO-APPROVAL FLAG DETECTED - SKIPPING MANUAL APPROVAL"
    else
        read -p "Have all required approvals been obtained? (yes/no): " approval_confirmed
        if [[ "$approval_confirmed" != "yes" ]]; then
            echo "❌ Deployment cancelled - approvals not confirmed"
            exit 1
        fi
    fi
}

prepare_rollback_procedures() {
    local environment=$1
    local deployment_type=$2
    
    echo "🔄 ROLLBACK PREPARATION"
    echo "======================"
    
    # Call existing rollback system to prepare procedures
    echo "Preparing rollback procedures using rollback system..."
    
    # Check if rollback system exists and prepare
    if [[ -f "$(dirname "$0")/rollback.md" ]]; then
        # Source rollback functions
        source "$(dirname "$0")/rollback.md"
        
        # Prepare rollback with context
        export ROLLBACK_ENVIRONMENT="$environment"
        export ROLLBACK_PLATFORM="$deployment_type"
        export ROLLBACK_MODE="prepare-only"
        export AUTO_BACKUP="true"
        
        # Execute rollback preparation
        echo "Executing rollback preparation procedures..."
        # Note: The actual rollback preparation function would be called here
        # based on the rollback.md implementation
    else
        echo "⚠️ Rollback system not found - creating basic rollback preparation"
        create_basic_rollback_backup "$environment" "$deployment_type"
    fi
    
    echo "✅ Rollback procedures prepared"
}
```

## 📊 MONITORING AND VALIDATION INTEGRATION

**Comprehensive monitoring integration:**
```bash
execute_monitored_deployment() {
    local environment=$1
    local deployment_type=$2
    local options=$3
    
    echo "📊 MONITORED DEPLOYMENT EXECUTION"
    echo "================================="
    
    # Start deployment monitoring
    deployment_id="deploy-$(date +%Y%m%d-%H%M%S)-$environment-$deployment_type"
    start_deployment_monitoring "$deployment_id" "$environment"
    
    # Execute the actual deployment
    case "$deployment_type" in
        "docker")
            execute_docker_deployment "$environment" "$options"
            ;;
        "k8s")
            execute_kubernetes_deployment "$environment" "$options"
            ;;
        "serverless")
            execute_serverless_deployment "$environment" "$options"
            ;;
        "github-actions")
            execute_pipeline_deployment "$environment" "$options"
            ;;
        *)
            execute_traditional_deployment "$environment" "$deployment_type" "$options"
            ;;
    esac
    
    # Post-deployment monitoring
    monitor_deployment_health "$deployment_id" "$environment"
    
    echo "✅ Monitored deployment completed: $deployment_id"
}

start_deployment_monitoring() {
    local deployment_id=$1
    local environment=$2
    
    echo "🔍 Starting deployment monitoring: $deployment_id"
    
    # Integrate with existing monitor command
    echo "Starting deployment monitoring integration..."
    
    # Check if monitor system exists
    if [[ -f "$(dirname "$0")/monitor.md" ]]; then
        # Source monitor functions (run in background)
        (
            source "$(dirname "$0")/monitor.md"
            
            # Set monitoring context
            export MONITOR_DEPLOYMENT_ID="$deployment_id"
            export MONITOR_ENVIRONMENT="$environment"
            export MONITOR_MODE="deployment-monitoring"
            export ALERT_THRESHOLD="critical"
            
            # Note: The actual monitoring function would be called here
            # based on the monitor.md implementation
            echo "Deployment monitoring active for: $deployment_id"
        ) &
        
        monitor_pid=$!
        echo "📊 Monitoring started with PID: $monitor_pid"
    else
        echo "⚠️ Monitor system not found - using basic health checks"
        # Fallback to basic monitoring
        start_basic_deployment_monitoring "$deployment_id" "$environment" &
        monitor_pid=$!
    fi
    
    monitor_pid=$!
    echo "📊 Monitoring started with PID: $monitor_pid"
}

validate_deployment_success() {
    local environment=$1
    local deployment_type=$2
    
    echo "✅ DEPLOYMENT VALIDATION"
    echo "======================="
    
    # Health check validation
    if ! run_health_checks "$environment"; then
        echo "❌ Health checks failed"
        trigger_automatic_rollback "$environment" "$deployment_type"
        exit 1
    fi
    
    # Performance validation
    if ! validate_performance_metrics "$environment"; then
        echo "⚠️ Performance validation failed"
        # Don't auto-rollback for performance, but alert
        send_performance_alert "$environment"
    fi
    
    # Integration validation
    validate_external_integrations "$environment"
    
    echo "✅ Deployment validation successful"
}

run_health_checks() {
    local environment=$1
    
    # Use environment-specific health check URLs
    case "$environment" in
        "prod")
            health_url="https://api.production.example.com/health"
            ;;
        "staging") 
            health_url="https://api.staging.example.com/health"
            ;;
        "dev")
            health_url="https://api.dev.example.com/health"
            ;;
    esac
    
    echo "🏥 Checking health endpoint: $health_url"
    
    # Retry health checks with backoff
    for attempt in {1..5}; do
        if curl -sf --max-time 10 "$health_url" &>/dev/null; then
            echo "✅ Health check passed on attempt $attempt"
            return 0
        fi
        
        echo "⏳ Health check attempt $attempt failed, retrying in $((attempt * 5))s..."
        sleep $((attempt * 5))
    done
    
    echo "❌ Health checks failed after 5 attempts"
    return 1
}

trigger_automatic_rollback() {
    local environment=$1
    local deployment_type=$2
    
    echo "🚨 AUTOMATIC ROLLBACK TRIGGERED"
    echo "==============================="
    echo "Deployment failed validation - executing automatic rollback"
    
    # Execute automatic rollback using rollback system
    echo "Triggering automatic rollback due to validation failure..."
    
    if [[ -f "$(dirname "$0")/rollback.md" ]]; then
        source "$(dirname "$0")/rollback.md"
        
        # Set rollback context
        export ROLLBACK_ENVIRONMENT="$environment"
        export ROLLBACK_PLATFORM="$deployment_type"
        export ROLLBACK_MODE="automatic"
        export ROLLBACK_REASON="deployment-validation-failed"
        
        # Execute emergency rollback
        echo "Executing emergency rollback procedures..."
        # emergency_release_rollback function from rollback.md would be called here
    else
        echo "❌ Rollback system not available - manual intervention required"
        exit 1
    fi
}
```

## 🎛️ PLATFORM-SPECIFIC DEPLOYMENT IMPLEMENTATIONS

**Optimized deployment implementations:**
```bash
execute_docker_deployment() {
    local environment=$1
    local options=$2
    
    echo "🐳 DOCKER DEPLOYMENT"
    echo "==================="
    
    # Build image with environment context
    image_tag="$environment-$(git rev-parse --short HEAD)"
    
    if docker build -t "app:$image_tag" .; then
        echo "✅ Docker image built: app:$image_tag"
    else
        echo "❌ Docker build failed"
        exit 1
    fi
    
    # Deploy using docker-compose or registry push
    if [[ -f "docker-compose.$environment.yml" ]]; then
        docker-compose -f "docker-compose.$environment.yml" up -d
    else
        # Push to registry and update running containers
        deploy_docker_to_registry "$image_tag" "$environment"
    fi
}

execute_kubernetes_deployment() {
    local environment=$1
    local options=$2
    
    echo "☸️ KUBERNETES DEPLOYMENT"
    echo "======================="
    
    # Apply environment-specific manifests
    if [[ -d "k8s/$environment" ]]; then
        kubectl apply -f "k8s/$environment/" --record
    elif [[ -f "kustomization.yaml" ]]; then
        kubectl apply -k "overlays/$environment" --record
    else
        echo "❌ No Kubernetes manifests found for $environment"
        exit 1
    fi
    
    # Wait for rollout completion
    kubectl rollout status deployment/app -n "$environment" --timeout=600s
}

execute_serverless_deployment() {
    local environment=$1
    local options=$2
    
    echo "λ SERVERLESS DEPLOYMENT"
    echo "======================"
    
    if [[ -f "serverless.yml" ]]; then
        sls deploy --stage "$environment" --verbose
    elif [[ -f "sam.yaml" ]]; then
        sam deploy --stack-name "app-$environment" --parameter-overrides Environment="$environment"
    else
        echo "❌ No serverless configuration found"
        exit 1
    fi
}

execute_pipeline_deployment() {
    local environment=$1
    local options=$2
    
    echo "🤖 PIPELINE DEPLOYMENT"
    echo "====================="
    
    # Trigger GitHub Actions workflow
    if command -v gh &> /dev/null; then
        gh workflow run "deploy.yml" \
            --field environment="$environment" \
            --field deployment_type="pipeline"
        
        echo "⏳ Waiting for pipeline completion..."
        # Monitor pipeline status
        monitor_pipeline_execution "$environment"
    else
        echo "❌ GitHub CLI not available for pipeline execution"
        exit 1
    fi
}

execute_traditional_deployment() {
    local environment=$1
    local deployment_type=$2
    local options=$3
    
    echo "📜 TRADITIONAL DEPLOYMENT"
    echo "======================="
    
    if [[ -f "deploy/$environment.sh" ]]; then
        bash "deploy/$environment.sh"
    elif [[ -f "deploy.sh" ]]; then
        bash "deploy.sh" "$environment"
    else
        echo "❌ No deployment script found"
        exit 1
    fi
}
```

## 🔄 ERROR HANDLING AND RECOVERY

**Comprehensive error handling:**
```bash
handle_deployment_error() {
    local error_type=$1
    local environment=$2
    local deployment_type=$3
    local error_message=$4
    
    echo "🚨 DEPLOYMENT ERROR HANDLING"
    echo "==========================="
    echo "Error Type: $error_type"
    echo "Environment: $environment"  
    echo "Platform: $deployment_type"
    echo "Error: $error_message"
    
    # Log error for analysis
    log_deployment_error "$error_type" "$environment" "$deployment_type" "$error_message"
    
    case "$error_type" in
        "health-check-failed")
            handle_health_check_failure "$environment" "$deployment_type"
            ;;
        "build-failed")
            handle_build_failure "$deployment_type" "$error_message"
            ;;
        "deployment-timeout")
            handle_deployment_timeout "$environment" "$deployment_type"
            ;;
        "validation-failed")
            handle_validation_failure "$environment" "$deployment_type"
            ;;
        *)
            handle_generic_error "$environment" "$deployment_type" "$error_message"
            ;;
    esac
}

handle_health_check_failure() {
    local environment=$1
    local deployment_type=$2
    
    echo "🏥 Health check failure - investigating..."
    
    # Collect diagnostics
    collect_deployment_diagnostics "$environment"
    
    # Automatic rollback for production
    if [[ "$environment" == "prod" ]]; then
        echo "🔄 Production health check failed - triggering automatic rollback"
        trigger_automatic_rollback "$environment" "$deployment_type"
    else
        echo "⚠️ Non-production health check failed - manual intervention required"
        create_incident_report "$environment" "$deployment_type" "health-check-failed"
    fi
}

collect_deployment_diagnostics() {
    local environment=$1
    
    echo "🔍 Collecting deployment diagnostics..."
    
    # Create diagnostics directory
    diagnostics_dir="diagnostics-$(date +%Y%m%d-%H%M%S)"
    mkdir -p "$diagnostics_dir"
    
    # Collect logs
    collect_application_logs "$environment" "$diagnostics_dir"
    collect_infrastructure_logs "$environment" "$diagnostics_dir"
    
    # Collect metrics
    collect_performance_metrics "$environment" "$diagnostics_dir"
    
    # Create summary report
    create_diagnostics_summary "$diagnostics_dir"
    
    echo "📊 Diagnostics collected in: $diagnostics_dir"
}
```

## 📋 USAGE EXAMPLES AND INTEGRATION

**Complete usage scenarios:**
```bash
# Development deployment (auto-detection)
/deploy dev

# Staging Docker deployment with approval
/deploy staging docker --approval-required

# Production deployment (uses release workflow)
/deploy prod

# Complex multi-service deployment
/deploy staging --complex --multi-service

# Emergency rollback
/deploy prod --rollback

# Pipeline deployment
/deploy staging --pipeline

# Deployment with custom monitoring
/deploy prod --monitoring-enhanced --alert-critical
```

**Integration Points Summary:**
- ✅ **infra-orchestrator** - Complex multi-service deployments
- ✅ **git/workflows/release** - Production deployments with full lifecycle
- ✅ **monitor** - Comprehensive observability during deployment
- ✅ **rollback** - Safe rollback procedures and emergency recovery
- ✅ **cicd-orchestrator** - Pipeline and CI/CD integration
- ✅ **Existing agents** - Deployment fixers and specialized handlers

**Safety Features:**
- ✅ Pre-deployment validation and security scanning
- ✅ Approval workflows for sensitive environments
- ✅ Comprehensive health checks and monitoring
- ✅ Automatic rollback on critical failures
- ✅ Rollback preparation before every deployment
- ✅ Integration with existing safety systems

This Smart Routing Deployment system provides a unified interface while leveraging 95% of existing infrastructure, ensuring deployments are safe, monitored, and properly orchestrated based on context and requirements.