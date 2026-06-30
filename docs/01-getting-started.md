# Getting Started

Welcome to the theme documentation. This page will walk you through the basics.

## Requirements

| Requirement | Version |
|-------------|---------|
| WordPress   | 6.4+    |
| PHP         | 8.1+    |
| Node.js     | 18+     |

## Installation

Clone or download the theme into your `wp-content/themes/` directory:

```bash
cd wp-content/themes/
git clone https://github.com/your-org/your-theme.git
```

Then activate it via **Appearance → Themes**.

## Adding documentation

Place Markdown files in your theme's `/docs/` directory. Prefix filenames with numbers to control tab order:

```
docs/
├── assets/
│   └── screenshot.png
├── _docs.json          ← optional: custom labels & icons
├── 01-getting-started.md
├── 02-configuration.md
└── 03-changelog.md
```

> **Tip:** Reference images using a relative `assets/` path and they'll resolve automatically.  
> Example: `![Screenshot](assets/screenshot.png)`

## Next steps

Head over to the **Configuration** tab to explore available options.
