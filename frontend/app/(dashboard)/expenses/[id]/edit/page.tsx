"use client";

import { FormEvent, useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { ReceiptUpload } from "@/components/expenses/receipt-upload";
import { useExpenseStore } from "@/lib/stores/expenses";
import { useExpenseCategoryStore } from "@/lib/stores/expense-categories";
import { useProjectStore } from "@/lib/stores/projects";
import { useClientStore } from "@/lib/stores/clients";
import { useI18n } from "@/components/providers/i18n-provider";

export default function EditExpensePage() {
  const { t } = useI18n();
  const params = useParams<{ id: string }>();
  const router = useRouter();
  const expenseId = params.id;

  const {
    currentExpense,
    fetchExpense,
    updateExpense,
    uploadReceipt,
    isLoading,
  } = useExpenseStore();
  const { categories, fetchCategories } = useExpenseCategoryStore();
  const { projects, fetchProjects } = useProjectStore();
  const { clients, fetchClients } = useClientStore();

  const [receiptFile, setReceiptFile] = useState<File | null>(null);
  const [form, setForm] = useState({
    description: "",
    amount: "",
    currency: "EUR",
    expense_category_id: "",
    date: "",
    project_id: "",
    client_id: "",
    payment_method: "card",
    status: "pending",
    tax_amount: "0",
    tax_rate: "",
    vendor: "",
    reference: "",
    notes: "",
    is_billable: false,
    is_reimbursable: false,
    receipt_path: "",
    receipt_mime_type: "",
  });

  useEffect(() => {
    fetchCategories();
    fetchProjects({ per_page: 100 });
    fetchClients({ per_page: 100 });
  }, [fetchCategories, fetchProjects, fetchClients]);

  useEffect(() => {
    if (!expenseId) {
      return;
    }

    fetchExpense(expenseId)
      .then((expense) => {
        if (!expense) {
          return;
        }

        setForm({
          description: expense.description,
          amount: String(expense.amount),
          currency: expense.currency,
          expense_category_id: expense.expense_category_id,
          date: expense.date,
          project_id: expense.project_id || "",
          client_id: expense.client_id || "",
          payment_method: expense.payment_method,
          status: expense.status,
          tax_amount: String(expense.tax_amount || 0),
          tax_rate: expense.tax_rate !== null ? String(expense.tax_rate) : "",
          vendor: expense.vendor || "",
          reference: expense.reference || "",
          notes: expense.notes || "",
          is_billable: expense.is_billable,
          is_reimbursable: expense.is_reimbursable,
          receipt_path: expense.receipt_path || "",
          receipt_mime_type: expense.receipt_mime_type || "",
        });
      })
      .catch((error) => {
        toast.error(
          (error as Error).message || t("expenses.edit.toasts.failed")
        );
        router.push("/expenses");
      });
  }, [expenseId, fetchExpense, router]);

  const submit = async (event: FormEvent) => {
    event.preventDefault();

    if (!expenseId) {
      return;
    }

    try {
      const updated = await updateExpense(expenseId, {
        description: form.description,
        amount: Number(form.amount),
        currency: form.currency,
        expense_category_id: form.expense_category_id,
        date: form.date,
        project_id: form.project_id || null,
        client_id: form.client_id || null,
        payment_method: form.payment_method,
        status: form.status,
        tax_amount: Number(form.tax_amount || 0),
        tax_rate: form.tax_rate ? Number(form.tax_rate) : null,
        vendor: form.vendor || null,
        reference: form.reference || null,
        notes: form.notes || null,
        is_billable: form.is_billable,
        is_reimbursable: form.is_reimbursable,
      });

      if (!updated) {
        return;
      }

      if (receiptFile) {
        await uploadReceipt(expenseId, receiptFile);
      }

      toast.success(t("expenses.edit.toasts.success"));
      router.push(`/expenses/${expenseId}`);
    } catch (error) {
      toast.error((error as Error).message || t("expenses.edit.toasts.failed"));
    }
  };

  return (
    <form className="space-y-6" onSubmit={submit}>
      <div className="space-y-1">
        <h1 className="text-3xl font-bold">{t("expenses.edit.title")}</h1>
        <p className="text-sm text-muted-foreground">
          {t("expenses.edit.description")}
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>{t("expenses.edit.expenseDetails")}</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-4 md:grid-cols-2">
          <div className="space-y-2 md:col-span-2">
            <Label htmlFor="expense-description">
              {t("expenses.edit.descriptionLabel")}
            </Label>
            <Input
              id="expense-description"
              value={form.description}
              onChange={(event) =>
                setForm((current) => ({
                  ...current,
                  description: event.target.value,
                }))
              }
              required
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="expense-amount">{t("expenses.edit.amount")}</Label>
            <Input
              id="expense-amount"
              type="number"
              step="0.01"
              min="0"
              value={form.amount}
              onChange={(event) =>
                setForm((current) => ({
                  ...current,
                  amount: event.target.value,
                }))
              }
              required
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="expense-currency">
              {t("expenses.edit.currency")}
            </Label>
            <Input
              id="expense-currency"
              value={form.currency}
              onChange={(event) =>
                setForm((current) => ({
                  ...current,
                  currency: event.target.value.toUpperCase(),
                }))
              }
              maxLength={3}
              required
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="expense-category">
              {t("expenses.edit.category")}
            </Label>
            <select
              id="expense-category"
              className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
              value={form.expense_category_id}
              onChange={(event) =>
                setForm((current) => ({
                  ...current,
                  expense_category_id: event.target.value,
                }))
              }
            >
              {categories.map((category) => (
                <option key={category.id} value={category.id}>
                  {category.name}
                </option>
              ))}
            </select>
          </div>

          <div className="space-y-2">
            <Label htmlFor="expense-date">{t("expenses.edit.date")}</Label>
            <Input
              id="expense-date"
              type="date"
              value={form.date}
              onChange={(event) =>
                setForm((current) => ({ ...current, date: event.target.value }))
              }
              required
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="expense-project">
              {t("expenses.edit.project")}
            </Label>
            <select
              id="expense-project"
              className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
              value={form.project_id}
              onChange={(event) =>
                setForm((current) => ({
                  ...current,
                  project_id: event.target.value,
                }))
              }
            >
              <option value="">{t("expenses.edit.noProject")}</option>
              {projects.map((project) => (
                <option key={project.id} value={project.id}>
                  {project.name}
                </option>
              ))}
            </select>
          </div>

          <div className="space-y-2">
            <Label htmlFor="expense-client">{t("expenses.edit.client")}</Label>
            <select
              id="expense-client"
              className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
              value={form.client_id}
              onChange={(event) =>
                setForm((current) => ({
                  ...current,
                  client_id: event.target.value,
                }))
              }
            >
              <option value="">{t("expenses.edit.noClient")}</option>
              {clients.map((client) => (
                <option key={client.id} value={client.id}>
                  {client.name}
                </option>
              ))}
            </select>
          </div>

          <div className="space-y-2">
            <Label htmlFor="expense-payment-method">
              {t("expenses.edit.paymentMethod")}
            </Label>
            <select
              id="expense-payment-method"
              className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
              value={form.payment_method}
              onChange={(event) =>
                setForm((current) => ({
                  ...current,
                  payment_method: event.target.value,
                }))
              }
            >
              <option value="cash">{t("expenses.edit.cash")}</option>
              <option value="card">{t("expenses.edit.card")}</option>
              <option value="bank_transfer">
                {t("expenses.edit.bankTransfer")}
              </option>
              <option value="other">{t("expenses.edit.other")}</option>
            </select>
          </div>

          <div className="space-y-2">
            <Label htmlFor="expense-status">{t("expenses.edit.status")}</Label>
            <select
              id="expense-status"
              className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
              value={form.status}
              onChange={(event) =>
                setForm((current) => ({
                  ...current,
                  status: event.target.value,
                }))
              }
            >
              <option value="pending">{t("expenses.edit.pending")}</option>
              <option value="approved">{t("expenses.edit.approved")}</option>
              <option value="rejected">{t("expenses.edit.rejected")}</option>
            </select>
          </div>

          <div className="space-y-2">
            <Label htmlFor="expense-tax-amount">
              {t("expenses.edit.taxAmount")}
            </Label>
            <Input
              id="expense-tax-amount"
              type="number"
              step="0.01"
              min="0"
              value={form.tax_amount}
              onChange={(event) =>
                setForm((current) => ({
                  ...current,
                  tax_amount: event.target.value,
                }))
              }
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="expense-tax-rate">
              {t("expenses.edit.taxRate")}
            </Label>
            <Input
              id="expense-tax-rate"
              type="number"
              step="0.01"
              min="0"
              value={form.tax_rate}
              onChange={(event) =>
                setForm((current) => ({
                  ...current,
                  tax_rate: event.target.value,
                }))
              }
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="expense-vendor">{t("expenses.edit.vendor")}</Label>
            <Input
              id="expense-vendor"
              value={form.vendor}
              onChange={(event) =>
                setForm((current) => ({
                  ...current,
                  vendor: event.target.value,
                }))
              }
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="expense-reference">
              {t("expenses.edit.reference")}
            </Label>
            <Input
              id="expense-reference"
              value={form.reference}
              onChange={(event) =>
                setForm((current) => ({
                  ...current,
                  reference: event.target.value,
                }))
              }
            />
          </div>

          <div className="space-y-2 md:col-span-2">
            <Label htmlFor="expense-notes">{t("expenses.edit.notes")}</Label>
            <Textarea
              id="expense-notes"
              rows={3}
              value={form.notes}
              onChange={(event) =>
                setForm((current) => ({
                  ...current,
                  notes: event.target.value,
                }))
              }
            />
          </div>

          <div className="flex flex-wrap gap-4 md:col-span-2">
            <label className="flex items-center gap-2 text-sm">
              <input
                type="checkbox"
                checked={form.is_billable}
                onChange={(event) =>
                  setForm((current) => ({
                    ...current,
                    is_billable: event.target.checked,
                  }))
                }
              />
              {t("expenses.edit.billable")}
            </label>
            <label className="flex items-center gap-2 text-sm">
              <input
                type="checkbox"
                checked={form.is_reimbursable}
                onChange={(event) =>
                  setForm((current) => ({
                    ...current,
                    is_reimbursable: event.target.checked,
                  }))
                }
              />
              {t("expenses.edit.reimbursable")}
            </label>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>{t("expenses.edit.receipt")}</CardTitle>
        </CardHeader>
        <CardContent>
          <ReceiptUpload
            file={receiptFile}
            onChange={setReceiptFile}
            existingUrl={form.receipt_path || null}
            existingMimeType={form.receipt_mime_type || null}
          />
        </CardContent>
      </Card>

      <div className="flex justify-end gap-2">
        <Button
          type="button"
          variant="outline"
          onClick={() => router.push(`/expenses/${expenseId}`)}
        >
          {t("expenses.edit.cancel")}
        </Button>
        <Button type="submit" disabled={isLoading}>
          {isLoading
            ? t("expenses.edit.saving")
            : t("expenses.edit.saveChanges")}
        </Button>
      </div>
    </form>
  );
}
