"use client";
import { AlertTriangle } from "lucide-react";
import { cn } from "@/lib/utils";
import { useI18n } from "@/components/providers/i18n-provider";

type TicketPriority = "low" | "normal" | "high" | "urgent";

const priorityConfig: Record<
  TicketPriority,
  { className: string; icon?: boolean }
> = {
  low: { className: "bg-gray-50 text-gray-500" },
  normal: { className: "bg-blue-50 text-blue-600" },
  high: { className: "bg-orange-100 text-orange-600" },
  urgent: { className: "bg-red-100 text-red-700", icon: true },
};

interface TicketPriorityBadgeProps {
  priority: TicketPriority;
  className?: string;
}

export function TicketPriorityBadge({
  priority,
  className,
}: TicketPriorityBadgeProps) {
  const { t } = useI18n();
  const config = priorityConfig[priority];
  return (
    <span
      className={cn(
        "inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium",
        config.className,
        className
      )}
    >
      {config.icon && <AlertTriangle className="h-3 w-3" aria-hidden="true" />}
      {t(`tickets.priority.${priority}`)}
    </span>
  );
}
