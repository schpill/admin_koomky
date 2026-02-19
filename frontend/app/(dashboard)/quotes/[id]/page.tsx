"use client";

import { useEffect, useMemo, useState } from "react";
import Link from "next/link";
import { useParams, useRouter } from "next/navigation";
import { toast } from "sonner";
import {
  ArrowRightLeft,
  CheckCircle2,
  ChevronLeft,
  Mail,
  XCircle,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { EmptyState } from "@/components/ui/empty-state";
import { Skeleton } from "@/components/ui/skeleton";
import { QuoteStatusBadge } from "@/components/quotes/quote-status-badge";
import { QuotePdfPreview } from "@/components/quotes/quote-pdf-preview";
import { CurrencyAmount } from "@/components/shared/currency-amount";
import { ConfirmationDialog } from "@/components/common/confirmation-dialog";
import { useQuoteStore } from "@/lib/stores/quotes";
import { useI18n } from "@/components/providers/i18n-provider";

function buildPreviewHtml(quote: any): string {
  if (!quote) {
    return "";
  }

  const rows = (quote.line_items || [])
    .map(
      (line: any) => `
      <tr>
        <td>${line.description}</td>
        <td>${Number(line.quantity).toFixed(2)}</td>
        <td>${Number(line.unit_price).toFixed(2)}</td>
        <td>${Number(line.total || line.quantity * line.unit_price).toFixed(2)}</td>
      </tr>
    `
    )
    .join("");

  return `
    <html>
      <body style="font-family: Arial, sans-serif; padding: 16px;">
        <h2>${quote.number}</h2>
        <p>Status: ${quote.status}</p>
        <p>Client: ${quote.client?.name || quote.client_id}</p>
        <p>Valid until: ${quote.valid_until}</p>
        <table style="width: 100%; border-collapse: collapse;" border="1" cellpadding="6">
          <thead><tr><th>Description</th><th>Qty</th><th>Unit</th><th>Total</th></tr></thead>
          <tbody>${rows}</tbody>
        </table>
        <p style="margin-top: 12px;"><strong>Total:</strong> ${Number(quote.total).toFixed(2)} ${quote.currency || "EUR"}</p>
      </body>
    </html>
  `;
}

export default function QuoteDetailPage() {
  const { t } = useI18n();
  const params = useParams<{ id: string }>();
  const router = useRouter();
  const quoteId = params.id;

  const {
    currentQuote,
    isLoading,
    fetchQuote,
    sendQuote,
    acceptQuote,
    rejectQuote,
    convertQuote,
  } = useQuoteStore();

  const [convertDialogOpen, setConvertDialogOpen] = useState(false);

  useEffect(() => {
    if (!quoteId) {
      return;
    }

    fetchQuote(quoteId).catch((error) => {
      toast.error((error as Error).message || "Unable to load quote");
      router.push("/quotes");
    });
  }, [fetchQuote, quoteId, router]);

  const lineItems = useMemo(
    () => currentQuote?.line_items || [],
    [currentQuote]
  );

  if (isLoading && !currentQuote) {
    return (
      <div className="space-y-4">
        <Skeleton className="h-8 w-52" />
        <Skeleton className="h-40 w-full" />
      </div>
    );
  }

  if (!currentQuote) {
    return (
      <EmptyState
        title={t("quotes.detail.notFound")}
        description={t("quotes.detail.notFoundDescription")}
        action={
          <Button asChild>
            <Link href="/quotes">{t("quotes.detail.backToQuotes")}</Link>
          </Button>
        }
      />
    );
  }

  const onSend = async () => {
    try {
      await sendQuote(currentQuote.id);
      toast.success(t("quotes.detail.toasts.sent"));
    } catch (error) {
      toast.error(
        (error as Error).message || t("quotes.detail.toasts.sendFailed")
      );
    }
  };

  const onAccept = async () => {
    try {
      await acceptQuote(currentQuote.id);
      toast.success(t("quotes.detail.toasts.accepted"));
    } catch (error) {
      toast.error(
        (error as Error).message || t("quotes.detail.toasts.acceptFailed")
      );
    }
  };

  const onReject = async () => {
    try {
      await rejectQuote(currentQuote.id);
      toast.success(t("quotes.detail.toasts.rejected"));
    } catch (error) {
      toast.error(
        (error as Error).message || t("quotes.detail.toasts.rejectFailed")
      );
    }
  };

  const onConvert = async () => {
    try {
      const invoice = await convertQuote(currentQuote.id);
      toast.success(t("quotes.detail.toasts.converted"));
      setConvertDialogOpen(false);
      if (invoice?.id) {
        router.push(`/invoices/${invoice.id}`);
      }
    } catch (error) {
      toast.error(
        (error as Error).message || t("quotes.detail.toasts.convertFailed")
      );
    }
  };

  return (
    <div className="space-y-6">
      <div className="space-y-2">
        <Button variant="ghost" className="-ml-2" asChild>
          <Link href="/quotes">
            <ChevronLeft className="mr-2 h-4 w-4" />
            {t("quotes.detail.backToQuotes")}
          </Link>
        </Button>

        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-3xl font-bold">{currentQuote.number}</h1>
            <p className="text-sm text-muted-foreground">
              {currentQuote.issue_date} - {currentQuote.valid_until}
            </p>
          </div>
          <div className="flex flex-wrap items-center gap-2">
            <QuoteStatusBadge status={currentQuote.status} />
            <Button type="button" variant="outline" onClick={onSend}>
              <Mail className="mr-2 h-4 w-4" />
              {t("quotes.detail.send")}
            </Button>
            <Button type="button" variant="outline" onClick={onAccept}>
              <CheckCircle2 className="mr-2 h-4 w-4" />
              {t("quotes.detail.accept")}
            </Button>
            <Button type="button" variant="outline" onClick={onReject}>
              <XCircle className="mr-2 h-4 w-4" />
              {t("quotes.detail.reject")}
            </Button>
            <Button
              type="button"
              variant="outline"
              onClick={() => setConvertDialogOpen(true)}
            >
              <ArrowRightLeft className="mr-2 h-4 w-4" />
              {t("quotes.detail.convertToInvoice")}
            </Button>
          </div>
        </div>
      </div>

      <div className="grid gap-6 lg:grid-cols-[2fr_1fr]">
        <Card>
          <CardHeader>
            <CardTitle>{t("quotes.detail.quoteDetails")}</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid gap-3 md:grid-cols-3">
              <div>
                <p className="text-xs text-muted-foreground">
                  {t("quotes.detail.client")}
                </p>
                <p className="font-medium">
                  {currentQuote.client?.name || currentQuote.client_id}
                </p>
              </div>
              <div>
                <p className="text-xs text-muted-foreground">
                  {t("quotes.detail.total")}
                </p>
                <p className="font-medium">
                  <CurrencyAmount
                    amount={Number(currentQuote.total)}
                    currency={currentQuote.currency || "EUR"}
                  />
                </p>
              </div>
              <div>
                <p className="text-xs text-muted-foreground">
                  {t("quotes.detail.convertedInvoice")}
                </p>
                {currentQuote.converted_invoice_id ? (
                  <Link
                    href={`/invoices/${currentQuote.converted_invoice_id}`}
                    className="font-medium text-primary hover:underline"
                  >
                    {currentQuote.converted_invoice_id}
                  </Link>
                ) : (
                  <p className="font-medium text-muted-foreground">
                    {t("quotes.detail.notConverted")}
                  </p>
                )}
              </div>
            </div>

            <div>
              <h2 className="mb-2 text-sm font-semibold">
                {t("quotes.detail.lineItems")}
              </h2>
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b text-left">
                      <th className="pb-2">{t("quotes.detail.description")}</th>
                      <th className="pb-2">{t("quotes.detail.qty")}</th>
                      <th className="pb-2">{t("quotes.detail.unit")}</th>
                      <th className="pb-2">{t("quotes.detail.vat")}</th>
                      <th className="pb-2">{t("quotes.detail.total")}</th>
                    </tr>
                  </thead>
                  <tbody>
                    {lineItems.map((line, index) => (
                      <tr
                        key={`${line.description}-${index}`}
                        className="border-b last:border-0"
                      >
                        <td className="py-2">{line.description}</td>
                        <td className="py-2">
                          {Number(line.quantity).toFixed(2)}
                        </td>
                        <td className="py-2">
                          {Number(line.unit_price).toFixed(2)}
                        </td>
                        <td className="py-2">
                          {Number(line.vat_rate).toFixed(2)}%
                        </td>
                        <td className="py-2">
                          <CurrencyAmount
                            amount={Number(
                              line.total || line.quantity * line.unit_price
                            )}
                            currency={currentQuote.currency || "EUR"}
                          />
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          </CardContent>
        </Card>

        <QuotePdfPreview html={buildPreviewHtml(currentQuote)} />
      </div>

      <ConfirmationDialog
        open={convertDialogOpen}
        onOpenChange={setConvertDialogOpen}
        onConfirm={onConvert}
        title={t("quotes.detail.convertDialog.title")}
        description={t("quotes.detail.convertDialog.description")}
        confirmText={t("quotes.detail.convertDialog.confirm")}
      />
    </div>
  );
}
