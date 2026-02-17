"use client";

export interface ImportRowError {
  row: number;
  field: string;
  message: string;
}

interface ImportErrorReportProps {
  errors: ImportRowError[];
}

export function ImportErrorReport({ errors }: ImportErrorReportProps) {
  if (errors.length === 0) {
    return null;
  }

  return (
    <div className="space-y-3 rounded-lg border border-destructive/30 bg-destructive/5 p-4">
      <h3 className="text-sm font-semibold text-destructive">Import errors</h3>
      <div className="overflow-x-auto">
        <table className="w-full min-w-[520px] text-left text-sm">
          <thead>
            <tr className="border-b border-border/70">
              <th className="pb-2 font-medium text-muted-foreground">Row</th>
              <th className="pb-2 font-medium text-muted-foreground">Field</th>
              <th className="pb-2 font-medium text-muted-foreground">Message</th>
            </tr>
          </thead>
          <tbody>
            {errors.map((error, index) => (
              <tr key={`${error.row}-${error.field}-${index}`} className="border-b border-border/40 last:border-0">
                <td className="py-2">Row {error.row}</td>
                <td className="py-2 font-mono text-xs">{error.field}</td>
                <td className="py-2">{error.message}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
