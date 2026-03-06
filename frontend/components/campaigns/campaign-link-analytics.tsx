"use client";

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

export interface CampaignLinkStat {
  url: string;
  total_clicks: number;
  unique_clicks: number;
  click_rate: number;
}

interface CampaignLinkAnalyticsProps {
  stats: CampaignLinkStat[];
  onExport: () => void;
}

export function CampaignLinkAnalytics({
  stats,
  onExport,
}: CampaignLinkAnalyticsProps) {
  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between space-y-0">
        <CardTitle>Link analytics</CardTitle>
        <Button variant="outline" onClick={onExport}>
          Export
        </Button>
      </CardHeader>
      <CardContent>
        {stats.length === 0 ? (
          <p className="text-sm text-muted-foreground">
            No tracked links for this campaign yet.
          </p>
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full text-sm">
              <thead>
                <tr className="border-b text-left text-muted-foreground">
                  <th className="py-2 pr-4">URL</th>
                  <th className="py-2 pr-4">Total clicks</th>
                  <th className="py-2 pr-4">Unique clicks</th>
                  <th className="py-2">Click rate</th>
                </tr>
              </thead>
              <tbody>
                {stats.map((stat) => (
                  <tr key={stat.url} className="border-b last:border-b-0">
                    <td className="py-3 pr-4 font-medium">{stat.url}</td>
                    <td className="py-3 pr-4">{stat.total_clicks}</td>
                    <td className="py-3 pr-4">{stat.unique_clicks}</td>
                    <td className="py-3">{stat.click_rate}%</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </CardContent>
    </Card>
  );
}
