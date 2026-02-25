# Command: doc-readme-create
Generate comprehensive README with badges, setup instructions, and examples

## Usage
```
/doc-readme-create [project-path]
```

## Description
Creates professional README.md files that follow best practices from top open source projects. Automatically detects project type, generates appropriate badges, and includes all essential sections for project onboarding.

## Implementation

### Multi-Phase README Generation

#### Phase 1: Project Analysis
```xml
<instructions>
Analyze the project to determine:
</instructions>

<analysis_targets>
- Programming language and framework
- Project type (library, application, tool, etc.)
- Key dependencies and requirements
- Available scripts and commands
- License type
- Test coverage metrics
- CI/CD configuration
</analysis_targets>

<files_to_check>
- package.json, composer.json, requirements.txt, go.mod, Cargo.toml
- LICENSE file
- CI/CD configs (.github/workflows, .gitlab-ci.yml, etc.)
- Test directories and coverage reports
- Docker/deployment configurations
</files_to_check>

<output>
Project metadata in structured format
</output>
```

#### Phase 2: README Structure Generation
```xml
<instructions>
Create comprehensive README structure based on project analysis
</instructions>

<context>
Project name: {{project_name}}
Project type: {{project_type}}
Language: {{primary_language}}
Audience: {{target_audience|developers}}
</context>

<sections>
1. Project Title with Logo/Banner
2. Badge Section
   - Build status
   - Coverage
   - Version
   - License
   - Downloads/Stars
3. One-line Description
4. Table of Contents
5. Key Features (bullet points)
6. Screenshots/Demo (if applicable)
7. Prerequisites
8. Installation
9. Quick Start
10. Usage Examples
11. API Documentation (link)
12. Configuration
13. Development Setup
14. Testing
15. Deployment
16. Contributing Guidelines
17. Changelog (link)
18. License
19. Acknowledgments
20. Support/Contact
</sections>

<output_format>
Markdown with proper formatting, syntax highlighting, and navigation
</output_format>
```

#### Phase 3: Content Generation
```xml
<instructions>
Generate detailed content for each README section
</instructions>

<section>{{current_section}}</section>

<requirements>
- Clear, concise writing
- Practical examples that work
- Platform-specific instructions (Windows/Mac/Linux)
- Common troubleshooting tips
- Links to detailed documentation
</requirements>

<style_guide>
- Use emoji sparingly for visual markers
- Consistent heading levels
- Code blocks with language hints
- Tables for structured data
- Collapsible sections for optional content
</style_guide>
```

### Badge Generation Templates

```markdown
<!-- Build Status -->
![Build Status](https://github.com/{{username}}/{{repo}}/workflows/CI/badge.svg)
![Tests](https://github.com/{{username}}/{{repo}}/workflows/tests/badge.svg)

<!-- Coverage -->
![Coverage](https://codecov.io/gh/{{username}}/{{repo}}/branch/main/graph/badge.svg)
![Coverage](https://coveralls.io/repos/github/{{username}}/{{repo}}/badge.svg?branch=main)

<!-- Version -->
![npm version](https://badge.fury.io/js/{{package}}.svg)
![PyPI version](https://badge.fury.io/py/{{package}}.svg)
![Maven Central](https://maven-badges.herokuapp.com/maven-central/{{group}}/{{artifact}}/badge.svg)

<!-- License -->
![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)
![License: Apache 2.0](https://img.shields.io/badge/License-Apache_2.0-blue.svg)

<!-- Downloads/Popularity -->
![npm downloads](https://img.shields.io/npm/dm/{{package}}.svg)
![GitHub stars](https://img.shields.io/github/stars/{{username}}/{{repo}}.svg)

<!-- Quality -->
![Maintainability](https://api.codeclimate.com/v1/badges/{{badge_id}}/maintainability)
![Security](https://snyk.io/test/github/{{username}}/{{repo}}/badge.svg)
```

### Installation Section Templates

