"use client";

import { useEffect } from "react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { AnalyticsSummaryCards } from "@/components/campaigns/analytics-summary-cards";
import { EngagementChart } from "@/components/campaigns/engagement-chart";
import { useCampaignStore } from "@/lib/stores/campaigns";
import { useAuthStore } from "@/lib/stores/auth";

interface CampaignAnalyticsPageProps {
  params: {
    id: string;
  };
}

export default function CampaignAnalyticsPage({ params }: CampaignAnalyticsPageProps) {
  const { analytics, fetchCampaignAnalytics } = useCampaignStore();
  const accessToken = useAuthStore((state) => state.accessToken);

  useEffect(() => {
    fetchCampaignAnalytics(params.id).catch((error) => {
      toast.error((error as Error).message || "Unable to load analytics");
    });
  }, [fetchCampaignAnalytics, params.id]);

  const exportCsv = async () => {
    try {
      const baseUrl = process.env.NEXT_PUBLIC_API_URL || "http://localhost/api/v1";
      const response = await fetch(`${baseUrl}/campaigns/${params.id}/analytics/export`, {
        headers: {
          Accept: "text/csv",
          Authorization: accessToken ? `Bearer ${accessToken}` : "",
        },
      });

      if (!response.ok) {
        throw new Error("Unable to export analytics");
      }

      const blob = await response.blob();
      const url = URL.createObjectURL(blob);
      const anchor = document.createElement("a");
      anchor.href = url;
      anchor.download = `campaign-${params.id}-analytics.csv`;
      anchor.click();
      URL.revokeObjectURL(url);
    } catch (error) {
      toast.error((error as Error).message || "Unable to export analytics");
    }
  };

  if (!analytics) {
    return <p className="text-sm text-muted-foreground">Loading analytics...</p>;
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Campaign analytics</h1>
          <p className="text-sm text-muted-foreground">{analytics.campaign_name}</p>
        </div>

        <Button variant="outline" onClick={exportCsv}>
          Export CSV
        </Button>
      </div>

      <AnalyticsSummaryCards analytics={analytics} />

      <Card>
        <CardHeader>
          <CardTitle>Engagement chart</CardTitle>
        </CardHeader>
        <CardContent>
          <EngagementChart data={analytics.time_series || []} />
        </CardContent>
      </Card>
    </div>
  );
}
