# Chapter Template

Standardized template for book chapters ensuring consistent structure, natural flow, and proper integration with the book navigation system.

## Chapter Structure Template

```markdown
---
chapter: {{chapter.number}}
title: {{chapter.title}}
description: {{chapter.description}}
estimated_time: {{chapter.estimated_time}}
difficulty: {{chapter.difficulty}}
prerequisites: {{chapter.prerequisites}}
---

# Chapter {{chapter.number}}: {{chapter.title}}

## What You'll Learn

By the end of this chapter, you will:

{{#each learning_objectives}}
- {{this}}
{{/each}}

{{#if prerequisites}}
## Prerequisites

Before starting this chapter, you should:

{{#each prerequisites}}
- {{this}}
{{/each}}

{{#if prerequisite_chapters}}
Make sure you've completed:
{{#each prerequisite_chapters}}
- [Chapter {{number}}: {{title}}](../{{id}}/README.md)
{{/each}}
{{/if}}
{{/if}}

## Overview

{{chapter.overview}}

{{#if key_concepts}}
### Key Concepts

{{#each key_concepts}}
- **{{term}}**: {{definition}}
{{/each}}
{{/if}}

---

## {{section_1.title}}

{{section_1.content}}

{{#if section_1.example}}
### Example

```{{section_1.example.language}}
{{section_1.example.code}}
```

{{#if section_1.example.explanation}}
{{section_1.example.explanation}}
{{/if}}
{{/if}}

{{#if section_1.try_it}}
### Try It Yourself

{{section_1.try_it.instructions}}

<details>
<summary>Solution</summary>

```{{section_1.try_it.solution.language}}
{{section_1.try_it.solution.code}}
```

{{section_1.try_it.solution.explanation}}

</details>
{{/if}}

---

## {{section_2.title}}

{{section_2.content}}

{{#if section_2.important}}
> **Important**: {{section_2.important}}
{{/if}}

{{#if section_2.warning}}
> **Warning**: {{section_2.warning}}
{{/if}}

{{#if section_2.tip}}
> **Tip**: {{section_2.tip}}
{{/if}}

---

## {{section_3.title}}

{{section_3.content}}

{{#if section_3.comparison}}
### Comparison

| {{section_3.comparison.aspect}} | {{section_3.comparison.option1}} | {{section_3.comparison.option2}} |
|---|---|---|
{{#each section_3.comparison.rows}}
| {{aspect}} | {{option1}} | {{option2}} |
{{/each}}
{{/if}}

---

## Putting It All Together

{{synthesis.content}}

{{#if synthesis.complete_example}}
### Complete Example

```{{synthesis.complete_example.language}}
{{synthesis.complete_example.code}}
```

This example demonstrates:
{{#each synthesis.complete_example.demonstrates}}
- {{this}}
{{/each}}
{{/if}}

## Practice Exercises

{{#each exercises}}
### Exercise {{number}}: {{title}}

**Difficulty**: {{difficulty}}
**Estimated Time**: {{time}}

{{description}}

{{#if starter_code}}
**Starter Code**:
```{{starter_code.language}}
{{starter_code.code}}
```
{{/if}}

{{#if hints}}
<details>
<summary>Hints</summary>

{{#each hints}}
{{@index}}. {{this}}
{{/each}}

</details>
{{/if}}

<details>
<summary>Solution</summary>

```{{solution.language}}
{{solution.code}}
```

**Explanation**: {{solution.explanation}}

</details>

---
{{/each}}

## Knowledge Check

Test your understanding:

{{#each quiz_questions}}
### Question {{number}}

{{question}}

{{#each options}}
- {{#if correct}}**{{/if}}{{text}}{{#if correct}}**{{/if}}
{{/each}}

<details>
<summary>Answer</summary>

{{explanation}}

</details>

{{/each}}

## Chapter Summary

In this chapter, you learned:

{{#each summary_points}}
- ✅ {{this}}
{{/each}}

{{#if key_takeaways}}
### Key Takeaways

{{#each key_takeaways}}
1. **{{point}}**: {{explanation}}
{{/each}}
{{/if}}

## What's Next

{{next_chapter.preview}}

In the next chapter, you'll learn:
{{#each next_chapter.topics}}
- {{this}}
{{/each}}

**Ready to continue?** → [Chapter {{next_chapter.number}}: {{next_chapter.title}}](../{{next_chapter.id}}/README.md)

## Additional Resources

{{#if additional_resources}}
### Further Reading

{{#each additional_resources.reading}}
- [{{title}}]({{url}}) - {{description}}
{{/each}}

{{#if additional_resources.videos}}
### Video Tutorials

{{#each additional_resources.videos}}
- [{{title}}]({{url}}) - {{duration}}
{{/each}}
{{/if}}

{{#if additional_resources.tools}}
### Tools & References

{{#each additional_resources.tools}}
- [{{name}}]({{url}}) - {{description}}
{{/each}}
{{/if}}
{{/if}}

## Need Help?

If you're stuck or have questions:

- 💬 Ask in our [Discord community]({{discord_url}})
- 🔍 Search [existing discussions]({{discussions_url}})
- 📧 Email support: [{{support_email}}](mailto:{{support_email}})
- 🐛 Report issues: [GitHub Issues]({{issues_url}})

---

{{> navigation}}
```

