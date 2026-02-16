"use client";

import { useEffect } from "react";
import { Users, FolderKanban, FileText, CreditCard } from "lucide-react";
import { MetricCard } from "@/components/dashboard/metric-card";
import { RecentActivityWidget } from "@/components/dashboard/recent-activity-widget";
import { UpcomingDeadlinesWidget } from "@/components/dashboard/upcoming-deadlines-widget";
import { useDashboardStore } from "@/lib/stores/dashboard";
import { useI18n } from "@/components/providers/i18n-provider";

export default function DashboardPage() {
  const { stats, isLoading, fetchStats } = useDashboardStore();
  const { t } = useI18n();

  useEffect(() => {
    fetchStats();
  }, [fetchStats]);

  return (
    <div className="space-y-6">
      <h1 className="text-3xl font-bold">{t("dashboard.title")}</h1>

      {/* Responsive Grid: 1 col on mobile, 2 on tablet, 4 on desktop */}
      <div className="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
        <MetricCard
          title={t("dashboard.metrics.totalClients.title")}
          value={stats?.total_clients}
          isLoading={isLoading}
          icon={<Users className="h-4 w-4" />}
          description={t("dashboard.metrics.totalClients.description")}
        />
        <MetricCard
          title={t("dashboard.metrics.activeProjects.title")}
          value={stats?.active_projects}
          isLoading={isLoading}
          icon={<FolderKanban className="h-4 w-4" />}
          description={t("dashboard.metrics.activeProjects.description")}
        />
        <MetricCard
          title={t("dashboard.metrics.pendingInvoices.title")}
          value={stats?.pending_invoices_amount}
          isLoading={isLoading}
          icon={<FileText className="h-4 w-4" />}
          description={t("dashboard.metrics.pendingInvoices.description")}
        />
        <MetricCard
          title={t("dashboard.metrics.monthlyRevenue.title")}
          value="€0.00"
          isLoading={isLoading}
          icon={<CreditCard className="h-4 w-4" />}
          description={t("dashboard.metrics.monthlyRevenue.description")}
        />
      </div>

      {/* Responsive Layout: 1 col on mobile/tablet, 3 cols on desktop */}
      <div className="grid gap-6 grid-cols-1 lg:grid-cols-3">
        <div className="lg:col-span-2">
          <RecentActivityWidget
            activities={stats?.recent_activities}
            isLoading={isLoading}
          />
        </div>
        <div className="lg:col-span-1">
          <UpcomingDeadlinesWidget />
        </div>
      </div>
    </div>
  );
}
