"use client";

import { useState, useEffect } from "react";
import { apiClient } from "@/lib/api";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Loader2, History, Circle } from "lucide-react";
import { useI18n } from "@/components/providers/i18n-provider";

interface Activity {
  id: string;
  description: string;
  subject_type: string;
  subject_id: string;
  created_at: string;
  metadata?: any;
}

interface ClientTimelineProps {
  clientId: string;
}

export function ClientTimeline({ clientId }: ClientTimelineProps) {
  const { t, locale } = useI18n();
  const [activities, setActivities] = useState<Activity[]>([]);
  const [loading, setLoading] = useState(true);
  const dateLocale = locale === "fr" ? "fr-FR" : "en-US";

  useEffect(() => {
    const fetchActivities = async () => {
      try {
        const response = await apiClient.get<any>(`/activities`, {
          params: {
            subject_id: clientId,
            subject_type: "Client",
            per_page: 50,
          },
        });

        setActivities(response.data.data || []);
      } catch (error) {
        console.error("Failed to load activities", error);
      } finally {
        setLoading(false);
      }
    };

    fetchActivities();
  }, [clientId]);

  if (loading) {
    return (
      <div className="flex justify-center p-8">
        <Loader2 className="h-8 w-8 animate-spin text-primary" />
      </div>
    );
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <History className="h-5 w-5" />
          {t("clients.timeline.title")}
        </CardTitle>
        <CardDescription>{t("clients.timeline.description")}</CardDescription>
      </CardHeader>
      <CardContent>
        {activities.length === 0 ? (
          <div className="py-6 text-center text-muted-foreground">
            {t("clients.timeline.empty")}
          </div>
        ) : (
          <div className="relative space-y-6 before:absolute before:inset-y-0 before:left-2 before:w-[2px] before:bg-muted ml-1">
            {activities.map((activity) => (
              <div key={activity.id} className="relative pl-8">
                <Circle className="absolute left-0 top-1 h-4 w-4 fill-background stroke-primary stroke-[3px]" />
                <div className="flex flex-col">
                  <span className="text-sm font-medium">
                    {activity.description}
                  </span>
                  <span className="text-xs text-muted-foreground">
                    {new Date(activity.created_at).toLocaleString(dateLocale)}
                  </span>
                  {activity.metadata &&
                    Object.keys(activity.metadata).length > 0 && (
                      <pre className="mt-2 rounded bg-muted p-2 text-[10px] overflow-x-auto">
                        {JSON.stringify(activity.metadata, null, 2)}
                      </pre>
                    )}
                </div>
              </div>
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  );
}
