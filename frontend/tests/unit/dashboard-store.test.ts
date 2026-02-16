import { describe, it, expect, beforeEach, vi } from "vitest";
import { useDashboardStore } from "../../lib/stores/dashboard";

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

  it("fetches enhanced dashboard stats and updates state", async () => {
    const mockStats = {
      total_clients: 10,
      active_projects: 5,
      pending_invoices_amount: 1500,
      recent_activities: [],
      revenue_month: 500,
      revenue_quarter: 1200,
      revenue_year: 4200,
      pending_invoices_count: 3,
      overdue_invoices_count: 1,
      revenue_trend: [
        { month: "2026-01", total: 100 },
        { month: "2026-02", total: 200 },
      ],
      upcoming_deadlines: [
        {
          id: "p1",
          name: "Website",
          status: "in_progress",
          deadline: "2026-02-20",
        },
      ],
    };

    (apiClient.get as any).mockResolvedValue({ data: mockStats });

    await useDashboardStore.getState().fetchStats();

    const state = useDashboardStore.getState();
    expect(state.stats?.total_clients).toBe(10);
    expect(state.stats?.revenue_year).toBe(4200);
    expect(state.stats?.revenue_trend).toHaveLength(2);
    expect(state.stats?.upcoming_deadlines).toHaveLength(1);
    expect(state.isLoading).toBe(false);
    expect(state.error).toBeNull();
  });

  it("records dashboard fetch error", async () => {
    (apiClient.get as any).mockRejectedValue(new Error("dashboard failed"));

    await useDashboardStore.getState().fetchStats();

    expect(useDashboardStore.getState().stats).toBeNull();
    expect(useDashboardStore.getState().error).toBe("dashboard failed");
    expect(useDashboardStore.getState().isLoading).toBe(false);
  });
});
