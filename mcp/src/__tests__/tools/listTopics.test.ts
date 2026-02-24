import { describe, it, expect, vi } from "vitest";
import { listTopicsTool } from "../../tools/listTopics.js";

describe("listTopicsTool", () => {
  it("returns topics sorted string", async () => {
    const client = { listTopics: vi.fn().mockResolvedValue(["A", "B"]) } as any;

    const result = await listTopicsTool(client, "pat", {});

    expect(result.content[0].text).toBe("A\nB");
  });

  it("returns fallback message when no topics available", async () => {
    const client = { listTopics: vi.fn().mockResolvedValue([]) } as any;

    const result = await listTopicsTool(client, "pat", {});

    expect(result.content[0].text).toBe("No topics available");
  });

  it("content type is text", async () => {
    const client = { listTopics: vi.fn().mockResolvedValue(["Topic 1"]) } as any;

    const result = await listTopicsTool(client, "pat", {});

    expect(result.content[0].type).toBe("text");
  });
});
