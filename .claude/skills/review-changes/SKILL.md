---
allowed-tools: all
description: Comprehensive analysis of changes made by Claude Code with detailed explanations
---

# 🔍🔍 CRITICAL REQUIREMENT: COMPREHENSIVE CHANGE ANALYSIS! 🔍🔍

**THIS IS NOT A SIMPLE DIFF VIEW - THIS IS A COMPLETE CHANGE UNDERSTANDING DASHBOARD!**

When you run `/review-changes`, you are REQUIRED to:

1. **ANALYZE** all changes made by Claude Code (committed and uncommitted)
2. **CATEGORIZE** changes by type (code, tests, docs, config, infrastructure)
3. **EXPLAIN** the purpose and impact of each modification
4. **PROVIDE** before/after comparisons for key changes
5. **USE MULTIPLE AGENTS** for comprehensive parallel analysis:
   - Spawn one agent to analyze git history and commits
   - Spawn another to review uncommitted changes
   - Spawn another to assess impact and dependencies
   - Spawn another to generate explanations and summaries
   - Say: "I'll spawn multiple agents to analyze all changes comprehensively"
6. **DO NOT STOP** until:
   - ✅ All changes are documented and explained
   - ✅ Impact assessment is complete
   - ✅ Clear summary is provided
   - ✅ Actionable recommendations are generated

**FORBIDDEN BEHAVIORS:**
- ❌ "Just showing git diff output" → NO! Provide meaningful explanations!
- ❌ "Listing files without context" → NO! Explain what each change does!
- ❌ "Ignoring small changes" → NO! Every change matters for understanding!
- ❌ "Generic descriptions" → NO! Specific, contextual explanations required!

**MANDATORY WORKFLOW:**
```
1. Git history analysis → Recent commits by Claude
2. Uncommitted changes → Current working directory status
3. Change categorization → Group by type and purpose
4. Impact assessment → Dependencies and risk analysis
5. Explanation generation → Clear purpose for each change
6. Summary creation → Executive overview with metrics
7. Recommendations → Next steps and testing suggestions
```

**YOU ARE NOT DONE UNTIL:**
- Every changed file has a clear explanation
- All changes are properly categorized
- Impact and risk levels are assessed
- Clear before/after comparisons are shown
- Actionable recommendations are provided

---

## 🔍 COMPREHENSIVE CHANGE ANALYSIS PROTOCOL

**Step 0: Initialize Analysis Environment**
- [ ] Check git repository status
- [ ] Identify Claude-authored commits (look for 🤖 or Co-Authored-By: Claude)
- [ ] Capture current working directory state
- [ ] Set analysis scope (time range, branch, etc.)

**Step 1: Multi-Agent Analysis Deployment**
Deploy specialized analysis agents in parallel:

```
"I need to analyze all changes made by Claude Code comprehensively. Spawning analysis agents:
- Agent 1: Git history analysis for Claude commits
- Agent 2: Uncommitted changes and working directory status
- Agent 3: Dependency and impact assessment
- Agent 4: Change categorization and metrics
- Agent 5: Explanation and summary generation
Let me analyze all changes in parallel..."
```

**Step 2: Change Detection and Collection**
- [ ] Identify all commits authored/co-authored by Claude
- [ ] List all uncommitted changes (staged and unstaged)
- [ ] Track file movements and renames
- [ ] Detect new files and deletions
- [ ] Capture configuration changes

**Step 3: Change Categorization**
Organize changes by type and purpose:

```
📁 Code Changes:
   ├── Features Added: [list with explanations]
   ├── Bugs Fixed: [list with explanations]
   ├── Refactoring: [list with explanations]
   └── Performance: [list with explanations]

📝 Documentation:
   ├── New Docs: [list with purpose]
   ├── Updated Docs: [list with changes]
   └── Examples: [list with context]

🧪 Testing:
   ├── New Tests: [list with coverage]
   ├── Test Fixes: [list with issues resolved]
   └── Test Utils: [list with purpose]

⚙️ Configuration:
   ├── Build Config: [list with impact]
   ├── Dependencies: [list with versions]
   └── Settings: [list with effects]

🏗️ Infrastructure:
   ├── CI/CD: [list with workflow changes]
   ├── Deployment: [list with environment impact]
   └── Scripts: [list with automation added]
```