## Chapter Types Templates

### Getting Started Chapter

```markdown
# Chapter 2: Getting Started

## ⏱️ Time Estimate: 15 minutes

Welcome! This chapter will get you up and running with {{product_name}} in just 15 minutes.

## What We'll Build

By the end of this chapter, you'll have:
- ✅ {{product_name}} installed and configured
- ✅ Your first working example running
- ✅ Understanding of core concepts
- ✅ Next steps for your journey

## Step 1: Installation (3 minutes)

### Prerequisites Check

First, make sure you have:
- [ ] {{prerequisite_1}}
- [ ] {{prerequisite_2}}
- [ ] {{prerequisite_3}}

### Install {{product_name}}

{{#each platforms}}
#### {{name}}

```bash
{{install_command}}
```

Verify installation:
```bash
{{verify_command}}
```

You should see: `{{expected_output}}`

{{/each}}

## Step 2: Hello World (5 minutes)

Let's create your first {{product_name}} project:

### Create Project

```bash
{{create_command}}
```

### Write Your First Code

Create `{{first_file}}`:

```{{language}}
{{hello_world_code}}
```

### Run It

```bash
{{run_command}}
```

🎉 **Success!** You should see: `{{expected_output}}`

## Step 3: Understanding What Happened (4 minutes)

Let's break down what just happened:

{{#each concepts}}
### {{title}}

{{explanation}}

```{{language}}
{{code_highlight}}
```

{{/each}}

## Step 4: Next Steps (3 minutes)

### Immediate Next Steps

Now that you have {{product_name}} running:

1. **Explore Examples**: Check out [more examples](../examples/)
2. **Read Core Concepts**: Understand [how it works](../03-core-concepts/)
3. **Join Community**: Get help in [Discord]({{discord_url}})

### Your Learning Path

Based on your goals:

{{#each learning_paths}}
#### If you want to {{goal}}:
1. Read [{{step1.title}}]({{step1.path}})
2. Try [{{step2.title}}]({{step2.path}})
3. Build [{{step3.title}}]({{step3.path}})

{{/each}}

## Troubleshooting

### Common Issues

{{#each common_issues}}
#### {{issue}}

**Solution**: {{solution}}

{{/each}}

### Still Stuck?

- Check [FAQ](../11-troubleshooting/README.md)
- Search [issues]({{github_issues}})
- Ask in [Discord]({{discord_url}})

---

**Congratulations!** 🎊 You've successfully completed the Getting Started guide!

→ Continue to [Chapter 3: Core Concepts](../03-core-concepts/README.md)
```

### API Reference Chapter

