"use client";

import { Plus, Trash2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

export interface InvoiceLineItemInput {
  description: string;
  quantity: number;
  unit_price: number;
  vat_rate: number;
}

export interface InvoiceComputedTotals {
  subtotal: number;
  discountAmount: number;
  taxableSubtotal: number;
  taxAmount: number;
  grandTotal: number;
  vatBreakdown: Record<string, number>;
}

interface LineItemsEditorProps {
  items: InvoiceLineItemInput[];
  discountType: "percentage" | "fixed" | null;
  discountValue: number;
  onItemsChange: (items: InvoiceLineItemInput[]) => void;
  onDiscountTypeChange: (type: "percentage" | "fixed" | null) => void;
  onDiscountValueChange: (value: number) => void;
}

function toVatKey(rate: number): string {
  return Number.isInteger(rate)
    ? `${rate}`
    : rate.toFixed(2).replace(/0+$/, "").replace(/\.$/, "");
}

export function calculateInvoiceTotals(
  items: InvoiceLineItemInput[],
  discountType: "percentage" | "fixed" | null,
  discountValue: number
): InvoiceComputedTotals {
  const normalized = items.map((item) => {
    const quantity = Number.isFinite(item.quantity) ? Number(item.quantity) : 0;
    const unitPrice = Number.isFinite(item.unit_price)
      ? Number(item.unit_price)
      : 0;
    const lineTotal = Number((quantity * unitPrice).toFixed(2));

    return {
      ...item,
      quantity,
      unit_price: unitPrice,
      lineTotal,
    };
  });

  const subtotal = Number(
    normalized.reduce((sum, item) => sum + item.lineTotal, 0).toFixed(2)
  );

  let discountAmount = 0;
  if (discountType === "percentage") {
    discountAmount = Number(
      Math.min(
        subtotal,
        (subtotal * Math.min(100, Math.max(0, discountValue))) / 100
      ).toFixed(2)
    );
  }
  if (discountType === "fixed") {
    discountAmount = Number(
      Math.min(subtotal, Math.max(0, discountValue)).toFixed(2)
    );
  }

  const taxableSubtotal = Number(
    Math.max(0, subtotal - discountAmount).toFixed(2)
  );

  const vatBreakdown: Record<string, number> = {};
  let allocatedDiscount = 0;

  normalized.forEach((item, index) => {
    const share = subtotal > 0 ? item.lineTotal / subtotal : 0;
    const lineDiscount =
      index === normalized.length - 1
        ? Number((discountAmount - allocatedDiscount).toFixed(2))
        : Number((discountAmount * share).toFixed(2));

    if (index < normalized.length - 1) {
      allocatedDiscount = Number((allocatedDiscount + lineDiscount).toFixed(2));
    }

    const taxableLine = Number(
      Math.max(0, item.lineTotal - lineDiscount).toFixed(2)
    );
    const vatAmount = Number((taxableLine * (item.vat_rate / 100)).toFixed(2));
    const key = toVatKey(item.vat_rate);

    vatBreakdown[key] = Number(
      ((vatBreakdown[key] || 0) + vatAmount).toFixed(2)
    );
  });

  const taxAmount = Number(
    Object.values(vatBreakdown)
      .reduce((sum, amount) => sum + amount, 0)
      .toFixed(2)
  );
  const grandTotal = Number((taxableSubtotal + taxAmount).toFixed(2));

  return {
    subtotal,
    discountAmount,
    taxableSubtotal,
    taxAmount,
    grandTotal,
    vatBreakdown,
  };
}

function formatCurrency(value: number): string {
  return `${value.toFixed(2)} EUR`;
}

const VAT_RATES = [0, 5.5, 10, 20];

export function LineItemsEditor({
  items,
  discountType,
  discountValue,
  onItemsChange,
  onDiscountTypeChange,
  onDiscountValueChange,
}: LineItemsEditorProps) {
  const totals = calculateInvoiceTotals(items, discountType, discountValue);

  const handleItemChange = (
    index: number,
    key: keyof InvoiceLineItemInput,
    value: string
  ) => {
    const next = items.map((item, itemIndex) => {
      if (itemIndex !== index) {
        return item;
      }

      if (key === "description") {
        return { ...item, description: value };
      }

      return {
        ...item,
        [key]: Number(value),
      } as InvoiceLineItemInput;
    });

    onItemsChange(next);
  };

  const addLine = () => {
    onItemsChange([
      ...items,
      {
        description: "",
        quantity: 1,
        unit_price: 0,
        vat_rate: 20,
      },
    ]);
  };

  const removeLine = (index: number) => {
    if (items.length <= 1) {
      return;
    }

    onItemsChange(items.filter((_, itemIndex) => itemIndex !== index));
  };

  return (
    <div className="space-y-4">
      <div className="space-y-3">
        {items.map((item, index) => {
          const lineTotal = Number(
            (item.quantity * item.unit_price).toFixed(2)
          );

          return (
            <div
              key={`line-${index + 1}`}
              className="grid gap-2 rounded-lg border p-3 md:grid-cols-12"
            >
              <div className="space-y-1 md:col-span-5">
                <Label htmlFor={`line-description-${index}`}>Description</Label>
                <Input
                  id={`line-description-${index}`}
                  value={item.description}
                  onChange={(event) =>
                    handleItemChange(index, "description", event.target.value)
                  }
                />
              </div>

              <div className="space-y-1 md:col-span-2">
                <Label htmlFor={`line-quantity-${index}`}>Quantity</Label>
                <Input
                  id={`line-quantity-${index}`}
                  type="number"
                  min="0"
                  step="0.01"
                  value={item.quantity}
                  onChange={(event) =>
                    handleItemChange(index, "quantity", event.target.value)
                  }
                />
              </div>

              <div className="space-y-1 md:col-span-2">
                <Label htmlFor={`line-unit-price-${index}`}>Unit price</Label>
                <Input
                  id={`line-unit-price-${index}`}
                  type="number"
                  min="0"
                  step="0.01"
                  value={item.unit_price}
                  onChange={(event) =>
                    handleItemChange(index, "unit_price", event.target.value)
                  }
                />
              </div>

              <div className="space-y-1 md:col-span-2">
                <Label htmlFor={`line-vat-rate-${index}`}>VAT rate</Label>
                <select
                  id={`line-vat-rate-${index}`}
                  className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                  value={item.vat_rate}
                  onChange={(event) =>
                    handleItemChange(index, "vat_rate", event.target.value)
                  }
                >
                  {VAT_RATES.map((rate) => (
                    <option key={rate} value={rate}>
                      {rate}%
                    </option>
                  ))}
                </select>
              </div>

              <div className="flex items-end justify-between gap-2 md:col-span-1 md:flex-col md:items-end">
                <p className="text-xs text-muted-foreground">
                  {formatCurrency(lineTotal)}
                </p>
                <Button
                  type="button"
                  variant="ghost"
                  size="icon"
                  aria-label={`Remove line item ${index + 1}`}
                  onClick={() => removeLine(index)}
                >
                  <Trash2 className="h-4 w-4" />
                </Button>
              </div>
            </div>
          );
        })}
      </div>

      <Button type="button" variant="outline" onClick={addLine}>
        <Plus className="mr-2 h-4 w-4" />
        Add line item
      </Button>

      <div className="grid gap-3 rounded-lg border bg-muted/20 p-4 md:grid-cols-2">
        <div className="space-y-2">
          <Label htmlFor="discount-type">Discount type</Label>
          <select
            id="discount-type"
            className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
            value={discountType || "none"}
            onChange={(event) => {
              const value = event.target.value;
              if (value === "none") {
                onDiscountTypeChange(null);
                onDiscountValueChange(0);
                return;
              }

              onDiscountTypeChange(value as "percentage" | "fixed");
            }}
          >
            <option value="none">No discount</option>
            <option value="percentage">Percentage</option>
            <option value="fixed">Fixed</option>
          </select>
        </div>

        <div className="space-y-2">
          <Label htmlFor="discount-value">Discount value</Label>
          <Input
            id="discount-value"
            type="number"
            min="0"
            step="0.01"
            value={discountValue}
            onChange={(event) =>
              onDiscountValueChange(Number(event.target.value || 0))
            }
          />
        </div>

        <div className="space-y-1 text-sm">
          <p>Subtotal</p>
          <p className="font-medium">{formatCurrency(totals.subtotal)}</p>
          <p className="text-muted-foreground">
            Discount: {formatCurrency(totals.discountAmount)}
          </p>
        </div>

        <div className="space-y-1 text-sm">
          <p>VAT</p>
          <p className="font-medium">{formatCurrency(totals.taxAmount)}</p>
          <p>Grand total</p>
          <p className="text-base font-semibold">
            {formatCurrency(totals.grandTotal)}
          </p>
        </div>
      </div>
    </div>
  );
}
