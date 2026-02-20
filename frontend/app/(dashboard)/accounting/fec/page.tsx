"use client";

import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Download, FileText } from "lucide-react";
import { apiClient } from "@/lib/api";
import { useAuthStore } from "@/lib/stores/auth";
import { toast } from "sonner";

export default function FecExportPage() {
  const [dateFrom, setDateFrom] = useState("");
  const [dateTo, setDateTo] = useState("");
  const [entryCount, setEntryCount] = useState<number | null>(null);
  const [isLoading, setIsLoading] = useState(false);

  const handlePreview = async () => {
    if (!dateFrom || !dateTo) {
      toast.error("Please select date range");
      return;
    }

    setIsLoading(true);
    try {
      const response = await apiClient.get<{ data: { entry_count: number } }>(
        "/accounting/fec/count",
        {
          params: { date_from: dateFrom, date_to: dateTo },
        }
      );
      setEntryCount(response.data?.data?.entry_count || 0);
    } catch (error) {
      toast.error("Failed to get entry count");
    } finally {
      setIsLoading(false);
    }
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
        date_from: dateFrom,
        date_to: dateTo,
      });
      const response = await fetch(
        `${process.env.NEXT_PUBLIC_API_URL || "http://localhost/api/v1"}/accounting/fec?${query}`,
        {
          headers: {
            Accept: "text/plain",
            Authorization: `Bearer ${accessToken}`,
          },
        }
      );

      if (!response.ok) {
        throw new Error("Export failed");
      }

      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `FEC_Export_${dateFrom}_${dateTo}.txt`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      a.remove();

      toast.success("FEC export downloaded successfully");
    } catch (error) {
      toast.error("Failed to export FEC");
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">FEC Export</h1>
        <p className="text-sm text-muted-foreground">
          Generate FEC-compliant accounting export for French tax authorities
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <FileText className="h-5 w-5" />
            Export Configuration
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-4 md:grid-cols-2">
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

          <div className="flex gap-2">
            <Button
              variant="outline"
              onClick={handlePreview}
              disabled={isLoading}
            >
              Preview Entry Count
            </Button>
            <Button onClick={handleExport} disabled={!dateFrom || !dateTo}>
              <Download className="mr-2 h-4 w-4" />
              Export FEC
            </Button>
          </div>

          {entryCount !== null && (
            <div className="rounded-lg bg-muted p-4">
              <p className="text-sm">
                <span className="font-medium">{entryCount}</span> entries will
                be exported
              </p>
            </div>
          )}
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>About FEC Export</CardTitle>
        </CardHeader>
        <CardContent className="text-sm text-muted-foreground">
          <p>
            The Fichier des Écritures Comptables (FEC) is a standardized file
            format required by French tax authorities for accounting data
            submission. This export generates a semicolon-delimited UTF-8 file
            containing:
          </p>
          <ul className="mt-2 list-inside list-disc space-y-1">
            <li>Invoice entries (sales journal)</li>
            <li>Credit note entries (sales journal)</li>
            <li>Payment entries (bank journal)</li>
            <li>Expense entries (purchases journal)</li>
          </ul>
        </CardContent>
      </Card>
    </div>
  );
}
