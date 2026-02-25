# Command: doc-architecture-adr
Generate Architecture Decision Records (ADRs) using MADR format

## 🚨 CRITICAL OUTPUT PATH CONFIGURATION

**ALL Architecture Decision Records MUST be written to the `docs/architecture/` directory structure.**

### Standard Architecture Documentation Paths
- **Architecture Hub**: `docs/architecture/README.md` - Architecture documentation index
- **Decision Records**: `docs/architecture/decisions/` - Individual ADR files
- **Diagrams**: `docs/architecture/diagrams/` - Architecture diagrams and visuals
- **Patterns**: `docs/architecture/patterns/` - Architectural patterns documentation

Refer to `templates/shared/documentation-patterns.md` for complete path specifications.

## Usage
```
/doc-architecture-adr [decision-topic]
```

## Description
Creates structured Architecture Decision Records that document important technical decisions, their context, and consequences. Uses the MADR (Markdown Architectural Decision Records) format adopted by leading engineering teams.

## Implementation

### ADR Generation Process

#### Phase 1: Context Gathering
```xml
<instructions>
Analyze the technical decision context
</instructions>

<information_gathering>
- Current system state and constraints
- Problem that needs solving
- Stakeholders affected
- Technical and business requirements
- Timeline and urgency
- Available resources
- Risk tolerance
</information_gathering>

<existing_decisions>
- Review related ADRs
- Identify dependencies
- Check for contradictions
- Learn from past decisions
</existing_decisions>

<output>
Structured context for decision making
Output file: docs/architecture/decisions/ADR-{number}-{title}.md
</output>
```

#### Phase 2: Option Analysis
```xml
<instructions>
Generate and evaluate multiple solution options
</instructions>

<option_generation>
For each viable approach:
- Technical implementation details
- Pros and cons analysis
- Cost estimation (time, money, resources)
- Risk assessment
- Scalability implications
- Maintenance burden
- Team expertise requirements
</option_generation>

<evaluation_criteria>
- Performance impact
- Development effort
- Operational complexity
- Security implications
- Cost (initial and ongoing)
- Time to market
- Technical debt
- Team familiarity
</evaluation_criteria>

<output>
Comparative analysis matrix of all options
</output>
```

#### Phase 3: Decision Documentation
```xml
<instructions>
Create formal ADR document
</instructions>

<document_structure>
Output file: docs/architecture/decisions/ADR-{number}-{title}.md

1. Title (ADR-XXXX: Decision Title)
2. Status (proposed/accepted/rejected/deprecated/superseded)
3. Context and Problem Statement
4. Decision Drivers
5. Considered Options
6. Decision Outcome
7. Consequences (positive/negative)
8. Validation
9. Review Schedule
10. Related Documentation Links
</document_structure>

<writing_style>
- Clear and concise
- Technical but accessible
- Evidence-based
- Forward-looking
- Actionable
</writing_style>
```

### MADR Template Structure

**File: `docs/architecture/decisions/ADR-{number}-{title}.md`**

```markdown
# ADR-{{number}}: {{title}}

## Status
{{status}} <!-- proposed | accepted | rejected | deprecated | superseded by ADR-XXX -->

## Date
{{date}} <!-- YYYY-MM-DD -->

## Context and Problem Statement

{{problem_description}}

We need to {{specific_need}} because {{business_reason}}.

### Current Situation
- {{current_state_1}}
- {{current_state_2}}
- {{current_state_3}}

### Constraints
- {{constraint_1}} <!-- e.g., must complete by Q3 -->
- {{constraint_2}} <!-- e.g., budget limit of $X -->
- {{constraint_3}} <!-- e.g., must be compatible with Y -->

## Decision Drivers

Priority order (1 = highest):
1. {{driver_1}} <!-- e.g., Performance requirements -->
2. {{driver_2}} <!-- e.g., Development speed -->
3. {{driver_3}} <!-- e.g., Operational simplicity -->
4. {{driver_4}} <!-- e.g., Cost effectiveness -->
5. {{driver_5}} <!-- e.g., Team expertise -->

## Considered Options

### Option 1: {{option_1_name}}
{{option_1_description}}

**Pros:**
- ✅ {{pro_1}}
- ✅ {{pro_2}}
- ✅ {{pro_3}}

**Cons:**
- ❌ {{con_1}}
- ❌ {{con_2}}
- ❌ {{con_3}}

**Estimated Effort:** {{effort_estimate}}
**Risk Level:** {{risk_level}}

### Option 2: {{option_2_name}}
{{option_2_description}}

**Pros:**
- ✅ {{pro_1}}
- ✅ {{pro_2}}

**Cons:**
- ❌ {{con_1}}
- ❌ {{con_2}}

**Estimated Effort:** {{effort_estimate}}
**Risk Level:** {{risk_level}}

### Option 3: {{option_3_name}} (Status Quo)
{{option_3_description}}

**Pros:**
- ✅ No change required
- ✅ {{pro_2}}

**Cons:**
- ❌ {{con_1}}
- ❌ {{con_2}}

## Decision Outcome

### Chosen Option
**Option {{chosen_number}}: {{chosen_option_name}}**

### Rationale
We selected this option because:
1. {{reason_1}}
2. {{reason_2}}
3. {{reason_3}}

This best balances our need for {{primary_need}} with {{secondary_need}}.

### Implementation Plan
1. **Phase 1** ({{timeline_1}}): {{implementation_step_1}}
2. **Phase 2** ({{timeline_2}}): {{implementation_step_2}}
3. **Phase 3** ({{timeline_3}}): {{implementation_step_3}}

## Consequences

### Positive Consequences
- ✅ {{positive_1}}
- ✅ {{positive_2}}
- ✅ {{positive_3}}
- ✅ {{positive_4}}

### Negative Consequences
- ⚠️ {{negative_1}}
  - **Mitigation:** {{mitigation_1}}
- ⚠️ {{negative_2}}
  - **Mitigation:** {{mitigation_2}}
- ⚠️ {{negative_3}}
  - **Mitigation:** {{mitigation_3}}

### Technical Debt
{{technical_debt_description}}

**Payback Plan:** {{debt_resolution_timeline}}

## Validation

### Success Criteria
- [ ] {{success_metric_1}}
- [ ] {{success_metric_2}}
- [ ] {{success_metric_3}}

### Validation Method
{{how_we_will_validate}}

### Review Schedule
- **3 months:** Quick check on implementation progress
- **6 months:** Detailed review of outcomes vs. expectations
- **12 months:** Full retrospective and decision reassessment

## Related Decisions
- [{{related_adr_1}}]({{related_adr_1}}.md) - {{relationship_1}}
- [{{related_adr_2}}]({{related_adr_2}}.md) - {{relationship_2}}

## Related Documentation
- [Architecture Overview](../README.md)
- [System Diagrams](../diagrams/{{relevant_diagram}}.md)
- [Implementation Patterns](../patterns/{{relevant_pattern}}.md)

## References
- [{{reference_1_title}}]({{reference_1_url}})
- [{{reference_2_title}}]({{reference_2_url}})
- [{{reference_3_title}}]({{reference_3_url}})

## Notes
{{additional_notes}}

---
*Decision made by: {{decision_makers}}*
*Stakeholders consulted: {{stakeholders}}*
```

