"use client";

import { History } from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { useI18n } from "@/components/providers/i18n-provider";

interface Activity {
  id: string;
  description: string;
  created_at: string;
}

interface RecentActivityWidgetProps {
  activities?: Activity[];
  isLoading?: boolean;
}

export function RecentActivityWidget({
  activities = [],
  isLoading,
}: RecentActivityWidgetProps) {
  const { t, locale } = useI18n();
  const dateLocale = locale === "fr" ? "fr-FR" : "en-US";

  return (
    <Card className="col-span-full">
      <CardHeader>
        <CardTitle className="text-lg flex items-center gap-2">
          <History className="h-5 w-5 text-muted-foreground" />
          {t("dashboard.recentActivity.title")}
        </CardTitle>
      </CardHeader>
      <CardContent>
        {isLoading ? (
          <div className="space-y-4">
            {[1, 2, 3].map((i) => (
              <div
                key={i}
                className="flex flex-col gap-2 border-b pb-3 last:border-0 last:pb-0"
              >
                <Skeleton className="h-4 w-3/4" />
                <Skeleton className="h-3 w-1/4" />
              </div>
            ))}
          </div>
        ) : activities.length > 0 ? (
          <div className="space-y-4">
            {activities.map((activity) => (
              <div
                key={activity.id}
                className="flex items-center gap-4 text-sm border-b pb-3 last:border-0 last:pb-0"
              >
                <div className="flex-1">
                  <p className="font-medium">{activity.description}</p>
                  <p className="text-xs text-muted-foreground">
                    {new Date(activity.created_at).toLocaleString(dateLocale)}
                  </p>
                </div>
              </div>
            ))}
          </div>
        ) : (
          <p className="text-sm text-muted-foreground py-4 text-center">
            {t("dashboard.recentActivity.empty")}
          </p>
        )}
      </CardContent>
    </Card>
  );
}
