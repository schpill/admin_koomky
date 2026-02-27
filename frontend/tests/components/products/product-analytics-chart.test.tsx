import { describe, expect, it } from "vitest";
import { render, screen } from "@testing-library/react";
import { ProductAnalyticsChart } from "@/components/products/product-analytics-chart";

describe("ProductAnalyticsChart", () => {
  it("shows loading state", () => {
    render(<ProductAnalyticsChart data={[]} isLoading />);
    expect(
      screen.queryByText("Aucune donnée disponible")
    ).not.toBeInTheDocument();
  });

  it("shows empty state", () => {
    render(<ProductAnalyticsChart data={[]} />);
    expect(screen.getByText("Aucune donnée disponible")).toBeInTheDocument();
  });

  it("renders monthly data", () => {
    render(
      <ProductAnalyticsChart
        data={[
          { month: "2026-01", revenue: 1000 },
          { month: "2026-02", revenue: 500 },
        ]}
      />
    );

    expect(screen.getByText("janv. 26")).toBeInTheDocument();
    expect(screen.getByText("févr. 26")).toBeInTheDocument();
    expect(
      screen.getByText(
        (content) => content.includes("1") && content.includes("000,00")
      )
    ).toBeInTheDocument();
    expect(
      screen.getByText((content) => content.includes("500,00"))
    ).toBeInTheDocument();
  });
});
