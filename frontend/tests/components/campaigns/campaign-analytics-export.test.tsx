import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import CampaignAnalyticsPage from "@/app/(dashboard)/campaigns/[id]/analytics/page";
import { useCampaignStore } from "@/lib/stores/campaigns";

vi.mock("next/navigation", () => ({
  useParams: () => ({ id: "camp_1" }),
}));

vi.mock("@/components/providers/i18n-provider", () => ({
  useI18n: () => ({
    t: (key: string) => key,
  }),
}));

vi.mock("@/lib/stores/auth", () => ({
  useAuthStore: (selector: (state: { accessToken: string }) => string) =>
    selector({ accessToken: "token" }),
}));

vi.mock("@/lib/api", () => ({
  apiClient: {
    get: vi.fn().mockResolvedValue({ data: [] }),
  },
}));

describe("CampaignAnalyticsPage", () => {
  beforeEach(() => {
    useCampaignStore.setState({
      campaigns: [],
      currentCampaign: null,
      templates: [],
      analytics: {
        campaign_id: "camp_1",
        campaign_name: "Launch",
        total_recipients: 12,
        open_rate: 50,
        click_rate: 10,
        time_series: [{ date: "2026-03-06", opens: 4, clicks: 1 }],
      },
      comparison: [],
      pagination: null,
      isLoading: false,
      error: null,
      fetchCampaigns: vi.fn(),
      fetchCampaign: vi.fn(),
      createCampaign: vi.fn(),
      updateCampaign: vi.fn(),
      deleteCampaign: vi.fn(),
      sendCampaign: vi.fn(),
      pauseCampaign: vi.fn(),
      duplicateCampaign: vi.fn(),
      testSendCampaign: vi.fn(),
      selectAbWinner: vi.fn(),
      fetchTemplates: vi.fn(),
      createTemplate: vi.fn(),
      updateTemplate: vi.fn(),
      deleteTemplate: vi.fn(),
      fetchCampaignAnalytics: vi.fn().mockResolvedValue({ campaign_id: "camp_1" }),
      compareCampaigns: vi.fn(),
      updateEmailSettings: vi.fn(),
      updateSmsSettings: vi.fn(),
      updateNotificationPreferences: vi.fn(),
    });

    vi.stubGlobal(
      "fetch",
      vi.fn().mockResolvedValue({
        ok: true,
        blob: async () => new Blob(["report"]),
      })
    );

    Object.defineProperty(URL, "createObjectURL", {
      writable: true,
      value: vi.fn(() => "blob:report"),
    });
    Object.defineProperty(URL, "revokeObjectURL", {
      writable: true,
      value: vi.fn(),
    });
    const originalCreateElement = document.createElement.bind(document);
    vi.spyOn(document, "createElement").mockImplementation(
      ((tagName: string) => {
        if (tagName.toLowerCase() === "a") {
          return {
            click: vi.fn(),
            set href(_value: string) {},
            set download(_value: string) {},
          } as unknown as HTMLElement;
        }

        return originalCreateElement(tagName);
      }) as typeof document.createElement
    );
  });

  it("renders export buttons and calls the report export endpoints", async () => {
    render(<CampaignAnalyticsPage />);

    fireEvent.click(screen.getByRole("button", { name: /exportCsv/i }));
    fireEvent.click(screen.getByRole("button", { name: /exportPdf/i }));

    await waitFor(() => {
      expect(global.fetch).toHaveBeenCalledTimes(2);
    });
  });
});