```markdown
# Chapter 5: API Reference

## Overview

{{api.description}}

Base URL: `{{api.base_url}}`
Version: `{{api.version}}`

## Authentication

{{auth.description}}

### API Key Authentication

```bash
curl -H "X-API-Key: {{api_key}}" {{base_url}}/endpoint
```

### OAuth 2.0

```javascript
const token = await getOAuthToken({
  client_id: '{{client_id}}',
  client_secret: '{{client_secret}}',
  scope: '{{scope}}'
});
```

## Endpoints

{{#each endpoints}}
### {{method}} {{path}}

{{description}}

#### Parameters

{{#if path_params}}
**Path Parameters**
| Name | Type | Required | Description |
|------|------|----------|-------------|
{{#each path_params}}
| `{{name}}` | {{type}} | {{required}} | {{description}} |
{{/each}}
{{/if}}

{{#if query_params}}
**Query Parameters**
| Name | Type | Required | Description |
|------|------|----------|-------------|
{{#each query_params}}
| `{{name}}` | {{type}} | {{required}} | {{description}} |
{{/each}}
{{/if}}

{{#if body}}
**Request Body**
```json
{{body.example}}
```

**Schema**
```typescript
{{body.schema}}
```
{{/if}}

#### Response

**Success Response ({{success.status}})**
```json
{{success.example}}
```

{{#if error_responses}}
**Error Responses**
{{#each error_responses}}
- **{{status}}**: {{description}}
  ```json
  {{example}}
  ```
{{/each}}
{{/if}}

#### Examples

{{#each examples}}
**{{title}}**

{{#if curl}}
```bash
{{curl}}
```
{{/if}}

{{#if javascript}}
```javascript
{{javascript}}
```
{{/if}}

{{#if python}}
```python
{{python}}
```
{{/if}}

{{/each}}

---

{{/each}}

## Rate Limiting

{{rate_limiting.description}}

- **Rate Limit**: {{rate_limiting.requests}} requests per {{rate_limiting.window}}
- **Headers**: 
  - `X-RateLimit-Limit`: Total allowed requests
  - `X-RateLimit-Remaining`: Requests remaining
  - `X-RateLimit-Reset`: Time when limit resets

## Error Handling

### Error Response Format

```json
{
  "error": {
    "code": "ERROR_CODE",
    "message": "Human readable message",
    "details": {}
  }
}
```

### Common Error Codes

{{#each error_codes}}
- **{{code}}**: {{description}}
{{/each}}

## SDKs & Libraries

{{#each sdks}}
### {{language}}

Installation:
```bash
{{install_command}}
```

Quick Start:
```{{language_lower}}
{{quick_start}}
```

[Full Documentation]({{docs_url}})

{{/each}}

## Webhooks

{{#if webhooks}}
### Available Webhooks

{{#each webhooks}}
#### {{event}}

{{description}}

**Payload**:
```json
{{payload_example}}
```

{{/each}}

### Webhook Security

Verify webhook signatures:

```javascript
{{webhook_verification_code}}
```
{{/if}}

## Testing

### Test Environment

Base URL: `{{test.base_url}}`

Test API Keys:
- Public: `{{test.public_key}}`
- Secret: `{{test.secret_key}}`

### Postman Collection

[Download Postman Collection]({{postman_collection_url}})

### OpenAPI Specification

[Download OpenAPI Spec]({{openapi_spec_url}})

---

→ Next: [Chapter 6: Architecture](../06-architecture/README.md)
```

## Usage Instructions

### In Unified Generator

```javascript
// When generating a chapter
const chapter = generateChapter({
  template: 'chapter-template',
  data: {
    chapter: chapterConfig,
    sections: chapterContent,
    navigation: navigationData
  }
});
```

### Custom Chapter Types

```yaml
# In book.yaml
chapters:
  - id: "02-getting-started"
    template: "getting-started-chapter"
    
  - id: "05-api"
    template: "api-reference-chapter"
    
  - id: "custom"
    template: "custom-chapter-template"
```

### Template Variables

All templates support these variables:
- `{{book.*}}` - Book configuration
- `{{chapter.*}}` - Current chapter data
- `{{navigation.*}}` - Navigation elements
- `{{variables.*}}` - Custom variables from book.yaml