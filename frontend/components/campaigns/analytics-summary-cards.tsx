"use client";

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import type { CampaignAnalytics } from "@/lib/stores/campaigns";

interface AnalyticsSummaryCardsProps {
  analytics: CampaignAnalytics;
}

export function AnalyticsSummaryCards({
  analytics,
}: AnalyticsSummaryCardsProps) {
  const items = [
    { label: "Recipients", value: analytics.total_recipients },
    { label: "Open rate", value: `${analytics.open_rate}%` },
    { label: "Click rate", value: `${analytics.click_rate}%` },
    { label: "Sent", value: analytics.sent_count || 0 },
    { label: "Delivered", value: analytics.delivered_count || 0 },
    { label: "Bounced", value: analytics.bounced_count || 0 },
  ];

  return (
    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      {items.map((item) => (
        <Card key={item.label}>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium">{item.label}</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-2xl font-semibold">{item.value}</p>
          </CardContent>
        </Card>
      ))}
    </div>
  );
}
