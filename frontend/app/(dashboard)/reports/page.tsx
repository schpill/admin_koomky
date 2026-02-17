"use client";

import { useEffect, useMemo, useState } from "react";
import dynamic from "next/dynamic";
import { Download } from "lucide-react";
import { toast } from "sonner";
import { apiClient } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { OutstandingTable } from "@/components/reports/outstanding-table";
import { VatSummaryTable } from "@/components/reports/vat-summary-table";
import { useAuthStore } from "@/lib/stores/auth";

const RevenueChart = dynamic(
  () => import("@/components/reports/revenue-chart").then((mod) => mod.RevenueChart),
  {
    loading: () => (
      <div className="h-64 animate-pulse rounded-lg border border-border bg-muted/40" />
    ),
  }
);

interface RevenueResponse {
  total_revenue: number;
  count: number;
  by_month: Array<{ month: string; total: number; count: number }>;
}

interface OutstandingResponse {
  total_outstanding: number;
  total_invoices: number;
  items: Array<{
    id: string;
    number: string;
    client_name?: string;
    status: string;
    due_date: string;
    aging_days: number;
    aging_bucket: string;
    balance_due: number;
  }>;
}

interface VatSummaryResponse {
  total_vat: number;
  by_rate: Array<{ rate: string; taxable_amount: number; vat_amount: number }>;
}

const startOfYear = new Date(new Date().getFullYear(), 0, 1)
  .toISOString()
  .slice(0, 10);
const endOfYear = new Date(new Date().getFullYear(), 11, 31)
  .toISOString()
  .slice(0, 10);

