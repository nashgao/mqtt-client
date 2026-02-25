# Command: doc-audit-existing
Analyze and improve existing documentation using best practices

## 🚨 CRITICAL INPUT PATH CONFIGURATION

**This command audits documentation in the `docs/` directory by default.**

### Standard Documentation Audit Targets
- **Primary Target**: `docs/` directory and all subdirectories
- **Secondary**: Project root files (README.md, CONTRIBUTING.md)
- **Output**: Audit report saved to `docs/audit/documentation-audit-{date}.md`

Refer to `templates/shared/documentation-patterns.md` for complete path specifications.

## Usage
```
/doc-audit-existing [documentation-path|project-path]
# Defaults to auditing ./docs/ if no path specified
```

## Description
Performs comprehensive analysis of existing documentation against best practices from successful open source projects. Identifies gaps, inconsistencies, and improvement opportunities. Provides specific, actionable recommendations with rewritten sections.

## Implementation

### Three-Phase Documentation Audit

#### Phase 1: Structural Analysis
```xml
<instructions>
Analyze documentation structure and organization in docs/ directory
</instructions>

<default_audit_path>
docs/ directory structure with all subdirectories:
- docs/README.md (main documentation hub)
- docs/getting-started/
- docs/api/
- docs/architecture/
- docs/guides/
- docs/examples/
- docs/contributing/
</default_audit_path>

<structural_assessment>
- Information architecture and navigation
- Content categorization (Diátaxis framework compliance)
- File organization and naming conventions
- Cross-referencing and linking strategy
- Version control integration
- Search and discoverability
</structural_assessment>

<pattern_recognition>
- Compare against successful projects (React, Vue, Django, FastAPI)
- Identify missing standard sections
- Evaluate progressive disclosure implementation
- Check for multiple entry points
- Assess documentation-as-code practices
</pattern_recognition>

<output>
Structural analysis report with gap identification
</output>
```

#### Phase 2: Content Quality Analysis
```xml
<instructions>
Evaluate documentation content quality and completeness
</instructions>

<quality_metrics>
- Completeness: Coverage of all features/APIs
- Clarity: Readability scores (Fog index < 12)
- Accuracy: Technical correctness
- Currency: Up-to-date with codebase
- Consistency: Voice, tone, terminology
- Examples: Practical, working code samples
</quality_metrics>

<content_assessment>
For each documentation section:
- Readability analysis
- Technical accuracy check
- Code example validation
- Link verification
- Image/diagram relevance
- Prerequisite clarity
- Learning path coherence
</content_assessment>

<gap_analysis>
- Missing topics or sections
- Undocumented features
- Incomplete examples
- Broken references
- Outdated information
- Ambiguous instructions
</gap_analysis>
```

#### Phase 3: Improvement Generation
```xml
<instructions>
Generate specific improvements and rewritten sections
</instructions>

<improvement_strategy>
For each identified issue:
1. Specific problem description
2. Impact on user experience
3. Recommended fix
4. Rewritten content example
5. Priority level (critical/high/medium/low)
</improvement_strategy>

<rewrite_approach>
- Maintain existing style where good
- Apply best practices incrementally
- Preserve working examples
- Enhance rather than replace
- Provide before/after comparisons
</rewrite_approach>
```

### Audit Report Template

**File: `docs/audit/documentation-audit-{{audit_date}}.md`**

