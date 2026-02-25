---
description: Complete MCP integration testing command for validating Model Context Protocol tool installations and functionality
---

## 🚨 MANDATORY: Rule Enforcement for Test Commands

**This command operates under the Rule Enforcement Framework**
**Reference: `/templates/agents/../../shared/skills/rule-enforcement-framework.md`**

**CRITICAL ENFORCEMENT RULES:**
- 🔒 **Scope Containment**: Only modify files within assigned test scope
- 🔒 **Test Type Separation**: NEVER convert between UnitTestCase and BaseIntegrationTestCase
- 🔒 **Verification Mandate**: Execute actual test commands (`composer test` or `composer test:integration`)
- 🔒 **Exit Code Validation**: Confirm zero exit codes before claiming success
- 🔒 **No Architecture Changes**: No framework modifications without explicit permission

**IMMEDIATE HALT CONDITIONS:**
- Cross-test-type contamination detected
- File modifications outside assigned scope
- Success claims without command execution verification
- Architectural changes attempted without permission

---

# MCP Integration Testing Command

Comprehensive testing and validation framework for MCP (Model Context Protocol) tool installations, ensuring reliable functionality and integration with Claude Desktop.

## Command Overview

This command provides end-to-end testing capabilities for MCP tool installations, from pre-installation validation to post-deployment verification.

### Core Capabilities

- **Pre-installation validation**: System compatibility and dependency checking
- **Post-installation verification**: Configuration and connectivity testing  
- **Functionality testing**: Real-world operations testing for each MCP tool
- **Automated diagnostics**: Issue identification and resolution guidance
- **Integration testing**: Claude Desktop compatibility verification

## Usage

```bash
# Test single MCP tool
claude mcp-test aws

# Test multiple specific tools
claude mcp-test aws azure playwright github

# Test all common MCP tools
claude mcp-test --all

# Test tools for specific project type
claude mcp-test --project-type fullstack

# Run diagnostics only
claude mcp-test --diagnose-only aws

# Generate comprehensive report
claude mcp-test --report aws azure
```

## Command Implementation