export default function ReportsPage() {
  const [activeTab, setActiveTab] = useState("revenue");
  const [dateFrom, setDateFrom] = useState(startOfYear);
  const [dateTo, setDateTo] = useState(endOfYear);

  const [isLoading, setIsLoading] = useState(false);
  const [revenue, setRevenue] = useState<RevenueResponse | null>(null);
  const [outstanding, setOutstanding] = useState<OutstandingResponse | null>(
    null
  );
  const [vatSummary, setVatSummary] = useState<VatSummaryResponse | null>(null);

  const query = useMemo(
    () => ({ date_from: dateFrom, date_to: dateTo }),
    [dateFrom, dateTo]
  );

  useEffect(() => {
    const load = async () => {
      setIsLoading(true);
      try {
        const [revenueRes, outstandingRes, vatRes] = await Promise.all([
          apiClient.get<RevenueResponse>("/reports/revenue", { params: query }),
          apiClient.get<OutstandingResponse>("/reports/outstanding", {
            params: query,
          }),
          apiClient.get<VatSummaryResponse>("/reports/vat-summary", {
            params: query,
          }),
        ]);

        setRevenue(revenueRes.data);
        setOutstanding(outstandingRes.data);
        setVatSummary(vatRes.data);
      } catch (error) {
        toast.error((error as Error).message || "Unable to load reports");
      } finally {
        setIsLoading(false);
      }
    };

    load();
  }, [query]);

  const exportReport = async (type: string, format: "csv" | "pdf") => {
    const params = new URLSearchParams({
      type,
      format,
      date_from: dateFrom,
      date_to: dateTo,
    });

    const token = useAuthStore.getState().accessToken;
    if (!token) {
      toast.error("You must be authenticated to export reports");
      return;
    }

    const base = process.env.NEXT_PUBLIC_API_URL || "http://localhost/api/v1";

    try {
      const response = await fetch(
        `${base}/reports/export?${params.toString()}`,
        {
          method: "GET",
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: format === "pdf" ? "application/pdf" : "text/csv",
          },
        }
      );

      if (!response.ok) {
        throw new Error(`Export failed (${response.status})`);
      }

      const blob = await response.blob();
      const url = URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = `report-${type}.${format}`;
      document.body.appendChild(link);
      link.click();
      link.remove();
      URL.revokeObjectURL(url);
    } catch (error) {
      toast.error((error as Error).message || "Unable to export report");
    }
  };

  return (
    <div className="space-y-6">
      <div className="space-y-2">
        <h1 className="text-3xl font-bold">Financial reports</h1>
        <p className="text-sm text-muted-foreground">
          Revenue, outstanding amounts and VAT summary with exports.
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Filters</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid gap-3 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="report-date-from">From</Label>
              <Input
                id="report-date-from"
                type="date"
                value={dateFrom}
                onChange={(event) => setDateFrom(event.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="report-date-to">To</Label>
              <Input
                id="report-date-to"
                type="date"
                value={dateTo}
                onChange={(event) => setDateTo(event.target.value)}
              />
            </div>
          </div>
        </CardContent>
      </Card>

      <Tabs value={activeTab} onValueChange={setActiveTab}>
        <TabsList className="grid w-full grid-cols-3">
          <TabsTrigger value="revenue">Revenue</TabsTrigger>
          <TabsTrigger value="outstanding">Outstanding</TabsTrigger>
          <TabsTrigger value="vat">VAT</TabsTrigger>
        </TabsList>

        <TabsContent value="revenue" className="space-y-4 pt-4">
          <div className="flex flex-wrap gap-2">
            <Button
              variant="outline"
              onClick={() => exportReport("revenue", "csv")}
            >
              <Download className="mr-2 h-4 w-4" />
              Export CSV
            </Button>
            <Button
              variant="outline"
              onClick={() => exportReport("revenue", "pdf")}
            >
              <Download className="mr-2 h-4 w-4" />
              Export PDF
            </Button>
          </div>

          <Card>
            <CardContent className="pt-6">
              <div className="grid gap-4 md:grid-cols-2">
                <div className="rounded-md border p-4">
                  <p className="text-xs text-muted-foreground">Total revenue</p>
                  <p className="text-2xl font-bold">
                    {Number(revenue?.total_revenue || 0).toFixed(2)} EUR
                  </p>
                </div>
                <div className="rounded-md border p-4">
                  <p className="text-xs text-muted-foreground">
                    Invoices counted
                  </p>
                  <p className="text-2xl font-bold">{revenue?.count || 0}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <RevenueChart
            title={isLoading ? "Revenue trend (loading...)" : "Revenue trend"}
            data={revenue?.by_month || []}
          />
        </TabsContent>

        <TabsContent value="outstanding" className="space-y-4 pt-4">
          <div className="flex flex-wrap gap-2">
            <Button
              variant="outline"
              onClick={() => exportReport("outstanding", "csv")}
            >
              <Download className="mr-2 h-4 w-4" />
              Export CSV
            </Button>
            <Button
              variant="outline"
              onClick={() => exportReport("outstanding", "pdf")}
            >
              <Download className="mr-2 h-4 w-4" />
              Export PDF
            </Button>
          </div>

          <Card>
            <CardContent className="pt-6">
              <div className="grid gap-4 md:grid-cols-2">
                <div className="rounded-md border p-4">
                  <p className="text-xs text-muted-foreground">
                    Total outstanding
                  </p>
                  <p className="text-2xl font-bold">
                    {Number(outstanding?.total_outstanding || 0).toFixed(2)} EUR
                  </p>
                </div>
                <div className="rounded-md border p-4">
                  <p className="text-xs text-muted-foreground">
                    Invoices counted
                  </p>
                  <p className="text-2xl font-bold">
                    {outstanding?.total_invoices || 0}
                  </p>
                </div>
              </div>
            </CardContent>
          </Card>

          <OutstandingTable items={outstanding?.items || []} />
        </TabsContent>

        <TabsContent value="vat" className="space-y-4 pt-4">
          <div className="flex flex-wrap gap-2">
            <Button
              variant="outline"
              onClick={() => exportReport("vat-summary", "csv")}
            >
              <Download className="mr-2 h-4 w-4" />
              Export CSV
            </Button>
            <Button
              variant="outline"
              onClick={() => exportReport("vat-summary", "pdf")}
            >
              <Download className="mr-2 h-4 w-4" />
              Export PDF
            </Button>
          </div>

          <VatSummaryTable
            rows={vatSummary?.by_rate || []}
            totalVat={Number(vatSummary?.total_vat || 0)}
          />
        </TabsContent>
      </Tabs>
    </div>
  );
}
