import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { RevenueChart } from "@/components/reports/revenue-chart";

describe("RevenueChart", () => {
  it("renders chart rows with values", () => {
    render(
      <RevenueChart
        data={[
          { month: "2026-01", total: 1000 },
          { month: "2026-02", total: 1500 },
        ]}
      />
    );

    expect(screen.getByText("2026-01")).toBeInTheDocument();
    expect(screen.getByText("1000.00 EUR")).toBeInTheDocument();
    expect(screen.getByText("2026-02")).toBeInTheDocument();
    expect(screen.getByText("1500.00 EUR")).toBeInTheDocument();
  });

  it("renders empty state", () => {
    render(<RevenueChart data={[]} />);

    expect(screen.getByText("No data for this period.")).toBeInTheDocument();
  });
});
