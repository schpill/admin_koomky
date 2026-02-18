"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { useParams, useRouter } from "next/navigation";
import { toast } from "sonner";
import { Download, Pencil, Trash2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { CurrencyAmount } from "@/components/shared/currency-amount";
import { useExpenseStore, type Expense } from "@/lib/stores/expenses";
import { useAuthStore } from "@/lib/stores/auth";

export default function ExpenseDetailPage() {
  const params = useParams<{ id: string }>();
  const router = useRouter();
  const expenseId = params.id;

  const { currentExpense, fetchExpense, deleteExpense } = useExpenseStore();
  const [isDeleting, setDeleting] = useState(false);

  useEffect(() => {
    if (!expenseId) {
      return;
    }

    fetchExpense(expenseId).catch((error) => {
      toast.error((error as Error).message || "Unable to load expense");
      router.push("/expenses");
    });
  }, [expenseId, fetchExpense, router]);

  const expense = currentExpense as Expense | null;

  const remove = async () => {
    if (!expense) {
      return;
    }

    setDeleting(true);
    try {
      await deleteExpense(expense.id);
      toast.success("Expense deleted");
      router.push("/expenses");
    } catch (error) {
      toast.error((error as Error).message || "Unable to delete expense");
    } finally {
      setDeleting(false);
    }
  };

  const downloadReceipt = async () => {
    if (!expense) {
      return;
    }

    const token = useAuthStore.getState().accessToken;
    if (!token) {
      toast.error("Authentication required");
      return;
    }

    const base = process.env.NEXT_PUBLIC_API_URL || "http://localhost/api/v1";

    try {
      const response = await fetch(`${base}/expenses/${expense.id}/receipt`, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });

      if (!response.ok) {
        throw new Error("Unable to download receipt");
      }

      const blob = await response.blob();
      const url = URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download =
        expense.receipt_filename || `expense-${expense.id}-receipt`;
      document.body.appendChild(link);
      link.click();
      link.remove();
      URL.revokeObjectURL(url);
    } catch (error) {
      toast.error((error as Error).message || "Unable to download receipt");
    }
  };

  if (!expense) {
    return <p className="text-sm text-muted-foreground">Loading expense...</p>;
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="text-3xl font-bold">{expense.description}</h1>
          <p className="text-sm text-muted-foreground">{expense.date}</p>
        </div>
        <div className="flex gap-2">
          <Badge variant="outline">{expense.status}</Badge>
          <Button asChild variant="outline">
            <Link href={`/expenses/${expense.id}/edit`}>
              <Pencil className="mr-2 h-4 w-4" />
              Edit
            </Link>
          </Button>
          <Button variant="outline" onClick={remove} disabled={isDeleting}>
            <Trash2 className="mr-2 h-4 w-4" />
            {isDeleting ? "Deleting..." : "Delete"}
          </Button>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Expense summary</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div>
            <p className="text-xs text-muted-foreground">Amount</p>
            <p className="font-medium">
              <CurrencyAmount
                amount={Number(expense.amount || 0)}
                currency={expense.currency || "EUR"}
              />
            </p>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">Category</p>
            <p className="font-medium">{expense.category?.name || "-"}</p>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">Project</p>
            <p className="font-medium">{expense.project?.name || "-"}</p>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">Client</p>
            <p className="font-medium">{expense.client?.name || "-"}</p>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">Billable</p>
            <p className="font-medium">{expense.is_billable ? "Yes" : "No"}</p>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">Reimbursable</p>
            <p className="font-medium">
              {expense.is_reimbursable ? "Yes" : "No"}
            </p>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">Vendor</p>
            <p className="font-medium">{expense.vendor || "-"}</p>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">Reference</p>
            <p className="font-medium">{expense.reference || "-"}</p>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Notes</CardTitle>
        </CardHeader>
        <CardContent>
          <p className="text-sm text-muted-foreground">
            {expense.notes || "No notes"}
          </p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="flex flex-row items-center justify-between">
          <CardTitle>Receipt preview</CardTitle>
          {expense.receipt_path ? (
            <Button variant="outline" size="sm" onClick={downloadReceipt}>
              <Download className="mr-2 h-4 w-4" />
              Download
            </Button>
          ) : null}
        </CardHeader>
        <CardContent>
          {!expense.receipt_path ? (
            <p className="text-sm text-muted-foreground">
              No receipt uploaded.
            </p>
          ) : expense.receipt_mime_type?.startsWith("image/") ? (
            // eslint-disable-next-line @next/next/no-img-element
            <img
              src={`${process.env.NEXT_PUBLIC_STORAGE_URL || ""}/${expense.receipt_path}`}
              alt="Expense receipt"
              className="max-h-80 w-auto rounded border object-contain"
            />
          ) : expense.receipt_mime_type?.includes("pdf") ? (
            <iframe
              title="Expense receipt preview"
              src={`${process.env.NEXT_PUBLIC_STORAGE_URL || ""}/${expense.receipt_path}`}
              className="h-80 w-full rounded border"
            />
          ) : (
            <p className="text-sm text-muted-foreground">File attached.</p>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
