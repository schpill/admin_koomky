# Claude Desktop MCP Configuration

## Transport stdio

Ajouter dans `claude_desktop_config.json`:

```json
{
  "mcpServers": {
    "koomky-rag": {
      "command": "node",
      "args": ["/absolute/path/to/mcp/dist/index.js"],
      "env": {
        "MCP_TRANSPORT": "stdio",
        "MCP_KOOMKY_URL": "http://localhost:8000",
        "MCP_API_SECRET": "<secret>",
        "MCP_USER_PAT": "<personal-access-token-with-mcp-read>"
      }
    }
  }
}
```

## Transport SSE

- URL connexion: `GET http://localhost:3100/sse`
- Endpoint messages: `POST http://localhost:3100/messages`

## Tools disponibles

- `search_documents`
- `ask_question`
- `list_topics`
- `get_document_context`
