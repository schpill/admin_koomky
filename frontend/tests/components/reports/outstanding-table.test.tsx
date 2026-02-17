import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { OutstandingTable } from "@/components/reports/outstanding-table";

function makeItems(count: number) {
  return Array.from({ length: count }, (_, index) => ({
    id: `item-${index}`,
    number: `INV-${index + 1}`,
    client_name: `Client ${index + 1}`,
    status: "sent",
    due_date: "2026-02-01",
    aging_days: 10,
    aging_bucket: "0_30",
    balance_due: 100 + index,
  }));
}

describe("OutstandingTable", () => {
  it("enables virtualization for datasets larger than 100 rows", () => {
    render(<OutstandingTable items={makeItems(120)} />);

    const scrollContainer = screen.getByTestId("outstanding-table-scroll");
    expect(scrollContainer).toHaveAttribute("data-virtualized", "true");

    const visibleRows = screen.getAllByTestId("outstanding-table-row");
    expect(visibleRows.length).toBeLessThan(120);

    fireEvent.scroll(scrollContainer, { target: { scrollTop: 600 } });
    expect(screen.getAllByTestId("outstanding-table-row").length).toBeLessThan(
      120
    );
  });

  it("renders all rows when dataset is small", () => {
    render(<OutstandingTable items={makeItems(12)} />);

    const scrollContainer = screen.getByTestId("outstanding-table-scroll");
    expect(scrollContainer).toHaveAttribute("data-virtualized", "false");
    expect(screen.getAllByTestId("outstanding-table-row")).toHaveLength(12);
  });
});
