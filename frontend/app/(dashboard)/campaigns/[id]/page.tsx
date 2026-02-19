"use client";

import { useEffect } from "react";
import Link from "next/link";
import { useParams } from "next/navigation";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { CampaignPreview } from "@/components/campaigns/campaign-preview";
import { RecipientStatusTable } from "@/components/campaigns/recipient-status-table";
import { TestSendModal } from "@/components/campaigns/test-send-modal";
import { useCampaignStore } from "@/lib/stores/campaigns";
import { useI18n } from "@/components/providers/i18n-provider";

export default function CampaignDetailPage() {
  const { t } = useI18n();
  const params = useParams<{ id: string }>();
  const campaignId = params.id;
  const {
    currentCampaign,
    isLoading,
    fetchCampaign,
    pauseCampaign,
    duplicateCampaign,
    sendCampaign,
    testSendCampaign,
  } = useCampaignStore();

  useEffect(() => {
    if (!campaignId) {
      return;
    }

    fetchCampaign(campaignId).catch((error) => {
      toast.error((error as Error).message || "Unable to load campaign");
    });
  }, [campaignId, fetchCampaign]);

  if (!currentCampaign || currentCampaign.id !== campaignId) {
    return (
      <p className="text-sm text-muted-foreground">
        {t("campaigns.detail.loading")}
      </p>
    );
  }

  const recipients = Array.isArray(currentCampaign.recipients)
    ? currentCampaign.recipients
    : [];

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="text-3xl font-bold">{currentCampaign.name}</h1>
          <div className="mt-1 flex gap-2">
            <Badge variant="outline" className="uppercase">
              {currentCampaign.type}
            </Badge>
            <Badge className="capitalize">{currentCampaign.status}</Badge>
          </div>
        </div>

        <div className="flex flex-wrap gap-2">
          <Button variant="outline" asChild>
            <Link href={`/campaigns/${currentCampaign.id}/analytics`}>
              {t("campaigns.detail.analytics")}
            </Link>
          </Button>
          <Button
            variant="outline"
            onClick={() => duplicateCampaign(currentCampaign.id)}
          >
            {t("campaigns.detail.duplicate")}
          </Button>
          <Button
            variant="outline"
            onClick={() => pauseCampaign(currentCampaign.id)}
          >
            {t("campaigns.detail.pause")}
          </Button>
          <Button onClick={() => sendCampaign(currentCampaign.id)}>
            {t("campaigns.detail.send")}
          </Button>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>{t("campaigns.detail.messagePreview")}</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <CampaignPreview
            subject={currentCampaign.subject}
            content={currentCampaign.content}
            recipients={[
              {
                id: "preview",
                first_name: "Sample",
                last_name: "Recipient",
                email: "sample@example.com",
                company: "Demo",
              },
            ]}
          />

          <TestSendModal
            type={currentCampaign.type}
            isSubmitting={isLoading}
            onSend={(payload) => testSendCampaign(currentCampaign.id, payload)}
          />
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>{t("campaigns.detail.recipients")}</CardTitle>
        </CardHeader>
        <CardContent>
          <RecipientStatusTable recipients={recipients as any[]} />
        </CardContent>
      </Card>
    </div>
  );
}
