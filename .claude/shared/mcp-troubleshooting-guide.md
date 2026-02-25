# MCP Troubleshooting Guide

Comprehensive troubleshooting guide for common MCP (Model Context Protocol) installation and configuration issues, with automated diagnostic solutions and step-by-step fix procedures.

## Quick Issue Resolution Matrix

| Issue Category | Symptoms | Quick Fix | Full Solution |
|---|---|---|---|
| **Installation Failures** | Command not found, npm errors | `npm install -g @mcp/tool` | See [Installation Issues](#installation-issues) |
| **Configuration Errors** | JSON syntax error, tool not found | Check JSON with `jq` | See [Configuration Issues](#configuration-issues) |
| **Authentication Problems** | 401/403 errors, access denied | Re-authenticate with service | See [Authentication Issues](#authentication-issues) |
| **Permission Issues** | File access denied, command blocked | Check file permissions | See [Permission Issues](#permission-issues) |
| **Network/Connectivity** | Timeout, connection refused | Check firewall/proxy | See [Network Issues](#network-issues) |

## Installation Issues

### Node.js and npm Problems

#### Issue: "command not found: node"
```bash
# Diagnosis
which node
echo $PATH

# Fix - Install Node.js (macOS)
brew install node

# Fix - Install Node.js (Ubuntu/Debian)
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs

# Fix - Install Node.js (CentOS/RHEL)
curl -fsSL https://rpm.nodesource.com/setup_18.x | sudo bash -
sudo yum install -y nodejs

# Verify
node --version
npm --version
```

#### Issue: "npm install fails with permission errors"
```bash
# Diagnosis
npm config get prefix
ls -la ~/.npm

# Fix - Configure npm for global packages without sudo
mkdir ~/.npm-global
npm config set prefix '~/.npm-global'
echo 'export PATH=~/.npm-global/bin:$PATH' >> ~/.bashrc
source ~/.bashrc

# Alternative - Use npx for one-time installs
npx @mcp/tool-name
```

#### Issue: "Package not found or version conflicts"
```bash
# Diagnosis
npm list -g --depth=0
npm outdated -g

# Fix - Clean npm cache and reinstall
npm cache clean --force
npm install -g @mcp/tool-name@latest

# Fix - Use specific Node.js version with nvm
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash
nvm install 18
nvm use 18
npm install -g @mcp/tool-name
```

### Python and pip Issues

#### Issue: "python3: command not found"
```bash
# Diagnosis
which python3
python3 --version

# Fix - Install Python (macOS)
brew install python3

# Fix - Install Python (Ubuntu/Debian)
sudo apt update
sudo apt install python3 python3-pip

# Fix - Install Python (CentOS/RHEL)
sudo yum install python3 python3-pip

# Verify
python3 --version
pip3 --version
```

#### Issue: "pip install fails with SSL errors"
```bash
# Diagnosis
pip3 --version
curl -I https://pypi.org

# Fix - Upgrade pip and certificates
python3 -m pip install --upgrade pip
python3 -m pip install --upgrade certifi

# Fix - Use trusted hosts (temporary)
pip3 install --trusted-host pypi.org --trusted-host pypi.python.org package-name

# Fix - Check system certificates (macOS)
/Applications/Python\ 3.x/Install\ Certificates.command
```

## Configuration Issues

### Claude Desktop Configuration

#### Issue: "Invalid JSON in claude-desktop.json"
```bash
# Diagnosis
# Find config file location
find ~ -name "claude-desktop.json" 2>/dev/null

# Common locations
CONFIG_FILE="$HOME/.config/claude/claude-desktop.json"  # Linux
CONFIG_FILE="$HOME/Library/Application Support/claude/claude-desktop.json"  # macOS
CONFIG_FILE="$HOME/AppData/Roaming/claude/claude-desktop.json"  # Windows

# Validate JSON syntax
jq empty "$CONFIG_FILE"

# Fix - Show JSON errors with line numbers
jq . "$CONFIG_FILE" || echo "JSON syntax error detected"

# Fix - Basic JSON structure template
cat > "$CONFIG_FILE" << 'EOF'
{
  "mcp": {
    "servers": {}
  }
}
EOF

# Verify fix
jq . "$CONFIG_FILE" && echo "JSON syntax is now valid"
```

#### Issue: "MCP server not found in configuration"
```bash
# Diagnosis
jq '.mcp.servers | keys' "$CONFIG_FILE"

# Fix - Add MCP server to configuration
# Example for AWS MCP
jq '.mcp.servers["aws"] = {
  "command": "mcp-aws",
  "args": ["--region", "us-east-1"]
}' "$CONFIG_FILE" > "$CONFIG_FILE.tmp" && mv "$CONFIG_FILE.tmp" "$CONFIG_FILE"

# Fix - Add MCP server with proper structure
cat > "$CONFIG_FILE" << 'EOF'
{
  "mcp": {
    "servers": {
      "aws": {
        "command": "mcp-aws",
        "args": ["--region", "us-east-1"]
      },
      "github": {
        "command": "mcp-github", 
        "args": []
      }
    }
  }
}
EOF

# Verify
jq '.mcp.servers' "$CONFIG_FILE"
```

#### Issue: "MCP command path not found"
```bash
# Diagnosis
CONFIG_FILE="$HOME/.config/claude/claude-desktop.json"
COMMAND=$(jq -r '.mcp.servers."tool-name".command' "$CONFIG_FILE")
which "$COMMAND"

# Fix - Update command path to full path
FULL_PATH=$(which "$COMMAND")
jq ".mcp.servers.\"tool-name\".command = \"$FULL_PATH\"" "$CONFIG_FILE" > "$CONFIG_FILE.tmp"
mv "$CONFIG_FILE.tmp" "$CONFIG_FILE"

# Fix - Add to PATH if needed
echo "export PATH=\$PATH:$(dirname "$FULL_PATH")" >> ~/.bashrc
source ~/.bashrc
```

### File Permissions

#### Issue: "Permission denied accessing configuration"
```bash
# Diagnosis
ls -la "$CONFIG_FILE"
whoami
groups

# Fix - Set proper file permissions
chmod 644 "$CONFIG_FILE"
# If directory doesn't exist
mkdir -p "$(dirname "$CONFIG_FILE")"
chmod 755 "$(dirname "$CONFIG_FILE")"

# Fix - Change ownership if needed (be careful)
# Only if file is owned by root or wrong user
sudo chown $(whoami):$(id -gn) "$CONFIG_FILE"
```

## Authentication Issues

### AWS MCP Authentication

#### Issue: "AWS credentials not configured"
```bash
# Diagnosis
aws sts get-caller-identity
ls -la ~/.aws/

# Fix - Configure AWS credentials
aws configure
# Enter: Access Key ID, Secret Access Key, Region, Output format

# Fix - Using AWS CLI profiles
aws configure --profile mcp-profile
aws configure set profile.mcp-profile.region us-east-1

# Fix - Environment variables method
export AWS_ACCESS_KEY_ID="your-access-key"
export AWS_SECRET_ACCESS_KEY="your-secret-key"
export AWS_DEFAULT_REGION="us-east-1"

# Fix - Using IAM roles (EC2)
# Ensure EC2 instance has proper IAM role attached

# Verify
aws sts get-caller-identity --profile mcp-profile
```

#### Issue: "AWS region not set"
```bash
# Diagnosis
aws configure get region
echo $AWS_DEFAULT_REGION

# Fix - Set default region
aws configure set region us-east-1

# Fix - Set region for specific profile
aws configure set region us-east-1 --profile mcp-profile

# Fix - Environment variable
export AWS_DEFAULT_REGION="us-east-1"
echo 'export AWS_DEFAULT_REGION="us-east-1"' >> ~/.bashrc

# Verify
aws configure get region
```

### Azure MCP Authentication

#### Issue: "Azure CLI not authenticated"
```bash
# Diagnosis
az account show
az account list

# Fix - Login interactively
az login

# Fix - Login with service principal
az login --service-principal \
  --username "app-id" \
  --password "password" \
  --tenant "tenant-id"

# Fix - Login with device code (for remote systems)
az login --use-device-code

# Fix - Set subscription
az account set --subscription "subscription-name-or-id"

# Verify
az account show
```

### GitHub MCP Authentication

#### Issue: "GitHub CLI not authenticated"
```bash
# Diagnosis
gh auth status
git config --get user.name
git config --get user.email

# Fix - GitHub CLI authentication
gh auth login
# Follow prompts to authenticate via web browser or token

# Fix - Git configuration for GitHub
git config --global user.name "Your Name"
git config --global user.email "your.email@example.com"

# Fix - SSH key setup for GitHub
ssh-keygen -t ed25519 -C "your.email@example.com"
eval "$(ssh-agent -s)"
ssh-add ~/.ssh/id_ed25519
# Add public key to GitHub account

# Fix - Personal Access Token method
# Create token at https://github.com/settings/tokens
gh auth login --with-token < token.txt

# Verify
gh auth status
ssh -T git@github.com
```

## Network and Connectivity Issues

### Firewall and Proxy Issues

#### Issue: "Connection timeout or refused"
```bash
# Diagnosis
curl -I https://api.github.com
curl -I https://aws.amazon.com
ping 8.8.8.8

# Fix - Check proxy settings
echo $HTTP_PROXY
echo $HTTPS_PROXY
echo $NO_PROXY

# Fix - Configure npm proxy
npm config set proxy http://proxy.company.com:8080
npm config set https-proxy http://proxy.company.com:8080

# Fix - Configure git proxy
git config --global http.proxy http://proxy.company.com:8080
git config --global https.proxy http://proxy.company.com:8080

# Fix - Bypass proxy for specific domains
npm config set registry https://registry.npmjs.org/
git config --global http.https://github.com.proxy ""
```

#### Issue: "SSL certificate errors"
```bash
# Diagnosis
curl -v https://api.github.com
openssl version
curl --version

# Fix - Update certificates (macOS)
brew install ca-certificates
/Applications/Python\ 3.x/Install\ Certificates.command

# Fix - Update certificates (Ubuntu)
sudo apt-get update && sudo apt-get install ca-certificates

# Fix - Configure npm to use system certificates
npm config set cafile /etc/ssl/certs/ca-certificates.crt

# Fix - Temporary bypass (not recommended for production)
npm config set strict-ssl false
export NODE_TLS_REJECT_UNAUTHORIZED=0
```

## Platform-Specific Issues

### macOS Issues

#### Issue: "Command Line Tools not installed"
```bash
# Diagnosis
xcode-select --print-path
gcc --version

# Fix - Install Xcode Command Line Tools
xcode-select --install

# Fix - Reset Xcode Command Line Tools
sudo xcode-select --reset
xcode-select --install

# Verify
xcode-select --print-path
```

#### Issue: "Homebrew related problems"
```bash
# Diagnosis
brew doctor
brew --version

# Fix - Install Homebrew
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"

# Fix - Update Homebrew
brew update
brew upgrade

# Fix - Fix permissions
sudo chown -R $(whoami) /usr/local/var/homebrew
```

### Linux Issues

#### Issue: "Missing system dependencies"
```bash
# Ubuntu/Debian - Install common dependencies
sudo apt update
sudo apt install -y build-essential curl wget git

# CentOS/RHEL - Install development tools
sudo yum groupinstall "Development Tools"
sudo yum install curl wget git

# Fix - Install Node.js build dependencies
sudo apt install -y python3 make g++  # Ubuntu
sudo yum install python3 make gcc-c++ # CentOS
```

### Windows Issues

#### Issue: "PowerShell execution policy restrictions"
```powershell
# Diagnosis
Get-ExecutionPolicy

# Fix - Set execution policy for current user
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser

# Fix - Temporary bypass
PowerShell -ExecutionPolicy Bypass -File script.ps1
```

#### Issue: "Windows Subsystem for Linux (WSL) issues"
```bash
# Enable WSL
wsl --install

# Update WSL
wsl --update

# Set default WSL version
wsl --set-default-version 2

# Install Ubuntu
wsl --install -d Ubuntu
```

## MCP Tool-Specific Troubleshooting

### Playwright MCP Issues

#### Issue: "Browsers not installed"
```bash
# Diagnosis
playwright --version
ls ~/.cache/ms-playwright/

# Fix - Install Playwright browsers
playwright install

# Fix - Install specific browser
playwright install chromium

# Fix - Install system dependencies (Linux)
sudo apt install -y \
  libnss3 libatk-bridge2.0-0 libdrm2 libxkbcommon0 \
  libgtk-3-0 libx11-xcb1 libxcomposite1 libxdamage1 \
  libxext6 libxfixes3 libxrandr2 libxss1 libxtst6

# Verify
playwright --version
```

#### Issue: "Headless browser failures"
```bash
# Diagnosis - Test basic functionality
node -e "
const { chromium } = require('playwright');
(async () => {
  const browser = await chromium.launch();
  console.log('Browser launched successfully');
  await browser.close();
})().catch(console.error);
"

# Fix - Set display for headless environment
export DISPLAY=:99
Xvfb :99 -screen 0 1024x768x24 &

# Fix - Use different browser
node -e "
const { firefox } = require('playwright');
(async () => {
  const browser = await firefox.launch();
  await browser.close();
})();
"
```

### Filesystem MCP Issues

#### Issue: "File access permissions"
```bash
# Diagnosis
ls -la /path/to/directory
whoami
groups

# Fix - Change file permissions
chmod -R 755 /path/to/directory

# Fix - Change ownership
sudo chown -R $(whoami):$(id -gn) /path/to/directory

# Fix - Add user to appropriate group
sudo usermod -a -G group-name $(whoami)
# Logout and login for group changes to take effect
```

## Automated Diagnostic Script

```bash
#!/bin/bash
# mcp-auto-diagnostic.sh - Automated MCP issue diagnosis

echo "🔧 MCP Automated Diagnostic Tool"
echo "==============================="

# Check system prerequisites
check_system_prerequisites() {
    echo "📋 Checking System Prerequisites..."
    
    # Node.js
    if command -v node &>/dev/null; then
        echo "✅ Node.js: $(node --version)"
    else
        echo "❌ Node.js not found"
        echo "🔧 Fix: Install Node.js from https://nodejs.org"
    fi
    
    # npm
    if command -v npm &>/dev/null; then
        echo "✅ npm: $(npm --version)"
    else
        echo "❌ npm not found"
    fi
    
    # Python
    if command -v python3 &>/dev/null; then
        echo "✅ Python: $(python3 --version)"
    else
        echo "⚠️  Python3 not found (some MCP tools may not work)"
    fi
}

# Check Claude Desktop configuration
check_claude_config() {
    echo ""
    echo "📋 Checking Claude Desktop Configuration..."
    
    local config_paths=(
        "$HOME/.config/claude/claude-desktop.json"
        "$HOME/Library/Application Support/claude/claude-desktop.json"
        "$HOME/AppData/Roaming/claude/claude-desktop.json"
    )
    
    local config_found=false
    for config_path in "${config_paths[@]}"; do
        if [ -f "$config_path" ]; then
            echo "✅ Config found: $config_path"
            config_found=true
            
            # Check JSON validity
            if jq empty "$config_path" 2>/dev/null; then
                echo "✅ JSON syntax valid"
            else
                echo "❌ JSON syntax invalid"
                echo "🔧 Fix: Check JSON syntax with 'jq . \"$config_path\"'"
            fi
            
            # Check MCP section
            if jq -e '.mcp' "$config_path" >/dev/null 2>&1; then
                local server_count=$(jq '.mcp.servers | length' "$config_path")
                echo "✅ MCP section exists ($server_count servers configured)"
            else
                echo "⚠️  No MCP section found"
            fi
            break
        fi
    done
    
    if [ "$config_found" = false ]; then
        echo "❌ Claude Desktop configuration not found"
        echo "🔧 Fix: Install Claude Desktop application"
    fi
}

# Check MCP tool installations
check_mcp_tools() {
    echo ""
    echo "📋 Checking MCP Tool Installations..."
    
    local tools=("mcp-aws" "mcp-azure" "mcp-github" "mcp-playwright")
    for tool in "${tools[@]}"; do
        if command -v "$tool" &>/dev/null; then
            echo "✅ $tool installed"
        else
            echo "⚠️  $tool not found"
            echo "🔧 Install: npm install -g $tool"
        fi
    done
}

# Check service authentications
check_authentications() {
    echo ""
    echo "📋 Checking Service Authentications..."
    
    # AWS
    if command -v aws &>/dev/null; then
        if aws sts get-caller-identity &>/dev/null; then
            echo "✅ AWS CLI authenticated"
        else
            echo "❌ AWS CLI not authenticated"
            echo "🔧 Fix: aws configure"
        fi
    fi
    
    # Azure
    if command -v az &>/dev/null; then
        if az account show &>/dev/null; then
            echo "✅ Azure CLI authenticated"
        else
            echo "❌ Azure CLI not authenticated"
            echo "🔧 Fix: az login"
        fi
    fi
    
    # GitHub
    if command -v gh &>/dev/null; then
        if gh auth status &>/dev/null; then
            echo "✅ GitHub CLI authenticated"
        else
            echo "❌ GitHub CLI not authenticated"
            echo "🔧 Fix: gh auth login"
        fi
    fi
}

# Run all checks
main() {
    check_system_prerequisites
    check_claude_config
    check_mcp_tools
    check_authentications
    
    echo ""
    echo "🎯 Diagnostic Complete!"
    echo "Review the output above and apply suggested fixes."
    echo "For detailed troubleshooting, see: mcp-troubleshooting-guide.md"
}

main "$@"
```

## Recovery Procedures

### Complete MCP Reset

```bash
#!/bin/bash
# complete-mcp-reset.sh - Reset all MCP configurations and installations

echo "🔄 Complete MCP Reset Procedure"
echo "==============================="
read -p "This will remove all MCP configurations and reinstall. Continue? (y/N): " confirm

if [[ $confirm =~ ^[Yy]$ ]]; then
    # Backup existing configuration
    CONFIG_FILE="$HOME/.config/claude/claude-desktop.json"
    if [ -f "$CONFIG_FILE" ]; then
        cp "$CONFIG_FILE" "$CONFIG_FILE.backup.$(date +%Y%m%d_%H%M%S)"
        echo "✅ Configuration backed up"
    fi
    
    # Remove global MCP packages
    npm uninstall -g mcp-aws mcp-azure mcp-github mcp-playwright mcp-filesystem
    
    # Clear npm cache
    npm cache clean --force
    
    # Reinstall MCP packages
    npm install -g @mcp/aws @mcp/azure @mcp/github @mcp/playwright @mcp/filesystem
    
    # Reset configuration
    cat > "$CONFIG_FILE" << 'EOF'
{
  "mcp": {
    "servers": {}
  }
}
EOF
    
    echo "✅ MCP reset complete"
    echo "🔧 Next: Reconfigure your MCP tools manually"
else
    echo "❌ Reset cancelled"
fi
```

---

## Summary

This troubleshooting guide provides comprehensive solutions for common MCP installation and configuration issues, including:

- **System-level fixes** for Node.js, Python, and package manager issues
- **Configuration solutions** for Claude Desktop JSON syntax and structure problems  
- **Authentication procedures** for AWS, Azure, and GitHub services
- **Platform-specific solutions** for macOS, Linux, and Windows
- **Tool-specific troubleshooting** for Playwright, filesystem access, and other MCP tools
- **Automated diagnostic scripts** for quick issue identification
- **Recovery procedures** for complete reset scenarios

The guide is designed to work alongside the MCP Test Validator Agent to provide both proactive testing and reactive troubleshooting capabilities.