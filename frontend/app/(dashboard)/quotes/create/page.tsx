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
import { useQuoteStore } from "@/lib/stores/quotes";
import { useI18n } from "@/components/providers/i18n-provider";

const today = new Date().toISOString().slice(0, 10);
const inThirtyDays = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000)
  .toISOString()
  .slice(0, 10);

export default function CreateQuotePage() {
  const { t } = useI18n();
  const router = useRouter();
  const { clients, fetchClients } = useClientStore();
  const { currencies, rates, baseCurrency, fetchCurrencies, fetchRates } =
    useCurrencyStore();
  const { createQuote, isLoading } = useQuoteStore();

  const [clientId, setClientId] = useState("");
  const [issueDate, setIssueDate] = useState(today);
  const [validUntil, setValidUntil] = useState(inThirtyDays);
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
      (sum, item) =>
        sum + Number(item.quantity || 0) * Number(item.unit_price || 0),
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
      toast.error(t("quotes.create.selectClientRequired"));
      return;
    }

    const sanitizedItems = lineItems
      .map((line) => ({
        ...line,
        description: line.description.trim(),
      }))
      .filter((line) => line.description.length > 0);

    if (sanitizedItems.length === 0) {
      toast.error(t("quotes.create.lineItemRequired"));
      return;
    }

    try {
      const created = await createQuote({
        client_id: clientId,
        issue_date: issueDate,
        valid_until: validUntil,
        currency: currency.toUpperCase(),
        notes,
        discount_type: discountType,
        discount_value: discountType ? discountValue : null,
        line_items: sanitizedItems,
      });

      toast.success(t("quotes.create.toasts.success"));

      if (created?.id) {
        router.push(`/quotes/${created.id}`);
      } else {
        router.push("/quotes");
      }
    } catch (error) {
      toast.error((error as Error).message || t("quotes.create.toasts.failed"));
    }
  };

  return (
    <div className="space-y-6">
      <div className="space-y-2">
        <Button variant="ghost" asChild className="-ml-2">
          <Link href="/quotes">
            <ChevronLeft className="mr-2 h-4 w-4" />
            {t("quotes.create.backToQuotes")}
          </Link>
        </Button>
        <h1 className="text-3xl font-bold">{t("quotes.create.title")}</h1>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>{t("quotes.create.quoteInfo")}</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-3 md:grid-cols-3">
            <div className="space-y-2">
              <Label htmlFor="quote-client">{t("quotes.create.client")}</Label>
              <select
                id="quote-client"
                className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                value={clientId}
                onChange={(event) => setClientId(event.target.value)}
              >
                <option value="">{t("quotes.create.selectClient")}</option>
                {clients.map((client) => (
                  <option key={client.id} value={client.id}>
                    {client.name}
                  </option>
                ))}
              </select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="quote-issue-date">
                {t("quotes.create.issueDate")}
              </Label>
              <Input
                id="quote-issue-date"
                type="date"
                value={issueDate}
                onChange={(event) => setIssueDate(event.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="quote-valid-until">
                {t("quotes.create.validUntil")}
              </Label>
              <Input
                id="quote-valid-until"
                type="date"
                value={validUntil}
                onChange={(event) => setValidUntil(event.target.value)}
              />
            </div>
          </div>

          <div className="space-y-2 rounded-md border bg-muted/20 p-3">
            <CurrencySelector
              id="quote-currency"
              label={t("quotes.create.currency")}
              value={currency}
              currencies={currencies}
              onValueChange={setCurrency}
            />
            <p className="text-xs text-muted-foreground">
              {t("quotes.create.estimatedIn")}{" "}
              <span className="font-medium">{baseCurrency || "EUR"}:</span>{" "}
              <CurrencyAmount
                amount={estimatedBaseTotal}
                currency={baseCurrency || "EUR"}
                currencies={currencies}
              />
            </p>
            <p className="text-xs text-muted-foreground">
              {t("quotes.create.documentTotal")}:{" "}
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
            <Label htmlFor="quote-notes">{t("quotes.create.notes")}</Label>
            <Textarea
              id="quote-notes"
              rows={4}
              value={notes}
              onChange={(event) => setNotes(event.target.value)}
            />
          </div>

          <div className="flex justify-end">
            <Button type="button" onClick={handleSubmit} disabled={isLoading}>
              {isLoading
                ? t("quotes.create.saving")
                : t("quotes.create.saveDraft")}
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
