import { Badge } from "@/components/ui/badge";
import type { QuoteStatus } from "@/lib/stores/quotes";

interface QuoteStatusBadgeProps {
  status: QuoteStatus;
}

const STATUS_STYLE: Record<QuoteStatus, string> = {
  draft:
    "bg-slate-200 text-slate-700 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-100 dark:hover:bg-slate-700",
  sent: "bg-amber-200 text-amber-800 hover:bg-amber-200 dark:bg-amber-900/40 dark:text-amber-200 dark:hover:bg-amber-900/50",
  accepted:
    "bg-emerald-200 text-emerald-800 hover:bg-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-200 dark:hover:bg-emerald-900/50",
  rejected:
    "bg-rose-200 text-rose-800 hover:bg-rose-200 dark:bg-rose-900/40 dark:text-rose-200 dark:hover:bg-rose-900/50",
  expired:
    "bg-zinc-200 text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-700 dark:text-zinc-100 dark:hover:bg-zinc-700",
};

export function QuoteStatusBadge({ status }: QuoteStatusBadgeProps) {
  return (
    <Badge className={STATUS_STYLE[status] || STATUS_STYLE.draft}>
      {status.replaceAll("_", " ")}
    </Badge>
  );
}
