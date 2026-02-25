# Command: doc-getting-started
Generate step-by-step onboarding guides that get users productive in 15 minutes

## Usage
```
/doc-getting-started [project-path]
```

## Description
Creates comprehensive getting-started documentation in the `docs/getting-started/` directory that follows progressive disclosure principles. Based on successful patterns from React, Vue.js, FastAPI, and other leading frameworks.

## Implementation

### Three-Phase Getting Started Generation

#### Phase 1: User Journey Analysis
```xml
<instructions>
Analyze the project to identify the optimal learning path
</instructions>

<analysis_targets>
- Core functionality that delivers immediate value
- Minimal setup requirements
- Common use cases and workflows
- Potential stumbling blocks
- Success milestones
</analysis_targets>

<user_personas>
- Complete beginner to the technology
- Experienced developer new to this tool
- User evaluating the project
- Contributor wanting to help
</user_personas>

<output>
Structured learning path with time estimates
</output>
```

#### Phase 2: Progressive Disclosure Structure
```xml
<instructions>
Create getting-started guide with progressive complexity
</instructions>

<structure>
1. What You'll Build (1 minute)
   - Visual preview or description
   - End result demonstration
   
2. Prerequisites (2 minutes)
   - Required software with versions
   - System requirements
   - Knowledge prerequisites
   
3. Installation (3 minutes)
   - Multiple installation methods
   - Platform-specific instructions
   - Verification steps
   
4. Hello World (5 minutes)
   - Minimal working example
   - Explanation of each line
   - How to run and verify
   
5. Core Concepts (4 minutes)
   - 3-5 essential concepts
   - Visual diagrams if helpful
   - Relates to Hello World example
   
6. Building Your First Real Feature (10 minutes)
   - Practical, useful example
   - Step-by-step with checkpoints
   - Common variations
   
7. Next Steps
   - Links to tutorials
   - Common patterns
   - Community resources
</structure>

<time_budget>
Total: 15 minutes to first success
Each section has explicit time estimate
</time_budget>
```

#### Phase 3: Content Generation with Examples
```xml
<instructions>
Generate detailed content for each section
</instructions>

<writing_style>
- Conversational and encouraging
- No assumptions about prior knowledge
- Explain the "why" not just the "how"
- Celebrate small wins
</writing_style>

<code_examples>
- Start ultra-simple
- Each example builds on the previous
- Include expected output
- Highlight what changed and why
</code_examples>

<troubleshooting>
- Common errors and solutions
- Platform-specific issues
- "If you see this..." sections
- Debug tips
</troubleshooting>
```

### Section Templates

#### What You'll Build Section
```markdown
# Getting Started with {{Project Name}}

## What You'll Build (1 minute)

In the next 15 minutes, you'll create {{brief_description}}. Here's what it will look like when you're done:

{{screenshot_or_ascii_art}}

This guide will teach you:
- ✅ How to {{core_skill_1}}
- ✅ How to {{core_skill_2}}
- ✅ How to {{core_skill_3}}

No prior experience with {{technology}} required!
```

#### Prerequisites Section
```markdown
## Prerequisites (2 minutes)

Before we begin, make sure you have:

### Required Software
- **{{software_1}}** (version {{min_version}} or higher)
  ```bash
  # Check your version
  {{version_check_command}}
  ```
  [Download {{software_1}}]({{download_link}})

- **{{software_2}}** (version {{min_version}} or higher)
  ```bash
  # Check your version
  {{version_check_command}}
  ```
  [Download {{software_2}}]({{download_link}})

### System Requirements
- Operating System: Windows 10+, macOS 10.14+, or Linux
- RAM: 4GB minimum (8GB recommended)
- Disk Space: {{required_space}}

### Knowledge Requirements
- Basic command line familiarity
- Text editor usage (we recommend {{editor}})
- {{other_knowledge}} (optional but helpful)

💡 **Tip**: If you're missing any prerequisites, our [setup guide](setup.md) has detailed installation instructions.
```

#### Installation Section
```markdown
## Installation (3 minutes)

Choose your preferred installation method:

### Option 1: Quick Install (Recommended)
```bash
# Install globally via package manager
{{quick_install_command}}

# Verify installation
{{verify_command}}
```

You should see: `{{expected_output}}`

### Option 2: Install from Source
```bash
# Clone the repository
git clone {{repo_url}}
cd {{project_name}}

# Install dependencies
{{install_deps_command}}

# Run setup
{{setup_command}}
```

### Option 3: Docker (No installation needed)
```bash
# Pull and run the Docker image
docker run -it {{docker_image}} 

# Everything is pre-configured!
```

### ✅ Verification

Run this command to confirm everything is working:
```bash
{{final_verification_command}}
```

Expected output:
```
{{expected_verification_output}}
```

🎉 **Success!** You're ready to create your first {{project_type}}!
```

