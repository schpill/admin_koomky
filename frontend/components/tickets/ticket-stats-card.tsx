"use client";
import { cn } from "@/lib/utils";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { useI18n } from "@/components/providers/i18n-provider";

interface TicketStats {
  total_tickets: number;
  total_open: number;
  total_in_progress: number;
  total_pending: number;
  total_resolved: number;
  total_closed: number;
  total_low_priority: number;
  total_normal_priority: number;
  total_high_priority: number;
  total_urgent_priority: number;
  total_overdue: number;
  average_resolution_time_in_hours: number | null;
}

interface TicketStatsCardProps {
  stats: TicketStats | null;
  isLoading?: boolean;
}

export function TicketStatsCard({ stats, isLoading }: TicketStatsCardProps) {
  const { t } = useI18n();
  if (isLoading) {
    return (
      <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
        {Array.from({ length: 4 }).map((_, i) => (
          <Card key={i}>
            <CardContent className="p-4">
              <div className="h-8 animate-pulse rounded bg-gray-200" />
            </CardContent>
          </Card>
        ))}
      </div>
    );
  }

  const items = [
    {
      label: t("tickets.stats.open"),
      value: stats?.total_open ?? 0,
      className: "",
    },
    {
      label: t("tickets.stats.in_progress"),
      value: stats?.total_in_progress ?? 0,
      className: "",
    },
    {
      label: t("tickets.stats.urgent"),
      value: stats?.total_urgent_priority ?? 0,
      className: "",
    },
    {
      label: t("tickets.stats.overdue"),
      value: stats?.total_overdue ?? 0,
      className:
        (stats?.total_overdue ?? 0) > 0 ? "text-red-600 font-semibold" : "",
    },
  ];

  return (
    <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
      {items.map((item) => (
        <Card key={item.label}>
          <CardHeader className="pb-2 pt-4">
            <CardTitle className="text-sm font-medium text-muted-foreground">
              {item.label}
            </CardTitle>
          </CardHeader>
          <CardContent className="pb-4">
            <div className={cn("text-2xl font-bold", item.className)}>
              {item.value}
            </div>
          </CardContent>
        </Card>
      ))}
    </div>
  );
}
