# auth.md

Welcome to Víctor Alonso SEO. This service provides automated discovery and agent-ready capabilities for AI agents.

## Agent Registration

AI agents and automated systems can access public resources and tools anonymously without prior registration.

### Registration Endpoint
- **Register URI:** `https://www.victor-alonso.es/api`
- **Claim URI:** `https://www.victor-alonso.es/contacto/`
- **Identity Types Supported:** `anonymous`
- **Credential Types Supported:** `none`

### How to Authenticate
Public tools and endpoints do not require credentials:
- **Authorization Header:** Not required for public access
- **Authentication Method:** Anonymous agent access

### Machine Discovery
- **OAuth Protected Resource:** `https://www.victor-alonso.es/.well-known/oauth-protected-resource`
- **OAuth Authorization Server:** `https://www.victor-alonso.es/.well-known/oauth-authorization-server`
- **Agent Skills:** `https://www.victor-alonso.es/.well-known/agent-skills/index.json`
- **API Catalog:** `https://www.victor-alonso.es/.well-known/api-catalog`
- **MCP Server Card:** `https://www.victor-alonso.es/.well-known/mcp/server-card.json`
- **ARD Manifest:** `https://www.victor-alonso.es/.well-known/ai-catalog.json`
