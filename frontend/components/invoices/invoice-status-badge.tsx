import { Badge } from "@/components/ui/badge";
import type { InvoiceStatus } from "@/lib/stores/invoices";

interface InvoiceStatusBadgeProps {
  status: InvoiceStatus;
}

const STATUS_STYLE: Record<InvoiceStatus, string> = {
  draft: "bg-slate-200 text-slate-700 hover:bg-slate-200",
  sent: "bg-amber-200 text-amber-800 hover:bg-amber-200",
  viewed: "bg-amber-200 text-amber-800 hover:bg-amber-200",
  paid: "bg-emerald-200 text-emerald-800 hover:bg-emerald-200",
  partially_paid: "bg-sky-200 text-sky-800 hover:bg-sky-200",
  overdue: "bg-red-200 text-red-800 hover:bg-red-200",
  cancelled: "bg-zinc-200 text-zinc-700 hover:bg-zinc-200",
};

export function InvoiceStatusBadge({ status }: InvoiceStatusBadgeProps) {
  return (
    <Badge className={STATUS_STYLE[status] || STATUS_STYLE.draft}>
      {status.replaceAll("_", " ")}
    </Badge>
  );
}
