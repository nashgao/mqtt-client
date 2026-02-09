# Book Navigation Template

Template for generating book-style navigation with table of contents, chapter links, and reading progress indicators.

## Main Navigation Hub Template

```markdown
# {{book.title}}

{{book.description}}

## 📚 Table of Contents

{{#each chapters}}
### [Chapter {{number}}: {{title}}](./{{id}}/README.md)
{{#if description}}
{{description}}
{{/if}}

{{#if sections}}
{{#each sections}}
- [{{title}}](./{{../id}}/{{file}})
{{/each}}
{{/if}}

---
{{/each}}

## 🚀 Quick Start

- **New to {{book.title}}?** Start with [Chapter 1: Introduction](./01-introduction/README.md)
- **Setting up?** Jump to [Chapter 2: Getting Started](./02-getting-started/README.md)
- **API Reference?** See [Chapter 5: API Documentation](./05-api-reference/README.md)
- **Contributing?** Check [Chapter 9: Contributing Guidelines](./09-contributing/README.md)

## 📖 Reading Paths

### For Developers
1. [Introduction](./01-introduction/README.md) → 
2. [Getting Started](./02-getting-started/README.md) → 
3. [Core Concepts](./03-core-concepts/README.md) → 
4. [API Reference](./05-api-reference/README.md)

### For System Administrators
1. [Introduction](./01-introduction/README.md) → 
2. [Architecture](./06-architecture/README.md) → 
3. [Deployment Guide](./08-guides/deployment.md) → 
4. [Monitoring](./08-guides/monitoring.md)

### For Contributors
1. [Introduction](./01-introduction/README.md) → 
2. [Architecture](./06-architecture/README.md) → 
3. [Contributing](./09-contributing/README.md) → 
4. [Module Docs](./07-modules/README.md)

## 🔍 Search Documentation

Use the search box above or press `/` to search across all documentation.

## 📊 Documentation Stats

- **Chapters**: {{chapter_count}}
- **Total Pages**: {{page_count}}
- **Examples**: {{example_count}}
- **Last Updated**: {{last_updated}}
- **Version**: {{book.version}}

## 🤝 Need Help?

- 📧 Email: [{{support_email}}](mailto:{{support_email}})
- 💬 Discord: [Join our community]({{discord_url}})
- 🐛 Issues: [Report a bug]({{github_url}}/issues)
- 💡 Discussions: [Ask questions]({{github_url}}/discussions)

---

*Generated with Claude Code Documentation System v2.0*
```

## Chapter Navigation Template

```markdown
{{#if breadcrumbs}}
[Documentation](../README.md) > [{{chapter.title}}](./README.md){{#if section}} > {{section.title}}{{/if}}
{{/if}}

# {{chapter.title}}

{{#if chapter.toc}}
## On This Page

{{#each sections}}
- [{{title}}](#{{anchor}})
  {{#each subsections}}
  - [{{title}}](#{{anchor}})
  {{/each}}
{{/each}}
{{/if}}

{{content}}

---

## Navigation

{{#if prev_chapter}}
← Previous: [{{prev_chapter.title}}](../{{prev_chapter.id}}/README.md)
{{/if}}

{{#if next_chapter}}
Next: [{{next_chapter.title}}](../{{next_chapter.id}}/README.md) →
{{/if}}

{{#if related_chapters}}
## Related Chapters

{{#each related_chapters}}
- [{{title}}](../{{id}}/README.md) - {{description}}
{{/each}}
{{/if}}

{{#if progress_indicator}}
## Reading Progress

Chapter {{chapter.number}} of {{total_chapters}} ({{progress_percentage}}% complete)

[{{progress_bar}}]
{{/if}}
```

## Section Navigation Template

```markdown
{{#if breadcrumbs}}
[Documentation](../../README.md) > [{{chapter.title}}](../README.md) > {{section.title}}
{{/if}}

# {{section.title}}

{{#if section.toc}}
## Contents

{{#each topics}}
- [{{title}}](#{{anchor}})
{{/each}}
{{/if}}

{{content}}

---

## Within This Chapter

{{#each chapter.sections}}
{{#if (eq file ../current_section)}}
- **{{title}}** (current)
{{else}}
- [{{title}}](./{{file}})
{{/if}}
{{/each}}

## Chapter Navigation

← [Back to {{chapter.title}}](./README.md)

{{#if prev_section}}
← Previous: [{{prev_section.title}}](./{{prev_section.file}})
{{/if}}

{{#if next_section}}
Next: [{{next_section.title}}](./{{next_section.file}}) →
{{/if}}
```

