"use client";

import Link from "next/link";
import { useEffect } from "react";
import { Plus } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { EmptyState } from "@/components/ui/empty-state";
import { Skeleton } from "@/components/ui/skeleton";
import { useCreditNoteStore } from "@/lib/stores/creditNotes";
import { CreditNoteStatusBadge } from "@/components/credit-notes/credit-note-status-badge";

export default function CreditNotesPage() {
  const { creditNotes, isLoading, pagination, fetchCreditNotes } =
    useCreditNoteStore();

  useEffect(() => {
    fetchCreditNotes({ sort_by: "issue_date", sort_order: "desc" });
  }, [fetchCreditNotes]);

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="text-3xl font-bold">Credit notes</h1>
          <p className="text-sm text-muted-foreground">
            {pagination ? `${pagination.total} credit notes` : ""}
          </p>
        </div>
        <Button asChild>
          <Link href="/credit-notes/create">
            <Plus className="mr-2 h-4 w-4" />
            New credit note
          </Link>
        </Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Credit note list</CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading && creditNotes.length === 0 ? (
            <div className="space-y-3">
              <Skeleton className="h-12 w-full" />
              <Skeleton className="h-12 w-full" />
            </div>
          ) : creditNotes.length === 0 ? (
            <EmptyState
              title="No credit notes"
              description="Create your first credit note to track invoice adjustments."
            />
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b text-left">
                    <th className="pb-3 font-medium text-muted-foreground">
                      Number
                    </th>
                    <th className="pb-3 font-medium text-muted-foreground">
                      Invoice
                    </th>
                    <th className="pb-3 font-medium text-muted-foreground">
                      Issue date
                    </th>
                    <th className="pb-3 font-medium text-muted-foreground">
                      Total
                    </th>
                    <th className="pb-3 font-medium text-muted-foreground">
                      Status
                    </th>
                  </tr>
                </thead>
                <tbody>
                  {creditNotes.map((creditNote) => (
                    <tr key={creditNote.id} className="border-b last:border-0">
                      <td className="py-4">
                        <Link
                          href={`/credit-notes/${creditNote.id}`}
                          className="font-medium text-primary hover:underline"
                        >
                          {creditNote.number}
                        </Link>
                      </td>
                      <td className="py-4 text-muted-foreground">
                        {creditNote.invoice?.number || creditNote.invoice_id}
                      </td>
                      <td className="py-4 text-muted-foreground">
                        {creditNote.issue_date}
                      </td>
                      <td className="py-4">
                        {Number(creditNote.total || 0).toFixed(2)} EUR
                      </td>
                      <td className="py-4">
                        <CreditNoteStatusBadge status={creditNote.status} />
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
