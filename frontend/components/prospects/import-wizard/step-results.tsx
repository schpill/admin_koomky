"use client";

import { Button } from "@/components/ui/button";
import { ImportErrorRow, ImportSession } from "@/lib/stores/prospect-import";

interface StepResultsProps {
  session: ImportSession | null;
  progress: number;
  errors: ImportErrorRow[];
  isProcessing: boolean;
  onExportErrors: () => Promise<void>;
}

export function StepResults({
  session,
  progress,
  errors,
  isProcessing,
  onExportErrors,
}: StepResultsProps) {
  const statusText = isProcessing ? "En cours..." : session?.status || "En attente";

  return (
    <div className="space-y-4">
      <div>
        <p>Statut: {statusText}</p>
        <p>
          Progression: {session?.processed_rows || 0}/{session?.total_rows || 0} ({progress}
          %)
        </p>
      </div>

      {session && (session.status === "completed" || session.status === "failed") ? (
        <div className="rounded border p-3 text-sm">
          <p>Importés: {session.success_rows}</p>
          <p>Erreurs: {session.error_rows}</p>
        </div>
      ) : null}

      {errors.length > 0 ? (
        <div className="space-y-2">
          <h3 className="font-medium">Erreurs</h3>
          <ul className="space-y-1 text-sm">
            {errors.map((error) => (
              <li key={error.id}>
                Ligne {error.row_number}: {error.error_message}
              </li>
            ))}
          </ul>
          <Button variant="outline" onClick={onExportErrors}>
            Exporter les erreurs (CSV)
          </Button>
        </div>
      ) : null}
    </div>
  );
}
