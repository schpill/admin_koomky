import { describe, it, expect } from "vitest";
import { fireEvent, render, screen } from "@testing-library/react";
import { useState } from "react";
import {
  LineItemsEditor,
  type InvoiceLineItemInput,
} from "@/components/invoices/line-items-editor";

function LineItemsEditorHarness() {
  const [items, setItems] = useState<InvoiceLineItemInput[]>([
    {
      description: "Design",
      quantity: 2,
      unit_price: 100,
      vat_rate: 20,
    },
  ]);
  const [discountType, setDiscountType] = useState<
    "percentage" | "fixed" | null
  >("percentage");
  const [discountValue, setDiscountValue] = useState(10);

  return (
    <LineItemsEditor
      items={items}
      discountType={discountType}
      discountValue={discountValue}
      onItemsChange={setItems}
      onDiscountTypeChange={setDiscountType}
      onDiscountValueChange={setDiscountValue}
    />
  );
}

describe("LineItemsEditor", () => {
  it("adds and removes rows", () => {
    render(<LineItemsEditorHarness />);

    expect(screen.getAllByLabelText("Description")).toHaveLength(1);

    fireEvent.click(screen.getByRole("button", { name: "Add line item" }));
    expect(screen.getAllByLabelText("Description")).toHaveLength(2);

    fireEvent.click(screen.getByRole("button", { name: "Remove line item 2" }));
    expect(screen.getAllByLabelText("Description")).toHaveLength(1);
  });

  it("computes subtotal vat discount and grand total", () => {
    render(<LineItemsEditorHarness />);

    expect(screen.getByText("Subtotal")).toBeInTheDocument();
    expect(screen.getAllByText("200.00 EUR").length).toBeGreaterThan(0);
    expect(screen.getByText("VAT")).toBeInTheDocument();
    expect(screen.getByText("36.00 EUR")).toBeInTheDocument();
    expect(screen.getByText("Grand total")).toBeInTheDocument();
    expect(screen.getByText("216.00 EUR")).toBeInTheDocument();

    fireEvent.change(screen.getByLabelText("Discount value"), {
      target: { value: "0" },
    });

    expect(screen.getByText("240.00 EUR")).toBeInTheDocument();
  });
});
