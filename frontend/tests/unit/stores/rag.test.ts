import { beforeEach, describe, expect, it, vi } from "vitest";

vi.mock("@/lib/api", () => ({
  apiClient: {
    post: vi.fn(),
    get: vi.fn(),
  },
}));

vi.mock("@/lib/portal", () => ({
  portalApiClient: {
    post: vi.fn(),
    get: vi.fn(),
  },
}));

import { apiClient } from "@/lib/api";
import { useRagStore } from "@/lib/stores/rag";

describe("rag store", () => {
  beforeEach(() => {
    useRagStore.setState({
      messages: [],
      loading: false,
      error: null,
      sources: [],
    });
    vi.clearAllMocks();
  });

  it("askQuestion appends user and assistant messages", async () => {
    (apiClient.post as any).mockResolvedValue({
      data: {
        answer: "ok",
        sources: [{ document_id: "d1", chunk_index: 0, score: 0.9 }],
      },
    });

    await useRagStore.getState().askQuestion("hello");

    const state = useRagStore.getState();
    expect(state.messages.length).toBe(2);
    expect(state.messages[0].role).toBe("user");
    expect(state.messages[1].role).toBe("assistant");
  });

  it("clearHistory empties state", () => {
    useRagStore.setState({
      messages: [
        {
          id: "1",
          role: "user",
          content: "q",
          created_at: new Date().toISOString(),
        },
      ],
      loading: false,
      error: null,
      sources: [{ document_id: "d", chunk_index: 0, score: 0.1 }],
    });

    useRagStore.getState().clearHistory();

    expect(useRagStore.getState().messages).toHaveLength(0);
    expect(useRagStore.getState().sources).toHaveLength(0);
  });
});