```markdown
# Documentation Audit Report

**Project:** {{project_name}}
**Audit Date:** {{audit_date}}
**Documentation Path:** docs/
**Documentation Version:** {{doc_version}}
**Auditor:** Claude Code Documentation Audit Tool

## Executive Summary

**Overall Score:** {{score}}/100

### Key Findings
- ✅ **Strengths:** {{key_strengths}}
- ⚠️ **Areas for Improvement:** {{improvement_areas}}
- ❌ **Critical Issues:** {{critical_issues}}

## Detailed Analysis

### 1. Structural Assessment

#### Information Architecture
**Current State:**
{{current_structure_description}}

**Issues Found:**
1. {{structure_issue_1}}
2. {{structure_issue_2}}

**Recommendations:**
```
{{recommended_structure}}
```

#### Diátaxis Framework Compliance
| Category | Current Coverage | Target | Gap |
|----------|-----------------|--------|-----|
| Tutorials | {{tutorial_coverage}}% | 100% | {{tutorial_gap}} |
| How-to Guides | {{howto_coverage}}% | 100% | {{howto_gap}} |
| Reference | {{reference_coverage}}% | 100% | {{reference_gap}} |
| Explanations | {{explanation_coverage}}% | 100% | {{explanation_gap}} |

### 2. Content Quality

#### Readability Analysis
- **Fog Index:** {{fog_index}} (Target: < 12)
- **Average Sentence Length:** {{avg_sentence_length}} words
- **Complex Words:** {{complex_word_percentage}}%

**Sections Needing Simplification:**
1. {{complex_section_1}} - Fog Index: {{fog_1}}
2. {{complex_section_2}} - Fog Index: {{fog_2}}

#### Completeness Audit

**Documented Features:** {{documented_count}}/{{total_features}} ({{percentage}}%)

**Missing Documentation:**
- [ ] {{missing_feature_1}}
- [ ] {{missing_feature_2}}
- [ ] {{missing_feature_3}}

#### Code Examples Assessment

**Total Examples:** {{example_count}}
**Working Examples:** {{working_count}} ({{working_percentage}}%)
**Outdated Examples:** {{outdated_count}}

**Examples Needing Update:**
1. {{outdated_example_1}} - {{issue_1}}
2. {{outdated_example_2}} - {{issue_2}}

### 3. Specific Improvements

#### Priority 1: Critical Issues

##### Issue: {{critical_issue_title}}
**Location:** `{{file_path}}:{{line_number}}`

**Current Content:**
```markdown
{{current_content}}
```

**Problem:** {{problem_description}}

**Improved Version:**
```markdown
{{improved_content}}
```

**Rationale:** {{improvement_rationale}}

#### Priority 2: High Impact Improvements

##### Issue: {{high_priority_issue}}
[Similar format as above]

#### Priority 3: Medium Priority Enhancements

##### Issue: {{medium_priority_issue}}
[Similar format as above]

### 4. Quick Wins

These improvements can be implemented immediately for quick impact:

1. **Fix Broken Links** ({{broken_link_count}} found)
   ```bash
   {{link_fix_commands}}
   ```

2. **Update Version Numbers**
   - Change {{old_version}} to {{current_version}} in {{file_count}} files

3. **Add Missing Alt Text**
   - {{images_without_alt}} images need alt text

### 5. Long-term Recommendations

#### Documentation Strategy
1. {{strategy_recommendation_1}}
2. {{strategy_recommendation_2}}
3. {{strategy_recommendation_3}}

#### Process Improvements
- [ ] Implement documentation CI/CD pipeline
- [ ] Add documentation linting
- [ ] Create contribution guidelines
- [ ] Set up automated link checking
- [ ] Enable community contributions

## Action Plan

### Week 1: Critical Fixes
- [ ] {{week1_task_1}}
- [ ] {{week1_task_2}}
- [ ] {{week1_task_3}}

### Week 2-3: Content Updates
- [ ] {{week2_task_1}}
- [ ] {{week2_task_2}}

### Week 4: Structure Reorganization
- [ ] {{week4_task_1}}
- [ ] {{week4_task_2}}

### Ongoing: Maintenance
- [ ] Weekly documentation review
- [ ] Monthly link checking
- [ ] Quarterly content audit
```

### Common Documentation Issues

#### Getting Started Problems
```xml
<instructions>
Identify and fix common getting-started issues
</instructions>

<common_problems>
- Missing prerequisites
- Unclear installation steps
- No quick success path
- Assumed knowledge
- Platform-specific issues ignored
- No troubleshooting section
</common_problems>

<improvements>
For each problem:
- Specific fix with example
- Time estimate to implement
- Impact on user experience
</improvements>
```

#### API Documentation Gaps
```xml
<instructions>
Find and fix API documentation issues
</instructions>

<api_issues>
- Missing endpoints
- Incomplete parameters
- No example requests/responses
- Missing error codes
- No authentication details
- Unclear rate limiting
</api_issues>

<fixes>
Generate complete API documentation following OpenAPI spec
</fixes>
```

