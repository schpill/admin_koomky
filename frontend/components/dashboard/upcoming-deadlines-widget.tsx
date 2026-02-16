"use client";

import Link from "next/link";
import { CalendarClock } from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { useI18n } from "@/components/providers/i18n-provider";
import { Skeleton } from "@/components/ui/skeleton";

interface UpcomingDeadline {
  id: string;
  reference?: string;
  name: string;
  status: string;
  deadline?: string | null;
  client_name?: string | null;
}

interface UpcomingDeadlinesWidgetProps {
  deadlines?: UpcomingDeadline[];
  isLoading?: boolean;
}

export function UpcomingDeadlinesWidget({
  deadlines = [],
  isLoading,
}: UpcomingDeadlinesWidgetProps) {
  const { t } = useI18n();

  return (
    <Card className="col-span-full md:col-span-1">
      <CardHeader>
        <CardTitle className="text-lg flex items-center gap-2">
          <CalendarClock className="h-5 w-5 text-muted-foreground" />
          {t("dashboard.upcomingDeadlines.title")}
        </CardTitle>
      </CardHeader>
      <CardContent>
        {isLoading ? (
          <div className="space-y-3">
            <Skeleton className="h-10 w-full" />
            <Skeleton className="h-10 w-full" />
            <Skeleton className="h-10 w-full" />
          </div>
        ) : deadlines.length === 0 ? (
          <div className="flex flex-col items-center justify-center py-8 text-center space-y-2">
            <div className="bg-muted rounded-full p-3">
              <CalendarClock className="h-6 w-6 text-muted-foreground opacity-50" />
            </div>
            <p className="text-sm font-medium">
              {t("dashboard.upcomingDeadlines.emptyTitle")}
            </p>
            <p className="text-xs text-muted-foreground max-w-[200px]">
              {t("dashboard.upcomingDeadlines.emptyDescription")}
            </p>
          </div>
        ) : (
          <div className="space-y-3">
            {deadlines.map((deadline) => (
              <Link
                key={deadline.id}
                href={`/projects/${deadline.id}`}
                className="block rounded-md border p-3 text-sm transition-colors hover:bg-muted/40"
              >
                <p className="font-medium">{deadline.name}</p>
                <p className="text-xs text-muted-foreground">
                  {deadline.deadline || "-"} •{" "}
                  {deadline.client_name || "No client"}
                </p>
              </Link>
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  );
}
