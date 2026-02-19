"use client";

import { useEffect, useState } from "react";
import { toast } from "sonner";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { useInvoiceStore } from "@/lib/stores/invoices";
import { useI18n } from "@/components/providers/i18n-provider";

export default function InvoicingSettingsPage() {
  const { t } = useI18n();
  const {
    invoicingSettings,
    isLoading,
    fetchInvoicingSettings,
    updateInvoicingSettings,
  } = useInvoiceStore();

  const [paymentTermsDays, setPaymentTermsDays] = useState(30);
  const [bankDetails, setBankDetails] = useState("");
  const [invoiceFooter, setInvoiceFooter] = useState("");
  const [numberingPattern, setNumberingPattern] = useState("FAC-YYYY-NNNN");

  useEffect(() => {
    fetchInvoicingSettings().catch((error) => {
      toast.error(
        (error as Error).message || t("settings.invoicing.toasts.loadFailed")
      );
    });
  }, [fetchInvoicingSettings]);

  useEffect(() => {
    if (!invoicingSettings) {
      return;
    }

    setPaymentTermsDays(invoicingSettings.payment_terms_days || 30);
    setBankDetails(invoicingSettings.bank_details || "");
    setInvoiceFooter(invoicingSettings.invoice_footer || "");
    setNumberingPattern(
      invoicingSettings.invoice_numbering_pattern || "FAC-YYYY-NNNN"
    );
  }, [invoicingSettings]);

  const preview = numberingPattern
    .replace("YYYY", String(new Date().getFullYear()))
    .replace("NNNN", "0001");

  const onSave = async () => {
    try {
      await updateInvoicingSettings({
        payment_terms_days: paymentTermsDays,
        bank_details: bankDetails || null,
        invoice_footer: invoiceFooter || null,
        invoice_numbering_pattern: numberingPattern,
      });
      toast.success(t("settings.invoicing.toasts.success"));
    } catch (error) {
      toast.error(
        (error as Error).message || t("settings.invoicing.toasts.saveFailed")
      );
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">{t("settings.invoicing.title")}</h1>
        <p className="text-sm text-muted-foreground">
          {t("settings.invoicing.description")}
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>{t("settings.invoicing.financialDefaults")}</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="payment-terms-days">
              {t("settings.invoicing.defaultPaymentTerms")}
            </Label>
            <Input
              id="payment-terms-days"
              type="number"
              min="1"
              value={paymentTermsDays}
              onChange={(event) =>
                setPaymentTermsDays(Number(event.target.value || 30))
              }
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="invoice-numbering-pattern">
              {t("settings.invoicing.numberingPattern")}
            </Label>
            <Input
              id="invoice-numbering-pattern"
              value={numberingPattern}
              onChange={(event) => setNumberingPattern(event.target.value)}
            />
            <p className="text-xs text-muted-foreground">
              {t("settings.invoicing.preview")} {preview}
            </p>
          </div>

          <div className="space-y-2">
            <Label htmlFor="bank-details">
              {t("settings.invoicing.bankDetails")}
            </Label>
            <Textarea
              id="bank-details"
              rows={4}
              value={bankDetails}
              onChange={(event) => setBankDetails(event.target.value)}
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="invoice-footer">
              {t("settings.invoicing.invoiceFooter")}
            </Label>
            <Textarea
              id="invoice-footer"
              rows={4}
              value={invoiceFooter}
              onChange={(event) => setInvoiceFooter(event.target.value)}
            />
          </div>

          <div className="flex justify-end">
            <Button type="button" onClick={onSave} disabled={isLoading}>
              {isLoading
                ? t("settings.invoicing.saving")
                : t("settings.invoicing.saveChanges")}
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