```bash
#!/bin/bash
# mcp-integration-testing.md - Complete MCP testing framework

# Source the MCP Test Validator Agent
source "$(dirname "${BASH_SOURCE[0]}")/../agents/mcp-test-validator.md"

# Source the troubleshooting guide utilities
source "$(dirname "${BASH_SOURCE[0]}")/../shared/mcp-troubleshooting-guide.md"

# Command entry point
mcp_integration_testing() {
    local command="$1"
    shift
    
    case "$command" in
        "test")
            run_mcp_testing "$@"
            ;;
        "validate")
            run_mcp_validation "$@"
            ;;
        "diagnose")
            run_mcp_diagnostics "$@"
            ;;
        "fix")
            run_mcp_automated_fixes "$@"
            ;;
        "report")
            generate_mcp_test_report "$@"
            ;;
        *)
            show_mcp_testing_help
            ;;
    esac
}

# Main testing orchestrator
run_mcp_testing() {
    local tools=()
    local options=()
    local project_type=""
    local test_all=false
    local diagnose_only=false
    local generate_report=false
    
    # Parse arguments
    while [[ $# -gt 0 ]]; do
        case $1 in
            --all)
                test_all=true
                shift
                ;;
            --project-type)
                project_type="$2"
                shift 2
                ;;
            --diagnose-only)
                diagnose_only=true
                shift
                ;;
            --report)
                generate_report=true
                shift
                ;;
            --verbose)
                options+=("verbose")
                shift
                ;;
            --fix-issues)
                options+=("auto-fix")
                shift
                ;;
            -*)
                echo "Unknown option: $1" >&2
                show_mcp_testing_help
                return 1
                ;;
            *)
                tools+=("$1")
                shift
                ;;
        esac
    done
    
    # Determine tools to test
    if [ "$test_all" = true ]; then
        tools=("aws" "azure" "playwright" "github" "filesystem")
    elif [ -n "$project_type" ]; then
        case "$project_type" in
            "web")
                tools=("playwright" "github")
                ;;
            "cloud-aws")
                tools=("aws" "github" "filesystem")
                ;;
            "cloud-azure")
                tools=("azure" "github" "filesystem")
                ;;
            "fullstack")
                tools=("aws" "playwright" "github" "filesystem")
                ;;
            "backend")
                tools=("aws" "github" "filesystem")
                ;;
            "frontend")
                tools=("playwright" "github")
                ;;
            *)
                echo "Unknown project type: $project_type" >&2
                echo "Supported types: web, cloud-aws, cloud-azure, fullstack, backend, frontend"
                return 1
                ;;
        esac
    fi
    
    if [ ${#tools[@]} -eq 0 ]; then
        echo "No MCP tools specified. Use --all, --project-type, or specify tools directly."
        show_mcp_testing_help
        return 1
    fi
    
    echo "🚀 MCP Integration Testing Framework"
    echo "===================================="
    echo "Testing tools: ${tools[*]}"
    echo "Options: ${options[*]}"
    echo ""
    
    # Run diagnostics only if requested
    if [ "$diagnose_only" = true ]; then
        for tool in "${tools[@]}"; do
            echo "🔧 Running diagnostics for: $tool"
            diagnose_mcp_issues "$tool"
            echo ""
        done
        return 0
    fi
    
    # Run comprehensive testing
    local failed_tools=()
    local successful_tools=()
    
    for tool in "${tools[@]}"; do
        echo "🧪 Testing MCP tool: $tool"
        echo "$(printf '=%.0s' {1..50})"
        
        if test_single_mcp_tool_comprehensive "$tool" "${options[@]}"; then
            successful_tools+=("$tool")
            echo "✅ $tool: ALL TESTS PASSED"
        else
            failed_tools+=("$tool")
            echo "❌ $tool: TESTS FAILED"
            
            # Auto-fix if requested
            if [[ " ${options[*]} " =~ " auto-fix " ]]; then
                echo "🔧 Attempting automated fixes for $tool..."
                attempt_automated_fixes "$tool"
            fi
        fi
        echo ""
    done
    
    # Display summary
    echo "📊 Testing Summary"
    echo "=================="
    echo "✅ Successful tools (${#successful_tools[@]}): ${successful_tools[*]}"
    if [ ${#failed_tools[@]} -gt 0 ]; then
        echo "❌ Failed tools (${#failed_tools[@]}): ${failed_tools[*]}"
        echo ""
        echo "🔧 Troubleshooting recommendations:"
        for tool in "${failed_tools[@]}"; do
            echo "  - Review diagnostic log: /tmp/mcp_diagnostic_${tool}_*.log"
            echo "  - Run manual fixes: claude mcp-test fix $tool"
        done
    fi
    
    # Generate report if requested
    if [ "$generate_report" = true ]; then
        generate_comprehensive_test_report "${successful_tools[@]}" "${failed_tools[@]}"
    fi
    
    # Return success only if all tools passed
    [ ${#failed_tools[@]} -eq 0 ]
}

# Comprehensive single tool testing
test_single_mcp_tool_comprehensive() {
    local tool="$1"
    shift
    local options=("$@")
    
    local verbose=false
    [[ " ${options[*]} " =~ " verbose " ]] && verbose=true
    
    # Phase 1: Pre-installation validation
    echo "📋 Phase 1: Pre-installation validation"
    if ! validate_mcp_system_requirements; then
        echo "❌ System requirements not met for $tool"
        return 1
    fi
    
    if ! validate_mcp_dependencies "$tool"; then
        echo "❌ Dependencies not met for $tool"
        return 1
    fi
    
    [ "$verbose" = true ] && echo "✅ Pre-installation validation passed"
    
    # Phase 2: Configuration verification
    echo "📋 Phase 2: Configuration verification"
    local claude_config=""
    local config_paths=(
        "$HOME/.config/claude/claude-desktop.json"
        "$HOME/Library/Application Support/claude/claude-desktop.json"
        "$HOME/AppData/Roaming/claude/claude-desktop.json"
    )
    
    for path in "${config_paths[@]}"; do
        if [ -f "$path" ]; then
            claude_config="$path"
            break
        fi
    done
    
    if [ -z "$claude_config" ]; then
        echo "❌ Claude Desktop configuration not found"
        return 1
    fi
    
    if ! verify_mcp_installation "$tool" "$claude_config"; then
        echo "❌ Configuration verification failed for $tool"
        return 1
    fi
    
    [ "$verbose" = true ] && echo "✅ Configuration verification passed"
    
    # Phase 3: Connectivity testing
    echo "📋 Phase 3: Connectivity testing"
    if ! test_mcp_connectivity "$tool"; then
        echo "❌ Connectivity test failed for $tool"
        return 1
    fi
    
    [ "$verbose" = true ] && echo "✅ Connectivity testing passed"
    
    # Phase 4: Functionality testing
    echo "📋 Phase 4: Functionality testing"
    local func_test_result=false
    
    case "$tool" in
        "aws"|"mcp-aws")
            run_aws_mcp_test_suite && func_test_result=true
            ;;
        "azure"|"mcp-azure")
            run_azure_mcp_test_suite && func_test_result=true
            ;;
        "playwright"|"mcp-playwright")
            run_playwright_mcp_test_suite && func_test_result=true
            ;;
        "github"|"mcp-github")
            run_github_mcp_test_suite && func_test_result=true
            ;;
        "filesystem"|"mcp-filesystem")
            test_filesystem_mcp_functionality && func_test_result=true
            ;;
        *)
            echo "⚠️  No specific functionality tests for $tool, running generic tests"
            test_generic_mcp_functionality "$tool" && func_test_result=true
            ;;
    esac
    
    if [ "$func_test_result" != true ]; then
        echo "❌ Functionality testing failed for $tool"
        return 1
    fi
    
    [ "$verbose" = true ] && echo "✅ Functionality testing passed"
    
    # Phase 5: Integration testing
    echo "📋 Phase 5: Integration testing"
    if ! test_claude_desktop_integration "$tool"; then
        echo "❌ Integration testing failed for $tool"
        return 1
    fi
    
    [ "$verbose" = true ] && echo "✅ Integration testing passed"
    
    echo "✅ All phases completed successfully for $tool"
    return 0
}

# Validation-only command
run_mcp_validation() {
    local tools=("$@")
    
    if [ ${#tools[@]} -eq 0 ]; then
        echo "Usage: claude mcp-test validate <tool1> [tool2 ...]"
        return 1
    fi
    
    echo "🔍 MCP Validation Framework"
    echo "==========================="
    
    for tool in "${tools[@]}"; do
        echo "📋 Validating: $tool"
        
        echo "  🔍 System requirements..."
        if validate_mcp_system_requirements; then
            echo "    ✅ System requirements met"
        else
            echo "    ❌ System requirements not met"
        fi
        
        echo "  🔍 Dependencies..."
        if validate_mcp_dependencies "$tool"; then
            echo "    ✅ Dependencies satisfied"
        else
            echo "    ❌ Dependencies missing"
        fi
        
        echo "  🔍 Configuration..."
        local config_file=$(find ~ -name "claude-desktop.json" -type f 2>/dev/null | head -1)
        if [ -n "$config_file" ] && validate_claude_desktop_config "$config_file"; then
            echo "    ✅ Configuration valid"
        else
            echo "    ❌ Configuration issues detected"
        fi
        
        echo ""
    done
}

# Diagnostics-only command
run_mcp_diagnostics() {
    local tools=("$@")
    
    if [ ${#tools[@]} -eq 0 ]; then
        echo "Usage: claude mcp-test diagnose <tool1> [tool2 ...]"
        return 1
    fi
    
    echo "🔧 MCP Diagnostics Framework"
    echo "============================"
    
    for tool in "${tools[@]}"; do
        echo "🔍 Diagnosing: $tool"
        diagnose_mcp_issues "$tool"
        echo ""
    done
}

# Automated fixes command
run_mcp_automated_fixes() {
    local tools=("$@")
    
    if [ ${#tools[@]} -eq 0 ]; then
        echo "Usage: claude mcp-test fix <tool1> [tool2 ...]"
        return 1
    fi
    
    echo "🔧 MCP Automated Fixes Framework"
    echo "================================"
    
    for tool in "${tools[@]}"; do
        echo "🔧 Attempting fixes for: $tool"
        attempt_automated_fixes "$tool"
        echo ""
    done
}

# Attempt automated fixes for common issues
attempt_automated_fixes() {
    local tool="$1"
    
    echo "  🔍 Identifying fixable issues..."
    
    # Check Node.js installation
    if ! command -v node &>/dev/null; then
        echo "  🔧 Installing Node.js..."
        if command -v brew &>/dev/null; then
            brew install node
        elif command -v apt-get &>/dev/null; then
            curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
            sudo apt-get install -y nodejs
        else
            echo "    ❌ Cannot auto-install Node.js. Please install manually."
        fi
    fi
    
    # Check npm global permissions
    if [ ! -d "$HOME/.npm-global" ] && command -v npm &>/dev/null; then
        echo "  🔧 Configuring npm global directory..."
        mkdir -p ~/.npm-global
        npm config set prefix '~/.npm-global'
        
        # Add to PATH if not already there
        if [[ ":$PATH:" != *":$HOME/.npm-global/bin:"* ]]; then
            echo 'export PATH=~/.npm-global/bin:$PATH' >> ~/.bashrc
            export PATH=~/.npm-global/bin:$PATH
        fi
    fi
    
    # Tool-specific fixes
    case "$tool" in
        "aws"|"mcp-aws")
            attempt_aws_fixes
            ;;
        "azure"|"mcp-azure")
            attempt_azure_fixes
            ;;
        "playwright"|"mcp-playwright")
            attempt_playwright_fixes
            ;;
        "github"|"mcp-github")
            attempt_github_fixes
            ;;
    esac
    
    echo "  ✅ Automated fixes attempted for $tool"
}

# AWS-specific automated fixes
attempt_aws_fixes() {
    echo "    🔧 AWS-specific fixes..."
    
    # Install AWS CLI if missing
    if ! command -v aws &>/dev/null; then
        echo "      Installing AWS CLI..."
        if command -v brew &>/dev/null; then
            brew install awscli
        elif command -v apt-get &>/dev/null; then
            sudo apt-get update
            sudo apt-get install -y awscli
        fi
    fi
    
    # Install MCP AWS package if missing
    if ! command -v mcp-aws &>/dev/null && command -v npm &>/dev/null; then
        echo "      Installing MCP AWS package..."
        npm install -g @mcp/aws
    fi
}

# Azure-specific automated fixes
attempt_azure_fixes() {
    echo "    🔧 Azure-specific fixes..."
    
    # Install Azure CLI if missing
    if ! command -v az &>/dev/null; then
        echo "      Installing Azure CLI..."
        if command -v brew &>/dev/null; then
            brew install azure-cli
        elif command -v apt-get &>/dev/null; then
            curl -sL https://aka.ms/InstallAzureCLIDeb | sudo bash
        fi
    fi
    
    # Install MCP Azure package if missing
    if ! command -v mcp-azure &>/dev/null && command -v npm &>/dev/null; then
        echo "      Installing MCP Azure package..."
        npm install -g @mcp/azure
    fi
}

# Playwright-specific automated fixes
attempt_playwright_fixes() {
    echo "    🔧 Playwright-specific fixes..."
    
    # Install Playwright if missing
    if ! command -v playwright &>/dev/null && command -v npm &>/dev/null; then
        echo "      Installing Playwright..."
        npm install -g playwright
        playwright install
    fi
    
    # Install system dependencies on Linux
    if [ "$(uname -s)" = "Linux" ] && command -v apt-get &>/dev/null; then
        echo "      Installing browser dependencies..."
        sudo apt-get install -y libnss3 libatk-bridge2.0-0 libgtk-3-0 libasound2
    fi
    
    # Install MCP Playwright package if missing
    if ! command -v mcp-playwright &>/dev/null && command -v npm &>/dev/null; then
        echo "      Installing MCP Playwright package..."
        npm install -g @mcp/playwright
    fi
}

# GitHub-specific automated fixes
attempt_github_fixes() {
    echo "    🔧 GitHub-specific fixes..."
    
    # Configure Git if not configured
    if ! git config user.name &>/dev/null; then
        echo "      Git user configuration needed. Please run:"
        echo "        git config --global user.name 'Your Name'"
        echo "        git config --global user.email 'your.email@example.com'"
    fi
    
    # Install GitHub CLI if missing
    if ! command -v gh &>/dev/null; then
        echo "      Installing GitHub CLI..."
        if command -v brew &>/dev/null; then
            brew install gh
        elif command -v apt-get &>/dev/null; then
            curl -fsSL https://cli.github.com/packages/githubcli-archive-keyring.gpg | sudo dd of=/usr/share/keyrings/githubcli-archive-keyring.gpg
            echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/githubcli-archive-keyring.gpg] https://cli.github.com/packages stable main" | sudo tee /etc/apt/sources.list.d/github-cli.list > /dev/null
            sudo apt update
            sudo apt install gh
        fi
    fi
    
    # Install MCP GitHub package if missing
    if ! command -v mcp-github &>/dev/null && command -v npm &>/dev/null; then
        echo "      Installing MCP GitHub package..."
        npm install -g @mcp/github
    fi
}

# Generate comprehensive test report
generate_mcp_test_report() {
    local tools=("$@")
    local report_file="/tmp/mcp_integration_test_report_$(date +%Y%m%d_%H%M%S).md"
    
    echo "📊 Generating MCP Integration Test Report..."
    
    {
        echo "# MCP Integration Test Report"
        echo ""
        echo "**Generated:** $(date)"
        echo "**System:** $(uname -a)"
        echo ""
        
        echo "## Executive Summary"
        echo ""
        echo "This report provides comprehensive testing results for MCP tool installations"
        echo "and their integration with Claude Desktop."
        echo ""
        
        echo "## Tested Tools"
        echo ""
        for tool in "${tools[@]}"; do
            echo "### $tool"
            echo ""
            
            # Run quick validation for each tool
            if validate_mcp_system_requirements &>/dev/null && validate_mcp_dependencies "$tool" &>/dev/null; then
                echo "- **Status:** ✅ Validated"
            else
                echo "- **Status:** ❌ Issues detected"
            fi
            
            # Check if tool command exists
            if command -v "mcp-$tool" &>/dev/null || command -v "$tool" &>/dev/null; then
                echo "- **Installation:** ✅ Installed"
            else
                echo "- **Installation:** ❌ Not found"
            fi
            
            # Check configuration
            local config_file=$(find ~ -name "claude-desktop.json" -type f 2>/dev/null | head -1)
            if [ -n "$config_file" ] && jq -e ".mcp.servers.\"$tool\"" "$config_file" &>/dev/null; then
                echo "- **Configuration:** ✅ Configured"
            else
                echo "- **Configuration:** ❌ Not configured"
            fi
            
            echo ""
        done
        
        echo "## System Environment"
        echo ""
        echo "- **Node.js:** $(node --version 2>/dev/null || echo 'Not installed')"
        echo "- **npm:** $(npm --version 2>/dev/null || echo 'Not installed')"
        echo "- **Python:** $(python3 --version 2>/dev/null || echo 'Not installed')"
        
        if command -v aws &>/dev/null; then
            echo "- **AWS CLI:** $(aws --version)"
        fi
        
        if command -v az &>/dev/null; then
            echo "- **Azure CLI:** $(az --version | head -1)"
        fi
        
        if command -v gh &>/dev/null; then
            echo "- **GitHub CLI:** $(gh --version | head -1)"
        fi
        
        echo ""
        echo "## Recommendations"
        echo ""
        echo "1. Review individual tool configurations in Claude Desktop"
        echo "2. Ensure all required authentication is completed"
        echo "3. Restart Claude Desktop after configuration changes"
        echo "4. Run functionality tests for critical workflows"
        echo ""
        
        echo "## Next Steps"
        echo ""
        echo "- Address any failed validations"
        echo "- Configure authentication for cloud services"
        echo "- Test MCP tools with actual Claude Desktop usage"
        echo "- Set up monitoring for MCP tool health"
        
    } > "$report_file"
    
    echo "📊 Report generated: $report_file"
    
    # Display summary
    echo ""
    echo "📋 Quick Summary:"
    echo "=================="
    for tool in "${tools[@]}"; do
        if command -v "mcp-$tool" &>/dev/null || command -v "$tool" &>/dev/null; then
            echo "✅ $tool: Installed"
        else
            echo "❌ $tool: Not installed"
        fi
    done
}

# Help information
show_mcp_testing_help() {
    cat << 'EOF'
MCP Integration Testing Command

USAGE:
  claude mcp-test <subcommand> [options] [tools...]

SUBCOMMANDS:
  test       Run comprehensive testing for MCP tools
  validate   Run pre-installation validation only
  diagnose   Run diagnostics and issue identification
  fix        Attempt automated fixes for common issues
  report     Generate detailed test report

TEST OPTIONS:
  --all                Test all common MCP tools
  --project-type TYPE  Test tools for project type (web, cloud-aws, cloud-azure, fullstack, backend, frontend)
  --diagnose-only      Run diagnostics without functionality tests
  --report            Generate comprehensive test report
  --verbose           Enable verbose output
  --fix-issues        Attempt automated fixes for detected issues

EXAMPLES:
  # Test single tool
  claude mcp-test test aws
  
  # Test multiple tools with auto-fix
  claude mcp-test test aws azure --fix-issues
  
  # Test all tools for fullstack project
  claude mcp-test test --project-type fullstack --verbose
  
  # Run diagnostics only
  claude mcp-test diagnose playwright
  
  # Generate report for specific tools
  claude mcp-test report aws github

SUPPORTED MCP TOOLS:
  aws         - Amazon Web Services MCP
  azure       - Microsoft Azure MCP  
  playwright  - Browser automation MCP
  github      - GitHub operations MCP
  filesystem  - File system operations MCP

For troubleshooting help, see: templates/shared/mcp-troubleshooting-guide.md
EOF
}

# Export main function
export -f mcp_integration_testing
export -f run_mcp_testing
export -f test_single_mcp_tool_comprehensive
export -f run_mcp_validation
export -f run_mcp_diagnostics
export -f run_mcp_automated_fixes
export -f attempt_automated_fixes
export -f generate_mcp_test_report
export -f show_mcp_testing_help

# Command aliases for easier access
alias mcp-test='mcp_integration_testing test'
alias mcp-validate='mcp_integration_testing validate'
alias mcp-diagnose='mcp_integration_testing diagnose'
alias mcp-fix='mcp_integration_testing fix'
alias mcp-report='mcp_integration_testing report'
```

