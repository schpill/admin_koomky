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
import { useCreditNoteStore } from "@/lib/stores/creditNotes";

export default function CreditNoteDetailPage() {
  const params = useParams<{ id: string }>();
  const router = useRouter();
  const creditNoteId = params.id;

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
        title="Credit note not found"
        description="This credit note may have been deleted or you no longer have access."
        action={
          <Button asChild>
            <Link href="/credit-notes">Back to credit notes</Link>
          </Button>
        }
      />
    );
  }

  const onSend = async () => {
    try {
      await sendCreditNote(currentCreditNote.id);
      toast.success("Credit note sent");
    } catch (error) {
      toast.error((error as Error).message || "Unable to send credit note");
    }
  };

  const onApply = async () => {
    try {
      await applyCreditNote(currentCreditNote.id);
      toast.success("Credit note applied");
    } catch (error) {
      toast.error((error as Error).message || "Unable to apply credit note");
    }
  };

  return (
    <div className="space-y-6">
      <div className="space-y-2">
        <Button variant="ghost" className="-ml-2" asChild>
          <Link href="/credit-notes">
            <ChevronLeft className="mr-2 h-4 w-4" />
            Back to credit notes
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
              Send
            </Button>
            <Button type="button" variant="outline" onClick={onApply}>
              <CheckCircle2 className="mr-2 h-4 w-4" />
              Apply
            </Button>
          </div>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Credit note details</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-3 md:grid-cols-3">
            <div>
              <p className="text-xs text-muted-foreground">Invoice</p>
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
              <p className="text-xs text-muted-foreground">Total</p>
              <p className="font-medium">
                {Number(currentCreditNote.total).toFixed(2)} EUR
              </p>
            </div>
            <div>
              <p className="text-xs text-muted-foreground">Reason</p>
              <p className="font-medium">{currentCreditNote.reason || "-"}</p>
            </div>
          </div>

          <div>
            <h2 className="mb-2 text-sm font-semibold">Line items</h2>
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b text-left">
                    <th className="pb-2">Description</th>
                    <th className="pb-2">Qty</th>
                    <th className="pb-2">Unit</th>
                    <th className="pb-2">VAT</th>
                    <th className="pb-2">Total</th>
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
                        {Number(
                          line.total || line.quantity * line.unit_price
                        ).toFixed(2)}
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