### Common ADR Types

#### Technology Selection ADR
```xml
<instructions>
Document technology/framework/tool selection decisions
</instructions>

<specific_sections>
- Technology comparison matrix
- Migration path from current technology
- Training and hiring implications
- Vendor lock-in considerations
- Community and ecosystem evaluation
- Long-term support considerations
</specific_sections>
```

#### Architecture Pattern ADR
```xml
<instructions>
Document architectural pattern decisions (microservices, monolith, etc.)
</instructions>

<specific_sections>
- Current architecture assessment
- Pattern trade-offs analysis
- Scaling implications
- Complexity vs. flexibility
- Team structure alignment
- Operational requirements
</specific_sections>
```

#### Data Storage ADR
```xml
<instructions>
Document database and data storage decisions
</instructions>

<specific_sections>
- Data characteristics (volume, velocity, variety)
- Consistency requirements
- Query patterns
- Scaling requirements
- Backup and recovery needs
- Compliance and security requirements
</specific_sections>
```

#### Security Decision ADR
```xml
<instructions>
Document security-related architectural decisions
</instructions>

<specific_sections>
- Threat model
- Security requirements
- Compliance needs
- Authentication/authorization approach
- Encryption strategy
- Audit and monitoring requirements
</specific_sections>
```

### ADR Lifecycle Management

#### Status Transitions
```
proposed → accepted → superseded by ADR-XXX
    ↓         ↓            ↓
rejected  deprecated   deprecated
```

#### Linking ADRs
```markdown
## Related Decisions

### Supersedes
- [ADR-001: {{old_decision}}](ADR-001-{{old_decision_slug}}.md) - Replaced due to {{reason}}

### Superseded By
- [ADR-042: {{new_decision}}](ADR-042-{{new_decision_slug}}.md) - Updated approach for {{reason}}

### Depends On
- [ADR-015: {{dependency}}](ADR-015-{{dependency_slug}}.md) - Required for {{aspect}}

### Related To
- [ADR-023: {{related_decision}}](ADR-023-{{related_decision_slug}}.md) - Similar problem space

### Architecture Documentation
- [Architecture Overview](../README.md)
- [System Components](../diagrams/system-overview.md)
- [Design Patterns](../patterns/README.md)
```

## Advanced Features

### Decision Impact Analysis
```markdown
## Impact Analysis

### Systems Affected
| System | Impact Level | Changes Required | Timeline |
|--------|-------------|------------------|----------|
| {{system_1}} | High | {{changes_1}} | {{timeline_1}} |
| {{system_2}} | Medium | {{changes_2}} | {{timeline_2}} |
| {{system_3}} | Low | {{changes_3}} | {{timeline_3}} |

### Team Impact
| Team | Training Needed | Resource Allocation | Deadline |
|------|----------------|-------------------|----------|
| {{team_1}} | {{training_1}} | {{resources_1}} | {{deadline_1}} |
| {{team_2}} | {{training_2}} | {{resources_2}} | {{deadline_2}} |
```

### Risk Register
```markdown
## Risk Register

| Risk | Probability | Impact | Mitigation | Owner |
|------|------------|--------|------------|--------|
| {{risk_1}} | High | High | {{mitigation_1}} | {{owner_1}} |
| {{risk_2}} | Medium | High | {{mitigation_2}} | {{owner_2}} |
| {{risk_3}} | Low | Medium | {{mitigation_3}} | {{owner_3}} |
```

### Cost-Benefit Analysis
```markdown
## Cost-Benefit Analysis

### Costs
- **Development:** ${{dev_cost}} ({{dev_hours}} hours)
- **Infrastructure:** ${{infra_cost}}/month
- **Training:** ${{training_cost}}
- **Opportunity Cost:** {{opportunity_description}}

### Benefits
- **Performance Improvement:** {{performance_gain}}
- **Cost Savings:** ${{savings}}/year
- **Developer Productivity:** {{productivity_gain}}
- **Risk Reduction:** {{risk_reduction}}

### ROI Timeline
Break-even point: {{break_even_months}} months
```

## Quality Checklist
✅ Problem clearly stated
✅ All viable options considered
✅ Decision rationale documented
✅ Consequences (both positive and negative) identified
✅ Success criteria defined
✅ Review schedule established