## Cross-Reference Template

```markdown
{{#if see_also}}
## See Also

{{#each see_also}}
- [{{title}}]({{path}}) - {{description}}
{{/each}}
{{/if}}

{{#if external_links}}
## External Resources

{{#each external_links}}
- [{{title}}]({{url}}) - {{description}}
{{/each}}
{{/if}}

{{#if examples}}
## Related Examples

{{#each examples}}
- [{{title}}]({{path}}) - {{description}}
{{/each}}
{{/if}}
```

## Search Results Template

```markdown
# Search Results for "{{query}}"

Found {{result_count}} results across {{chapter_count}} chapters.

{{#each results}}
## [{{chapter.title}}]({{chapter.path}}) > [{{section.title}}]({{section.path}})

{{excerpt}}

**Relevance**: {{relevance_score}}% | **Location**: {{location}}

---
{{/each}}

{{#if no_results}}
No results found for "{{query}}".

### Try:
- Using different keywords
- Checking spelling
- Using broader terms
- Browsing the [Table of Contents](./README.md)
{{/if}}
```

## Index Template

```markdown
# Index

## A-Z Index of Topics

{{#each index_entries}}
### {{letter}}

{{#each entries}}
- **{{term}}**: {{#each references}}[{{location}}]({{path}}){{#unless @last}}, {{/unless}}{{/each}}
{{/each}}

{{/each}}

## Category Index

{{#each categories}}
### {{name}}

{{#each terms}}
- [{{term}}]({{path}})
{{/each}}

{{/each}}

## API Index

{{#each api_items}}
### {{type}}: `{{name}}`

- **Location**: [{{chapter}}]({{path}})
- **Description**: {{description}}
- **Since**: v{{since}}

{{/each}}
```

## Footer Template

```markdown
---

<div align="center">

### Quick Links

[Table of Contents]({{toc_path}}) | 
[Index]({{index_path}}) | 
[Search]({{search_path}}) | 
[Glossary]({{glossary_path}})

### Documentation Info

Version {{version}} | 
Last Updated: {{last_updated}} | 
[Edit on GitHub]({{edit_url}})

### Get Help

[Report Issue]({{issue_url}}) | 
[Ask Question]({{discussion_url}}) | 
[Join Community]({{community_url}})

---

*Built with Claude Code Documentation System*

</div>
```

## Progress Bar Component

```markdown
## Your Progress

**Current Chapter**: {{current_chapter.title}}
**Completed**: {{completed_chapters}}/{{total_chapters}} chapters

### Progress Overview

{{#each chapters}}
{{#if completed}}
✅ {{title}}
{{else if current}}
📖 **{{title}}** ← You are here
{{else}}
⭕ {{title}}
{{/if}}
{{/each}}

**Estimated time remaining**: {{time_remaining}} minutes
```

## Responsive Navigation CSS

```css
/* For documentation sites that support custom CSS */
.doc-navigation {
  position: sticky;
  top: 0;
  background: white;
  border-bottom: 1px solid #e1e4e8;
  padding: 1rem;
  z-index: 100;
}

.chapter-nav {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin: 2rem 0;
  padding: 1rem;
  background: #f6f8fa;
  border-radius: 6px;
}

.breadcrumbs {
  color: #586069;
  margin-bottom: 1rem;
}

.progress-bar {
  height: 4px;
  background: #e1e4e8;
  border-radius: 2px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: #28a745;
  transition: width 0.3s ease;
}

@media (max-width: 768px) {
  .chapter-nav {
    flex-direction: column;
    gap: 1rem;
  }
}
```

## Usage in Templates

### Basic Navigation
```yaml
navigation:
  type: "book"
  template: "book-navigation.md"
  options:
    breadcrumbs: true
    toc: true
    progress: true
```

### Custom Navigation
```javascript
// In generation script
const navigation = generateNavigation({
  chapters: bookConfig.chapters,
  currentChapter: currentId,
  template: 'book-navigation',
  options: {
    showProgress: true,
    showBreadcrumbs: true,
    tocDepth: 3
  }
});
```

### Dynamic Updates
```javascript
// For web-based documentation
document.addEventListener('DOMContentLoaded', () => {
  updateProgress();
  highlightCurrentSection();
  generateMiniToc();
});
```