---

## Integration with Claude Code Workflow

This command integrates seamlessly with the existing Claude Code ecosystem:

### Agent Integration
- Uses the **MCP Test Validator Agent** for core testing functionality
- Leverages the **Test Orchestrator Agent** for advanced test execution patterns
- Integrates with troubleshooting utilities from the shared components

### Command Structure
- Follows Claude Code command patterns and conventions
- Provides both individual tool testing and batch operations
- Supports project-type-based tool selection for common workflows

### Reporting and Diagnostics
- Generates detailed reports compatible with Claude Code reporting standards
- Provides actionable diagnostics and automated fix suggestions
- Integrates with existing logging and monitoring systems

## Usage Examples

### Development Workflow Integration

```bash
# Pre-deployment validation
claude mcp-test validate --all

# Full testing before production
claude mcp-test test --project-type fullstack --verbose --report

# Troubleshooting failing tools
claude mcp-test diagnose aws
claude mcp-test fix aws

# Continuous integration checks
claude mcp-test test aws github --fix-issues
```

### Project-Specific Testing

```bash
# Web development project
claude mcp-test test --project-type web

# Cloud-native application
claude mcp-test test --project-type cloud-aws --verbose

# Full-stack application with comprehensive testing
claude mcp-test test --project-type fullstack --report
```

This command provides a complete solution for MCP tool testing and validation, ensuring reliable functionality across all supported tools and integration points.