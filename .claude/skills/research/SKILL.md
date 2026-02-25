---
allowed-tools: all
description: Intelligent research orchestrator for comprehensive multi-source investigation and synthesis
---

# 🔍🔍🔍 CRITICAL REQUIREMENT: COMPREHENSIVE RESEARCH MODE ENGAGED

**THIS IS NOT A SIMPLE SEARCH TASK - THIS IS A COMPREHENSIVE RESEARCH INVESTIGATION TASK!**

When you run `/research`, you are REQUIRED to:

1. **INVESTIGATE** the topic comprehensively using multi-source analysis
2. **SYNTHESIZE** findings across web, codebase, and documentation sources
3. **VALIDATE** information through cross-referencing and credibility assessment
4. **STRUCTURE** research into actionable insights with confidence levels
5. **USE MULTIPLE AGENTS AGGRESSIVELY** for parallel research streams:
   - Spawn agents to explore different information sources simultaneously
   - Deploy synthesis agents for pattern recognition across sources
   - Use validation agents for fact-checking and credibility assessment
   - Say: "I'll spawn multiple research agents to investigate this comprehensively"

**FORBIDDEN BEHAVIORS:**
- ❌ "Single source research is enough" → NO! Multi-source validation required!
- ❌ "Quick search and report" → NO! Deep investigation with synthesis needed!
- ❌ "Raw data dump without analysis" → NO! Structured insights required!
- ❌ "Skip credibility assessment" → NO! Source validation is mandatory!
- ❌ "Sequential research only" → NO! Parallel investigation streams required!

**MANDATORY WORKFLOW:**
```
1. SCOPE_MODE: Define research boundaries and success criteria
2. PARALLEL_MODE: Deploy multiple agents for simultaneous investigation
3. SYNTHESIS_MODE: Analyze patterns and connections across sources
4. VALIDATION_MODE: Cross-reference and verify critical findings
5. STRUCTURE_MODE: Organize into actionable insights with confidence scoring
```

**YOU ARE NOT DONE UNTIL:**
- ✅ Multiple independent sources consulted and analyzed
- ✅ Information validated through cross-referencing
- ✅ Patterns and insights synthesized across sources
- ✅ Confidence levels assigned to all findings
- ✅ Actionable recommendations provided with evidence

---

🛑 **MANDATORY RESEARCH PROTOCOL** 🛑
1. Parse research query from $ARGUMENTS
2. Define research scope and boundaries
3. Deploy parallel research agents immediately
4. Synthesize findings with pattern recognition
5. Deliver structured, actionable insights

Execute comprehensive research investigation with ZERO tolerance for shallow analysis.

**FORBIDDEN SHORTCUT PATTERNS:**
- "First search result is good enough" → NO, comprehensive investigation required
- "Skip validation for trusted sources" → NO, always cross-reference
- "Present raw findings without synthesis" → NO, structured insights needed
- "Sequential research is sufficient" → NO, parallel streams required
- "Assume information is current" → NO, verify timeliness

You are researching: $ARGUMENTS

Let me deploy comprehensive multi-source research with parallel investigation streams.

🚨 **REMEMBER: Research quality depends on source diversity, validation rigor, and synthesis depth!** 🚨

## 🔍 PHASE 1: RESEARCH SCOPE DEFINITION

**Step 0: Query Analysis and Boundary Setting**
Parse the research query to establish:
- Primary research questions and objectives
- Scope boundaries and constraints
- Success criteria for research completion
- Required depth and breadth of investigation
- Output format and actionability requirements

**Research Scope Checklist:**
- [ ] Core questions clearly identified
- [ ] Investigation boundaries defined
- [ ] Success metrics established
- [ ] Depth requirements determined
- [ ] Output expectations clarified

## 🚀 PHASE 2: PARALLEL INVESTIGATION DEPLOYMENT

**Step 1: Multi-Agent Research Strategy**
Deploy specialized research agents for parallel investigation:

### Web Research Agent:
```markdown
<function_calls>
<invoke name="Task">
<parameter name="subagent_type">general-purpose</parameter>
<parameter name="description">Web investigation</parameter>
<parameter name="prompt">You are the Web Research Agent for comprehensive investigation.

Your responsibilities:
1. Search for authoritative sources on the topic
2. Fetch and analyze relevant web content
3. Identify recent developments and updates
4. Assess source credibility and bias
5. Generate findings report with citations

Research topic: [TOPIC]
Focus on: Recent, authoritative, diverse sources</parameter>
</invoke>
</function_calls>
```

