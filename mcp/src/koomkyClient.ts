import axios, { AxiosInstance } from "axios";

export interface RagAskResult {
  answer: string;
  sources: Array<{ document_id: string; title?: string; chunk_index: number; score: number }>;
  tokens_used: number;
  latency_ms: number;
}

export class KoomkyClient {
  private readonly http: AxiosInstance;

  constructor(
    private readonly baseUrl: string,
    private readonly mcpSecret: string
  ) {
    this.http = axios.create({
      baseURL: `${baseUrl.replace(/\/$/, "")}/api/v1`,
      timeout: 20000,
      headers: {
        "X-MCP-Secret": mcpSecret,
        Accept: "application/json"
      }
    });
  }

  private authHeaders(pat: string): Record<string, string> {
    return {
      Authorization: `Bearer ${pat}`
    };
  }

  async ask(pat: string, question: string, clientId?: string): Promise<RagAskResult> {
    const response = await this.http.post(
      "/mcp/rag/ask",
      { question, client_id: clientId },
      { headers: this.authHeaders(pat) }
    );

    return response.data.data as RagAskResult;
  }

  async search(pat: string, query: string, limit = 5, clientId?: string): Promise<any[]> {
    const response = await this.http.get("/mcp/rag/search", {
      headers: this.authHeaders(pat),
      params: { q: query, limit, client_id: clientId }
    });

    return response.data.data ?? [];
  }

  async listTopics(pat: string): Promise<string[]> {
    const response = await this.http.get("/mcp/rag/status", {
      headers: this.authHeaders(pat)
    });

    const docs: any[] = response.data.data?.data ?? [];
    const topics = docs
      .flatMap((d: any) => [d.title, d.mime_type])
      .filter((value: unknown): value is string => typeof value === "string" && value.length > 0);

    return [...new Set(topics)].sort((a, b) => a.localeCompare(b));
  }

  async getDocumentContext(pat: string, documentId: string): Promise<any[]> {
    const response = await this.http.get("/mcp/rag/search", {
      headers: this.authHeaders(pat),
      params: { q: "*", limit: 20 }
    });

    const chunks = response.data.data ?? [];

    return chunks.filter((chunk: any) => chunk.document_id === documentId);
  }
}
