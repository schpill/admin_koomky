"use client";

import Link from "next/link";
import { useEffect, useMemo, useState } from "react";
import { useRouter } from "next/navigation";
import { toast } from "sonner";
import { ChevronLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import {
  LineItemsEditor,
  type InvoiceLineItemInput,
} from "@/components/invoices/line-items-editor";
import { CurrencyAmount } from "@/components/shared/currency-amount";
import { CurrencySelector } from "@/components/shared/currency-selector";
import { useCurrencyStore } from "@/lib/stores/currencies";
import { useInvoiceStore } from "@/lib/stores/invoices";
import { useCreditNoteStore } from "@/lib/stores/creditNotes";
import { useI18n } from "@/components/providers/i18n-provider";

const today = new Date().toISOString().slice(0, 10);

export default function CreateCreditNotePage() {
  const { t } = useI18n();
  const router = useRouter();
  const { currencies, rates, baseCurrency, fetchCurrencies, fetchRates } =
    useCurrencyStore();
  const { invoices, currentInvoice, fetchInvoices, fetchInvoice } =
    useInvoiceStore();
  const { createCreditNote, isLoading } = useCreditNoteStore();

  const [invoiceId, setInvoiceId] = useState("");
  const [currency, setCurrency] = useState("EUR");
  const [issueDate, setIssueDate] = useState(today);
  const [reason, setReason] = useState("");
  const [lineItems, setLineItems] = useState<InvoiceLineItemInput[]>([]);

  useEffect(() => {
    fetchInvoices({ per_page: 100, sort_by: "issue_date", sort_order: "desc" });
    fetchCurrencies();
    fetchRates();
  }, [fetchCurrencies, fetchInvoices, fetchRates]);

  useEffect(() => {
    if (!invoiceId) {
      setLineItems([]);
      return;
    }

    fetchInvoice(invoiceId)
      .then((invoice) => {
        const prefilled = (invoice?.line_items || []).map((line) => ({
          description: line.description,
          quantity: Number(line.quantity),
          unit_price: Number(line.unit_price),
          vat_rate: Number(line.vat_rate),
        }));

        setLineItems(prefilled.length > 0 ? prefilled : []);
      })
      .catch((error) => {
        toast.error(
          (error as Error).message || "Unable to load invoice details"
        );
      });
  }, [invoiceId, fetchInvoice]);

  const selectedInvoice = useMemo(() => {
    return currentInvoice?.id === invoiceId
      ? currentInvoice
      : invoices.find((invoice) => invoice.id === invoiceId) || null;
  }, [currentInvoice, invoiceId, invoices]);

  useEffect(() => {
    if (selectedInvoice?.currency) {
      setCurrency(selectedInvoice.currency.toUpperCase());
    }
  }, [selectedInvoice?.currency]);

  const estimatedDocumentTotal = useMemo(() => {
    return lineItems.reduce((sum, item) => {
      const subtotal =
        Number(item.quantity || 0) * Number(item.unit_price || 0);
      const tax = subtotal * (Number(item.vat_rate || 0) / 100);
      return sum + subtotal + tax;
    }, 0);
  }, [lineItems]);

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
    if (!invoiceId) {
      toast.error(t("creditNotes.create.selectInvoiceRequired"));
      return;
    }

    const sanitizedItems = lineItems
      .map((line) => ({
        ...line,
        description: line.description.trim(),
      }))
      .filter((line) => line.description.length > 0);

    if (sanitizedItems.length === 0) {
      toast.error(t("creditNotes.create.lineItemRequired"));
      return;
    }

    try {
      const created = await createCreditNote({
        invoice_id: invoiceId,
        issue_date: issueDate,
        currency: currency.toUpperCase(),
        reason,
        line_items: sanitizedItems,
      });

      toast.success(t("creditNotes.create.toasts.success"));

      if (created?.id) {
        router.push(`/credit-notes/${created.id}`);
      } else {
        router.push("/credit-notes");
      }
    } catch (error) {
      toast.error(
        (error as Error).message || t("creditNotes.create.toasts.failed")
      );
    }
  };

  return (
    <div className="space-y-6">
      <div className="space-y-2">
        <Button variant="ghost" asChild className="-ml-2">
          <Link href="/credit-notes">
            <ChevronLeft className="mr-2 h-4 w-4" />
            {t("creditNotes.create.backToCreditNotes")}
          </Link>
        </Button>
        <h1 className="text-3xl font-bold">{t("creditNotes.create.title")}</h1>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>{t("creditNotes.create.creditNoteInfo")}</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-3 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="credit-note-invoice">
                {t("creditNotes.create.invoice")}
              </Label>
              <select
                id="credit-note-invoice"
                className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                value={invoiceId}
                onChange={(event) => setInvoiceId(event.target.value)}
              >
                <option value="">
                  {t("creditNotes.create.selectInvoice")}
                </option>
                {invoices.map((invoice) => (
                  <option key={invoice.id} value={invoice.id}>
                    {invoice.number} -{" "}
                    {Number(invoice.balance_due || 0).toFixed(2)} EUR{" "}
                    {t("creditNotes.create.due")}
                  </option>
                ))}
              </select>
            </div>

            <div className="space-y-2">
              <Label htmlFor="credit-note-issue-date">
                {t("creditNotes.create.issueDate")}
              </Label>
              <Input
                id="credit-note-issue-date"
                type="date"
                value={issueDate}
                onChange={(event) => setIssueDate(event.target.value)}
              />
            </div>
          </div>

          {selectedInvoice && (
            <div className="rounded-md border bg-muted/30 p-3 text-sm">
              <p className="font-medium">
                {t("creditNotes.create.selectedInvoice")}{" "}
                {selectedInvoice.number}
              </p>
              <p className="text-muted-foreground">
                {t("creditNotes.create.remainingBalance")}{" "}
                <CurrencyAmount
                  amount={Number(selectedInvoice.balance_due || 0)}
                  currency={selectedInvoice.currency || currency}
                  currencies={currencies}
                />
              </p>
            </div>
          )}

          <div className="space-y-2 rounded-md border bg-muted/20 p-3">
            <CurrencySelector
              id="credit-note-currency"
              label={t("creditNotes.create.currency")}
              value={currency}
              currencies={currencies}
              onValueChange={setCurrency}
            />
            <p className="text-xs text-muted-foreground">
              {t("creditNotes.create.estimatedIn")}{" "}
              <span className="font-medium">{baseCurrency || "EUR"}:</span>{" "}
              <CurrencyAmount
                amount={estimatedBaseTotal}
                currency={baseCurrency || "EUR"}
                currencies={currencies}
              />
            </p>
            <p className="text-xs text-muted-foreground">
              {t("creditNotes.create.documentTotal")}{" "}
              <CurrencyAmount
                amount={estimatedDocumentTotal}
                currency={currency}
                currencies={currencies}
              />
            </p>
          </div>

          <LineItemsEditor
            items={lineItems}
            discountType={null}
            discountValue={0}
            onItemsChange={setLineItems}
            onDiscountTypeChange={() => undefined}
            onDiscountValueChange={() => undefined}
          />

          <div className="space-y-2">
            <Label htmlFor="credit-note-reason">
              {t("creditNotes.create.reason")}
            </Label>
            <Textarea
              id="credit-note-reason"
              rows={4}
              value={reason}
              onChange={(event) => setReason(event.target.value)}
            />
          </div>

          <div className="flex justify-end">
            <Button type="button" onClick={handleSubmit} disabled={isLoading}>
              {isLoading
                ? t("creditNotes.create.saving")
                : t("creditNotes.create.saveDraft")}
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
