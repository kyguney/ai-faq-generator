# AI FAQ Generator

**Contributors:** WPBits  
**Tags:** faq, ai, openai, gutenberg, schema  
**Requires at least:** 6.0  
**Requires PHP:** 7.4  
**Tested up to:** 6.5  
**Stable tag:** 1.0.0  
**License:** GPL-2.0-or-later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

AI-powered FAQ generation for WordPress — generate FAQ content from any post or page using OpenAI-compatible APIs, with built-in Gutenberg block and SEO schema support.

---

## Description

AI FAQ Generator automatically creates frequently asked question content from your existing WordPress posts and pages. Connect any OpenAI-compatible API provider, hit generate, and get structured FAQ content ready for display.

**Features:**

- **AI-Powered Generation** — Generate FAQs from post title and content using any OpenAI-compatible provider (OpenAI, OpenRouter, Ollama, DeepSeek, LocalAI, LM Studio)
- **Gutenberg Block** — Display FAQs in posts/pages using the built-in FAQ block with accordion style
- **SEO Schema** — Automatically output FAQPage JSON-LD structured data for search engine visibility
- **Configurable** — Control model, temperature, and FAQ count from the settings panel
- **Developer Friendly** — Pluggable AI provider architecture, service-based design, comprehensive test coverage

---

## Installation

1. Upload the `ai-faq-generator` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **AI FAQ Generator → Settings**
4. Enter your API key and configure your provider settings
5. Open any post/page editor to start generating FAQs

---

## Frequently Asked Questions

### What API providers are supported?

Any OpenAI-compatible API provider — OpenAI, OpenRouter, Ollama, DeepSeek, LocalAI, and LM Studio are all supported.

### Do I need an API key?

Yes, you'll need an API key from your chosen provider. The plugin settings let you configure the endpoint URL and authentication.

### Does this work with the WordPress block editor?

Yes, the plugin includes a dedicated FAQ Gutenberg block for displaying generated FAQs in posts and pages.

### What is the free tier limit?

Free users can generate up to 5 FAQs per generation. Upgrade to Pro for unlimited FAQ generation and additional features.

---

## Architecture

```
ai-faq-generator/
├── admin/                    # Admin settings pages
├── blocks/                   # Gutenberg block
├── includes/
│   ├── interfaces/          # AI provider interface
│   ├── services/            # Prompt builder, response parser
│   └── class-loader.php     # Autoloader
├── tests/                    # PHPUnit test suite
└── ai-faq-generator.php      # Main plugin file
```

**Key Components:**

- **AIProviderInterface** (`includes/interfaces/class-ai-provider-interface.php`) — Pluggable interface for AI service providers
- **OpenAIClient** — OpenAI-compatible API client implementation
- **Prompt_Builder** — Stateless service that constructs prompt strings from post data
- **FAQ_Generator** — Orchestrates the generation workflow using provider + builder

---

## Changelog

### 1.0.0
- Initial release
- AI provider interface architecture
- OpenAI-compatible API client
- Prompt builder service
- Gutenberg FAQ block
- FAQPage JSON-LD schema output
- WordPress REST API settings endpoints

---

## Upgrade Notice

### 1.0.0
Initial public release.