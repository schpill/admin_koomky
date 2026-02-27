import { describe, expect, it } from "vitest";
import { fireEvent, render, screen } from "@testing-library/react";
import { ProductSalesTable } from "@/components/products/product-sales-table";

const sales = [
  {
    id: "sale_1",
    quantity: 1,
    unit_price: 100,
    total_price: 100,
    currency_code: "EUR",
    status: "confirmed" as const,
    sold_at: "2026-02-01T10:00:00Z",
    client: { id: "c1", name: "Acme" },
  },
  {
    id: "sale_2",
    quantity: 1,
    unit_price: 80,
    total_price: 80,
    currency_code: "EUR",
    status: "pending" as const,
    sold_at: "2026-02-02T10:00:00Z",
    client: { id: "c2", name: "Globex" },
  },
];

describe("ProductSalesTable", () => {
  it("renders sales rows", () => {
    render(<ProductSalesTable sales={sales} />);

    expect(screen.getByText("Acme")).toBeInTheDocument();
    expect(screen.getByText("Globex")).toBeInTheDocument();
    expect(screen.getAllByText("Confirmée").length).toBeGreaterThan(0);
    expect(screen.getAllByText("En attente").length).toBeGreaterThan(0);
  });

  it("filters by status", () => {
    render(<ProductSalesTable sales={sales} />);

    fireEvent.click(screen.getByRole("button", { name: "Confirmée" }));

    expect(screen.getByText("Acme")).toBeInTheDocument();
    expect(screen.queryByText("Globex")).not.toBeInTheDocument();
  });

  it("shows loading skeleton", () => {
    render(<ProductSalesTable sales={[]} isLoading />);

    expect(screen.queryByText("Aucune vente trouvée.")).not.toBeInTheDocument();
  });
});
