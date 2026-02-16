"use client";

import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import type { InvoiceStatus } from "@/lib/stores/invoices";

interface InvoiceFilterBarProps {
  clients: Array<{ id: string; name: string }>;
  onApply: (filters: Record<string, string>) => void;
}

const STATUS_OPTIONS: Array<{ value: InvoiceStatus; label: string }> = [
  { value: "draft", label: "Draft" },
  { value: "sent", label: "Sent" },
  { value: "viewed", label: "Viewed" },
  { value: "partially_paid", label: "Partially paid" },
  { value: "paid", label: "Paid" },
  { value: "overdue", label: "Overdue" },
  { value: "cancelled", label: "Cancelled" },
];

export function InvoiceFilterBar({ clients, onApply }: InvoiceFilterBarProps) {
  const [status, setStatus] = useState("");
  const [clientId, setClientId] = useState("");
  const [dateFrom, setDateFrom] = useState("");
  const [dateTo, setDateTo] = useState("");

  return (
    <div className="rounded-lg border p-4">
      <div className="grid gap-3 md:grid-cols-4">
        <div className="space-y-2">
          <Label htmlFor="invoice-filter-status">Status</Label>
          <select
            id="invoice-filter-status"
            className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
            value={status}
            onChange={(event) => setStatus(event.target.value)}
          >
            <option value="">All statuses</option>
            {STATUS_OPTIONS.map((option) => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            ))}
          </select>
        </div>

        <div className="space-y-2">
          <Label htmlFor="invoice-filter-client">Client</Label>
          <select
            id="invoice-filter-client"
            className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
            value={clientId}
            onChange={(event) => setClientId(event.target.value)}
          >
            <option value="">All clients</option>
            {clients.map((client) => (
              <option key={client.id} value={client.id}>
                {client.name}
              </option>
            ))}
          </select>
        </div>

        <div className="space-y-2">
          <Label htmlFor="invoice-filter-from">From</Label>
          <Input
            id="invoice-filter-from"
            type="date"
            value={dateFrom}
            onChange={(event) => setDateFrom(event.target.value)}
          />
        </div>

        <div className="space-y-2">
          <Label htmlFor="invoice-filter-to">To</Label>
          <Input
            id="invoice-filter-to"
            type="date"
            value={dateTo}
            onChange={(event) => setDateTo(event.target.value)}
          />
        </div>
      </div>

      <div className="mt-3 flex justify-end">
        <Button
          type="button"
          onClick={() =>
            onApply({
              status,
              client_id: clientId,
              date_from: dateFrom,
              date_to: dateTo,
            })
          }
        >
          Apply filters
        </Button>
      </div>
    </div>
  );
}