**Step 4: Impact Assessment**
Evaluate the scope and risk of changes:

- [ ] Identify affected modules and dependencies
- [ ] Assess breaking changes
- [ ] Evaluate performance implications
- [ ] Check security considerations
- [ ] Determine testing requirements

**Risk Level Classification:**
- 🟢 **Low Risk**: Isolated changes, well-tested
- 🟡 **Medium Risk**: Multiple modules affected, needs review
- 🔴 **High Risk**: Core functionality changed, extensive testing required

**Step 5: Detailed Change Explanations**
For each significant change, provide:

```
📄 File: [path/to/file]
📊 Change Type: [Feature/Fix/Refactor/etc.]
🎯 Purpose: [Clear explanation of why this change was made]
🔧 What Changed:
   - Before: [Key aspects before change]
   - After: [Key aspects after change]
💥 Impact: [Who/what is affected by this change]
✅ Benefits: [Improvements this change brings]
⚠️ Considerations: [Things to watch or test]
```

**Step 6: Before/After Comparisons**
Show clear comparisons for critical changes:

```diff
# Example for key function changes
- OLD: function processData(data) { return data }
+ NEW: function processData(data) { 
+   validateData(data);
+   return transformData(data);
+ }

Purpose: Added validation and transformation for data integrity
```

**Step 7: Executive Summary Generation**
Create a high-level overview:

```
## 📊 CHANGE SUMMARY DASHBOARD

**Total Changes:** [X files changed, Y insertions, Z deletions]
**Time Period:** [Date range of changes]
**Primary Focus:** [Main purpose of changes]

### 🎯 Key Achievements:
- ✅ [Major feature/fix 1]
- ✅ [Major feature/fix 2]
- ✅ [Major improvement 3]

### 📈 Metrics:
- Code Coverage: [Before] → [After]
- Performance: [Impact assessment]
- Technical Debt: [Reduced/Added]
- Documentation: [Coverage percentage]

### 🔍 Change Distribution:
- Features: XX%
- Bug Fixes: XX%
- Refactoring: XX%
- Documentation: XX%
- Tests: XX%
```

**Step 8: Actionable Recommendations**
Provide clear next steps:

- [ ] **Testing Required**: [Specific test scenarios]
- [ ] **Review Focus**: [Critical files needing human review]
- [ ] **Documentation Needs**: [What needs to be documented]
- [ ] **Deployment Considerations**: [What to check before deploy]
- [ ] **Monitoring**: [What to watch after deployment]

## 🚫 ANALYSIS ANTI-PATTERNS

**FORBIDDEN APPROACHES:**
- ❌ "Raw git diff without explanation" → NO, provide context!
- ❌ "File list without purpose" → NO, explain why each change!
- ❌ "Generic change descriptions" → NO, be specific!
- ❌ "Ignoring minor changes" → NO, completeness matters!
- ❌ "No actionable insights" → NO, provide recommendations!

## ✅ COMPLETION CRITERIA

The analysis is complete when:
- [ ] All Claude-authored changes are identified
- [ ] Every change has a clear explanation
- [ ] Impact assessment is comprehensive
- [ ] Risk levels are assigned
- [ ] Before/after comparisons shown for critical changes
- [ ] Executive summary is clear and actionable
- [ ] Recommendations are specific and testable
- [ ] Visual organization aids understanding

## 🎯 FINAL COMMITMENT

I will now execute comprehensive change analysis. I will:
- ✅ Spawn multiple agents for parallel analysis
- ✅ Analyze ALL changes made by Claude Code
- ✅ Provide detailed explanations for each change
- ✅ Create clear categorization and metrics
- ✅ Generate actionable recommendations
- ✅ Present information in an organized, understandable format

I will NOT:
- ❌ Show raw diffs without explanation
- ❌ Skip any changes as "minor"
- ❌ Provide generic descriptions
- ❌ Leave any change unexplained
- ❌ Forget to assess impact and risk

**REMEMBER: Users need to UNDERSTAND what was changed and WHY!**

The analysis is complete ONLY when users have full visibility into all modifications with clear explanations and actionable next steps.

**Executing comprehensive change analysis NOW...**