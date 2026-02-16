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

  it("should fetch a single client and set currentClient", async () => {
    const client = { id: "10", name: "Acme" };
    (apiClient.get as any).mockResolvedValue({ data: client });

    await useClientStore.getState().fetchClient("10");

    const state = useClientStore.getState();
    expect(state.currentClient?.id).toBe("10");
    expect(state.error).toBeNull();
  });

  it("should update a client in list and currentClient", async () => {
    useClientStore.setState({
      clients: [
        { id: "1", name: "Old Name" } as any,
        { id: "2", name: "Other" } as any,
      ],
      currentClient: { id: "1", name: "Old Name" } as any,
    });

    (apiClient.put as any).mockResolvedValue({
      data: { id: "1", name: "New Name" },
    });

    await useClientStore.getState().updateClient("1", { name: "New Name" });

    const state = useClientStore.getState();
    expect(state.clients[0].name).toBe("New Name");
    expect(state.currentClient?.name).toBe("New Name");
  });

  it("should delete a client from the list", async () => {
    useClientStore.setState({
      clients: [
        { id: "1", name: "To Delete" } as any,
        { id: "2", name: "Keep" } as any,
      ],
    });
    (apiClient.delete as any).mockResolvedValue({});

    await useClientStore.getState().deleteClient("1");

    const state = useClientStore.getState();
    expect(state.clients).toHaveLength(1);
    expect(state.clients[0].id).toBe("2");
  });

  it("should prepend restored client to list", async () => {
    useClientStore.setState({
      clients: [{ id: "2", name: "Existing" } as any],
    });
    (apiClient.post as any).mockResolvedValue({
      data: { id: "1", name: "Restored" },
    });

    await useClientStore.getState().restoreClient("1");

    const state = useClientStore.getState();
    expect(state.clients[0].id).toBe("1");
  });

  it("should set error and rethrow on failing actions", async () => {
    const error = new Error("Request failed");
    (apiClient.put as any).mockRejectedValue(error);

    await expect(
      useClientStore.getState().updateClient("1", { name: "X" }),
    ).rejects.toThrow("Request failed");

    expect(useClientStore.getState().error).toBe("Request failed");
  });
});
