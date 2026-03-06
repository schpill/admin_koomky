"use client";

import { ChangeEvent, useState } from "react";
import { Button } from "@/components/ui/button";

interface StepUploadProps {
  isUploading: boolean;
  onAnalyze: (file: File) => Promise<void>;
}

export function StepUpload({ isUploading, onAnalyze }: StepUploadProps) {
  const [file, setFile] = useState<File | null>(null);
  const [error, setError] = useState<string | null>(null);

  const handleFile = (event: ChangeEvent<HTMLInputElement>) => {
    const selected = event.target.files?.[0] || null;
    setError(null);

    if (!selected) {
      setFile(null);
      return;
    }

    const ext = selected.name.split(".").pop()?.toLowerCase();
    if (!ext || !["csv", "xlsx", "xls"].includes(ext)) {
      setError("Format invalide. Utilisez CSV ou XLSX.");
      setFile(null);
      return;
    }

    if (selected.size > 5 * 1024 * 1024) {
      setError("Le fichier dépasse 5 Mo.");
      setFile(null);
      return;
    }

    setFile(selected);
  };

  return (
    <div className="space-y-4">
      <input
        data-testid="import-file-input"
        type="file"
        accept=".csv,.xlsx,.xls"
        onChange={handleFile}
      />

      {file ? (
        <p className="text-sm">Fichier sélectionné: {file.name}</p>
      ) : null}
      {error ? <p className="text-sm text-destructive">{error}</p> : null}

      <Button
        onClick={() => file && onAnalyze(file)}
        disabled={!file || isUploading}
      >
        {isUploading ? "Analyse en cours..." : "Analyser le fichier"}
      </Button>
    </div>
  );
}
