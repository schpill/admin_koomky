"use client";

import { useCallback, useEffect, useState } from "react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Download, Receipt } from "lucide-react";
import { apiClient } from "@/lib/api";
import { useAuthStore } from "@/lib/stores/auth";
import { toast } from "sonner";

interface VatReport {
  year: number;
  period_type: string;
  periods: Array<{
    month?: number;
    quarter?: number;
    period: string;
    vat_collected: Record<string, number>;
    total_collected: number;
    total_deductible: number;
    net_due: number;
    is_credit: boolean;
  }>;
  totals: {
    total_collected: number;
    total_deductible: number;
    net_due: number;
  };
}

const currentYear = new Date().getFullYear();
const years = Array.from({ length: 5 }, (_, i) => currentYear - i);

export default function VatDeclarationPage() {
  const [report, setReport] = useState<VatReport | null>(null);
  const [year, setYear] = useState(currentYear);
  const [periodType, setPeriodType] = useState<"monthly" | "quarterly">(
    "monthly"
  );
  const [isLoading, setIsLoading] = useState(false);

  const fetchReport = useCallback(async () => {
    setIsLoading(true);
    try {
      const response = await apiClient.get<{ data: VatReport }>(
        "/accounting/vat",
        {
          params: { year, period_type: periodType },
        }
      );
      setReport(response.data?.data || null);
    } catch (error) {
      toast.error("Failed to fetch VAT report");
    } finally {
      setIsLoading(false);
    }
  }, [year, periodType]);

  useEffect(() => {
    fetchReport();
  }, [fetchReport]);

  const handleExportCsv = async () => {
    const accessToken = useAuthStore.getState().accessToken;
    if (!accessToken) {
      toast.error("Authentication required");
      return;
    }

    try {
      const query = new URLSearchParams({
        year: year.toString(),
        period_type: periodType,
      });
      const response = await fetch(
        `${process.env.NEXT_PUBLIC_API_URL || "http://localhost/api/v1"}/accounting/vat/export?${query}`,
        {
          headers: {
            Accept: "text/csv",
            Authorization: `Bearer ${accessToken}`,
          },
        }
      );

      if (!response.ok) throw new Error("Export failed");

      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `TVA_${year}_${periodType}.csv`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      a.remove();

      toast.success("VAT report exported successfully");
    } catch (error) {
      toast.error("Failed to export VAT report");
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
          <h1 className="text-3xl font-bold">VAT Declaration</h1>
          <p className="text-sm text-muted-foreground">
            View and export VAT declaration report
          </p>
        </div>
        <div className="flex gap-2">
          <select
            className="h-10 rounded-md border border-input bg-background px-3 text-sm"
            value={year}
            onChange={(e) => setYear(parseInt(e.target.value))}
          >
            {years.map((y) => (
              <option key={y} value={y}>
                {y}
              </option>
            ))}
          </select>
          <select
            className="h-10 rounded-md border border-input bg-background px-3 text-sm"
            value={periodType}
            onChange={(e) =>
              setPeriodType(e.target.value as "monthly" | "quarterly")
            }
          >
            <option value="monthly">Monthly</option>
            <option value="quarterly">Quarterly</option>
          </select>
          <Button onClick={handleExportCsv}>
            <Download className="mr-2 h-4 w-4" />
            Export CSV
          </Button>
        </div>
      </div>

      <div className="grid gap-4 md:grid-cols-3">
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">
              Total VAT Collected
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {formatAmount(report?.totals?.total_collected || 0)}
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">
              Total VAT Deductible
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {formatAmount(report?.totals?.total_deductible || 0)}
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">
              Net VAT Due
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div
              className={`text-2xl font-bold ${(report?.totals?.net_due || 0) < 0 ? "text-green-600" : ""}`}
            >
              {formatAmount(report?.totals?.net_due || 0)}
            </div>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Receipt className="h-5 w-5" />
            VAT by Period
          </CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <p className="text-muted-foreground">Loading...</p>
          ) : report?.periods ? (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b text-left">
                    <th className="pb-3">Period</th>
                    <th className="pb-3 text-right">VAT 0%</th>
                    <th className="pb-3 text-right">VAT 5.5%</th>
                    <th className="pb-3 text-right">VAT 10%</th>
                    <th className="pb-3 text-right">VAT 20%</th>
                    <th className="pb-3 text-right">Collected</th>
                    <th className="pb-3 text-right">Deductible</th>
                    <th className="pb-3 text-right">Net Due</th>
                  </tr>
                </thead>
                <tbody>
                  {report.periods.map((period, index) => (
                    <tr key={index} className="border-b last:border-0">
                      <td className="py-3 font-medium">{period.period}</td>
                      <td className="py-3 text-right">
                        {formatAmount(period.vat_collected["0"] || 0)}
                      </td>
                      <td className="py-3 text-right">
                        {formatAmount(period.vat_collected["5.5"] || 0)}
                      </td>
                      <td className="py-3 text-right">
                        {formatAmount(period.vat_collected["10"] || 0)}
                      </td>
                      <td className="py-3 text-right">
                        {formatAmount(period.vat_collected["20"] || 0)}
                      </td>
                      <td className="py-3 text-right">
                        {formatAmount(period.total_collected)}
                      </td>
                      <td className="py-3 text-right">
                        {formatAmount(period.total_deductible)}
                      </td>
                      <td className="py-3 text-right">
                        <Badge
                          variant={period.net_due < 0 ? "secondary" : "default"}
                        >
                          {formatAmount(period.net_due)}
                        </Badge>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <p className="text-muted-foreground">No data available</p>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
