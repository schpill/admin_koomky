import { render, screen } from "@testing-library/react";
import { describe, it, expect } from "vitest";
import { CampaignSummaryWidget } from "@/components/dashboard/campaign-summary-widget";

describe("CampaignSummaryWidget", () => {
  it("renders campaign dashboard metrics", () => {
    render(
      <CampaignSummaryWidget
        activeCampaigns={4}
        averageOpenRate={42.5}
        averageClickRate={7.2}
      />
    );

    expect(screen.getByText("4")).toBeInTheDocument();
    expect(screen.getByText("42.5%")).toBeInTheDocument();
    expect(screen.getByText("7.2%")).toBeInTheDocument();
  });
});
