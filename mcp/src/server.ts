import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import { SSEServerTransport } from "@modelcontextprotocol/sdk/server/sse.js";
import { createMcpExpressApp } from "@modelcontextprotocol/sdk/server/express.js";
import * as z from "zod/v4";
import { KoomkyClient } from "./koomkyClient.js";
import { searchDocumentsTool } from "./tools/searchDocuments.js";
import { askQuestionTool } from "./tools/askQuestion.js";
import { listTopicsTool } from "./tools/listTopics.js";
import { getDocumentContextTool } from "./tools/getDocumentContext.js";

export async function buildServer() {
  const server = new McpServer({
    name: "koomky-rag-mcp",
    version: "1.0.0"
  });

  const baseUrl = process.env.MCP_KOOMKY_URL ?? "http://api:8000";
  const secret = process.env.MCP_API_SECRET ?? "";
  const pat = process.env.MCP_USER_PAT ?? "";
  const client = new KoomkyClient(baseUrl, secret);

  server.registerTool(
    "search_documents",
    {
      description: "Recherche semantique dans les documents de la GED",
      inputSchema: {
        query: z.string().min(1),
        limit: z.number().int().min(1).max(20).optional(),
        client_id: z.string().uuid().optional()
      }
    },
    async ({ query, limit, client_id }) => searchDocumentsTool(client, pat, { query, limit, client_id })
  );

  server.registerTool(
    "ask_question",
    {
      description: "Pose une question et retourne une reponse RAG",
      inputSchema: {
        question: z.string().min(1).max(1000),
        client_id: z.string().uuid().optional()
      }
    },
    async ({ question, client_id }) => askQuestionTool(client, pat, { question, client_id })
  );

  server.registerTool(
    "list_topics",
    {
      description: "Liste les thematiques disponibles",
      inputSchema: {}
    },
    async () => listTopicsTool(client, pat, {})
  );

  server.registerTool(
    "get_document_context",
    {
      description: "Retourne le contenu d'un document",
      inputSchema: {
        document_id: z.string().uuid()
      }
    },
    async ({ document_id }) => getDocumentContextTool(client, pat, { document_id })
  );

  return server;
}

export async function startServer() {
  const server = await buildServer();
  const transport = (process.env.MCP_TRANSPORT ?? "stdio").toLowerCase();

  if (transport === "sse") {
    const app = createMcpExpressApp();
    const transports: Record<string, SSEServerTransport> = {};

    app.get("/sse", async (_req, res) => {
      const sseTransport = new SSEServerTransport("/messages", res);
      transports[sseTransport.sessionId] = sseTransport;

      sseTransport.onclose = () => {
        delete transports[sseTransport.sessionId];
      };

      await server.connect(sseTransport);
    });

    app.post("/messages", async (req, res) => {
      const sessionId = req.query.sessionId;
      if (typeof sessionId !== "string" || !transports[sessionId]) {
        res.status(400).json({ error: "Invalid session" });
        return;
      }

      await transports[sessionId].handlePostMessage(req, res, req.body);
    });

    const port = Number(process.env.MCP_PORT ?? 3100);
    app.listen(port, () => {
      // eslint-disable-next-line no-console
      console.log(`MCP SSE server listening on :${port}`);
    });

    return;
  }

  const stdio = new StdioServerTransport();
  await server.connect(stdio);
}
