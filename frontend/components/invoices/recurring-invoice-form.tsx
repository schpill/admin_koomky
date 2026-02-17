"use client";

import { useMemo, useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import {
  LineItemsEditor,
  type InvoiceLineItemInput,
} from "@/components/invoices/line-items-editor";
import type {
  RecurringInvoiceFrequency,
  RecurringInvoiceProfilePayload,
} from "@/lib/stores/recurring-invoices";

const RECURRING_WITH_DAY: RecurringInvoiceFrequency[] = [
  "monthly",
  "quarterly",
  "semiannual",
  "annual",
];

const FREQUENCIES: Array<{ value: RecurringInvoiceFrequency; label: string }> =
  [
    { value: "weekly", label: "Weekly" },
    { value: "biweekly", label: "Biweekly" },
    { value: "monthly", label: "Monthly" },
    { value: "quarterly", label: "Quarterly" },
    { value: "semiannual", label: "Semiannual" },
    { value: "annual", label: "Annual" },
  ];

interface RecurringInvoiceFormProps {
  clients: Array<{ id: string; name: string }>;
  initialPayload?: Partial<RecurringInvoiceProfilePayload>;
  isSubmitting?: boolean;
  submitLabel?: string;
  onSubmit: (payload: RecurringInvoiceProfilePayload) => Promise<void> | void;
  onCancel?: () => void;
}

function today(): string {
  return new Date().toISOString().slice(0, 10);
}

export function RecurringInvoiceForm({
  clients,
  initialPayload,
  isSubmitting = false,
  submitLabel = "Save profile",
  onSubmit,
  onCancel,
}: RecurringInvoiceFormProps) {
  const [clientId, setClientId] = useState(initialPayload?.client_id || "");
  const [name, setName] = useState(initialPayload?.name || "");
  const [frequency, setFrequency] = useState<RecurringInvoiceFrequency>(
    initialPayload?.frequency || "monthly"
  );
  const [startDate, setStartDate] = useState(
    initialPayload?.start_date || today()
  );
  const [nextDueDate, setNextDueDate] = useState(
    initialPayload?.next_due_date || initialPayload?.start_date || today()
  );
  const [endDate, setEndDate] = useState(initialPayload?.end_date || "");
  const [dayOfMonth, setDayOfMonth] = useState<number>(
    initialPayload?.day_of_month || 1
  );
  const [paymentTermsDays, setPaymentTermsDays] = useState(
    initialPayload?.payment_terms_days || 30
  );
  const [taxRate, setTaxRate] = useState<number>(
    initialPayload?.tax_rate || 20
  );
  const [discountType, setDiscountType] = useState<
    "percentage" | "fixed" | null
  >("percentage");
  const [discountValue, setDiscountValue] = useState<number>(
    initialPayload?.discount_percent || 0
  );
  const [maxOccurrences, setMaxOccurrences] = useState<string>(
    initialPayload?.max_occurrences
      ? String(initialPayload.max_occurrences)
      : ""
  );
  const [autoSend, setAutoSend] = useState<boolean>(
    initialPayload?.auto_send || false
  );
  const [currency, setCurrency] = useState(initialPayload?.currency || "EUR");
  const [notes, setNotes] = useState(initialPayload?.notes || "");
  const [lineItems, setLineItems] = useState<InvoiceLineItemInput[]>(
    initialPayload?.line_items || [
      {
        description: "",
        quantity: 1,
        unit_price: 0,
        vat_rate: 20,
      },
    ]
  );
  const [error, setError] = useState<string | null>(null);

  const requiresDay = useMemo(
    () => RECURRING_WITH_DAY.includes(frequency),
    [frequency]
  );

  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();
    setError(null);

    if (!clientId) {
      setError("Please select a client");
      return;
    }

    if (!name.trim()) {
      setError("Profile name is required");
      return;
    }

    if (endDate && endDate < startDate) {
      setError("End date must be after start date");
      return;
    }

    const sanitizedItems = lineItems
      .map((line) => ({
        ...line,
        description: line.description.trim(),
      }))
      .filter((line) => line.description.length > 0);

    if (sanitizedItems.length === 0) {
      setError("At least one line item is required");
      return;
    }

    await onSubmit({
      client_id: clientId,
      name: name.trim(),
      frequency,
      start_date: startDate,
      next_due_date: nextDueDate,
      end_date: endDate || null,
      day_of_month: requiresDay ? dayOfMonth : null,
      line_items: sanitizedItems,
      notes,
      payment_terms_days: paymentTermsDays,
      tax_rate: taxRate,
      discount_percent: discountType === "percentage" ? discountValue : null,
      max_occurrences: maxOccurrences ? Number(maxOccurrences) : null,
      auto_send: autoSend,
      currency,
    });
  };

  return (
    <form className="space-y-6" onSubmit={handleSubmit}>
      <div className="grid gap-3 md:grid-cols-2">
        <div className="space-y-2">
          <Label htmlFor="recurring-client">Client</Label>
          <select
            id="recurring-client"
            className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
            value={clientId}
            onChange={(event) => setClientId(event.target.value)}
          >
            <option value="">Select client</option>
            {clients.map((client) => (
              <option key={client.id} value={client.id}>
                {client.name}
              </option>
            ))}
          </select>
        </div>

        <div className="space-y-2">
          <Label htmlFor="recurring-name">Profile name</Label>
          <Input
            id="recurring-name"
            value={name}
            onChange={(event) => setName(event.target.value)}
          />
        </div>

        <div className="space-y-2">
          <Label htmlFor="recurring-frequency">Frequency</Label>
          <select
            id="recurring-frequency"
            className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
            value={frequency}
            onChange={(event) =>
              setFrequency(event.target.value as RecurringInvoiceFrequency)
            }
          >
            {FREQUENCIES.map((frequencyOption) => (
              <option key={frequencyOption.value} value={frequencyOption.value}>
                {frequencyOption.label}
              </option>
            ))}
          </select>
        </div>

        {requiresDay && (
          <div className="space-y-2">
            <Label htmlFor="recurring-day-of-month">Day of month</Label>
            <Input
              id="recurring-day-of-month"
              type="number"
              min={1}
              max={31}
              value={dayOfMonth}
              onChange={(event) =>
                setDayOfMonth(Number(event.target.value || 1))
              }
            />
          </div>
        )}

        <div className="space-y-2">
          <Label htmlFor="recurring-start-date">Start date</Label>
          <Input
            id="recurring-start-date"
            type="date"
            value={startDate}
            onChange={(event) => setStartDate(event.target.value)}
          />
        </div>

        <div className="space-y-2">
          <Label htmlFor="recurring-next-due-date">Next due date</Label>
          <Input
            id="recurring-next-due-date"
            type="date"
            value={nextDueDate}
            onChange={(event) => setNextDueDate(event.target.value)}
          />
        </div>

        <div className="space-y-2">
          <Label htmlFor="recurring-end-date">End date (optional)</Label>
          <Input
            id="recurring-end-date"
            type="date"
            value={endDate || ""}
            onChange={(event) => setEndDate(event.target.value)}
          />
        </div>

        <div className="space-y-2">
          <Label htmlFor="recurring-max-occurrences">Max occurrences</Label>
          <Input
            id="recurring-max-occurrences"
            type="number"
            min={1}
            value={maxOccurrences}
            onChange={(event) => setMaxOccurrences(event.target.value)}
            placeholder="Leave empty for no limit"
          />
        </div>

        <div className="space-y-2">
          <Label htmlFor="recurring-payment-terms-days">
            Payment terms (days)
          </Label>
          <Input
            id="recurring-payment-terms-days"
            type="number"
            min={1}
            value={paymentTermsDays}
            onChange={(event) =>
              setPaymentTermsDays(Number(event.target.value || 30))
            }
          />
        </div>

        <div className="space-y-2">
          <Label htmlFor="recurring-tax-rate">Default tax rate</Label>
          <Input
            id="recurring-tax-rate"
            type="number"
            min={0}
            max={100}
            step="0.01"
            value={taxRate}
            onChange={(event) => setTaxRate(Number(event.target.value || 0))}
          />
        </div>

        <div className="space-y-2">
          <Label htmlFor="recurring-currency">Currency</Label>
          <Input
            id="recurring-currency"
            value={currency}
            onChange={(event) => setCurrency(event.target.value.toUpperCase())}
            maxLength={3}
          />
        </div>

        <div className="space-y-2">
          <Label htmlFor="recurring-auto-send">
            Auto send generated invoices
          </Label>
          <select
            id="recurring-auto-send"
            className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
            value={autoSend ? "yes" : "no"}
            onChange={(event) => setAutoSend(event.target.value === "yes")}
          >
            <option value="no">No</option>
            <option value="yes">Yes</option>
          </select>
        </div>
      </div>

      <LineItemsEditor
        items={lineItems}
        discountType={discountType}
        discountValue={discountValue}
        onItemsChange={setLineItems}
        onDiscountTypeChange={setDiscountType}
        onDiscountValueChange={setDiscountValue}
      />

      <div className="space-y-2">
        <Label htmlFor="recurring-notes">Notes</Label>
        <Textarea
          id="recurring-notes"
          rows={3}
          value={notes}
          onChange={(event) => setNotes(event.target.value)}
        />
      </div>

      {error && <p className="text-sm text-destructive">{error}</p>}

      <div className="flex items-center justify-end gap-2">
        {onCancel && (
          <Button type="button" variant="outline" onClick={onCancel}>
            Cancel
          </Button>
        )}
        <Button type="submit" disabled={isSubmitting}>
          {isSubmitting ? "Saving..." : submitLabel}
        </Button>
      </div>
    </form>
  );
}
