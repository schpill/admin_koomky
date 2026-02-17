"use client";

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

interface CampaignSummaryWidgetProps {
  activeCampaigns: number;
  averageOpenRate: number;
  averageClickRate: number;
}

export function CampaignSummaryWidget({
  activeCampaigns,
  averageOpenRate,
  averageClickRate,
}: CampaignSummaryWidgetProps) {
  return (
    <Card>
      <CardHeader>
        <CardTitle>Campaign summary</CardTitle>
      </CardHeader>
      <CardContent className="grid gap-4 sm:grid-cols-3">
        <div>
          <p className="text-xs uppercase tracking-wide text-muted-foreground">
            Active campaigns
          </p>
          <p className="text-2xl font-semibold">{activeCampaigns}</p>
        </div>
        <div>
          <p className="text-xs uppercase tracking-wide text-muted-foreground">
            Avg open rate
          </p>
          <p className="text-2xl font-semibold">{averageOpenRate}%</p>
        </div>
        <div>
          <p className="text-xs uppercase tracking-wide text-muted-foreground">
            Avg click rate
          </p>
          <p className="text-2xl font-semibold">{averageClickRate}%</p>
        </div>
      </CardContent>
    </Card>
  );
}