### Codebase Research Agent:
```markdown
<function_calls>
<invoke name="Task">
<parameter name="subagent_type">general-purpose</parameter>
<parameter name="description">Code investigation</parameter>
<parameter name="prompt">You are the Codebase Research Agent for implementation analysis.

Your responsibilities:
1. Search for relevant code patterns and implementations
2. Analyze existing solutions and approaches
3. Identify best practices and anti-patterns
4. Map dependencies and integration points
5. Generate technical findings report

Research focus: [TECHNICAL_ASPECT]
Analyze: Patterns, implementations, best practices</parameter>
</invoke>
</function_calls>
```

### Documentation Research Agent:
```markdown
<function_calls>
<invoke name="Task">
<parameter name="subagent_type">general-purpose</parameter>
<parameter name="description">Docs investigation</parameter>
<parameter name="prompt">You are the Documentation Research Agent for reference analysis.

Your responsibilities:
1. Search official documentation and guides
2. Extract API references and specifications
3. Identify configuration and usage patterns
4. Find examples and tutorials
5. Generate documentation insights report

Documentation focus: [DOCS_AREA]
Extract: Specifications, examples, best practices</parameter>
</invoke>
</function_calls>
```

### Academic Research Agent (when applicable):
```markdown
<function_calls>
<invoke name="Task">
<parameter name="subagent_type">general-purpose</parameter>
<parameter name="description">Academic research</parameter>
<parameter name="prompt">You are the Academic Research Agent for theoretical investigation.

Your responsibilities:
1. Search for academic papers and research
2. Analyze theoretical foundations
3. Identify proven methodologies
4. Extract empirical evidence
5. Generate academic findings report

Research area: [ACADEMIC_TOPIC]
Focus on: Peer-reviewed, empirical, theoretical foundations</parameter>
</invoke>
</function_calls>
```

**Parallel Execution Requirements:**
- [ ] All agents deployed simultaneously
- [ ] Independent investigation streams active
- [ ] No blocking between agent operations
- [ ] Results aggregation prepared
- [ ] Timeout and fallback strategies ready

## 🔄 PHASE 3: SYNTHESIS AND PATTERN RECOGNITION

**Step 2: Cross-Source Pattern Analysis**
Synthesize findings across all research streams:

### Synthesis Agent:
```markdown
<function_calls>
<invoke name="Task">
<parameter name="subagent_type">general-purpose</parameter>
<parameter name="description">Synthesize findings</parameter>
<parameter name="prompt">You are the Synthesis Agent for research integration.

Your responsibilities:
1. Aggregate findings from all research agents
2. Identify patterns and connections across sources
3. Detect contradictions and resolve conflicts
4. Extract key themes and insights
5. Generate unified synthesis report

Synthesis focus: Pattern recognition, insight extraction, conflict resolution</parameter>
</invoke>
</function_calls>
```

**Synthesis Requirements:**
- [ ] All sources compared and contrasted
- [ ] Common patterns identified
- [ ] Contradictions highlighted and resolved
- [ ] Key insights extracted
- [ ] Confidence levels assigned

## ✅ PHASE 4: VALIDATION AND CREDIBILITY ASSESSMENT

**Step 3: Information Validation Protocol**
Validate critical findings through cross-referencing:

### Validation Agent:
```markdown
<function_calls>
<invoke name="Task">
<parameter name="subagent_type">general-purpose</parameter>
<parameter name="description">Validate findings</parameter>
<parameter name="prompt">You are the Validation Agent for research verification.

Your responsibilities:
1. Cross-reference critical claims across sources
2. Verify factual accuracy and currency
3. Assess source credibility and potential bias
4. Check for consensus vs. controversy
5. Generate validation report with confidence scores

Validation criteria: Accuracy, currency, credibility, consensus</parameter>
</invoke>
</function_calls>
```

**Validation Checklist:**
- [ ] Facts cross-referenced across multiple sources
- [ ] Source credibility scores assigned
- [ ] Information currency verified
- [ ] Consensus vs. controversy identified
- [ ] Confidence levels calculated

## 📊 PHASE 5: STRUCTURED OUTPUT GENERATION

**Step 4: Research Report Structure**
Organize findings into actionable insights:

