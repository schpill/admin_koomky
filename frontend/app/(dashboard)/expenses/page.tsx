"use client";

import Link from "next/link";
import { useEffect, useMemo, useState } from "react";
import { toast } from "sonner";
import { Plus, ReceiptText, Trash2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Badge } from "@/components/ui/badge";
import { CurrencyAmount } from "@/components/shared/currency-amount";
import { useExpenseStore } from "@/lib/stores/expenses";
import { useExpenseCategoryStore } from "@/lib/stores/expense-categories";
import { useProjectStore } from "@/lib/stores/projects";

export default function ExpensesPage() {
  const {
    expenses,
    isLoading,
    pagination,
    fetchExpenses,
    deleteExpense,
    updateExpense,
  } = useExpenseStore();
  const { categories, fetchCategories } = useExpenseCategoryStore();
  const { projects, fetchProjects } = useProjectStore();

  const [filters, setFilters] = useState<Record<string, string>>({});
  const [selectedIds, setSelectedIds] = useState<string[]>([]);
  const [bulkCategoryId, setBulkCategoryId] = useState("");

  useEffect(() => {
    fetchCategories();
    fetchProjects({ per_page: 100 });
  }, [fetchCategories, fetchProjects]);

  useEffect(() => {
    fetchExpenses({
      ...filters,
      per_page: 50,
    });
  }, [fetchExpenses, filters]);

  const allSelected = useMemo(() => {
    return expenses.length > 0 && selectedIds.length === expenses.length;
  }, [expenses.length, selectedIds.length]);

  const toggleRow = (id: string) => {
    setSelectedIds((current) =>
      current.includes(id)
        ? current.filter((item) => item !== id)
        : [...current, id]
    );
  };

  const toggleAll = () => {
    setSelectedIds(allSelected ? [] : expenses.map((expense) => expense.id));
  };

  const deleteSelected = async () => {
    if (selectedIds.length === 0) {
      return;
    }

    try {
      await Promise.all(selectedIds.map((id) => deleteExpense(id)));
      setSelectedIds([]);
      toast.success("Selected expenses deleted");
    } catch (error) {
      toast.error((error as Error).message || "Unable to delete selected expenses");
    }
  };

  const categorizeSelected = async () => {
    if (!bulkCategoryId || selectedIds.length === 0) {
      return;
    }

    try {
      await Promise.all(
        selectedIds.map((id) => {
          const expense = expenses.find((item) => item.id === id);
          if (!expense) {
            return Promise.resolve(null);
          }

          return updateExpense(id, {
            ...expense,
            expense_category_id: bulkCategoryId,
            date: expense.date,
          });
        })
      );
      toast.success("Selected expenses recategorized");
      setSelectedIds([]);
      setBulkCategoryId("");
      await fetchExpenses({ ...filters, per_page: 50 });
    } catch (error) {
      toast.error((error as Error).message || "Unable to recategorize expenses");
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="text-3xl font-bold">Expenses</h1>
          <p className="text-sm text-muted-foreground">
            {pagination ? `${pagination.total} expense records` : "Expense tracking"}
          </p>
        </div>
        <Button asChild>
          <Link href="/expenses/create">
            <Plus className="mr-2 h-4 w-4" />
            Quick add
          </Link>
        </Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Filters</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-3 md:grid-cols-3 lg:grid-cols-6">
          <div className="space-y-1">
            <Label htmlFor="filter-date-from">From</Label>
            <Input
              id="filter-date-from"
              type="date"
              value={filters.date_from || ""}
              onChange={(event) =>
                setFilters((current) => ({
                  ...current,
                  date_from: event.target.value,
                }))
              }
            />
          </div>
          <div className="space-y-1">
            <Label htmlFor="filter-date-to">To</Label>
            <Input
              id="filter-date-to"
              type="date"
              value={filters.date_to || ""}
              onChange={(event) =>
                setFilters((current) => ({
                  ...current,
                  date_to: event.target.value,
                }))
              }
            />
          </div>
          <div className="space-y-1">
            <Label htmlFor="filter-category">Category</Label>
            <select
              id="filter-category"
              className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
              value={filters.expense_category_id || ""}
              onChange={(event) =>
                setFilters((current) => ({
                  ...current,
                  expense_category_id: event.target.value,
                }))
              }
            >
              <option value="">All categories</option>
              {categories.map((category) => (
                <option key={category.id} value={category.id}>
                  {category.name}
                </option>
              ))}
            </select>
          </div>
          <div className="space-y-1">
            <Label htmlFor="filter-project">Project</Label>
            <select
              id="filter-project"
              className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
              value={filters.project_id || ""}
              onChange={(event) =>
                setFilters((current) => ({
                  ...current,
                  project_id: event.target.value,
                }))
              }
            >
              <option value="">All projects</option>
              {projects.map((project) => (
                <option key={project.id} value={project.id}>
                  {project.name}
                </option>
              ))}
            </select>
          </div>
          <div className="space-y-1">
            <Label htmlFor="filter-billable">Billable</Label>
            <select
              id="filter-billable"
              className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
              value={filters.billable || ""}
              onChange={(event) =>
                setFilters((current) => ({
                  ...current,
                  billable: event.target.value,
                }))
              }
            >
              <option value="">All</option>
              <option value="true">Billable</option>
              <option value="false">Non-billable</option>
            </select>
          </div>
          <div className="space-y-1">
            <Label htmlFor="filter-status">Status</Label>
            <select
              id="filter-status"
              className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
              value={filters.status || ""}
              onChange={(event) =>
                setFilters((current) => ({
                  ...current,
                  status: event.target.value,
                }))
              }
            >
              <option value="">All statuses</option>
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
            </select>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="flex flex-row items-center justify-between">
          <CardTitle>Expense list</CardTitle>
          <div className="flex flex-wrap items-center gap-2">
            <select
              className="h-9 rounded-md border border-input bg-background px-2 text-xs"
              value={bulkCategoryId}
              onChange={(event) => setBulkCategoryId(event.target.value)}
            >
              <option value="">Bulk categorize</option>
              {categories.map((category) => (
                <option key={category.id} value={category.id}>
                  {category.name}
                </option>
              ))}
            </select>
            <Button
              type="button"
              variant="outline"
              size="sm"
              onClick={categorizeSelected}
              disabled={!bulkCategoryId || selectedIds.length === 0}
            >
              Apply category
            </Button>
            <Button
              type="button"
              variant="outline"
              size="sm"
              onClick={deleteSelected}
              disabled={selectedIds.length === 0}
            >
              <Trash2 className="mr-2 h-4 w-4" />
              Delete selected
            </Button>
          </div>
        </CardHeader>
        <CardContent>
          {isLoading && expenses.length === 0 ? (
            <p className="text-sm text-muted-foreground">Loading expenses...</p>
          ) : expenses.length === 0 ? (
            <p className="text-sm text-muted-foreground">
              No expenses found for the selected filters.
            </p>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b text-left">
                    <th className="pb-3">
                      <input
                        type="checkbox"
                        checked={allSelected}
                        onChange={toggleAll}
                        aria-label="Select all expenses"
                      />
                    </th>
                    <th className="pb-3">Date</th>
                    <th className="pb-3">Description</th>
                    <th className="pb-3">Category</th>
                    <th className="pb-3">Amount</th>
                    <th className="pb-3">Project</th>
                    <th className="pb-3">Billable</th>
                    <th className="pb-3">Receipt</th>
                    <th className="pb-3">Status</th>
                  </tr>
                </thead>
                <tbody>
                  {expenses.map((expense) => (
                    <tr key={expense.id} className="border-b last:border-0">
                      <td className="py-3">
                        <input
                          type="checkbox"
                          checked={selectedIds.includes(expense.id)}
                          onChange={() => toggleRow(expense.id)}
                          aria-label={`Select expense ${expense.description}`}
                        />
                      </td>
                      <td className="py-3 text-muted-foreground">{expense.date}</td>
                      <td className="py-3">
                        <Link
                          href={`/expenses/${expense.id}`}
                          className="font-medium text-primary hover:underline"
                        >
                          {expense.description}
                        </Link>
                      </td>
                      <td className="py-3">{expense.category?.name || "-"}</td>
                      <td className="py-3">
                        <CurrencyAmount
                          amount={Number(expense.amount || 0)}
                          currency={expense.currency || "EUR"}
                        />
                      </td>
                      <td className="py-3 text-muted-foreground">
                        {expense.project?.name || "-"}
                      </td>
                      <td className="py-3">
                        {expense.is_billable ? (
                          <Badge variant="outline">Billable</Badge>
                        ) : (
                          <Badge variant="secondary">Non-billable</Badge>
                        )}
                      </td>
                      <td className="py-3">
                        {expense.receipt_path ? (
                          <ReceiptText className="h-4 w-4 text-primary" />
                        ) : (
                          <span className="text-xs text-muted-foreground">-</span>
                        )}
                      </td>
                      <td className="py-3">
                        <Badge variant="outline">{expense.status}</Badge>
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
