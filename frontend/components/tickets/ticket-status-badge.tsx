"use client";
import { cn } from "@/lib/utils";
import { useI18n } from "@/components/providers/i18n-provider";

type TicketStatus = "open" | "in_progress" | "pending" | "resolved" | "closed";

const statusClassName: Record<TicketStatus, string> = {
  open: "bg-gray-100 text-gray-700",
  in_progress: "bg-blue-100 text-blue-700",
  pending: "bg-orange-100 text-orange-700",
  resolved: "bg-green-100 text-green-700",
  closed: "bg-slate-100 text-slate-700",
};

interface TicketStatusBadgeProps {
  status: TicketStatus;
  className?: string;
}

export function TicketStatusBadge({
  status,
  className,
}: TicketStatusBadgeProps) {
  const { t } = useI18n();
  return (
    <span
      className={cn(
        "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium",
        statusClassName[status],
        className
      )}
    >
      {t(`tickets.status.${status}`)}
    </span>
  );
}
