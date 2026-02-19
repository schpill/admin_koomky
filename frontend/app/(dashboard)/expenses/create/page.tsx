"use client";

import { FormEvent, useEffect, useMemo, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
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

export default function CreateExpensePage() {
  const { t } = useI18n();
  const router = useRouter();
  const searchParams = useSearchParams();
  const { createExpense, uploadReceipt, isLoading } = useExpenseStore();
  const { categories, fetchCategories } = useExpenseCategoryStore();
  const { projects, fetchProjects } = useProjectStore();
  const { clients, fetchClients } = useClientStore();

  const [receiptFile, setReceiptFile] = useState<File | null>(null);
  const [form, setForm] = useState({
    description: "",
    amount: "",
    currency: "EUR",
    expense_category_id: "",
    date: new Date().toISOString().slice(0, 10),
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
  });

  useEffect(() => {
    fetchCategories();
    fetchProjects({ per_page: 100 });
    fetchClients({ per_page: 100 });
  }, [fetchCategories, fetchProjects, fetchClients]);

  useEffect(() => {
    const projectId = searchParams.get("project_id");
    if (projectId) {
      setForm((current) => ({ ...current, project_id: projectId }));
    }
  }, [searchParams]);

  useEffect(() => {
    if (!form.expense_category_id && categories.length > 0) {
      setForm((current) => ({
        ...current,
        expense_category_id: categories[0].id,
      }));
    }
  }, [categories, form.expense_category_id]);

  const selectedProject = useMemo(
    () => projects.find((project) => project.id === form.project_id),
    [form.project_id, projects]
  );

  useEffect(() => {
    if (selectedProject?.client?.id) {
      setForm((current) => ({
        ...current,
        client_id: selectedProject.client?.id || current.client_id,
      }));
    }
  }, [selectedProject?.client?.id]);

  const submit = async (event: FormEvent) => {
    event.preventDefault();

    try {
      const payload = {
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
      };

      const created = await createExpense(payload);
      if (!created) {
        return;
      }

      if (receiptFile) {
        await uploadReceipt(created.id, receiptFile);
      }

      toast.success(t("expenses.create.toasts.success"));
      router.push(`/expenses/${created.id}`);
    } catch (error) {
      toast.error(
        (error as Error).message || t("expenses.create.toasts.failed")
      );
    }
  };

  return (
    <form className="space-y-6" onSubmit={submit}>
      <div className="space-y-1">
        <h1 className="text-3xl font-bold">{t("expenses.create.title")}</h1>
        <p className="text-sm text-muted-foreground">
          {t("expenses.create.description")}
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>{t("expenses.create.expenseDetails")}</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-4 md:grid-cols-2">
          <div className="space-y-2 md:col-span-2">
            <Label htmlFor="expense-description">
              {t("expenses.create.descriptionLabel")}
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
            <Label htmlFor="expense-amount">
              {t("expenses.create.amount")}
            </Label>
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
              {t("expenses.create.currency")}
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
              {t("expenses.create.category")}
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
              required
            >
              <option value="">{t("expenses.create.selectCategory")}</option>
              {categories.map((category) => (
                <option key={category.id} value={category.id}>
                  {category.name}
                </option>
              ))}
            </select>
          </div>

          <div className="space-y-2">
            <Label htmlFor="expense-date">{t("expenses.create.date")}</Label>
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
              {t("expenses.create.project")}
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
              <option value="">{t("expenses.create.noProject")}</option>
              {projects.map((project) => (
                <option key={project.id} value={project.id}>
                  {project.name}
                </option>
              ))}
            </select>
          </div>

          <div className="space-y-2">
            <Label htmlFor="expense-client">
              {t("expenses.create.client")}
            </Label>
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
              <option value="">{t("expenses.create.noClient")}</option>
              {clients.map((client) => (
                <option key={client.id} value={client.id}>
                  {client.name}
                </option>
              ))}
            </select>
          </div>

          <div className="space-y-2">
            <Label htmlFor="expense-payment-method">
              {t("expenses.create.paymentMethod")}
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
              <option value="cash">{t("expenses.create.cash")}</option>
              <option value="card">{t("expenses.create.card")}</option>
              <option value="bank_transfer">
                {t("expenses.create.bankTransfer")}
              </option>
              <option value="other">{t("expenses.create.other")}</option>
            </select>
          </div>

          <div className="space-y-2">
            <Label htmlFor="expense-status">
              {t("expenses.create.status")}
            </Label>
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
              <option value="pending">{t("expenses.filters.pending")}</option>
              <option value="approved">{t("expenses.filters.approved")}</option>
              <option value="rejected">{t("expenses.filters.rejected")}</option>
            </select>
          </div>

          <div className="space-y-2">
            <Label htmlFor="expense-tax-amount">
              {t("expenses.create.taxAmount")}
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
              {t("expenses.create.taxRate")}
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
            <Label htmlFor="expense-vendor">
              {t("expenses.create.vendor")}
            </Label>
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
              {t("expenses.create.reference")}
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
            <Label htmlFor="expense-notes">{t("expenses.create.notes")}</Label>
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
              {t("expenses.create.billable")}
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
              {t("expenses.create.reimbursable")}
            </label>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>{t("expenses.create.receipt")}</CardTitle>
        </CardHeader>
        <CardContent>
          <ReceiptUpload file={receiptFile} onChange={setReceiptFile} />
        </CardContent>
      </Card>

      <div className="flex justify-end gap-2">
        <Button
          type="button"
          variant="outline"
          onClick={() => router.push("/expenses")}
        >
          {t("expenses.create.cancel")}
        </Button>
        <Button type="submit" disabled={isLoading}>
          {isLoading ? t("expenses.create.saving") : t("expenses.create.title")}
        </Button>
      </div>
    </form>
  );
}
