"use client";

import { useEffect, useMemo, useState } from "react";
import { toast } from "sonner";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { CurrencyAmount } from "@/components/shared/currency-amount";
import { apiClient } from "@/lib/api";

interface ProfitLossReport {
  revenue: number;
  expenses: number;
  profit: number;
  margin: number;
  base_currency: string;
  by_month: Array<{
    month: string;
    revenue: number;
    expenses: number;
    profit: number;
  }>;
  by_project: Array<{
    project_id: string;
    project_name?: string | null;
    project_reference?: string | null;
    revenue: number;
    expenses: number;
    profit: number;
  }>;
  by_client: Array<{
    client_id: string;
    client_name?: string | null;
    revenue: number;
    expenses: number;
    profit: number;
  }>;
}

const startOfYear = new Date(new Date().getFullYear(), 0, 1)
  .toISOString()
  .slice(0, 10);
const today = new Date().toISOString().slice(0, 10);

export default function ProfitLossReportPage() {
  const [dateFrom, setDateFrom] = useState(startOfYear);
  const [dateTo, setDateTo] = useState(today);
  const [report, setReport] = useState<ProfitLossReport | null>(null);
  const [isLoading, setLoading] = useState(false);

  useEffect(() => {
    setLoading(true);
    apiClient
      .get<ProfitLossReport>("/reports/profit-loss", {
        params: {
          date_from: dateFrom,
          date_to: dateTo,
        },
      })
      .then((response) => setReport(response.data))
      .catch((error) => {
        toast.error((error as Error).message || "Unable to load P&L report");
      })
      .finally(() => setLoading(false));
  }, [dateFrom, dateTo]);

  const currency = useMemo(
    () => report?.base_currency || "EUR",
    [report?.base_currency]
  );

  return (
    <div className="space-y-6">
      <div className="space-y-1">
        <h1 className="text-3xl font-bold">Profit & loss report</h1>
        <p className="text-sm text-muted-foreground">
          Revenue versus expenses with monthly trend and margin.
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Filters</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-3 md:grid-cols-2">
          <div className="space-y-2">
            <Label htmlFor="pl-date-from">From</Label>
            <Input
              id="pl-date-from"
              type="date"
              value={dateFrom}
              onChange={(event) => setDateFrom(event.target.value)}
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="pl-date-to">To</Label>
            <Input
              id="pl-date-to"
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
            <CardTitle className="text-sm">Revenue</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-2xl font-semibold">
              <CurrencyAmount
                amount={Number(report?.revenue || 0)}
                currency={currency}
              />
            </p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle className="text-sm">Expenses</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-2xl font-semibold">
              <CurrencyAmount
                amount={Number(report?.expenses || 0)}
                currency={currency}
              />
            </p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle className="text-sm">Profit</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-2xl font-semibold">
              <CurrencyAmount
                amount={Number(report?.profit || 0)}
                currency={currency}
              />
            </p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle className="text-sm">Margin</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-2xl font-semibold">
              {Number(report?.margin || 0).toFixed(2)}%
            </p>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Monthly trend</CardTitle>
        </CardHeader>
        <CardContent className="space-y-2">
          {(report?.by_month || []).length === 0 ? (
            <p className="text-sm text-muted-foreground">
              No monthly trend data.
            </p>
          ) : (
            report?.by_month?.map((item) => (
              <div
                key={item.month}
                className="grid gap-2 rounded border p-3 text-sm md:grid-cols-4"
              >
                <span className="font-medium">{item.month}</span>
                <span>
                  Revenue:{" "}
                  <CurrencyAmount amount={item.revenue} currency={currency} />
                </span>
                <span>
                  Expenses:{" "}
                  <CurrencyAmount amount={item.expenses} currency={currency} />
                </span>
                <span>
                  Profit:{" "}
                  <CurrencyAmount amount={item.profit} currency={currency} />
                </span>
              </div>
            ))
          )}
        </CardContent>
      </Card>

      <div className="grid gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Project breakdown</CardTitle>
          </CardHeader>
          <CardContent className="space-y-2">
            {(report?.by_project || []).length === 0 ? (
              <p className="text-sm text-muted-foreground">No project data.</p>
            ) : (
              report?.by_project?.map((project) => (
                <div
                  key={project.project_id}
                  className="rounded border p-3 text-sm"
                >
                  <p className="font-medium">
                    {project.project_name ||
                      project.project_reference ||
                      "Project"}
                  </p>
                  <p>
                    Profit:{" "}
                    <CurrencyAmount
                      amount={project.profit}
                      currency={currency}
                    />
                  </p>
                </div>
              ))
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Client breakdown</CardTitle>
          </CardHeader>
          <CardContent className="space-y-2">
            {(report?.by_client || []).length === 0 ? (
              <p className="text-sm text-muted-foreground">No client data.</p>
            ) : (
              report?.by_client?.map((client) => (
                <div
                  key={client.client_id}
                  className="rounded border p-3 text-sm"
                >
                  <p className="font-medium">
                    {client.client_name || "Client"}
                  </p>
                  <p>
                    Profit:{" "}
                    <CurrencyAmount
                      amount={client.profit}
                      currency={currency}
                    />
                  </p>
                </div>
              ))
            )}
          </CardContent>
        </Card>
      </div>

      {isLoading ? (
        <p className="text-sm text-muted-foreground">Refreshing report...</p>
      ) : null}
    </div>
  );
}
