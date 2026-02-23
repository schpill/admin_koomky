"use client";
import { cn } from "@/lib/utils";

type TicketStatus = "open" | "in_progress" | "pending" | "resolved" | "closed";

const statusConfig: Record<TicketStatus, { label: string; className: string }> =
  {
    open: { label: "Open", className: "bg-gray-100 text-gray-700" },
    in_progress: {
      label: "In Progress",
      className: "bg-blue-100 text-blue-700",
    },
    pending: { label: "Pending", className: "bg-orange-100 text-orange-700" },
    resolved: { label: "Resolved", className: "bg-green-100 text-green-700" },
    closed: { label: "Closed", className: "bg-slate-100 text-slate-700" },
  };

interface TicketStatusBadgeProps {
  status: TicketStatus;
  className?: string;
}

export function TicketStatusBadge({
  status,
  className,
}: TicketStatusBadgeProps) {
  const config = statusConfig[status];
  return (
    <span
      className={cn(
        "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium",
        config.className,
        className
      )}
    >
      {config.label}
    </span>
  );
}
