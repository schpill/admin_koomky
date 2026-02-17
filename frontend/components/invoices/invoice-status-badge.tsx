import { Badge } from "@/components/ui/badge";
import type { InvoiceStatus } from "@/lib/stores/invoices";

interface InvoiceStatusBadgeProps {
  status: InvoiceStatus;
}

const STATUS_STYLE: Record<InvoiceStatus, string> = {
  draft:
    "bg-slate-200 text-slate-700 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-100 dark:hover:bg-slate-700",
  sent: "bg-amber-200 text-amber-800 hover:bg-amber-200 dark:bg-amber-900/40 dark:text-amber-200 dark:hover:bg-amber-900/50",
  viewed:
    "bg-amber-200 text-amber-800 hover:bg-amber-200 dark:bg-amber-900/40 dark:text-amber-200 dark:hover:bg-amber-900/50",
  paid: "bg-emerald-200 text-emerald-800 hover:bg-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-200 dark:hover:bg-emerald-900/50",
  partially_paid:
    "bg-sky-200 text-sky-800 hover:bg-sky-200 dark:bg-sky-900/40 dark:text-sky-200 dark:hover:bg-sky-900/50",
  overdue:
    "bg-red-200 text-red-800 hover:bg-red-200 dark:bg-red-900/40 dark:text-red-200 dark:hover:bg-red-900/50",
  cancelled:
    "bg-zinc-200 text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-700 dark:text-zinc-100 dark:hover:bg-zinc-700",
};

export function InvoiceStatusBadge({ status }: InvoiceStatusBadgeProps) {
  return (
    <Badge className={STATUS_STYLE[status] || STATUS_STYLE.draft}>
      {status.replaceAll("_", " ")}
    </Badge>
  );
}
