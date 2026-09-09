# auth.md

Authentication and access policy for AI agents, crawlers, and LLM applications interacting with victor-alonso.es.

## Audience and Scope
This document outlines authentication requirements and machine access policies for `https://www.victor-alonso.es`.

## Authentication Policy
- **Public & Anonymous Access:** All public tools (WebMCP tools, SEO Page Analyzer, Log Analyzer, Schema Generator) and content representation endpoints (HTML, Markdown content negotiation, `llms.txt`) are free and open. No API keys, OAuth tokens, or account registration are required for public agent usage.
- **Supported Identity Types:** `["anonymous"]`
- **Credential Types:** `["none"]`
- **Protected Resources:** For custom, high-volume programmatic batch analysis or dedicated consulting endpoints, authentication is coordinated directly via OAuth or mutual agreement.

## Endpoints
- **Agent Discovery:** `https://www.victor-alonso.es/.well-known/agent-skills/index.json`
- **API Catalog:** `https://www.victor-alonso.es/.well-known/api-catalog`
- **MCP Server Card:** `https://www.victor-alonso.es/.well-known/mcp/server-card.json`
- **A2A Agent Card:** `https://www.victor-alonso.es/.well-known/agent-card.json`
- **Contact & Provisioning:** `soy@victor-alonso.es` | `https://www.victor-alonso.es/contacto/`

## Rate Limits and Etiquette
- Please maintain a polite crawl rate (< 20 requests per minute).
- Respect directives declared in `robots.txt` and `Content-Signal`.
