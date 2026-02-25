import { describe, it, expect, vi, beforeEach } from "vitest";
import axios from "axios";
import { KoomkyClient } from "../koomkyClient.js";

vi.mock("axios");

describe("KoomkyClient", () => {
  beforeEach(() => {
    vi.resetAllMocks();
  });

  it("calls ask endpoint", async () => {
    const post = vi.fn().mockResolvedValue({ data: { data: { answer: "ok", sources: [], tokens_used: 1, latency_ms: 1 } } });
    (axios.create as any).mockReturnValue({ post });

    const client = new KoomkyClient("http://api:8000", "secret");
    const result = await client.ask("pat", "Q?");

    expect(post).toHaveBeenCalled();
    expect(result.answer).toBe("ok");
  });
});
