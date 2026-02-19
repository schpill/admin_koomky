"use client";

import { useEffect, useMemo, useState } from "react";
import { toast } from "sonner";
import { Download } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { CurrencyAmount } from "@/components/shared/currency-amount";
import { useExpenseStore } from "@/lib/stores/expenses";
import { useI18n } from "@/components/providers/i18n-provider";

const startOfMonth = new Date(
  new Date().getFullYear(),
  new Date().getMonth(),
  1
)
  .toISOString()
  .slice(0, 10);
const endOfMonth = new Date().toISOString().slice(0, 10);

export default function ExpenseReportPage() {
  const { t } = useI18n();
  const { report, isLoading, fetchReport, exportReport } = useExpenseStore();
  const [dateFrom, setDateFrom] = useState(startOfMonth);
  const [dateTo, setDateTo] = useState(endOfMonth);

  useEffect(() => {
    fetchReport({ date_from: dateFrom, date_to: dateTo }).catch((error) => {
      toast.error(
        (error as Error).message || t("expenses.report.toasts.loadFailed")
      );
    });
  }, [dateFrom, dateTo, fetchReport]);

  const topCategories = useMemo(
    () => [...(report?.by_category || [])].sort((a, b) => b.total - a.total),
    [report?.by_category]
  );

  const exportCsv = async () => {
    try {
      const blob = await exportReport({ date_from: dateFrom, date_to: dateTo });
      const url = URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = "expenses-report.csv";
      document.body.appendChild(link);
      link.click();
      link.remove();
      URL.revokeObjectURL(url);
    } catch (error) {
      toast.error(
        (error as Error).message || t("expenses.report.toasts.loadFailed")
      );
    }
  };

  const currency = report?.base_currency || "EUR";

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="text-3xl font-bold">{t("expenses.report.title")}</h1>
          <p className="text-sm text-muted-foreground">
            {t("expenses.report.description")}
          </p>
        </div>
        <Button variant="outline" onClick={exportCsv}>
          <Download className="mr-2 h-4 w-4" />
          {t("expenses.report.exportCsv")}
        </Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>{t("expenses.filters.heading")}</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-3 md:grid-cols-2">
          <div className="space-y-2">
            <Label htmlFor="expense-report-from">
              {t("expenses.report.filters.from")}
            </Label>
            <Input
              id="expense-report-from"
              type="date"
              value={dateFrom}
              onChange={(event) => setDateFrom(event.target.value)}
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="expense-report-to">
              {t("expenses.report.filters.to")}
            </Label>
            <Input
              id="expense-report-to"
              type="date"
              value={dateTo}
              onChange={(event) => setDateTo(event.target.value)}
            />
          </div>
        </CardContent>
      </Card>

      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <Card>
          <CardHeader>
            <CardTitle className="text-sm">
              {t("expenses.report.stats.total")}
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-2xl font-semibold">
              <CurrencyAmount
                amount={Number(report?.total_expenses || 0)}
                currency={currency}
              />
            </p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle className="text-sm">
              {t("expenses.report.stats.avgPerMonth")}
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-2xl font-semibold">
              <CurrencyAmount
                amount={
                  Number(report?.total_expenses || 0) /
                  Math.max(1, Number(report?.by_month?.length || 1))
                }
                currency={currency}
              />
            </p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle className="text-sm">
              {t("expenses.report.stats.billableTotal")}
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-2xl font-semibold">
              <CurrencyAmount
                amount={Number(report?.billable_split?.billable || 0)}
                currency={currency}
              />
            </p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle className="text-sm">
              {t("expenses.report.stats.taxTotal")}
            </CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-2xl font-semibold">
              <CurrencyAmount
                amount={Number(report?.tax_total || 0)}
                currency={currency}
              />
            </p>
          </CardContent>
        </Card>
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>{t("expenses.report.categoryBreakdown")}</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            {topCategories.length === 0 ? (
              <p className="text-sm text-muted-foreground">
                {t("expenses.report.noData")}
              </p>
            ) : (
              topCategories.map((item) => (
                <div key={item.category}>
                  <div className="mb-1 flex items-center justify-between text-sm">
                    <span>{item.category}</span>
                    <span>
                      <CurrencyAmount amount={item.total} currency={currency} />
                    </span>
                  </div>
                  <div className="h-2 rounded bg-muted">
                    <div
                      className="h-2 rounded bg-primary"
                      style={{
                        width: `${
                          (item.total /
                            Math.max(1, Number(report?.total_expenses || 1))) *
                          100
                        }%`,
                      }}
                    />
                  </div>
                </div>
              ))
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>{t("expenses.report.monthlyTrend")}</CardTitle>
          </CardHeader>
          <CardContent className="space-y-2">
            {(report?.by_month || []).length === 0 ? (
              <p className="text-sm text-muted-foreground">
                {t("expenses.report.noData")}
              </p>
            ) : (
              report?.by_month?.map((item) => (
                <div
                  key={item.month}
                  className="flex items-center justify-between rounded border p-2 text-sm"
                >
                  <span>{item.month}</span>
                  <span>
                    <CurrencyAmount amount={item.total} currency={currency} />
                  </span>
                </div>
              ))
            )}
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>{t("expenses.report.projectAllocation")}</CardTitle>
        </CardHeader>
        <CardContent>
          {(report?.by_project || []).length === 0 ? (
            <p className="text-sm text-muted-foreground">
              {t("expenses.report.noProjectAllocation")}
            </p>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b text-left">
                    <th className="pb-3">{t("expenses.filters.project")}</th>
                    <th className="pb-3">
                      {t("expenses.report.expenseCount")}
                    </th>
                    <th className="pb-3">{t("expenses.report.total")}</th>
                  </tr>
                </thead>
                <tbody>
                  {report?.by_project?.map((item) => (
                    <tr key={item.project_reference} className="border-b">
                      <td className="py-2">
                        {item.project_name ||
                          item.project_reference ||
                          t("expenses.report.unassigned")}
                      </td>
                      <td className="py-2">{item.count}</td>
                      <td className="py-2">
                        <CurrencyAmount
                          amount={item.total}
                          currency={currency}
                        />
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </CardContent>
      </Card>

      {isLoading ? (
        <p className="text-sm text-muted-foreground">
          {t("expenses.report.refreshing")}
        </p>
      ) : null}
    </div>
  );
}
