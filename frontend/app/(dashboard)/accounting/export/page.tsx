"use client";

import { useEffect, useState } from "react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Download } from "lucide-react";
import { apiClient } from "@/lib/api";
import { useAuthStore } from "@/lib/stores/auth";
import { toast } from "sonner";

const FORMATS = [
  { value: "pennylane", label: "Pennylane" },
  { value: "sage", label: "Sage" },
  { value: "generic", label: "Generic CSV" },
];

export default function AccountingExportPage() {
  const [format, setFormat] = useState("pennylane");
  const [dateFrom, setDateFrom] = useState("");
  const [dateTo, setDateTo] = useState("");
  const [columns, setColumns] = useState<string[]>([]);
  const [isLoading, setIsLoading] = useState(false);

  const fetchColumns = async (targetFormat: string = format) => {
    try {
      const response = await apiClient.get<{
        data: Record<string, { name: string; columns: string[] }>;
      }>("/accounting/export/formats");
      const formats = response.data?.data || {};
      if (formats[targetFormat]) {
        setColumns(formats[targetFormat].columns);
      }
    } catch (error) {
      toast.error(
        error instanceof Error ? error.message : "Failed to fetch columns"
      );
    }
  };

  useEffect(() => {
    fetchColumns();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const handleFormatChange = (newFormat: string) => {
    setFormat(newFormat);
    fetchColumns(newFormat);
  };

  const handleExport = async () => {
    if (!dateFrom || !dateTo) {
      toast.error("Please select date range");
      return;
    }

    const accessToken = useAuthStore.getState().accessToken;
    if (!accessToken) {
      toast.error("Authentication required");
      return;
    }

    try {
      const query = new URLSearchParams({
        format,
        date_from: dateFrom,
        date_to: dateTo,
      });
      const response = await fetch(
        `${process.env.NEXT_PUBLIC_API_URL || "http://localhost/api/v1"}/accounting/export?${query}`,
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
      a.download = `Accounting_${format}_${dateFrom}_${dateTo}.csv`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      a.remove();

      toast.success("Accounting export downloaded successfully");
    } catch (error) {
      toast.error("Failed to export accounting data");
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">Accounting Export</h1>
        <p className="text-sm text-muted-foreground">
          Export to Pennylane, Sage, or generic CSV format
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Export Configuration</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-4 md:grid-cols-3">
            <div className="space-y-2">
              <Label>Target Software</Label>
              <select
                className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                value={format}
                onChange={(e) => handleFormatChange(e.target.value)}
              >
                {FORMATS.map((f) => (
                  <option key={f.value} value={f.value}>
                    {f.label}
                  </option>
                ))}
              </select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="date_from">Date From</Label>
              <Input
                id="date_from"
                type="date"
                value={dateFrom}
                onChange={(e) => setDateFrom(e.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="date_to">Date To</Label>
              <Input
                id="date_to"
                type="date"
                value={dateTo}
                onChange={(e) => setDateTo(e.target.value)}
              />
            </div>
          </div>

          <Button onClick={handleExport} disabled={!dateFrom || !dateTo}>
            <Download className="mr-2 h-4 w-4" />
            Export to {FORMATS.find((f) => f.value === format)?.label}
          </Button>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Column Preview</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="flex flex-wrap gap-2">
            {columns.map((col, index) => (
              <span
                key={index}
                className="rounded bg-muted px-2 py-1 text-xs font-medium"
              >
                {col}
              </span>
            ))}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
