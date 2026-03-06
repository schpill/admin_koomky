import { beforeEach, describe, expect, it, vi } from "vitest";
import { useCampaignStore } from "@/lib/stores/campaigns";

vi.mock("@/lib/api", () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
  },
}));

import { apiClient } from "@/lib/api";

describe("campaign ab store", () => {
  beforeEach(() => {
    useCampaignStore.setState({
      campaigns: [],
      currentCampaign: null,
      templates: [],
      analytics: null,
      comparison: [],
      pagination: null,
      isLoading: false,
      error: null,
    });
    vi.clearAllMocks();
  });

  it("selects ab winner and updates campaign state", async () => {
    (apiClient.post as any).mockResolvedValue({
      data: {
        id: "camp_1",
        name: "AB",
        type: "email",
        status: "sending",
        content: "x",
        is_ab_test: true,
        ab_winner_variant_id: "var_b",
        variants: [
          { id: "var_a", label: "A", send_percent: 50 },
          { id: "var_b", label: "B", send_percent: 50 },
        ],
      },
    });

    const campaign = await useCampaignStore
      .getState()
      .selectAbWinner("camp_1", "var_b");

    expect(campaign.ab_winner_variant_id).toBe("var_b");
    expect(apiClient.post).toHaveBeenCalledWith(
      "/campaigns/camp_1/ab/select-winner",
      { variant_id: "var_b" }
    );
  });
});
