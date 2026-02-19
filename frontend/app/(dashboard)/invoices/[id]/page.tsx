"use client";

import { useEffect, useMemo, useState } from "react";
import Link from "next/link";
import { useParams, useRouter } from "next/navigation";
import { toast } from "sonner";
import { ChevronLeft, Copy, Mail, Wallet } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { EmptyState } from "@/components/ui/empty-state";
import { Skeleton } from "@/components/ui/skeleton";
import { InvoiceStatusBadge } from "@/components/invoices/invoice-status-badge";
import { RecordPaymentModal } from "@/components/invoices/record-payment-modal";
import { SendInvoiceModal } from "@/components/invoices/send-invoice-modal";
import { InvoicePdfPreview } from "@/components/invoices/invoice-pdf-preview";
import { CurrencyAmount } from "@/components/shared/currency-amount";
import { useInvoiceStore } from "@/lib/stores/invoices";
import { apiClient } from "@/lib/api";
import { useI18n } from "@/components/providers/i18n-provider";

function buildPreviewHtml(invoice: any): string {
  if (!invoice) {
    return "";
  }

  const rows = (invoice.line_items || [])
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
        <h2>${invoice.number}</h2>
        <p>Status: ${invoice.status}</p>
        <p>Client: ${invoice.client?.name || invoice.client_id}</p>
        <table style="width: 100%; border-collapse: collapse;" border="1" cellpadding="6">
          <thead><tr><th>Description</th><th>Qty</th><th>Unit</th><th>Total</th></tr></thead>
          <tbody>${rows}</tbody>
        </table>
        <p style="margin-top: 12px;"><strong>Total:</strong> ${Number(invoice.total).toFixed(2)} ${invoice.currency || "EUR"}</p>
      </body>
    </html>
  `;
}

export default function InvoiceDetailPage() {
  const { t } = useI18n();
  const params = useParams<{ id: string }>();
  const router = useRouter();
  const invoiceId = params.id;

  const {
    currentInvoice,
    isLoading,
    fetchInvoice,
    sendInvoice,
    duplicateInvoice,
    recordPayment,
  } = useInvoiceStore();

  const [paymentModalOpen, setPaymentModalOpen] = useState(false);
  const [sendModalOpen, setSendModalOpen] = useState(false);
  const [portalPaymentEnabled, setPortalPaymentEnabled] = useState(false);

  useEffect(() => {
    if (!invoiceId) {
      return;
    }

    fetchInvoice(invoiceId).catch((error) => {
      toast.error((error as Error).message || "Unable to load invoice");
      router.push("/invoices");
    });
  }, [fetchInvoice, invoiceId, router]);

  useEffect(() => {
    apiClient
      .get<any>("/settings/portal")
      .then((response) =>
        setPortalPaymentEnabled(Boolean(response.data?.payment_enabled))
      )
      .catch(() => setPortalPaymentEnabled(false));
  }, []);

  const payments = useMemo(
    () => currentInvoice?.payments || [],
    [currentInvoice]
  );

  if (isLoading && !currentInvoice) {
    return (
      <div className="space-y-4">
        <Skeleton className="h-8 w-52" />
        <Skeleton className="h-40 w-full" />
      </div>
    );
  }

  if (!currentInvoice) {
    return (
      <EmptyState
        title={t("invoices.detail.notFound")}
        description={t("invoices.detail.notFoundDescription")}
        action={
          <Button asChild>
            <Link href="/invoices">{t("invoices.detail.backToInvoices")}</Link>
          </Button>
        }
      />
    );
  }

  const onSend = async () => {
    try {
      await sendInvoice(currentInvoice.id);
      toast.success(t("invoices.detail.toasts.sent"));
    } catch (error) {
      toast.error(
        (error as Error).message || t("invoices.detail.toasts.sendFailed")
      );
    }
  };

  const onDuplicate = async () => {
    try {
      const clone = await duplicateInvoice(currentInvoice.id);
      toast.success(t("invoices.detail.toasts.duplicated"));

      if (clone?.id) {
        router.push(`/invoices/${clone.id}`);
      }
    } catch (error) {
      toast.error(
        (error as Error).message || t("invoices.detail.toasts.duplicateFailed")
      );
    }
  };

  return (
    <div className="space-y-6">
      <div className="space-y-2">
        <Button variant="ghost" className="-ml-2" asChild>
          <Link href="/invoices">
            <ChevronLeft className="mr-2 h-4 w-4" />
            {t("invoices.detail.backToInvoices")}
          </Link>
        </Button>

        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-3xl font-bold">{currentInvoice.number}</h1>
            <p className="text-sm text-muted-foreground">
              {currentInvoice.issue_date} - {currentInvoice.due_date}
            </p>
          </div>
          <div className="flex flex-wrap items-center gap-2">
            <InvoiceStatusBadge status={currentInvoice.status} />
            {portalPaymentEnabled &&
            ["sent", "viewed", "partially_paid", "overdue"].includes(
              currentInvoice.status
            ) ? (
              <span className="rounded-full border px-2 py-0.5 text-xs font-medium">
                {t("invoices.detail.portalPayment")}
              </span>
            ) : null}
            <Button
              type="button"
              variant="outline"
              onClick={() => setSendModalOpen(true)}
            >
              <Mail className="mr-2 h-4 w-4" />
              {t("invoices.detail.send")}
            </Button>
            <Button
              type="button"
              variant="outline"
              onClick={() => setPaymentModalOpen(true)}
            >
              <Wallet className="mr-2 h-4 w-4" />
              {t("invoices.detail.recordPayment")}
            </Button>
            <Button type="button" variant="outline" onClick={onDuplicate}>
              <Copy className="mr-2 h-4 w-4" />
              {t("invoices.detail.duplicate")}
            </Button>
          </div>
        </div>
      </div>

      <div className="grid gap-6 lg:grid-cols-[2fr_1fr]">
        <Card>
          <CardHeader>
            <CardTitle>{t("invoices.detail.invoiceDetails")}</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid gap-3 md:grid-cols-3">
              <div>
                <p className="text-xs text-muted-foreground">
                  {t("invoices.detail.client")}
                </p>
                <p className="font-medium">
                  {currentInvoice.client?.name || currentInvoice.client_id}
                </p>
              </div>
              <div>
                <p className="text-xs text-muted-foreground">
                  {t("invoices.detail.total")}
                </p>
                <p className="font-medium">
                  <CurrencyAmount
                    amount={Number(currentInvoice.total)}
                    currency={currentInvoice.currency || "EUR"}
                  />
                </p>
              </div>
              <div>
                <p className="text-xs text-muted-foreground">
                  {t("invoices.detail.amountPaid")}
                </p>
                <p className="font-medium">
                  <CurrencyAmount
                    amount={Number(currentInvoice.amount_paid || 0)}
                    currency={currentInvoice.currency || "EUR"}
                  />
                </p>
              </div>
            </div>

            <div>
              <h2 className="mb-2 text-sm font-semibold">
                {t("invoices.detail.lineItems")}
              </h2>
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b text-left">
                      <th className="pb-2">
                        {t("invoices.detail.description")}
                      </th>
                      <th className="pb-2">{t("invoices.detail.qty")}</th>
                      <th className="pb-2">{t("invoices.detail.unit")}</th>
                      <th className="pb-2">{t("invoices.detail.vat")}</th>
                      <th className="pb-2">{t("invoices.detail.total")}</th>
                    </tr>
                  </thead>
                  <tbody>
                    {(currentInvoice.line_items || []).map((line, index) => (
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
                            currency={currentInvoice.currency || "EUR"}
                          />
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>

            <div>
              <h2 className="mb-2 text-sm font-semibold">
                {t("invoices.detail.paymentHistory")}
              </h2>
              {payments.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                  {t("invoices.detail.noPayment")}
                </p>
              ) : (
                <ul className="space-y-2">
                  {payments.map((payment) => (
                    <li
                      key={payment.id}
                      className="rounded-md border p-2 text-sm"
                    >
                      {payment.payment_date} -{" "}
                      <CurrencyAmount
                        amount={Number(payment.amount)}
                        currency={currentInvoice.currency || "EUR"}
                      />
                    </li>
                  ))}
                </ul>
              )}
            </div>

            <div>
              <h2 className="mb-2 text-sm font-semibold">
                {t("invoices.detail.linkedCreditNotes")}
              </h2>
              {(currentInvoice.credit_notes || []).length === 0 ? (
                <p className="text-sm text-muted-foreground">
                  {t("invoices.detail.noCreditNote")}
                </p>
              ) : (
                <ul className="space-y-2">
                  {(currentInvoice.credit_notes || []).map((creditNote) => (
                    <li
                      key={creditNote.id}
                      className="rounded-md border p-2 text-sm"
                    >
                      <Link
                        href={`/credit-notes/${creditNote.id}`}
                        className="font-medium text-primary hover:underline"
                      >
                        {creditNote.number}
                      </Link>{" "}
                      -{" "}
                      <CurrencyAmount
                        amount={Number(creditNote.total)}
                        currency={currentInvoice.currency || "EUR"}
                      />
                    </li>
                  ))}
                </ul>
              )}
            </div>
          </CardContent>
        </Card>

        <InvoicePdfPreview html={buildPreviewHtml(currentInvoice)} />
      </div>

      <SendInvoiceModal
        open={sendModalOpen}
        onOpenChange={setSendModalOpen}
        invoiceNumber={currentInvoice.number}
        clientEmail={currentInvoice.client?.email}
        onSubmit={onSend}
      />

      <RecordPaymentModal
        open={paymentModalOpen}
        onOpenChange={setPaymentModalOpen}
        invoiceTotal={Number(currentInvoice.total || 0)}
        amountPaid={Number(currentInvoice.amount_paid || 0)}
        onSubmit={async (payload) => {
          await recordPayment(currentInvoice.id, payload);
          toast.success(t("invoices.detail.toasts.paymentRecorded"));
        }}
      />
    </div>
  );
}
