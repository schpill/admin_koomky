"use client";

import { useEffect, useState } from "react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { toast } from "sonner";
import { apiClient } from "@/lib/api";

interface AccountingSettings {
  accounting_journal_sales: string;
  accounting_journal_purchases: string;
  accounting_journal_bank: string;
  accounting_auxiliary_prefix: string | null;
  fiscal_year_start_month: number;
}

export default function AccountingSettingsPage() {
  const [settings, setSettings] = useState<AccountingSettings>({
    accounting_journal_sales: "VTE",
    accounting_journal_purchases: "ACH",
    accounting_journal_bank: "BQ",
    accounting_auxiliary_prefix: "",
    fiscal_year_start_month: 1,
  });
  const [isLoading, setIsLoading] = useState(false);
  const [isSaving, setIsSaving] = useState(false);

  useEffect(() => {
    fetchSettings();
  }, []);

  const fetchSettings = async () => {
    setIsLoading(true);
    try {
      const response = await apiClient.get<{ data: AccountingSettings }>(
        "/settings/accounting"
      );
      if (response.data?.data) {
        setSettings(response.data.data);
      }
    } catch (error) {
      toast.error("Failed to fetch accounting settings");
    } finally {
      setIsLoading(false);
    }
  };

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSaving(true);

    try {
      await apiClient.put("/settings/accounting", settings);
      toast.success("Accounting settings saved successfully");
    } catch (error) {
      toast.error("Failed to save accounting settings");
    } finally {
      setIsSaving(false);
    }
  };

  const months = [
    { value: 1, label: "January" },
    { value: 2, label: "February" },
    { value: 3, label: "March" },
    { value: 4, label: "April" },
    { value: 5, label: "May" },
    { value: 6, label: "June" },
    { value: 7, label: "July" },
    { value: 8, label: "August" },
    { value: 9, label: "September" },
    { value: 10, label: "October" },
    { value: 11, label: "November" },
    { value: 12, label: "December" },
  ];

  if (isLoading) {
    return <p className="text-muted-foreground">Loading...</p>;
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">Accounting Settings</h1>
        <p className="text-sm text-muted-foreground">
          Configure journal codes and fiscal year settings
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Journal Codes</CardTitle>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleSave} className="space-y-4">
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
              <div className="space-y-2">
                <Label htmlFor="sales_journal">Sales Journal Code</Label>
                <Input
                  id="sales_journal"
                  value={settings.accounting_journal_sales}
                  onChange={(e) =>
                    setSettings({
                      ...settings,
                      accounting_journal_sales: e.target.value,
                    })
                  }
                  placeholder="VTE"
                  maxLength={10}
                />
                <p className="text-xs text-muted-foreground">
                  Default: VTE (Journal des ventes)
                </p>
              </div>

              <div className="space-y-2">
                <Label htmlFor="purchases_journal">
                  Purchases Journal Code
                </Label>
                <Input
                  id="purchases_journal"
                  value={settings.accounting_journal_purchases}
                  onChange={(e) =>
                    setSettings({
                      ...settings,
                      accounting_journal_purchases: e.target.value,
                    })
                  }
                  placeholder="ACH"
                  maxLength={10}
                />
                <p className="text-xs text-muted-foreground">
                  Default: ACH (Journal des achats)
                </p>
              </div>

              <div className="space-y-2">
                <Label htmlFor="bank_journal">Bank Journal Code</Label>
                <Input
                  id="bank_journal"
                  value={settings.accounting_journal_bank}
                  onChange={(e) =>
                    setSettings({
                      ...settings,
                      accounting_journal_bank: e.target.value,
                    })
                  }
                  placeholder="BQ"
                  maxLength={10}
                />
                <p className="text-xs text-muted-foreground">
                  Default: BQ (Journal de banque)
                </p>
              </div>
            </div>

            <div className="grid gap-4 md:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="auxiliary_prefix">
                  Auxiliary Account Prefix
                </Label>
                <Input
                  id="auxiliary_prefix"
                  value={settings.accounting_auxiliary_prefix || ""}
                  onChange={(e) =>
                    setSettings({
                      ...settings,
                      accounting_auxiliary_prefix: e.target.value || null,
                    })
                  }
                  placeholder="Optional prefix for client auxiliary accounts"
                  maxLength={10}
                />
                <p className="text-xs text-muted-foreground">
                  Prefix added to client IDs for auxiliary account codes
                </p>
              </div>

              <div className="space-y-2">
                <Label htmlFor="fiscal_year_start">
                  Fiscal Year Start Month
                </Label>
                <select
                  id="fiscal_year_start"
                  className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                  value={settings.fiscal_year_start_month}
                  onChange={(e) =>
                    setSettings({
                      ...settings,
                      fiscal_year_start_month: parseInt(e.target.value),
                    })
                  }
                >
                  {months.map((month) => (
                    <option key={month.value} value={month.value}>
                      {month.label}
                    </option>
                  ))}
                </select>
                <p className="text-xs text-muted-foreground">
                  Month when your fiscal year begins (default: January)
                </p>
              </div>
            </div>

            <Button type="submit" disabled={isSaving}>
              {isSaving ? "Saving..." : "Save Settings"}
            </Button>
          </form>
        </CardContent>
      </Card>
    </div>
  );
}