#### Example Quality Issues
```xml
<instructions>
Audit and improve code examples
</instructions>

<example_problems>
- Non-runnable code
- Missing imports/setup
- No expected output shown
- Outdated syntax
- Poor practices demonstrated
- No error handling shown
</example_problems>

<improvements>
- Make all examples runnable
- Add setup instructions
- Show expected output
- Include error handling
- Follow best practices
</improvements>
```

### Consistency Enforcement

#### Terminology Standardization
```markdown
## Terminology Inconsistencies Found

| Current Terms | Recommended Standard | Occurrences |
|--------------|---------------------|-------------|
| {{term_variations}} | {{standard_term}} | {{count}} |
| user/customer/client | user | 47 |
| function/method/procedure | function | 23 |

## Automated Fix Script
```bash
# Replace all variations with standard terms
{{replacement_script}}
```
```

#### Style Guide Violations
```markdown
## Style Guide Compliance

### Voice and Tone
- **Active vs Passive:** {{active_percentage}}% active (Target: > 80%)
- **Second Person Usage:** {{second_person_usage}} (Good ✅)
- **Imperative Mood:** {{imperative_usage}} in headings

### Formatting Issues
- Inconsistent heading capitalization: {{heading_issues}}
- Code block language hints missing: {{missing_hints}}
- List formatting variations: {{list_issues}}
```

### Automated Improvements

#### Link Checking
```bash
# Check all documentation links in docs/ directory
/doc-audit-existing docs/ --check-links

# Output format:
✅ Valid: 234 links
❌ Broken: 12 links
  - docs/api/README.md:23 -> ../schemas/User.md (file not found)
  - docs/getting-started/README.md:45 -> installation.md (missing anchor)
⚠️ Redirect: 8 links
🔍 Anchor missing: 3 links

Report saved to: docs/audit/link-check-{{date}}.md
```

#### Readability Optimization
```xml
<instructions>
Automatically simplify complex documentation
</instructions>

<simplification_rules>
- Split sentences > 25 words
- Replace jargon with simple terms
- Add explanations for acronyms
- Break up large paragraphs
- Add visual breaks with headings
</simplification_rules>
```

## Integration Features

### CI/CD Integration
```yaml
# .github/workflows/doc-audit.yml
name: Documentation Audit
on: 
  pull_request:
    paths:
      - 'docs/**'
  schedule:
    - cron: '0 0 * * 0' # Weekly

jobs:
  audit:
    runs-on: ubuntu-latest
    steps:
      - name: Run Documentation Audit
        run: |
          /doc-audit-existing docs/
          
      - name: Check for audit report
        run: |
          ls -la docs/audit/
          
      - name: Upload audit report
        uses: actions/upload-artifact@v3
        with:
          name: documentation-audit
          path: docs/audit/
          
      - name: Comment PR with Results
        if: github.event_name == 'pull_request'
        uses: actions/github-script@v6
        with:
          script: |
            // Post audit results as PR comment
```

### Tracking Improvements

**File: `docs/audit/quality-metrics-history.md`**

```markdown
# Documentation Quality Metrics History

## Baseline ({{baseline_date}})
**Audit Report**: [documentation-audit-{{baseline_date}}.md](documentation-audit-{{baseline_date}}.md)
- Completeness: {{baseline_completeness}}%
- Readability: {{baseline_readability}}
- Examples: {{baseline_examples}}
- Broken Links: {{baseline_broken}}

## Current ({{current_date}})
**Audit Report**: [documentation-audit-{{current_date}}.md](documentation-audit-{{current_date}}.md)
- Completeness: {{current_completeness}}% ({{completeness_change}})
- Readability: {{current_readability}} ({{readability_change}})
- Examples: {{current_examples}} ({{examples_change}})
- Broken Links: {{current_broken}} ({{broken_change}})

### Trend: {{trend_direction}} {{trend_percentage}}% improvement

## All Audit Reports
- [Latest Audit Report](documentation-audit-latest.md)
- [Quality Metrics Dashboard](quality-dashboard.md)
- [Improvement Action Plan](improvement-plan.md)
```

## Quality Metrics
✅ All documentation sections analyzed
✅ Specific, actionable recommendations provided
✅ Priority levels assigned to all issues
✅ Rewritten content examples included
✅ Implementation timeline created
✅ Success metrics defined