```markdown
# RESEARCH REPORT: [TOPIC]

## 📋 EXECUTIVE SUMMARY
- **Key Finding 1** (Confidence: 95%) - [Evidence: 5 sources]
- **Key Finding 2** (Confidence: 85%) - [Evidence: 3 sources]
- **Key Finding 3** (Confidence: 90%) - [Evidence: 4 sources]

## 🔍 DETAILED FINDINGS

### Finding Category 1
**Claim**: [Specific claim or insight]
**Evidence**: [Source citations with links]
**Confidence**: [High/Medium/Low with percentage]
**Implications**: [What this means practically]

### Finding Category 2
[Similar structure...]

## 🎯 ACTIONABLE RECOMMENDATIONS
1. **Immediate Action**: [What to do now]
   - Justification: [Why based on research]
   - Implementation: [How to execute]

2. **Short-term Strategy**: [1-3 month horizon]
   - Justification: [Research-backed reasoning]
   - Implementation: [Execution approach]

3. **Long-term Consideration**: [3+ month horizon]
   - Justification: [Strategic reasoning]
   - Implementation: [Planning approach]

## 📚 SOURCES AND CITATIONS
- [Source 1]: URL/Reference (Credibility: High)
- [Source 2]: URL/Reference (Credibility: Medium)
- [Additional sources...]

## ⚠️ CAVEATS AND LIMITATIONS
- Information gaps identified
- Conflicting viewpoints noted
- Areas requiring further research
```

**Output Quality Standards:**
- [ ] Executive summary captures essence
- [ ] Findings clearly categorized
- [ ] Evidence properly cited
- [ ] Confidence levels transparent
- [ ] Recommendations actionable

## 🔄 PHASE 6: ITERATIVE REFINEMENT

**Step 5: Research Depth Expansion**
For areas requiring deeper investigation:

**Depth Expansion Triggers:**
- Low confidence critical findings
- Contradictory information
- User-requested deep dives
- High-impact decision points
- Knowledge gaps identified

**Expansion Protocol:**
1. Identify specific areas needing depth
2. Deploy specialized deep-dive agents
3. Focus on primary sources
4. Increase validation rigor
5. Update findings with new evidence

## 📈 RESEARCH QUALITY METRICS

**Quality Assessment Checklist:**
- [ ] **Source Diversity**: Minimum 5 independent sources
- [ ] **Validation Rate**: 80%+ claims cross-referenced
- [ ] **Confidence Threshold**: 70%+ average confidence
- [ ] **Currency Check**: 90%+ information < 2 years old
- [ ] **Actionability Score**: All findings lead to recommendations

## 🚨 RESEARCH ANTI-PATTERNS (FORBIDDEN)

- ❌ "Wikipedia is enough" → NO, multiple authoritative sources required
- ❌ "First page of search results" → NO, deep investigation needed
- ❌ "Trust without verification" → NO, all claims need validation
- ❌ "Data without analysis" → NO, synthesis and insights required
- ❌ "Generic recommendations" → NO, specific actionable guidance needed
- ❌ "Sequential searching" → NO, parallel investigation required

## 🎯 RESEARCH COMPLETION CRITERIA

**Research is complete when:**
✓ Multiple independent sources analyzed (minimum 5)
✓ Information validated through cross-referencing
✓ Patterns synthesized into coherent insights
✓ Confidence levels assigned to all findings
✓ Actionable recommendations provided
✓ Sources properly cited and credited
✓ Limitations and gaps acknowledged

## 📋 FINAL RESEARCH COMMITMENT

**I will execute COMPLETE research protocol:**
- ✅ Deploy multiple agents for parallel investigation
- ✅ Synthesize findings across diverse sources
- ✅ Validate information through cross-referencing
- ✅ Assign confidence levels to all claims
- ✅ Deliver structured, actionable insights
- ✅ Maintain academic rigor in citations

**I will NOT:**
- ❌ Rely on single sources or shallow searches
- ❌ Present unvalidated information as fact
- ❌ Skip synthesis in favor of data dumps
- ❌ Ignore contradictory evidence
- ❌ Provide generic non-actionable findings
- ❌ Research sequentially when parallel is possible

## 🧠 REMEMBER:

This is RESEARCH mode - comprehensive investigation with multi-source validation, pattern synthesis, and actionable insights. Quality comes from source diversity, validation rigor, and synthesis depth.

**Executing comprehensive research protocol NOW...**