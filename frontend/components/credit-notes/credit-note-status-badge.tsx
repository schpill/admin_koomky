import { Badge } from "@/components/ui/badge";
import type { CreditNoteStatus } from "@/lib/stores/creditNotes";

interface CreditNoteStatusBadgeProps {
  status: CreditNoteStatus;
}

const STATUS_STYLE: Record<CreditNoteStatus, string> = {
  draft:
    "bg-slate-200 text-slate-700 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-100 dark:hover:bg-slate-700",
  sent: "bg-amber-200 text-amber-800 hover:bg-amber-200 dark:bg-amber-900/40 dark:text-amber-200 dark:hover:bg-amber-900/50",
  applied:
    "bg-emerald-200 text-emerald-800 hover:bg-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-200 dark:hover:bg-emerald-900/50",
};

export function CreditNoteStatusBadge({ status }: CreditNoteStatusBadgeProps) {
  return (
    <Badge className={STATUS_STYLE[status] || STATUS_STYLE.draft}>
      {status.replaceAll("_", " ")}
    </Badge>
  );
}
