import { describe, it, expect, vi } from "vitest";
import { getDocumentContextTool } from "../../tools/getDocumentContext.js";

describe("getDocumentContextTool", () => {
  it("returns chunks as JSON text content", async () => {
    const chunks = [
      { chunk_index: 0, content: "Premier paragraphe du document.", token_count: 6 },
      { chunk_index: 1, content: "Second paragraphe du document.", token_count: 5 },
    ];
    const client = { getDocumentContext: vi.fn().mockResolvedValue(chunks) } as any;
    const docId = "00000000-0000-0000-0000-000000000042";

    const result = await getDocumentContextTool(client, "pat", { document_id: docId });

    expect(result.content[0].type).toBe("text");
    const parsed = JSON.parse(result.content[0].text);
    expect(parsed).toHaveLength(2);
    expect(parsed[0].chunk_index).toBe(0);
    expect(parsed[1].content).toContain("Second");
  });

  it("returns empty array JSON when document has no chunks", async () => {
    const client = { getDocumentContext: vi.fn().mockResolvedValue([]) } as any;

    const result = await getDocumentContextTool(client, "pat", {
      document_id: "00000000-0000-0000-0000-000000000001",
    });

    expect(JSON.parse(result.content[0].text)).toEqual([]);
  });

  it("throws ZodError when document_id is not a UUID", async () => {
    const client = { getDocumentContext: vi.fn() } as any;

    await expect(
      getDocumentContextTool(client, "pat", { document_id: "not-a-uuid" })
    ).rejects.toThrow();
  });
});
