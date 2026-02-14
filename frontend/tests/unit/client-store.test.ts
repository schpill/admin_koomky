import { describe, it, expect, beforeEach, vi } from "vitest";
import { useClientStore } from "../../lib/stores/clients";

// Mock apiClient
vi.mock("../../lib/api", () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
  },
}));

import { apiClient } from "../../lib/api";

describe("useClientStore", () => {
  beforeEach(() => {
    useClientStore.setState({
      clients: [],
      pagination: null,
      isLoading: false,
      error: null,
    });
    vi.clearAllMocks();
  });

  it("should fetch clients and update state", async () => {
    const mockData = {
      data: {
        data: [{ id: "1", name: "Client 1" }],
        meta: { current_page: 1, last_page: 1, total: 1 },
      },
    };
    (apiClient.get as any).mockResolvedValue(mockData);

    await useClientStore.getState().fetchClients();

    const state = useClientStore.getState();
    expect(state.clients).toHaveLength(1);
    expect(state.clients[0].name).toBe("Client 1");
    expect(state.pagination?.total).toBe(1);
  });

  it("should handle create client", async () => {
    const newClient = { id: "2", name: "New Client" };
    (apiClient.post as any).mockResolvedValue({ data: newClient });

    await useClientStore.getState().createClient({ name: "New Client" });

    const state = useClientStore.getState();
    expect(state.clients[0].name).toBe("New Client");
  });
});
