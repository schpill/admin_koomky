import { describe, it, expect, vi } from "vitest";
import { askQuestionTool } from "../../tools/askQuestion.js";

describe("askQuestionTool", () => {
  it("returns answer with sources", async () => {
    const client = {
      ask: vi.fn().mockResolvedValue({
        answer: "Oui",
        sources: [{ document_id: "d1", chunk_index: 0, score: 0.8, title: "CGV" }],
      }),
    } as any;

    const result = await askQuestionTool(client, "pat", { question: "Q" });

    expect(result.content[0].text).toContain("Oui");
    expect(result.content[0].text).toContain("Sources");
    expect(result.content[0].text).toContain("CGV#0");
  });

  it("renders empty sources section when no sources returned", async () => {
    const client = {
      ask: vi.fn().mockResolvedValue({ answer: "Je ne sais pas", sources: [] }),
    } as any;

    const result = await askQuestionTool(client, "pat", { question: "?" });

    expect(result.content[0].text).toContain("Je ne sais pas");
    expect(result.content[0].text).toContain("Sources:");
  });

  it("throws ZodError when question is empty", async () => {
    const client = { ask: vi.fn() } as any;

    await expect(askQuestionTool(client, "pat", { question: "" })).rejects.toThrow();
  });

  it("throws ZodError when question exceeds 1000 chars", async () => {
    const client = { ask: vi.fn() } as any;

    await expect(
      askQuestionTool(client, "pat", { question: "a".repeat(1001) })
    ).rejects.toThrow();
  });
});
