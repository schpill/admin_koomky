import { beforeEach, describe, expect, it, vi } from "vitest";
import { useProspectStore } from "@/lib/stores/prospects";

vi.mock("@/lib/api", () => ({
  apiClient: {
    get: vi.fn(),
    put: vi.fn(),
    post: vi.fn(),
  },
}));

import { apiClient } from "@/lib/api";

describe("useProspectStore", () => {
  beforeEach(() => {
    useProspectStore.setState({
      clients: [],
      total: 0,
      page: 1,
      filters: {},
      isLoading: false,
      error: null,
    });
    vi.clearAllMocks();
  });

  it("fetches prospects and converts to client", async () => {
    (apiClient.get as any).mockResolvedValueOnce({
      data: {
        data: [{ id: "cli_1", name: "Acme", status: "prospect" }],
        meta: { total: 1, current_page: 1 },
      },
    });

    await useProspectStore.getState().fetchProspects();
    expect(useProspectStore.getState().clients).toHaveLength(1);

    (apiClient.put as any).mockResolvedValue({ data: {} });
    (apiClient.get as any).mockResolvedValueOnce({
      data: { data: [], meta: { total: 0, current_page: 1 } },
    });
    await useProspectStore.getState().convertToClient("cli_1");

    expect(apiClient.put).toHaveBeenCalledWith("/clients/cli_1", {
      status: "active",
    });
  });
});
