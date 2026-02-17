"use client";

import { cn } from "@/lib/utils";

export type ImportStage =
  | "idle"
  | "uploading"
  | "parsing"
  | "validating"
  | "creating"
  | "completed"
  | "error";

const stageMeta: Record<
  Exclude<ImportStage, "idle">,
  { label: string; progress: number }
> = {
  uploading: { label: "Uploading file", progress: 20 },
  parsing: { label: "Parsing CSV", progress: 40 },
  validating: { label: "Validating rows", progress: 60 },
  creating: { label: "Creating records", progress: 80 },
  completed: { label: "Import completed", progress: 100 },
  error: { label: "Import failed", progress: 100 },
};

interface ImportProgressIndicatorProps {
  stage: ImportStage;
}

export function ImportProgressIndicator({ stage }: ImportProgressIndicatorProps) {
  if (stage === "idle") {
    return null;
  }

  const meta = stageMeta[stage];

  return (
    <div
      className="space-y-2 rounded-lg border border-border bg-card p-4"
      role="status"
      aria-live="polite"
    >
      <div className="flex items-center justify-between text-sm">
        <span className={cn(stage === "error" && "text-destructive")}>
          {meta.label}
        </span>
        <span className="font-medium">{meta.progress}%</span>
      </div>
      <div className="h-2 w-full overflow-hidden rounded-full bg-muted">
        <div
          className={cn(
            "h-full rounded-full bg-primary transition-all duration-300",
            stage === "error" && "bg-destructive"
          )}
          style={{ width: `${meta.progress}%` }}
        />
      </div>
    </div>
  );
}
