"use client";

import { useEffect, useMemo, useState } from "react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { useCampaignStore } from "@/lib/stores/campaigns";

export default function CampaignComparePage() {
  const { campaigns, comparison, fetchCampaigns, compareCampaigns, isLoading } =
    useCampaignStore();
  const [selected, setSelected] = useState<string[]>([]);

  useEffect(() => {
    fetchCampaigns().catch(() => undefined);
  }, [fetchCampaigns]);

  const canCompare = selected.length >= 2;

  const selectedSet = useMemo(() => new Set(selected), [selected]);

  const toggleSelection = (id: string) => {
    setSelected((current) => {
      if (current.includes(id)) {
        return current.filter((item) => item !== id);
      }

      return [...current, id];
    });
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">Compare campaigns</h1>
        <p className="text-sm text-muted-foreground">
          Pick at least two campaigns to compare performance.
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Select campaigns</CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          {campaigns.map((campaign) => (
            <label
              key={campaign.id}
              className="flex cursor-pointer items-center gap-2 rounded-md border p-2"
            >
              <input
                type="checkbox"
                checked={selectedSet.has(campaign.id)}
                onChange={() => toggleSelection(campaign.id)}
              />
              <span className="text-sm">{campaign.name}</span>
            </label>
          ))}

          <Button
            onClick={() => compareCampaigns(selected)}
            disabled={!canCompare || isLoading}
          >
            {isLoading ? "Comparing..." : "Compare"}
          </Button>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Comparison result</CardTitle>
        </CardHeader>
        <CardContent>
          {comparison.length === 0 ? (
            <p className="text-sm text-muted-foreground">
              No comparison data yet.
            </p>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b text-left text-muted-foreground">
                    <th className="pb-2 font-medium">Campaign</th>
                    <th className="pb-2 font-medium">Recipients</th>
                    <th className="pb-2 font-medium">Open rate</th>
                    <th className="pb-2 font-medium">Click rate</th>
                  </tr>
                </thead>
                <tbody>
                  {comparison.map((item) => (
                    <tr
                      key={item.campaign_id}
                      className="border-b last:border-0"
                    >
                      <td className="py-2">
                        {item.campaign_name || item.campaign_id}
                      </td>
                      <td className="py-2">{item.total_recipients}</td>
                      <td className="py-2">{item.open_rate}%</td>
                      <td className="py-2">{item.click_rate}%</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
