"use client";

import { useEffect, useState } from "react";
import { useParams } from "next/navigation";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { AnalyticsSummaryCards } from "@/components/campaigns/analytics-summary-cards";
import {
  CampaignLinkAnalytics,
  type CampaignLinkStat,
} from "@/components/campaigns/campaign-link-analytics";
import { EngagementChart } from "@/components/campaigns/engagement-chart";
import { useCampaignStore } from "@/lib/stores/campaigns";
import { useAuthStore } from "@/lib/stores/auth";
import { useI18n } from "@/components/providers/i18n-provider";
import { apiClient } from "@/lib/api";

export default function CampaignAnalyticsPage() {
  const { t } = useI18n();
  const params = useParams<{ id: string }>();
  const campaignId = params.id;
  const { analytics, fetchCampaignAnalytics } = useCampaignStore();
  const accessToken = useAuthStore((state) => state.accessToken);
  const [linkStats, setLinkStats] = useState<CampaignLinkStat[]>([]);

  useEffect(() => {
    if (!campaignId) {
      return;
    }

    fetchCampaignAnalytics(campaignId).catch((error) => {
      toast.error((error as Error).message || "Unable to load analytics");
    });

    apiClient
      .get<CampaignLinkStat[]>(`/campaigns/${campaignId}/links`)
      .then((response) => {
        setLinkStats(response.data || []);
      })
      .catch((error) => {
        toast.error(
          (error as Error).message || "Unable to load link analytics"
        );
      });
  }, [campaignId, fetchCampaignAnalytics]);

  const exportCsv = async () => {
    if (!campaignId) {
      toast.error("Missing campaign id");
      return;
    }

    try {
      const baseUrl =
        process.env.NEXT_PUBLIC_API_URL || "http://localhost/api/v1";
      const response = await fetch(
        `${baseUrl}/campaigns/${campaignId}/report/csv`,
        {
          headers: {
            Accept: "text/csv",
            Authorization: accessToken ? `Bearer ${accessToken}` : "",
          },
        }
      );

      if (!response.ok) {
        throw new Error("Unable to export analytics");
      }

      const blob = await response.blob();
      const url = URL.createObjectURL(blob);
      const anchor = document.createElement("a");
      anchor.href = url;
      anchor.download = `campaign-${campaignId}-report.csv`;
      anchor.click();
      URL.revokeObjectURL(url);
    } catch (error) {
      toast.error((error as Error).message || "Unable to export analytics");
    }
  };

  const exportPdf = async () => {
    if (!campaignId) {
      toast.error("Missing campaign id");
      return;
    }

    try {
      const baseUrl =
        process.env.NEXT_PUBLIC_API_URL || "http://localhost/api/v1";
      const response = await fetch(
        `${baseUrl}/campaigns/${campaignId}/report/pdf`,
        {
          headers: {
            Accept: "application/pdf",
            Authorization: accessToken ? `Bearer ${accessToken}` : "",
          },
        }
      );

      if (!response.ok) {
        throw new Error("Unable to export report PDF");
      }

      const blob = await response.blob();
      const url = URL.createObjectURL(blob);
      const anchor = document.createElement("a");
      anchor.href = url;
      anchor.download = `campaign-${campaignId}-report.pdf`;
      anchor.click();
      URL.revokeObjectURL(url);
    } catch (error) {
      toast.error((error as Error).message || "Unable to export report PDF");
    }
  };

  const exportLinksCsv = async () => {
    if (!campaignId) {
      toast.error("Missing campaign id");
      return;
    }

    try {
      const baseUrl =
        process.env.NEXT_PUBLIC_API_URL || "http://localhost/api/v1";
      const response = await fetch(
        `${baseUrl}/campaigns/${campaignId}/links/export`,
        {
          headers: {
            Accept: "text/csv",
            Authorization: accessToken ? `Bearer ${accessToken}` : "",
          },
        }
      );

      if (!response.ok) {
        throw new Error("Unable to export link analytics");
      }

      const blob = await response.blob();
      const url = URL.createObjectURL(blob);
      const anchor = document.createElement("a");
      anchor.href = url;
      anchor.download = `campaign-${campaignId}-links.csv`;
      anchor.click();
      URL.revokeObjectURL(url);
    } catch (error) {
      toast.error(
        (error as Error).message || "Unable to export link analytics"
      );
    }
  };

  if (!analytics) {
    return (
      <p className="text-sm text-muted-foreground">
        {t("campaigns.analyticsPage.loading")}
      </p>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">
            {t("campaigns.analyticsPage.title")}
          </h1>
          <p className="text-sm text-muted-foreground">
            {analytics.campaign_name}
          </p>
        </div>

        <div className="flex gap-2">
          <Button variant="outline" onClick={exportCsv}>
            {t("campaigns.analyticsPage.exportCsv")}
          </Button>
          <Button variant="outline" onClick={exportPdf}>
            {t("campaigns.analyticsPage.exportPdf")}
          </Button>
        </div>
      </div>

      <AnalyticsSummaryCards analytics={analytics} />

      <Card>
        <CardHeader>
          <CardTitle>{t("campaigns.analyticsPage.chart")}</CardTitle>
        </CardHeader>
        <CardContent>
          <EngagementChart data={analytics.time_series || []} />
        </CardContent>
      </Card>

      <CampaignLinkAnalytics stats={linkStats} onExport={exportLinksCsv} />
    </div>
  );
}
