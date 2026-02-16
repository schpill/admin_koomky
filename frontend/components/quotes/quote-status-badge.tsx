import { Badge } from "@/components/ui/badge";
import type { QuoteStatus } from "@/lib/stores/quotes";

interface QuoteStatusBadgeProps {
  status: QuoteStatus;
}

const STATUS_STYLE: Record<QuoteStatus, string> = {
  draft: "bg-slate-200 text-slate-700 hover:bg-slate-200",
  sent: "bg-amber-200 text-amber-800 hover:bg-amber-200",
  accepted: "bg-emerald-200 text-emerald-800 hover:bg-emerald-200",
  rejected: "bg-rose-200 text-rose-800 hover:bg-rose-200",
  expired: "bg-zinc-200 text-zinc-700 hover:bg-zinc-200",
};

export function QuoteStatusBadge({ status }: QuoteStatusBadgeProps) {
  return (
    <Badge className={STATUS_STYLE[status] || STATUS_STYLE.draft}>
      {status.replaceAll("_", " ")}
    </Badge>
  );
}
