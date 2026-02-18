"use client";

import { useEffect, useMemo, useState } from "react";
import { toast } from "sonner";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { CurrencyAmount } from "@/components/shared/currency-amount";
import { apiClient } from "@/lib/api";

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
          (error as Error).message || "Unable to load profitability report"
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
        <h1 className="text-3xl font-bold">Project profitability</h1>
        <p className="text-sm text-muted-foreground">
          Compare revenue, time cost and expenses per project.
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Filters</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-3 md:grid-cols-2">
          <div className="space-y-2">
            <Label htmlFor="project-profit-from">From</Label>
            <Input
              id="project-profit-from"
              type="date"
              value={dateFrom}
              onChange={(event) => setDateFrom(event.target.value)}
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="project-profit-to">To</Label>
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
          <CardTitle>Profitability table</CardTitle>
        </CardHeader>
        <CardContent>
          {sortedRows.length === 0 ? (
            <p className="text-sm text-muted-foreground">
              {isLoading
                ? "Loading project profitability..."
                : "No project data for selected period."}
            </p>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b text-left">
                    <th className="pb-3">Project</th>
                    <th className="pb-3">Client</th>
                    <th className="pb-3">Revenue</th>
                    <th className="pb-3">Time cost</th>
                    <th className="pb-3">Expenses</th>
                    <th className="pb-3">Profit</th>
                    <th className="pb-3">Margin</th>
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
                        <CurrencyAmount amount={row.revenue} currency={currency} />
                      </td>
                      <td className="py-2">
                        <CurrencyAmount amount={row.time_cost} currency={currency} />
                      </td>
                      <td className="py-2">
                        <CurrencyAmount amount={row.expenses} currency={currency} />
                      </td>
                      <td
                        className={`py-2 font-medium ${
                          row.profit >= 0 ? "text-emerald-600" : "text-destructive"
                        }`}
                      >
                        <CurrencyAmount amount={row.profit} currency={currency} />
                      </td>
                      <td
                        className={`py-2 font-medium ${
                          row.margin >= 0 ? "text-emerald-600" : "text-destructive"
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
