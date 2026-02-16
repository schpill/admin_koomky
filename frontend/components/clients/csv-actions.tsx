"use client";

import { useRef, useState } from "react";
import { Download, Upload } from "lucide-react";
import { Button } from "@/components/ui/button";
import { toast } from "sonner";
import { useClientStore } from "@/lib/stores/clients";
import { useI18n } from "@/components/providers/i18n-provider";

export function CsvActions() {
  const fileInputRef = useRef<HTMLInputElement>(null);
  const [isImporting, setIsImporting] = useState(false);
  const fetchClients = useClientStore((state) => state.fetchClients);
  const { t } = useI18n();

  const handleExport = async () => {
    try {
      const response = await fetch(
        `${process.env.NEXT_PUBLIC_API_URL || "http://localhost/api/v1"}/clients/export/csv`,
        {
          headers: {
            Authorization: `Bearer ${localStorage.getItem("koomky-auth") ? JSON.parse(localStorage.getItem("koomky-auth")!).state.accessToken : ""}`,
          },
        }
      );

      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = "clients.csv";
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      toast.success(t("clients.csv.toasts.exportSuccess"));
    } catch (error) {
      toast.error(t("clients.csv.toasts.exportFailed"));
    }
  };

  const handleImport = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    setIsImporting(true);
    const formData = new FormData();
    formData.append("file", file);

    try {
      // Need to use raw fetch for FormData with our current apiClient setup if it doesn't handle multipart
      const response = await fetch(
        `${process.env.NEXT_PUBLIC_API_URL || "http://localhost/api/v1"}/clients/import/csv`,
        {
          method: "POST",
          headers: {
            Authorization: `Bearer ${localStorage.getItem("koomky-auth") ? JSON.parse(localStorage.getItem("koomky-auth")!).state.accessToken : ""}`,
          },
          body: formData,
        }
      );

      if (!response.ok) throw new Error(t("clients.csv.toasts.importFailed"));

      toast.success(t("clients.csv.toasts.importSuccess"));
      fetchClients(); // Refresh list
    } catch (error) {
      toast.error(t("clients.csv.toasts.importFailedWithHint"));
    } finally {
      setIsImporting(false);
      if (fileInputRef.current) fileInputRef.current.value = "";
    }
  };

  return (
    <div className="flex gap-2">
      <input
        type="file"
        ref={fileInputRef}
        onChange={handleImport}
        className="hidden"
        accept=".csv"
      />
      <Button
        variant="outline"
        size="sm"
        onClick={() => fileInputRef.current?.click()}
        disabled={isImporting}
      >
        <Upload className="mr-2 h-4 w-4" />
        {isImporting ? t("clients.csv.importing") : t("clients.csv.import")}
      </Button>
      <Button variant="outline" size="sm" onClick={handleExport}>
        <Download className="mr-2 h-4 w-4" /> {t("clients.csv.export")}
      </Button>
    </div>
  );
}
