"use client";

import { useEffect, useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import {
  Calendar,
  TrendingUp,
  TrendingDown,
  Receipt,
  Clock,
} from "lucide-react";
import { apiClient } from "@/lib/api";
import { toast } from "sonner";

interface FiscalYearSummary {
  year: number;
  date_from: string;
  date_to: string;
  revenue: {
    total: number;
    accrual_basis: number;
  };
  expenses: {
    total: number;
    by_category: Array<{ category_name: string; total: number; count: number }>;
  };
  net_profit: number;
  margin_percent: number;
  vat_position: {
    vat_collected: number;
    vat_deductible: number;
    net_due: number;
    is_credit: boolean;
  };
  outstanding_receivables: {
    total: number;
    overdue: number;
    invoice_count: number;
  };
}

export default function FiscalYearSummaryPage() {
  const [summary, setSummary] = useState<FiscalYearSummary | null>(null);
  const [year, setYear] = useState(new Date().getFullYear());
  const [isLoading, setIsLoading] = useState(false);

  useEffect(() => {
    fetchSummary();
  }, [year]);

  const fetchSummary = async () => {
    setIsLoading(true);
    try {
      const response = await apiClient.get<{ data: FiscalYearSummary }>(
        "/accounting/fiscal-year",
        {
          params: { year },
        }
      );
      setSummary(response.data?.data || null);
    } catch (error) {
      toast.error("Failed to fetch fiscal year summary");
    } finally {
      setIsLoading(false);
    }
  };

  const formatAmount = (amount: number) => {
    return new Intl.NumberFormat("fr-FR", {
      style: "currency",
      currency: "EUR",
    }).format(amount);
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Fiscal Year Summary</h1>
          <p className="text-sm text-muted-foreground">
            Closing summary for the fiscal year
          </p>
        </div>
        <select
          className="h-10 rounded-md border border-input bg-background px-3 text-sm"
          value={year}
          onChange={(e) => setYear(parseInt(e.target.value))}
        >
          {[2024, 2023, 2022, 2021].map((y) => (
            <option key={y} value={y}>
              {y}
            </option>
          ))}
        </select>
      </div>

      {isLoading ? (
        <p className="text-muted-foreground">Loading...</p>
      ) : summary ? (
        <>
          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <Card>
              <CardHeader className="pb-2">
                <CardTitle className="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                  <TrendingUp className="h-4 w-4" />
                  Revenue
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold text-green-600">
                  {formatAmount(summary.revenue.total)}
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="pb-2">
                <CardTitle className="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                  <TrendingDown className="h-4 w-4" />
                  Expenses
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold text-red-600">
                  {formatAmount(summary.expenses.total)}
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="pb-2">
                <CardTitle className="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                  <Calendar className="h-4 w-4" />
                  Net Profit
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div
                  className={`text-2xl font-bold ${summary.net_profit >= 0 ? "text-green-600" : "text-red-600"}`}
                >
                  {formatAmount(summary.net_profit)}
                </div>
                <p className="text-sm text-muted-foreground">
                  {summary.margin_percent.toFixed(1)}% margin
                </p>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="pb-2">
                <CardTitle className="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                  <Receipt className="h-4 w-4" />
                  Net VAT Due
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div
                  className={`text-2xl font-bold ${summary.vat_position.is_credit ? "text-green-600" : ""}`}
                >
                  {formatAmount(summary.vat_position.net_due)}
                </div>
              </CardContent>
            </Card>
          </div>

          <div className="grid gap-6 lg:grid-cols-2">
            <Card>
              <CardHeader>
                <CardTitle>Expenses by Category</CardTitle>
              </CardHeader>
              <CardContent>
                {summary.expenses.by_category.length > 0 ? (
                  <div className="space-y-3">
                    {summary.expenses.by_category.map((cat, index) => (
                      <div
                        key={index}
                        className="flex items-center justify-between"
                      >
                        <div>
                          <span className="font-medium">
                            {cat.category_name}
                          </span>
                          <span className="ml-2 text-sm text-muted-foreground">
                            ({cat.count} entries)
                          </span>
                        </div>
                        <span className="font-medium">
                          {formatAmount(cat.total)}
                        </span>
                      </div>
                    ))}
                  </div>
                ) : (
                  <p className="text-muted-foreground">No expense data</p>
                )}
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <Clock className="h-5 w-5" />
                  Outstanding Receivables
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="space-y-4">
                  <div className="flex items-center justify-between">
                    <span>Total Outstanding</span>
                    <span className="font-medium">
                      {formatAmount(summary.outstanding_receivables.total)}
                    </span>
                  </div>
                  <div className="flex items-center justify-between">
                    <span>Overdue</span>
                    <Badge
                      variant={
                        summary.outstanding_receivables.overdue > 0
                          ? "destructive"
                          : "secondary"
                      }
                    >
                      {formatAmount(summary.outstanding_receivables.overdue)}
                    </Badge>
                  </div>
                  <div className="flex items-center justify-between">
                    <span>Invoice Count</span>
                    <span className="font-medium">
                      {summary.outstanding_receivables.invoice_count}
                    </span>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>

          <Card>
            <CardHeader>
              <CardTitle>VAT Position</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="grid gap-4 md:grid-cols-3">
                <div className="rounded-lg bg-muted p-4">
                  <p className="text-sm text-muted-foreground">VAT Collected</p>
                  <p className="text-xl font-bold">
                    {formatAmount(summary.vat_position.vat_collected)}
                  </p>
                </div>
                <div className="rounded-lg bg-muted p-4">
                  <p className="text-sm text-muted-foreground">
                    VAT Deductible
                  </p>
                  <p className="text-xl font-bold">
                    {formatAmount(summary.vat_position.vat_deductible)}
                  </p>
                </div>
                <div className="rounded-lg bg-muted p-4">
                  <p className="text-sm text-muted-foreground">Net Due</p>
                  <p
                    className={`text-xl font-bold ${summary.vat_position.is_credit ? "text-green-600" : ""}`}
                  >
                    {formatAmount(summary.vat_position.net_due)}
                  </p>
                </div>
              </div>
            </CardContent>
          </Card>
        </>
      ) : (
        <p className="text-muted-foreground">No data available</p>
      )}
    </div>
  );
}
