import { beforeEach, describe, expect, it, vi } from "vitest";
import { render, screen, waitFor } from "@testing-library/react";
import { ProductStatsBar } from "@/components/products/product-stats-bar";

const mockFetchGlobalAnalytics = vi.fn();
const useProductsStoreMock = vi.fn();

vi.mock("@/lib/stores/products", () => ({
  useProductsStore: () => useProductsStoreMock(),
}));

describe("ProductStatsBar", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it("shows loading placeholders", () => {
    useProductsStoreMock.mockReturnValue({
      fetchGlobalAnalytics: mockFetchGlobalAnalytics,
      globalAnalytics: null,
      analyticsLoading: true,
    });

    render(<ProductStatsBar />);

    expect(screen.getAllByText("Chargement...").length).toBeGreaterThan(0);
  });

  it("renders stats values", async () => {
    useProductsStoreMock.mockReturnValue({
      fetchGlobalAnalytics: mockFetchGlobalAnalytics,
      globalAnalytics: {
        top_products: [
          { id: "1", name: "Produit A", revenue: 1000, sales_count: 2 },
          { id: "2", name: "Produit B", revenue: 500, sales_count: 1 },
        ],
        active_products: 12,
        total_revenue: 1500,
        total_sales: 3,
      },
      analyticsLoading: false,
    });

    render(<ProductStatsBar />);

    await waitFor(() => {
      expect(mockFetchGlobalAnalytics).toHaveBeenCalledTimes(1);
    });

    expect(screen.getByText("Produits actifs")).toBeInTheDocument();
    expect(screen.getByText("12")).toBeInTheDocument();
    expect(
      screen.getByText(
        (content) => content.includes("1") && content.includes("500,00")
      )
    ).toBeInTheDocument();
    expect(screen.getByText("3")).toBeInTheDocument();
  });
});
