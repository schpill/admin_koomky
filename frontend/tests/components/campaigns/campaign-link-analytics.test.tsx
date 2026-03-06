import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { CampaignLinkAnalytics } from "@/components/campaigns/campaign-link-analytics";

describe("CampaignLinkAnalytics", () => {
  it("renders link stats and triggers export", async () => {
    const onExport = vi.fn();

    render(
      <CampaignLinkAnalytics
        stats={[
          {
            url: "https://example.com/a",
            total_clicks: 12,
            unique_clicks: 8,
            click_rate: 40,
          },
          {
            url: "https://example.com/b",
            total_clicks: 4,
            unique_clicks: 3,
            click_rate: 15,
          },
        ]}
        onExport={onExport}
      />
    );

    expect(screen.getByText("https://example.com/a")).toBeInTheDocument();
    expect(screen.getByText("12")).toBeInTheDocument();
    expect(screen.getByText("40%")).toBeInTheDocument();

    fireEvent.click(screen.getByRole("button", { name: /export/i }));
    expect(onExport).toHaveBeenCalledTimes(1);
  });
});
