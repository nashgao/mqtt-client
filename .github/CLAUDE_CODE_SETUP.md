# 🤖 Claude Code GitHub Actions Setup

This repository includes workflows for integrating Claude Code into your CI/CD pipeline.

## 📋 Prerequisites

1. **Claude API Key**: Get your API key from [Anthropic Console](https://console.anthropic.com/)
2. **GitHub Secrets**: Add your API key to repository secrets

## 🔧 Setup Instructions

### Step 1: Add Claude API Key to GitHub Secrets

1. Go to your repository on GitHub
2. Navigate to Settings → Secrets and variables → Actions
3. Click "New repository secret"
4. Add:
   - Name: `CLAUDE_API_KEY`
   - Value: Your Claude API key

### Step 2: Enable Workflows

The repository includes three workflow templates:

1. **ai-code-review.yml** - Basic code review checklist (no API needed)
2. **claude-code-review.yml** - Claude-powered PR reviews (requires API)
3. **claude-code-comprehensive.yml** - Full analysis suite (requires API)

### Step 3: Configure Workflows

#### For Basic AI Review (No API Required)
```yaml
# Already configured and ready to use
# Provides automated checklists and review templates
```

#### For Claude Code Integration
```yaml
# In .github/workflows/claude-code-review.yml
# Update the model version if needed:
model: claude-3-opus-20240229  # or latest version
```

## 🚀 Available Features

### 1. **Pull Request Reviews**
- Automated code review on every PR
- Security vulnerability detection
- Performance suggestions
- Best practices enforcement

### 2. **Security Scanning**
- Dependency vulnerability checks
- Code security analysis
- OWASP compliance checks

### 3. **Code Improvements**
- Refactoring suggestions
- Performance optimizations
- Documentation generation

### 4. **Test Generation**
- Automatic test case creation
- Coverage improvement suggestions
- Edge case identification

## 📊 Workflow Triggers

| Workflow | Trigger | Purpose |
|----------|---------|---------|
| AI Code Review | Pull Request | Basic review checklist |
| Claude Review | Pull Request | AI-powered analysis |
| Claude Comprehensive | Push/Schedule | Full codebase analysis |

## 🔍 Example Usage

### Manual Trigger
```bash
gh workflow run "Claude Code Comprehensive Analysis" \
  -f analysis_type=security
```

### PR Comment Commands (future feature)
```
/claude review        # Trigger code review
/claude security      # Run security scan
/claude suggest       # Get improvement suggestions
/claude test          # Generate tests
```

## ⚙️ Configuration Options

### Basic Configuration
```yaml
analysis-config: |
  {
    "review": {
      "enabled": true,
      "focus_areas": ["security", "performance"]
    }
  }
```

### Advanced Configuration
```yaml
analysis-config: |
  {
    "review": {
      "enabled": true,
      "focus_areas": ["security", "performance", "maintainability"],
      "severity_threshold": "medium",
      "auto_approve_safe": false
    },
    "security": {
      "enabled": true,
      "scan_dependencies": true,
      "block_on_critical": true
    }
  }
```

## 🛡️ Security Best Practices

1. **Never commit API keys** - Always use GitHub Secrets
2. **Limit permissions** - Use minimal required permissions
3. **Review suggestions** - Don't auto-merge Claude suggestions
4. **Rotate keys** - Regularly update your API keys

## 🔗 Resources

- [Claude API Documentation](https://docs.anthropic.com/claude/reference/getting-started-with-the-api)
- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [Anthropic Console](https://console.anthropic.com/)

## 📝 Notes

- **Rate Limits**: Be aware of API rate limits
- **Costs**: Claude API usage may incur costs
- **Privacy**: Code is sent to Anthropic for analysis

## 🚧 Coming Soon

- [ ] Inline PR suggestions
- [ ] Automatic fix commits
- [ ] Custom rule definitions
- [ ] Team-specific configurations
- [ ] Slack/Discord notifications

---

For questions or issues, please open a GitHub issue or contact the maintainers.