#### Hello World Section
```markdown
## Your First {{Project Type}} (5 minutes)

Let's create something simple to understand the basics.

### Step 1: Create a new project
```bash
# Create a project directory
mkdir my-first-{{project}}
cd my-first-{{project}}

# Initialize the project
{{init_command}}
```

### Step 2: Create your first file
Create a file called `{{filename}}` with the following content:

```{{language}}
{{hello_world_code}}
```

**What's happening here?**
- Line 1: {{line_1_explanation}}
- Line 2: {{line_2_explanation}}
- Line 3: {{line_3_explanation}}

### Step 3: Run your code
```bash
{{run_command}}
```

You should see:
```
{{expected_output}}
```

🎊 **Congratulations!** You just created your first {{project_type}}!

### Understanding What Just Happened

1. **{{concept_1}}**: {{brief_explanation}}
2. **{{concept_2}}**: {{brief_explanation}}
3. **{{concept_3}}**: {{brief_explanation}}

These three concepts are the foundation of everything else you'll learn.
```

#### Building Real Feature Section
```markdown
## Building Something Useful (10 minutes)

Now let's build {{practical_feature}} - something you can actually use!

### What We're Building
{{feature_description}}

### Step 1: Set up the structure
```{{language}}
{{initial_structure_code}}
```

### Step 2: Add core functionality
```{{language}}
{{core_functionality_code}}
```

**Key additions:**
- {{addition_1}}: {{why_its_important}}
- {{addition_2}}: {{why_its_important}}

### Step 3: Make it interactive
```{{language}}
{{interactive_code}}
```

### Step 4: Test it out
```bash
{{test_command}}
```

Try these inputs:
- `{{test_input_1}}` → {{expected_result_1}}
- `{{test_input_2}}` → {{expected_result_2}}

### Customization Options

Want to make it your own? Try these modifications:

1. **Change {{customization_1}}**:
   ```{{language}}
   {{customization_code_1}}
   ```

2. **Add {{customization_2}}**:
   ```{{language}}
   {{customization_code_2}}
   ```

### Common Issues and Solutions

<details>
<summary>Error: "{{common_error_1}}"</summary>

This usually means {{error_explanation}}. Fix it by:
```bash
{{error_solution_1}}
```
</details>

<details>
<summary>Error: "{{common_error_2}}"</summary>

This happens when {{error_explanation}}. Solution:
```bash
{{error_solution_2}}
```
</details>
```

#### Next Steps Section
```markdown
## Where to Go From Here

🎯 **You've completed the getting started guide!** You now know how to:
- ✅ {{achievement_1}}
- ✅ {{achievement_2}}
- ✅ {{achievement_3}}

### Recommended Learning Path

#### Beginner Path (Next 2-3 hours)
1. 📖 [Tutorial: {{tutorial_1_name}}]({{tutorial_1_link}}) - {{tutorial_1_description}}
2. 📖 [Tutorial: {{tutorial_2_name}}]({{tutorial_2_link}}) - {{tutorial_2_description}}
3. 🧪 [Exercise: {{exercise_name}}]({{exercise_link}}) - Practice what you've learned

#### Intermediate Path
- 🔧 [How-to: {{howto_1}}]({{howto_1_link}})
- 🔧 [How-to: {{howto_2}}]({{howto_2_link}})
- 📚 [API Reference]({{api_ref_link}})

#### Advanced Topics
- 🚀 [{{advanced_1}}]({{advanced_1_link}})
- 🚀 [{{advanced_2}}]({{advanced_2_link}})

### Join the Community

- 💬 [Discord Server]({{discord_link}}) - Get help and share your projects
- 🐦 [Twitter]({{twitter_link}}) - Latest updates and tips
- 📺 [YouTube Channel]({{youtube_link}}) - Video tutorials
- 🌟 [GitHub]({{github_link}}) - Star us if this helped!

### Get Help

Stuck on something? 
- Check our [FAQ](../troubleshooting/FAQ.md)
- Search [existing issues]({{issues_link}})
- Ask on [Stack Overflow]({{so_link}}) with tag `{{project_tag}}`
- Review [Troubleshooting Guide](troubleshooting.md)
```

## Interactive Elements

### Progress Indicators
```markdown
## Your Progress

- [x] Prerequisites verified
- [x] {{project_name}} installed
- [x] Hello World completed
- [ ] First real feature built
- [ ] Customization attempted
- [ ] Ready for tutorials
```

### Self-Check Quizzes
```markdown
### Quick Check

Before moving on, make sure you can:

<details>
<summary>1. What command creates a new project?</summary>

Answer: `{{init_command}}`
</details>

<details>
<summary>2. What are the three core concepts we learned?</summary>

Answer: {{concept_1}}, {{concept_2}}, and {{concept_3}}
</details>
```

## Quality Metrics
✅ Time to first success: < 15 minutes
✅ Zero assumed knowledge beyond prerequisites
✅ Every code example is complete and runnable
✅ Success celebration at each milestone
✅ Clear next steps for continued learning