#### Node.js/npm
```markdown
## Installation

### Prerequisites
- Node.js >= 14.0.0
- npm >= 6.0.0 or yarn >= 1.22.0

### Install via npm
\`\`\`bash
npm install {{package_name}}
\`\`\`

### Install via yarn
\`\`\`bash
yarn add {{package_name}}
\`\`\`

### Development Installation
\`\`\`bash
# Clone the repository
git clone https://github.com/{{username}}/{{repo}}.git
cd {{repo}}

# Install dependencies
npm install

# Run development server
npm run dev
\`\`\`
```

#### Python
```markdown
## Installation

### Prerequisites
- Python >= 3.8
- pip >= 20.0

### Install via pip
\`\`\`bash
pip install {{package_name}}
\`\`\`

### Install from source
\`\`\`bash
# Clone the repository
git clone https://github.com/{{username}}/{{repo}}.git
cd {{repo}}

# Create virtual environment
python -m venv venv
source venv/bin/activate  # On Windows: venv\\Scripts\\activate

# Install in development mode
pip install -e .
\`\`\`
```

### Quick Start Template
```markdown
## Quick Start

Get up and running in less than 5 minutes:

\`\`\`{{language}}
{{minimal_working_example}}
\`\`\`

### Step-by-Step Guide

1. **Install the package**
   \`\`\`bash
   {{install_command}}
   \`\`\`

2. **Import and initialize**
   \`\`\`{{language}}
   {{import_statement}}
   {{initialization_code}}
   \`\`\`

3. **Basic usage**
   \`\`\`{{language}}
   {{basic_usage_example}}
   \`\`\`

4. **Run the example**
   \`\`\`bash
   {{run_command}}
   \`\`\`

Expected output:
\`\`\`
{{expected_output}}
\`\`\`
```

### Feature Section Template
```markdown
## ✨ Features

- 🚀 **High Performance** - {{performance_description}}
- 🔧 **Easy Configuration** - {{configuration_description}}
- 📦 **Zero Dependencies** - {{dependencies_description}}
- 🔌 **Plugin System** - {{plugin_description}}
- 📚 **Comprehensive Documentation** - {{docs_description}}
- 🧪 **Well Tested** - {{testing_description}}
- 🌍 **I18n Support** - {{i18n_description}}
- 📱 **Mobile Friendly** - {{mobile_description}}
```

### Contributing Section Template
```markdown
## Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

### Quick Contribution Steps

1. Fork the repository
2. Create your feature branch (\`git checkout -b feature/AmazingFeature\`)
3. Commit your changes (\`git commit -m 'Add some AmazingFeature'\`)
4. Push to the branch (\`git push origin feature/AmazingFeature\`)
5. Open a Pull Request

### Development Setup

\`\`\`bash
# Clone your fork
git clone https://github.com/your-username/{{repo}}.git
cd {{repo}}

# Install dependencies
{{install_dev_deps}}

# Run tests
{{test_command}}

# Run linter
{{lint_command}}
\`\`\`
```

## Advanced Features

### Auto-Detection Capabilities
- Language detection from file extensions and config files
- Framework detection (React, Vue, Django, Express, etc.)
- License detection from LICENSE file
- CI/CD platform detection
- Package manager detection

### README Variations

#### Library README
Focus on API documentation, integration examples, and version compatibility

#### Application README  
Focus on deployment, configuration, and user guides

#### CLI Tool README
Focus on command reference, examples, and shell completion setup

## Output Example

**File: `docs/README.md`**

```markdown
# 🚀 Awesome Project

![Build Status](https://github.com/user/repo/workflows/CI/badge.svg)
![Coverage](https://codecov.io/gh/user/repo/branch/main/graph/badge.svg)
![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)

> One-line description that captures the essence of your project

## 📋 Table of Contents

- [Features](#features)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Usage](#usage)
- [API Documentation](api/README.md)
- [Contributing](contributing/README.md)
- [License](#license)

## ✨ Features

- 🚀 Lightning fast performance
- 🔧 Zero configuration required
- 📦 Lightweight with no dependencies
- 🧪 100% test coverage

[... rest of README content ...]
```

## Quality Checks
✅ All essential sections included
✅ Installation instructions work on all platforms
✅ Code examples are tested and functional
✅ Links are valid and point to correct resources
✅ Badges display correct project status