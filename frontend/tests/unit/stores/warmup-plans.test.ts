import { beforeEach, describe, expect, it, vi } from "vitest";
import { useWarmupPlansStore } from "@/lib/stores/warmup-plans";

vi.mock("@/lib/api", () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
  },
}));

import { apiClient } from "@/lib/api";

describe("useWarmupPlansStore", () => {
  beforeEach(() => {
    useWarmupPlansStore.setState({
      plans: [],
      currentPlan: null,
      isLoading: false,
      error: null,
    });
    vi.clearAllMocks();
  });

  it("fetches and mutates warmup plans", async () => {
    const plan = {
      id: "plan_1",
      name: "IP warm-up",
      status: "active",
      current_day: 2,
      current_daily_limit: 80,
      daily_volume_start: 50,
      daily_volume_max: 500,
      increment_percent: 30,
    };

    (apiClient.get as any).mockResolvedValueOnce({ data: [plan] });
    await useWarmupPlansStore.getState().fetchPlans();
    expect(useWarmupPlansStore.getState().plans).toHaveLength(1);

    (apiClient.post as any).mockResolvedValueOnce({ data: plan });
    await useWarmupPlansStore.getState().createPlan({
      name: "IP warm-up",
      daily_volume_start: 50,
      daily_volume_max: 500,
      increment_percent: 30,
    });
    expect(useWarmupPlansStore.getState().currentPlan?.id).toBe("plan_1");

    (apiClient.put as any).mockResolvedValueOnce({
      data: { ...plan, name: "Updated warm-up" },
    });
    await useWarmupPlansStore
      .getState()
      .updatePlan("plan_1", { name: "Updated warm-up" });
    expect(useWarmupPlansStore.getState().plans[0]?.name).toBe(
      "Updated warm-up"
    );

    (apiClient.patch as any).mockResolvedValueOnce({
      data: { ...plan, status: "paused" },
    });
    await useWarmupPlansStore.getState().pausePlan("plan_1");
    expect(useWarmupPlansStore.getState().plans[0]?.status).toBe("paused");

    (apiClient.patch as any).mockResolvedValueOnce({
      data: { ...plan, status: "active" },
    });
    await useWarmupPlansStore.getState().resumePlan("plan_1");
    expect(useWarmupPlansStore.getState().plans[0]?.status).toBe("active");

    (apiClient.delete as any).mockResolvedValueOnce({ data: null });
    await useWarmupPlansStore.getState().deletePlan("plan_1");
    expect(useWarmupPlansStore.getState().plans).toHaveLength(0);
  });
});
