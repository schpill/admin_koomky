"use client";

import { useEffect } from "react";
import Link from "next/link";
import { TrendingUp, Target, DollarSign, GitBranch } from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { Button } from "@/components/ui/button";
import { CurrencyAmount } from "@/components/shared/currency-amount";
import { useLeadStore } from "@/lib/stores/leads";

const STATUS_CONFIG: Record<
  string,
  { label: string; color: string; bgColor: string }
> = {
  new: {
    label: "New",
    color: "text-blue-600",
    bgColor: "bg-blue-500",
  },
  contacted: {
    label: "Contacted",
    color: "text-purple-600",
    bgColor: "bg-purple-500",
  },
  qualified: {
    label: "Qualified",
    color: "text-cyan-600",
    bgColor: "bg-cyan-500",
  },
  proposal_sent: {
    label: "Proposal",
    color: "text-amber-600",
    bgColor: "bg-amber-500",
  },
  negotiating: {
    label: "Negotiating",
    color: "text-orange-600",
    bgColor: "bg-orange-500",
  },
  won: {
    label: "Won",
    color: "text-green-600",
    bgColor: "bg-green-500",
  },
  lost: {
    label: "Lost",
    color: "text-red-600",
    bgColor: "bg-red-500",
  },
};

const ACTIVE_STATUSES = [
  "new",
  "contacted",
  "qualified",
  "proposal_sent",
  "negotiating",
];

export function PipelineSummaryWidget() {
  const { analytics, isLoading, fetchAnalytics } = useLeadStore();

  useEffect(() => {
    fetchAnalytics();
  }, [fetchAnalytics]);

  const leadsByStatus = analytics?.leads_by_status || {};
  const totalActiveLeads = ACTIVE_STATUSES.reduce(
    (sum, status) => sum + (leadsByStatus[status] || 0),
    0
  );

  const maxCount = Math.max(
    ...ACTIVE_STATUSES.map((status) => leadsByStatus[status] || 0),
    1
  );

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
        <CardTitle className="text-base flex items-center gap-2">
          <GitBranch className="h-4 w-4 text-muted-foreground" />
          Lead Pipeline
        </CardTitle>
        <Button asChild variant="ghost" size="sm">
          <Link href="/leads">View all</Link>
        </Button>
      </CardHeader>
      <CardContent className="space-y-4">
        {/* Key metrics row */}
        <div className="grid grid-cols-3 gap-3">
          <div className="rounded-md border p-2">
            <div className="flex items-center gap-1 text-xs text-muted-foreground">
              <DollarSign className="h-3 w-3" />
              Pipeline value
            </div>
            {isLoading ? (
              <Skeleton className="h-6 w-20 mt-1" />
            ) : (
              <p className="text-lg font-semibold">
                <CurrencyAmount
                  amount={Number(analytics?.total_pipeline_value || 0)}
                  currency="EUR"
                />
              </p>
            )}
          </div>
          <div className="rounded-md border p-2">
            <div className="flex items-center gap-1 text-xs text-muted-foreground">
              <Target className="h-3 w-3" />
              Win rate
            </div>
            {isLoading ? (
              <Skeleton className="h-6 w-12 mt-1" />
            ) : (
              <p className="text-lg font-semibold">
                {Number(analytics?.win_rate || 0).toFixed(1)}%
              </p>
            )}
          </div>
          <div className="rounded-md border p-2">
            <div className="flex items-center gap-1 text-xs text-muted-foreground">
              <TrendingUp className="h-3 w-3" />
              Avg deal
            </div>
            {isLoading ? (
              <Skeleton className="h-6 w-16 mt-1" />
            ) : (
              <p className="text-lg font-semibold">
                <CurrencyAmount
                  amount={Number(analytics?.average_deal_value || 0)}
                  currency="EUR"
                />
              </p>
            )}
          </div>
        </div>

        {/* Mini funnel visualization */}
        <div className="space-y-2">
          <p className="text-xs font-medium text-muted-foreground">
            Active leads ({totalActiveLeads})
          </p>
          {isLoading ? (
            <div className="space-y-2">
              {[1, 2, 3, 4, 5].map((i) => (
                <div key={i} className="flex items-center gap-2">
                  <Skeleton className="h-4 w-16" />
                  <Skeleton className="h-4 flex-1" />
                </div>
              ))}
            </div>
          ) : totalActiveLeads === 0 ? (
            <p className="text-sm text-muted-foreground py-4 text-center">
              No active leads in pipeline.
            </p>
          ) : (
            <div className="space-y-1.5">
              {ACTIVE_STATUSES.map((status) => {
                const config = STATUS_CONFIG[status];
                const count = leadsByStatus[status] || 0;
                const percentage = (count / maxCount) * 100;

                return (
                  <div key={status} className="flex items-center gap-2">
                    <span className={`text-xs w-20 ${config.color}`}>
                      {config.label}
                    </span>
                    <div className="flex-1 h-4 bg-muted rounded-sm overflow-hidden">
                      <div
                        className={`h-full ${config.bgColor} transition-all duration-300`}
                        style={{ width: `${percentage}%` }}
                      />
                    </div>
                    <span className="text-xs font-medium w-6 text-right">
                      {count}
                    </span>
                  </div>
                );
              })}
            </div>
          )}
        </div>

        {/* Terminal status summary */}
        {!isLoading && (leadsByStatus.won > 0 || leadsByStatus.lost > 0) && (
          <div className="flex gap-4 pt-2 border-t text-xs text-muted-foreground">
            <span className="text-green-600">{leadsByStatus.won || 0} won</span>
            <span className="text-red-600">{leadsByStatus.lost || 0} lost</span>
          </div>
        )}
      </CardContent>
    </Card>
  );
}
