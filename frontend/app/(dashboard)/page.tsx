"use client";

import { useEffect } from "react";
import { Users, FolderKanban, FileText, CreditCard } from "lucide-react";
import { MetricCard } from "@/components/dashboard/metric-card";
import { RecentActivityWidget } from "@/components/dashboard/recent-activity-widget";
import { UpcomingDeadlinesWidget } from "@/components/dashboard/upcoming-deadlines-widget";
import { useDashboardStore } from "@/lib/stores/dashboard";
import { Skeleton } from "@/components/ui/skeleton";

export default function DashboardPage() {
  const { stats, isLoading, fetchStats } = useDashboardStore();

  useEffect(() => {
    fetchStats();
  }, [fetchStats]);

  if (isLoading && !stats) {
    return (
      <div className="space-y-6">
        <h1 className="text-3xl font-bold">Dashboard</h1>
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
          {[1, 2, 3, 4].map((i) => (
            <Skeleton key={i} className="h-24 w-full" />
          ))}
        </div>
        <div className="grid gap-6 md:grid-cols-3">
          <Skeleton className="h-64 md:col-span-2" />
          <Skeleton className="h-64 md:col-span-1" />
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <h1 className="text-3xl font-bold">Dashboard</h1>

      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <MetricCard
          title="Total Clients"
          value={stats?.total_clients || 0}
          icon={<Users className="h-4 w-4" />}
          description="Directly managed"
        />
        <MetricCard
          title="Active Projects"
          value={stats?.active_projects || 0}
          icon={<FolderKanban className="h-4 w-4" />}
          description="In progress"
        />
        <MetricCard
          title="Pending Invoices"
          value={stats?.pending_invoices_amount || 0}
          icon={<FileText className="h-4 w-4" />}
          description="Amount to collect"
        />
        <MetricCard
          title="Monthly Revenue"
          value="€0.00"
          icon={<CreditCard className="h-4 w-4" />}
          description="Last 30 days"
        />
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        <div className="lg:col-span-2">
          <RecentActivityWidget activities={stats?.recent_activities || []} />
        </div>
        <div className="lg:col-span-1">
          <UpcomingDeadlinesWidget />
        </div>
      </div>
    </div>
  );
}
