"use client";

import { useEffect } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { useLeadStore } from "@/lib/stores/leads";
import { CurrencyAmount } from "@/components/shared/currency-amount";

export default function LeadAnalyticsPage() {
  const { analytics, fetchAnalytics, isLoading } = useLeadStore();

  useEffect(() => {
    fetchAnalytics();
  }, [fetchAnalytics]);

  const statusLabels: Record<string, string> = {
    new: "New",
    contacted: "Contacted",
    qualified: "Qualified",
    proposal_sent: "Proposal Sent",
    negotiating: "Negotiating",
    won: "Won",
    lost: "Lost",
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">Pipeline Analytics</h1>
        <p className="text-sm text-muted-foreground">
          Track your sales performance
        </p>
      </div>

      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">
              Total Pipeline Value
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              <CurrencyAmount
                amount={analytics?.total_pipeline_value || 0}
                currency="EUR"
              />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">
              Win Rate
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {analytics?.win_rate?.toFixed(1) || 0}%
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">
              Average Deal Value
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              <CurrencyAmount
                amount={analytics?.average_deal_value || 0}
                currency="EUR"
              />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">
              Avg Time to Close
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {analytics?.average_time_to_close || 0} days
            </div>
          </CardContent>
        </Card>
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Leads by Status</CardTitle>
          </CardHeader>
          <CardContent>
            {analytics?.leads_by_status ? (
              <div className="space-y-3">
                {Object.entries(analytics.leads_by_status).map(
                  ([status, count]) => (
                    <div
                      key={status}
                      className="flex items-center justify-between"
                    >
                      <span className="capitalize">
                        {statusLabels[status] || status}
                      </span>
                      <span className="font-medium">{count}</span>
                    </div>
                  )
                )}
              </div>
            ) : (
              <p className="text-muted-foreground">No data available</p>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Pipeline by Source</CardTitle>
          </CardHeader>
          <CardContent>
            {analytics?.pipeline_by_source &&
            analytics.pipeline_by_source.length > 0 ? (
              <div className="space-y-3">
                {analytics.pipeline_by_source.map((item) => (
                  <div
                    key={item.source}
                    className="flex items-center justify-between"
                  >
                    <div>
                      <span className="capitalize">{item.source}</span>
                      <span className="ml-2 text-sm text-muted-foreground">
                        ({item.count} leads)
                      </span>
                    </div>
                    <CurrencyAmount amount={item.total_value} currency="EUR" />
                  </div>
                ))}
              </div>
            ) : (
              <p className="text-muted-foreground">No data available</p>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
