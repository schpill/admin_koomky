import { describe, it, expect, vi } from "vitest";
import { searchDocumentsTool } from "../../tools/searchDocuments.js";

describe("searchDocumentsTool", () => {
  it("formats results as text content", async () => {
    const client = {
      search: vi.fn().mockResolvedValue([{ title: "Doc", score: 0.9 }]),
    } as any;

    const result = await searchDocumentsTool(client, "pat", { query: "doc" });

    expect(result.content[0].type).toBe("text");
    expect(result.content[0].text).toContain("Doc");
  });

  it("returns empty JSON array when no results", async () => {
    const client = { search: vi.fn().mockResolvedValue([]) } as any;

    const result = await searchDocumentsTool(client, "pat", { query: "nothing" });

    expect(result.content[0].text).toBe("[]");
  });

  it("throws ZodError when query is empty string", async () => {
    const client = { search: vi.fn() } as any;

    await expect(searchDocumentsTool(client, "pat", { query: "" })).rejects.toThrow();
  });

  it("passes client_id and limit to the client", async () => {
    const client = { search: vi.fn().mockResolvedValue([]) } as any;

    await searchDocumentsTool(client, "pat", {
      query: "contract",
      limit: 10,
      client_id: "00000000-0000-0000-0000-000000000001",
    });

    expect(client.search).toHaveBeenCalledWith(
      "pat",
      "contract",
      10,
      "00000000-0000-0000-0000-000000000001"
    );
  });
});
