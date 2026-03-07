"use client";

import { AlertTriangle, CheckCircle2, Info } from "lucide-react";

export interface DeliverabilityIssue {
  severity: "error" | "warning" | "info";
  message: string;
}

interface DeliverabilityBadgeProps {
  score: number;
  issues: DeliverabilityIssue[];
}

function tone(score: number): string {
  if (score >= 80) {
    return "bg-emerald-100 text-emerald-900 border-emerald-300";
  }

  if (score >= 50) {
    return "bg-amber-100 text-amber-900 border-amber-300";
  }

  return "bg-rose-100 text-rose-900 border-rose-300";
}

function iconFor(severity: DeliverabilityIssue["severity"]) {
  if (severity === "error") {
    return <AlertTriangle className="h-4 w-4" aria-hidden="true" />;
  }

  if (severity === "warning") {
    return <Info className="h-4 w-4" aria-hidden="true" />;
  }

  return <CheckCircle2 className="h-4 w-4" aria-hidden="true" />;
}

export function DeliverabilityBadge({
  score,
  issues,
}: DeliverabilityBadgeProps) {
  return (
    <div className="space-y-3 rounded-xl border border-border bg-card p-4">
      <div className="flex items-center justify-between gap-4">
        <div>
          <p className="text-sm font-medium text-muted-foreground">
            Deliverability
          </p>
          <p className="text-xs text-muted-foreground">
            Pre-send heuristic score
          </p>
        </div>
        <div
          aria-label={`Deliverability score ${score}`}
          className={`rounded-full border px-3 py-1 text-sm font-semibold ${tone(
            score
          )}`}
        >
          {score}/100
        </div>
      </div>

      {issues.length === 0 ? (
        <p className="text-sm text-muted-foreground">
          No issues detected for this draft.
        </p>
      ) : (
        <ul className="space-y-2">
          {issues.map((issue, index) => (
            <li
              key={`${issue.severity}-${index}`}
              className="flex items-start gap-2 text-sm"
            >
              <span className="mt-0.5">{iconFor(issue.severity)}</span>
              <span>{issue.message}</span>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
