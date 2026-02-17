"use client";

import { useMemo, useState } from "react";
import { Download, Upload, Trash2 } from "lucide-react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  ImportProgressIndicator,
  type ImportStage,
} from "@/components/settings/import-progress-indicator";
import {
  ImportErrorReport,
  type ImportRowError,
} from "@/components/settings/import-error-report";
import { ConfirmationDialog } from "@/components/common/confirmation-dialog";
import { useAuthStore } from "@/lib/stores/auth";

type ImportEntity = "projects" | "invoices" | "contacts";

const entityLabels: Record<ImportEntity, string> = {
  projects: "Projects",
  invoices: "Invoices",
  contacts: "Contacts",
};

const apiBase = process.env.NEXT_PUBLIC_API_URL || "http://localhost/api/v1";

function sleep(ms: number): Promise<void> {
  return new Promise((resolve) => {
    setTimeout(resolve, ms);
  });
}

export default function DataSettingsPage() {
  const [entity, setEntity] = useState<ImportEntity>("projects");
  const [file, setFile] = useState<File | null>(null);
  const [stage, setStage] = useState<ImportStage>("idle");
  const [importedCount, setImportedCount] = useState<number | null>(null);
  const [errors, setErrors] = useState<ImportRowError[]>([]);
  const [isDeletingAccount, setIsDeletingAccount] = useState(false);
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);

  const token = useAuthStore((state) => state.accessToken);

  const canRunImport = useMemo(
    () => Boolean(file) && stage !== "creating" && stage !== "uploading",
    [file, stage]
  );

  const downloadFullExport = async () => {
    if (!token) {
      toast.error("Authentication required");
      return;
    }

    try {
      const response = await fetch(`${apiBase}/export/full`, {
        method: "GET",
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/zip",
        },
      });

      if (!response.ok) {
        throw new Error(`Export failed (${response.status})`);
      }

      const blob = await response.blob();
      const url = URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = `koomky-export-${new Date().toISOString().slice(0, 10)}.zip`;
      document.body.appendChild(link);
      link.click();
      link.remove();
      URL.revokeObjectURL(url);
      toast.success("Export started");
    } catch (error) {
      toast.error((error as Error).message || "Export failed");
    }
  };

  const runImport = async () => {
    if (!token) {
      toast.error("Authentication required");
      return;
    }

    if (!file) {
      toast.error("Select a CSV file first");
      return;
    }

    const formData = new FormData();
    formData.append("file", file);

    setImportedCount(null);
    setErrors([]);

    try {
      setStage("uploading");
      await sleep(120);
      setStage("parsing");
      await sleep(120);
      setStage("validating");
      await sleep(120);
      setStage("creating");

      const response = await fetch(`${apiBase}/import/${entity}`, {
        method: "POST",
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
        body: formData,
      });

      const payload = await response.json();
      if (!response.ok) {
        throw new Error(payload?.message || `Import failed (${response.status})`);
      }

      const imported = Number(payload?.data?.imported || 0);
      const rowErrors = (payload?.data?.errors || []) as ImportRowError[];
      setImportedCount(imported);
      setErrors(rowErrors);
      setStage("completed");

      if (rowErrors.length > 0) {
        toast.warning(`Import completed with ${rowErrors.length} error(s)`);
      } else {
        toast.success("Import completed successfully");
      }
    } catch (error) {
      setStage("error");
      toast.error((error as Error).message || "Import failed");
    }
  };

  const deleteAccount = async () => {
    if (!token) {
      toast.error("Authentication required");
      return;
    }

    setIsDeletingAccount(true);
    try {
      const response = await fetch(`${apiBase}/account`, {
        method: "DELETE",
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
      });

      const payload = await response.json();
      if (!response.ok) {
        throw new Error(
          payload?.message || `Account deletion failed (${response.status})`
        );
      }

      toast.success("Account deletion scheduled (30-day grace period)");
      setDeleteDialogOpen(false);
    } catch (error) {
      toast.error((error as Error).message || "Account deletion failed");
    } finally {
      setIsDeletingAccount(false);
    }
  };

  return (
    <div className="space-y-6">
      <div className="space-y-1">
        <h1 className="text-3xl font-bold">Data management</h1>
        <p className="text-sm text-muted-foreground">
          Import CSV files, export your full archive, and schedule account
          deletion.
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Import data</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-3 md:grid-cols-[220px_1fr]">
            <Select
              value={entity}
              onValueChange={(value) => setEntity(value as ImportEntity)}
            >
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {Object.entries(entityLabels).map(([value, label]) => (
                  <SelectItem key={value} value={value}>
                    {label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>

            <input
              type="file"
              accept=".csv,text/csv"
              onChange={(event) => setFile(event.target.files?.[0] ?? null)}
              className="block w-full cursor-pointer rounded-md border border-input bg-background px-3 py-2 text-sm file:mr-3 file:cursor-pointer file:rounded-md file:border file:border-border file:bg-muted file:px-3 file:py-1 file:text-sm"
            />
          </div>

          <Button onClick={runImport} disabled={!canRunImport}>
            <Upload className="mr-2 h-4 w-4" />
            Import CSV
          </Button>

          <ImportProgressIndicator stage={stage} />

          {importedCount !== null && (
            <div className="rounded-md border border-border bg-muted/30 px-3 py-2 text-sm">
              Imported records:{" "}
              <span className="font-semibold">{importedCount}</span>
            </div>
          )}

          <ImportErrorReport errors={errors} />
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Export data</CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          <p className="text-sm text-muted-foreground">
            Download a full JSON archive in ZIP format for GDPR portability.
          </p>
          <Button variant="outline" onClick={downloadFullExport}>
            <Download className="mr-2 h-4 w-4" />
            Download full export
          </Button>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Account deletion</CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          <p className="text-sm text-muted-foreground">
            This action soft-deletes your account immediately and schedules
            permanent removal after 30 days.
          </p>
          <Button
            variant="destructive"
            onClick={() => setDeleteDialogOpen(true)}
          >
            <Trash2 className="mr-2 h-4 w-4" />
            Delete account
          </Button>
        </CardContent>
      </Card>

      <ConfirmationDialog
        open={deleteDialogOpen}
        onOpenChange={setDeleteDialogOpen}
        onConfirm={deleteAccount}
        title="Delete account"
        description="Are you sure you want to schedule account deletion? You can recover during the next 30 days."
        confirmText={isDeletingAccount ? "Deleting..." : "Confirm deletion"}
        variant="destructive"
      />
    </div>
  );
}
