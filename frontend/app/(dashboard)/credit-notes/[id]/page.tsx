"use client";

import { useEffect } from "react";
import Link from "next/link";
import { useParams, useRouter } from "next/navigation";
import { toast } from "sonner";
import { CheckCircle2, ChevronLeft, Mail } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { EmptyState } from "@/components/ui/empty-state";
import { Skeleton } from "@/components/ui/skeleton";
import { CreditNoteStatusBadge } from "@/components/credit-notes/credit-note-status-badge";
import { CurrencyAmount } from "@/components/shared/currency-amount";
import { useCreditNoteStore } from "@/lib/stores/creditNotes";
import { useI18n } from "@/components/providers/i18n-provider";

export default function CreditNoteDetailPage() {
  const params = useParams<{ id: string }>();
  const router = useRouter();
  const creditNoteId = params.id;

  const { t } = useI18n();
  const {
    currentCreditNote,
    isLoading,
    fetchCreditNote,
    sendCreditNote,
    applyCreditNote,
  } = useCreditNoteStore();

  useEffect(() => {
    if (!creditNoteId) {
      return;
    }

    fetchCreditNote(creditNoteId).catch((error) => {
      toast.error((error as Error).message || "Unable to load credit note");
      router.push("/credit-notes");
    });
  }, [fetchCreditNote, creditNoteId, router]);

  if (isLoading && !currentCreditNote) {
    return (
      <div className="space-y-4">
        <Skeleton className="h-8 w-52" />
        <Skeleton className="h-40 w-full" />
      </div>
    );
  }

  if (!currentCreditNote) {
    return (
      <EmptyState
        title={t("creditNotes.detail.notFound")}
        description={t("creditNotes.detail.notFoundDescription")}
        action={
          <Button asChild>
            <Link href="/credit-notes">
              {t("creditNotes.detail.backToCreditNotes")}
            </Link>
          </Button>
        }
      />
    );
  }

  const onSend = async () => {
    try {
      await sendCreditNote(currentCreditNote.id);
      toast.success(t("creditNotes.detail.toasts.sent"));
    } catch (error) {
      toast.error(
        (error as Error).message || t("creditNotes.detail.toasts.sendFailed")
      );
    }
  };

  const onApply = async () => {
    try {
      await applyCreditNote(currentCreditNote.id);
      toast.success(t("creditNotes.detail.toasts.applied"));
    } catch (error) {
      toast.error(
        (error as Error).message || t("creditNotes.detail.toasts.applyFailed")
      );
    }
  };

  return (
    <div className="space-y-6">
      <div className="space-y-2">
        <Button variant="ghost" className="-ml-2" asChild>
          <Link href="/credit-notes">
            <ChevronLeft className="mr-2 h-4 w-4" />
            {t("creditNotes.detail.backToCreditNotes")}
          </Link>
        </Button>

        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-3xl font-bold">{currentCreditNote.number}</h1>
            <p className="text-sm text-muted-foreground">
              {currentCreditNote.issue_date}
            </p>
          </div>
          <div className="flex flex-wrap items-center gap-2">
            <CreditNoteStatusBadge status={currentCreditNote.status} />
            <Button type="button" variant="outline" onClick={onSend}>
              <Mail className="mr-2 h-4 w-4" />
              {t("creditNotes.detail.send")}
            </Button>
            <Button type="button" variant="outline" onClick={onApply}>
              <CheckCircle2 className="mr-2 h-4 w-4" />
              {t("creditNotes.detail.apply")}
            </Button>
          </div>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>{t("creditNotes.detail.creditNoteDetails")}</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-3 md:grid-cols-3">
            <div>
              <p className="text-xs text-muted-foreground">
                {t("creditNotes.detail.invoice")}
              </p>
              {currentCreditNote.invoice?.id ? (
                <Link
                  href={`/invoices/${currentCreditNote.invoice.id}`}
                  className="font-medium text-primary hover:underline"
                >
                  {currentCreditNote.invoice.number}
                </Link>
              ) : (
                <p className="font-medium">{currentCreditNote.invoice_id}</p>
              )}
            </div>
            <div>
              <p className="text-xs text-muted-foreground">
                {t("creditNotes.detail.total")}
              </p>
              <p className="font-medium">
                <CurrencyAmount
                  amount={Number(currentCreditNote.total)}
                  currency={currentCreditNote.currency || "EUR"}
                />
              </p>
            </div>
            <div>
              <p className="text-xs text-muted-foreground">
                {t("creditNotes.detail.reason")}
              </p>
              <p className="font-medium">{currentCreditNote.reason || "-"}</p>
            </div>
          </div>

          <div>
            <h2 className="mb-2 text-sm font-semibold">
              {t("creditNotes.detail.lineItems")}
            </h2>
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b text-left">
                    <th className="pb-2">
                      {t("creditNotes.detail.description")}
                    </th>
                    <th className="pb-2">{t("creditNotes.detail.qty")}</th>
                    <th className="pb-2">{t("creditNotes.detail.unit")}</th>
                    <th className="pb-2">{t("creditNotes.detail.vat")}</th>
                    <th className="pb-2">{t("creditNotes.detail.total")}</th>
                  </tr>
                </thead>
                <tbody>
                  {(currentCreditNote.line_items || []).map((line, index) => (
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
                          currency={currentCreditNote.currency || "EUR"}
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
    </div>
  );
}
