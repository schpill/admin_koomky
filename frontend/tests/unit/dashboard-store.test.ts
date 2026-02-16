import { describe, it, expect, beforeEach, vi } from "vitest";
import { useDashboardStore } from "../../lib/stores/dashboard";

// Mock apiClient
vi.mock("../../lib/api", () => ({
  apiClient: {
    get: vi.fn(),
  },
}));

import { apiClient } from "../../lib/api";

describe("useDashboardStore", () => {
  beforeEach(() => {
    useDashboardStore.setState({
      stats: null,
      isLoading: false,
      error: null,
    });
    vi.clearAllMocks();
  });

  it("should fetch dashboard stats and update state", async () => {
    const mockStats = {
      total_clients: 10,
      active_projects: 5,
      pending_invoices_amount: 1500,
      recent_activities: [],
    };
    (apiClient.get as any).mockResolvedValue({ data: mockStats });

    await useDashboardStore.getState().fetchStats();

    const state = useDashboardStore.getState();
    expect(state.stats?.total_clients).toBe(10);
    expect(state.isLoading).toBe(false);
  });
});
