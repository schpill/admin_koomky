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
import { useI18n } from "@/components/providers/i18n-provider";

export default function ExpenseDetailPage() {
  const { t } = useI18n();
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
      toast.error(
        (error as Error).message || t("expenses.detail.toasts.deleteFailed")
      );
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
      toast.success(t("expenses.detail.toasts.deleted"));
      router.push("/expenses");
    } catch (error) {
      toast.error(
        (error as Error).message || t("expenses.detail.toasts.deleteFailed")
      );
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
      toast.error(t("expenses.detail.toasts.authRequired"));
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
      toast.error(
        (error as Error).message || t("expenses.detail.toasts.downloadFailed")
      );
    }
  };

  if (!expense) {
    return (
      <p className="text-sm text-muted-foreground">
        {t("expenses.detail.loading")}
      </p>
    );
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
              {t("expenses.detail.edit")}
            </Link>
          </Button>
          <Button variant="outline" onClick={remove} disabled={isDeleting}>
            <Trash2 className="mr-2 h-4 w-4" />
            {isDeleting
              ? t("expenses.detail.deleting")
              : t("expenses.detail.delete")}
          </Button>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>{t("expenses.detail.summary")}</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div>
            <p className="text-xs text-muted-foreground">
              {t("expenses.detail.amount")}
            </p>
            <p className="font-medium">
              <CurrencyAmount
                amount={Number(expense.amount || 0)}
                currency={expense.currency || "EUR"}
              />
            </p>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">
              {t("expenses.detail.category")}
            </p>
            <p className="font-medium">{expense.category?.name || "-"}</p>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">
              {t("expenses.detail.project")}
            </p>
            <p className="font-medium">{expense.project?.name || "-"}</p>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">
              {t("expenses.detail.client")}
            </p>
            <p className="font-medium">{expense.client?.name || "-"}</p>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">
              {t("expenses.detail.billable")}
            </p>
            <p className="font-medium">
              {expense.is_billable
                ? t("expenses.detail.yes")
                : t("expenses.detail.no")}
            </p>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">
              {t("expenses.detail.reimbursable")}
            </p>
            <p className="font-medium">
              {expense.is_reimbursable
                ? t("expenses.detail.yes")
                : t("expenses.detail.no")}
            </p>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">
              {t("expenses.detail.vendor")}
            </p>
            <p className="font-medium">{expense.vendor || "-"}</p>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">
              {t("expenses.detail.reference")}
            </p>
            <p className="font-medium">{expense.reference || "-"}</p>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>{t("expenses.detail.notes")}</CardTitle>
        </CardHeader>
        <CardContent>
          <p className="text-sm text-muted-foreground">
            {expense.notes || t("expenses.detail.noNotes")}
          </p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="flex flex-row items-center justify-between">
          <CardTitle>{t("expenses.detail.receiptPreview")}</CardTitle>
          {expense.receipt_path ? (
            <Button variant="outline" size="sm" onClick={downloadReceipt}>
              <Download className="mr-2 h-4 w-4" />
              {t("expenses.detail.download")}
            </Button>
          ) : null}
        </CardHeader>
        <CardContent>
          {!expense.receipt_path ? (
            <p className="text-sm text-muted-foreground">
              {t("expenses.detail.noReceipt")}
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
            <p className="text-sm text-muted-foreground">
              {t("expenses.detail.fileAttached")}
            </p>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
