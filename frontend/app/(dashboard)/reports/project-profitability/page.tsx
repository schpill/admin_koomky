"use client";

import { useEffect, useMemo, useState } from "react";
import { toast } from "sonner";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { CurrencyAmount } from "@/components/shared/currency-amount";
import { apiClient } from "@/lib/api";
import { useI18n } from "@/components/providers/i18n-provider";

interface ProjectProfitabilityRow {
  project_id: string;
  project_reference?: string;
  project_name: string;
  client_name?: string | null;
  revenue: number;
  time_cost: number;
  expenses: number;
  profit: number;
  margin: number;
  currency: string;
}

const startOfYear = new Date(new Date().getFullYear(), 0, 1)
  .toISOString()
  .slice(0, 10);
const today = new Date().toISOString().slice(0, 10);

export default function ProjectProfitabilityReportPage() {
  const { t } = useI18n();
  const [dateFrom, setDateFrom] = useState(startOfYear);
  const [dateTo, setDateTo] = useState(today);
  const [rows, setRows] = useState<ProjectProfitabilityRow[]>([]);
  const [isLoading, setLoading] = useState(false);

  useEffect(() => {
    setLoading(true);
    apiClient
      .get<ProjectProfitabilityRow[]>("/reports/project-profitability", {
        params: {
          date_from: dateFrom,
          date_to: dateTo,
        },
      })
      .then((response) => setRows(response.data || []))
      .catch((error) => {
        toast.error(
          (error as Error).message ||
            t("reports.projectProfitabilityReport.toasts.loadFailed")
        );
      })
      .finally(() => setLoading(false));
  }, [dateFrom, dateTo]);

  const sortedRows = useMemo(
    () => [...rows].sort((a, b) => b.profit - a.profit),
    [rows]
  );

  const currency = sortedRows[0]?.currency || "EUR";

  return (
    <div className="space-y-6">
      <div className="space-y-1">
        <h1 className="text-3xl font-bold">
          {t("reports.projectProfitabilityReport.title")}
        </h1>
        <p className="text-sm text-muted-foreground">
          {t("reports.projectProfitabilityReport.description")}
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Filters</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-3 md:grid-cols-2">
          <div className="space-y-2">
            <Label htmlFor="project-profit-from">
              {t("reports.filters.from")}
            </Label>
            <Input
              id="project-profit-from"
              type="date"
              value={dateFrom}
              onChange={(event) => setDateFrom(event.target.value)}
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="project-profit-to">{t("reports.filters.to")}</Label>
            <Input
              id="project-profit-to"
              type="date"
              value={dateTo}
              onChange={(event) => setDateTo(event.target.value)}
            />
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>
            {t("reports.projectProfitabilityReport.table.title")}
          </CardTitle>
        </CardHeader>
        <CardContent>
          {sortedRows.length === 0 ? (
            <p className="text-sm text-muted-foreground">
              {isLoading
                ? t("reports.projectProfitabilityReport.table.loading")
                : t("reports.projectProfitabilityReport.table.empty")}
            </p>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b text-left">
                    <th className="pb-3">
                      {t("reports.projectProfitabilityReport.table.project")}
                    </th>
                    <th className="pb-3">
                      {t("reports.projectProfitabilityReport.table.client")}
                    </th>
                    <th className="pb-3">
                      {t("reports.projectProfitabilityReport.table.revenue")}
                    </th>
                    <th className="pb-3">
                      {t("reports.projectProfitabilityReport.table.timeCost")}
                    </th>
                    <th className="pb-3">
                      {t("reports.projectProfitabilityReport.table.expenses")}
                    </th>
                    <th className="pb-3">
                      {t("reports.projectProfitabilityReport.table.profit")}
                    </th>
                    <th className="pb-3">
                      {t("reports.projectProfitabilityReport.table.margin")}
                    </th>
                  </tr>
                </thead>
                <tbody>
                  {sortedRows.map((row) => (
                    <tr key={row.project_id} className="border-b">
                      <td className="py-2">
                        <p className="font-medium">{row.project_name}</p>
                        <p className="text-xs text-muted-foreground">
                          {row.project_reference}
                        </p>
                      </td>
                      <td className="py-2">{row.client_name || "-"}</td>
                      <td className="py-2">
                        <CurrencyAmount
                          amount={row.revenue}
                          currency={currency}
                        />
                      </td>
                      <td className="py-2">
                        <CurrencyAmount
                          amount={row.time_cost}
                          currency={currency}
                        />
                      </td>
                      <td className="py-2">
                        <CurrencyAmount
                          amount={row.expenses}
                          currency={currency}
                        />
                      </td>
                      <td
                        className={`py-2 font-medium ${
                          row.profit >= 0
                            ? "text-emerald-600"
                            : "text-destructive"
                        }`}
                      >
                        <CurrencyAmount
                          amount={row.profit}
                          currency={currency}
                        />
                      </td>
                      <td
                        className={`py-2 font-medium ${
                          row.margin >= 0
                            ? "text-emerald-600"
                            : "text-destructive"
                        }`}
                      >
                        {Number(row.margin || 0).toFixed(2)}%
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
