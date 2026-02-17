"use client";

import Link from "next/link";
import { useEffect, useMemo, useState } from "react";
import { useRouter } from "next/navigation";
import { toast } from "sonner";
import { ChevronLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import {
  LineItemsEditor,
  type InvoiceLineItemInput,
} from "@/components/invoices/line-items-editor";
import { CurrencyAmount } from "@/components/shared/currency-amount";
import { CurrencySelector } from "@/components/shared/currency-selector";
import { useClientStore } from "@/lib/stores/clients";
import { useCurrencyStore } from "@/lib/stores/currencies";
import { useInvoiceStore } from "@/lib/stores/invoices";

const today = new Date().toISOString().slice(0, 10);
const inThirtyDays = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000)
  .toISOString()
  .slice(0, 10);

export default function CreateInvoicePage() {
  const router = useRouter();
  const { clients, fetchClients } = useClientStore();
  const { currencies, rates, baseCurrency, fetchCurrencies, fetchRates } =
    useCurrencyStore();
  const { createInvoice, isLoading } = useInvoiceStore();

  const [clientId, setClientId] = useState("");
  const [issueDate, setIssueDate] = useState(today);
  const [dueDate, setDueDate] = useState(inThirtyDays);
  const [currency, setCurrency] = useState("EUR");
  const [notes, setNotes] = useState("");
  const [discountType, setDiscountType] = useState<
    "percentage" | "fixed" | null
  >(null);
  const [discountValue, setDiscountValue] = useState(0);
  const [lineItems, setLineItems] = useState<InvoiceLineItemInput[]>([
    {
      description: "",
      quantity: 1,
      unit_price: 0,
      vat_rate: 20,
    },
  ]);

  useEffect(() => {
    fetchClients();
  }, [fetchClients]);

  useEffect(() => {
    fetchCurrencies();
    fetchRates();
  }, [fetchCurrencies, fetchRates]);

  useEffect(() => {
    if (!clientId) {
      return;
    }

    const selectedClient = clients.find((client) => client.id === clientId);
    if (selectedClient?.preferred_currency) {
      setCurrency(selectedClient.preferred_currency.toUpperCase());
    }
  }, [clientId, clients]);

  const estimatedDocumentTotal = useMemo(() => {
    const subtotal = lineItems.reduce(
      (sum, item) => sum + Number(item.quantity || 0) * Number(item.unit_price || 0),
      0
    );
    const taxAmount = lineItems.reduce((sum, item) => {
      const rowSubtotal =
        Number(item.quantity || 0) * Number(item.unit_price || 0);
      return sum + rowSubtotal * (Number(item.vat_rate || 0) / 100);
    }, 0);

    const discountAmount =
      discountType === "percentage"
        ? subtotal * (Number(discountValue || 0) / 100)
        : discountType === "fixed"
          ? Number(discountValue || 0)
          : 0;

    return Math.max(0, subtotal - discountAmount + taxAmount);
  }, [discountType, discountValue, lineItems]);

  const estimatedBaseTotal = useMemo(() => {
    const normalizedCurrency = currency.toUpperCase();
    const normalizedBase = String(baseCurrency || "EUR").toUpperCase();

    if (normalizedCurrency === normalizedBase) {
      return estimatedDocumentTotal;
    }

    const baseToDocumentRate = rates[normalizedCurrency];
    if (typeof baseToDocumentRate === "number" && baseToDocumentRate > 0) {
      return estimatedDocumentTotal / baseToDocumentRate;
    }

    return estimatedDocumentTotal;
  }, [baseCurrency, currency, estimatedDocumentTotal, rates]);

  const handleSubmit = async () => {
    if (!clientId) {
      toast.error("Please select a client");
      return;
    }

    const sanitizedItems = lineItems
      .map((line) => ({
        ...line,
        description: line.description.trim(),
      }))
      .filter((line) => line.description.length > 0);

    if (sanitizedItems.length === 0) {
      toast.error("At least one line item is required");
      return;
    }

    try {
      const created = await createInvoice({
        client_id: clientId,
        issue_date: issueDate,
        due_date: dueDate,
        currency: currency.toUpperCase(),
        notes,
        discount_type: discountType,
        discount_value: discountType ? discountValue : null,
        line_items: sanitizedItems,
      });

      toast.success("Invoice created");

      if (created?.id) {
        router.push(`/invoices/${created.id}`);
      } else {
        router.push("/invoices");
      }
    } catch (error) {
      toast.error((error as Error).message || "Unable to create invoice");
    }
  };

  return (
    <div className="space-y-6">
      <div className="space-y-2">
        <Button variant="ghost" asChild className="-ml-2">
          <Link href="/invoices">
            <ChevronLeft className="mr-2 h-4 w-4" />
            Back to invoices
          </Link>
        </Button>
        <h1 className="text-3xl font-bold">Create invoice</h1>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Invoice information</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-3 md:grid-cols-3">
            <div className="space-y-2">
              <Label htmlFor="invoice-client">Client</Label>
              <select
                id="invoice-client"
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
              <Label htmlFor="invoice-issue-date">Issue date</Label>
              <Input
                id="invoice-issue-date"
                type="date"
                value={issueDate}
                onChange={(event) => setIssueDate(event.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="invoice-due-date">Due date</Label>
              <Input
                id="invoice-due-date"
                type="date"
                value={dueDate}
                onChange={(event) => setDueDate(event.target.value)}
              />
            </div>
          </div>

          <div className="space-y-2 rounded-md border bg-muted/20 p-3">
            <CurrencySelector
              id="invoice-currency"
              label="Currency"
              value={currency}
              currencies={currencies}
              onValueChange={setCurrency}
            />
            <p className="text-xs text-muted-foreground">
              Estimated in{" "}
              <span className="font-medium">{baseCurrency || "EUR"}:</span>{" "}
              <CurrencyAmount
                amount={estimatedBaseTotal}
                currency={baseCurrency || "EUR"}
                currencies={currencies}
              />
            </p>
            <p className="text-xs text-muted-foreground">
              Document total:{" "}
              <CurrencyAmount
                amount={estimatedDocumentTotal}
                currency={currency}
                currencies={currencies}
              />
            </p>
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
            <Label htmlFor="invoice-notes">Notes</Label>
            <Textarea
              id="invoice-notes"
              rows={4}
              value={notes}
              onChange={(event) => setNotes(event.target.value)}
            />
          </div>

          <div className="flex justify-end">
            <Button type="button" onClick={handleSubmit} disabled={isLoading}>
              {isLoading ? "Saving..." : "Save draft"}
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
