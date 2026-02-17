import { describe, it, expect, beforeEach, vi } from "vitest";
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

const baseCampaign = {
  id: "camp_1",
  name: "Spring Launch",
  type: "email",
  status: "draft",
  subject: "Hello",
  content: "Hi {{first_name}}",
};

describe("useCampaignStore", () => {
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

  it("fetches campaigns list", async () => {
    (apiClient.get as any).mockResolvedValue({
      data: {
        data: [baseCampaign],
        current_page: 1,
        last_page: 1,
        total: 1,
        per_page: 15,
      },
    });

    await useCampaignStore.getState().fetchCampaigns();

    const state = useCampaignStore.getState();
    expect(state.campaigns).toHaveLength(1);
    expect(state.pagination?.total).toBe(1);
  });

  it("runs campaign actions and template operations", async () => {
    (apiClient.post as any).mockResolvedValueOnce({ data: baseCampaign });
    const created = await useCampaignStore
      .getState()
      .createCampaign(baseCampaign);
    expect(created?.id).toBe("camp_1");

    (apiClient.put as any).mockResolvedValueOnce({
      data: { ...baseCampaign, name: "Summer Launch" },
    });
    const updated = await useCampaignStore
      .getState()
      .updateCampaign("camp_1", { name: "Summer Launch" });
    expect(updated?.name).toBe("Summer Launch");

    (apiClient.post as any).mockResolvedValueOnce({
      data: { ...baseCampaign, status: "sending" },
    });
    const sent = await useCampaignStore.getState().sendCampaign("camp_1");
    expect(sent?.status).toBe("sending");

    (apiClient.post as any).mockResolvedValueOnce({
      data: { ...baseCampaign, status: "paused" },
    });
    const paused = await useCampaignStore.getState().pauseCampaign("camp_1");
    expect(paused?.status).toBe("paused");

    (apiClient.post as any).mockResolvedValueOnce({
      data: { ...baseCampaign, id: "camp_2", name: "Spring Launch Copy" },
    });
    const duplicate = await useCampaignStore
      .getState()
      .duplicateCampaign("camp_1");
    expect(duplicate?.id).toBe("camp_2");

    (apiClient.post as any).mockResolvedValueOnce({ data: null });
    await useCampaignStore
      .getState()
      .testSendCampaign("camp_1", { email: "qa@example.com" });

    (apiClient.get as any).mockResolvedValueOnce({
      data: [{ id: "tpl_1", name: "Template", type: "email", content: "x" }],
    });
    await useCampaignStore.getState().fetchTemplates();
    expect(useCampaignStore.getState().templates).toHaveLength(1);

    (apiClient.delete as any).mockResolvedValue({});
    await useCampaignStore.getState().deleteCampaign("camp_1");
  });

  it("fetches analytics and comparison", async () => {
    (apiClient.get as any).mockResolvedValueOnce({
      data: {
        campaign_id: "camp_1",
        total_recipients: 10,
        open_rate: 50,
        click_rate: 10,
        time_series: [{ hour: "2026-02-17 10:00:00", opens: 5, clicks: 1 }],
      },
    });

    const analytics = await useCampaignStore
      .getState()
      .fetchCampaignAnalytics("camp_1");

    expect(analytics?.campaign_id).toBe("camp_1");

    (apiClient.get as any).mockResolvedValueOnce({
      data: [
        { campaign_id: "camp_1", open_rate: 50 },
        { campaign_id: "camp_2", open_rate: 35 },
      ],
    });

    const comparison = await useCampaignStore
      .getState()
      .compareCampaigns(["camp_1", "camp_2"]);

    expect(comparison).toHaveLength(2);
  });

  it("updates communication settings and handles failures", async () => {
    (apiClient.put as any)
      .mockResolvedValueOnce({ data: { id: "u1" } })
      .mockResolvedValueOnce({ data: { id: "u1" } })
      .mockResolvedValueOnce({ data: { id: "u1" } });

    await useCampaignStore.getState().updateEmailSettings({
      provider: "smtp",
      from_email: "ops@example.com",
    });
    await useCampaignStore.getState().updateSmsSettings({ provider: "twilio" });
    await useCampaignStore
      .getState()
      .updateNotificationPreferences({ campaign_completed: { email: true } });

    (apiClient.get as any).mockRejectedValueOnce(new Error("campaigns failed"));
    await useCampaignStore.getState().fetchCampaigns();
    expect(useCampaignStore.getState().error).toBe("campaigns failed");
  });
});
