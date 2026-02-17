"use client";

import { useEffect } from "react";
import Link from "next/link";
import { Megaphone, Plus } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { EmptyState } from "@/components/ui/empty-state";
import { Skeleton } from "@/components/ui/skeleton";
import { useCampaignStore } from "@/lib/stores/campaigns";

function statusVariant(
  status: string
): "default" | "secondary" | "destructive" {
  if (status === "sent") {
    return "default";
  }

  if (status === "cancelled") {
    return "destructive";
  }

  return "secondary";
}

export default function CampaignsPage() {
  const { campaigns, pagination, isLoading, fetchCampaigns, sendCampaign } =
    useCampaignStore();

  useEffect(() => {
    fetchCampaigns().catch(() => undefined);
  }, [fetchCampaigns]);

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="text-3xl font-bold">Campaigns</h1>
          <p className="text-sm text-muted-foreground">
            {pagination?.total || 0} campaigns total
          </p>
        </div>
        <div className="flex gap-2">
          <Button asChild variant="outline">
            <Link href="/campaigns/segments">Segments</Link>
          </Button>
          <Button asChild>
            <Link href="/campaigns/create">
              <Plus className="mr-2 h-4 w-4" />
              New campaign
            </Link>
          </Button>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Campaign list</CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading && campaigns.length === 0 ? (
            <div className="space-y-3">
              <Skeleton className="h-12 w-full" />
              <Skeleton className="h-12 w-full" />
              <Skeleton className="h-12 w-full" />
            </div>
          ) : campaigns.length === 0 ? (
            <EmptyState
              icon={<Megaphone className="h-12 w-12" />}
              title="No campaigns yet"
              description="Create your first email or SMS campaign."
              action={
                <Button asChild>
                  <Link href="/campaigns/create">Create campaign</Link>
                </Button>
              }
            />
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b text-left text-muted-foreground">
                    <th className="pb-2 font-medium">Name</th>
                    <th className="pb-2 font-medium">Type</th>
                    <th className="pb-2 font-medium">Status</th>
                    <th className="pb-2 font-medium">Recipients</th>
                    <th className="pb-2 font-medium">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {campaigns.map((campaign) => (
                    <tr key={campaign.id} className="border-b last:border-0">
                      <td className="py-3">
                        <Link
                          href={`/campaigns/${campaign.id}`}
                          className="font-medium text-primary hover:underline"
                        >
                          {campaign.name}
                        </Link>
                      </td>
                      <td className="py-3">
                        <Badge variant="outline" className="uppercase">
                          {campaign.type}
                        </Badge>
                      </td>
                      <td className="py-3">
                        <Badge
                          variant={statusVariant(campaign.status)}
                          className="capitalize"
                        >
                          {campaign.status}
                        </Badge>
                      </td>
                      <td className="py-3">{campaign.recipients_count || 0}</td>
                      <td className="py-3">
                        <div className="flex flex-wrap gap-2">
                          <Button variant="outline" size="sm" asChild>
                            <Link href={`/campaigns/${campaign.id}/analytics`}>
                              Analytics
                            </Link>
                          </Button>
                          <Button
                            variant="secondary"
                            size="sm"
                            onClick={() => sendCampaign(campaign.id)}
                            disabled={
                              campaign.status === "sending" ||
                              campaign.status === "sent"
                            }
                          >
                            Send
                          </Button>
                        </div>
                      </td>
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
