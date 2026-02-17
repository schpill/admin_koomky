"use client";

import { useEffect } from "react";
import dynamic from "next/dynamic";
import { Users, FolderKanban, FileText, CreditCard } from "lucide-react";
import { MetricCard } from "@/components/dashboard/metric-card";
import { RecentActivityWidget } from "@/components/dashboard/recent-activity-widget";
import { UpcomingDeadlinesWidget } from "@/components/dashboard/upcoming-deadlines-widget";
import { CampaignSummaryWidget } from "@/components/dashboard/campaign-summary-widget";
import { RecurringInvoicesWidget } from "@/components/dashboard/recurring-invoices-widget";
import { CalendarWidget } from "@/components/dashboard/calendar-widget";
import { CurrencyAmount } from "@/components/shared/currency-amount";
import { useDashboardStore } from "@/lib/stores/dashboard";
import { useCalendarStore } from "@/lib/stores/calendar";
import { useI18n } from "@/components/providers/i18n-provider";
import { useNotificationStore } from "@/lib/stores/notifications";

const RevenueChart = dynamic(
  () =>
    import("@/components/reports/revenue-chart").then(
      (mod) => mod.RevenueChart
    ),
  {
    loading: () => (
      <div className="h-64 animate-pulse rounded-lg border border-border bg-muted/40" />
    ),
  }
);

export default function DashboardPage() {
  const { stats, isLoading, fetchStats } = useDashboardStore();
  const { events: calendarEvents, fetchEvents: fetchCalendarEvents } =
    useCalendarStore();
  const baseCurrency = stats?.base_currency || "EUR";
  const { t } = useI18n();
  const setNotifications = useNotificationStore(
    (state) => state.setNotifications
  );

  useEffect(() => {
    fetchStats();
  }, [fetchStats]);

  useEffect(() => {
    const from = new Date().toISOString().slice(0, 10);
    const to = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000)
      .toISOString()
      .slice(0, 10);
    fetchCalendarEvents({ date_from: from, date_to: to });
  }, [fetchCalendarEvents]);

  useEffect(() => {
    if (!stats?.recent_activities) {
      return;
    }

    const notifications = stats.recent_activities
      .filter((activity: any) =>
        String(activity?.description || "")
          .toLowerCase()
          .includes("campaign")
      )
      .map((activity: any) => ({
        id: String(activity.id),
        title: "Campaign event",
        body: String(activity.description || "Campaign updated"),
        created_at: String(activity.created_at || new Date().toISOString()),
        read_at: null,
      }));

    setNotifications(notifications);
  }, [setNotifications, stats?.recent_activities]);

  return (
    <div className="space-y-6">
      <h1 className="text-3xl font-bold">{t("dashboard.title")}</h1>

      {/* Responsive Grid: 1 col on mobile, 2 on tablet, 3 on desktop */}
      <div className="grid gap-4 grid-cols-1 md:grid-cols-2 xl:grid-cols-3">
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
          value={
            <CurrencyAmount
              amount={Number(stats?.revenue_month || 0)}
              currency={baseCurrency}
            />
          }
          isLoading={isLoading}
          icon={<CreditCard className="h-4 w-4" />}
          description={t("dashboard.metrics.monthlyRevenue.description")}
        />
      </div>

      <div className="grid gap-4 grid-cols-1 md:grid-cols-2 xl:grid-cols-3">
        <MetricCard
          title="Quarter revenue"
          value={
            <CurrencyAmount
              amount={Number(stats?.revenue_quarter || 0)}
              currency={baseCurrency}
            />
          }
          isLoading={isLoading}
          icon={<CreditCard className="h-4 w-4" />}
          description="Current quarter"
        />
        <MetricCard
          title="Year revenue"
          value={
            <CurrencyAmount
              amount={Number(stats?.revenue_year || 0)}
              currency={baseCurrency}
            />
          }
          isLoading={isLoading}
          icon={<CreditCard className="h-4 w-4" />}
          description="Current year"
        />
        <MetricCard
          title="Pending invoices"
          value={stats?.pending_invoices_count}
          isLoading={isLoading}
          icon={<FileText className="h-4 w-4" />}
          description="Awaiting payment"
        />
        <MetricCard
          title="Overdue invoices"
          value={stats?.overdue_invoices_count}
          isLoading={isLoading}
          icon={<FileText className="h-4 w-4" />}
          description="Need follow-up"
        />
      </div>

      <RevenueChart
        title="Revenue trend (12 months)"
        data={stats?.revenue_trend || []}
      />

      <CampaignSummaryWidget
        activeCampaigns={stats?.active_campaigns_count || 0}
        averageOpenRate={Number(stats?.average_campaign_open_rate || 0)}
        averageClickRate={Number(stats?.average_campaign_click_rate || 0)}
      />

      <RecurringInvoicesWidget
        activeCount={stats?.recurring_profiles_active_count || 0}
        estimatedMonthlyRevenue={
          Number(stats?.recurring_estimated_revenue_month || 0)
        }
        currency={baseCurrency}
        upcomingProfiles={stats?.recurring_upcoming_due_profiles || []}
      />

      <CalendarWidget
        events={(calendarEvents || []).map((event) => ({
          id: event.id,
          title: event.title,
          start_at: event.start_at,
          type: event.type,
        }))}
      />

      {/* Responsive Layout: 1 col on mobile/tablet, 3 cols on desktop */}
      <div className="grid gap-6 grid-cols-1 lg:grid-cols-3">
        <div className="lg:col-span-2">
          <RecentActivityWidget
            activities={stats?.recent_activities}
            isLoading={isLoading}
          />
        </div>
        <div className="lg:col-span-1">
          <UpcomingDeadlinesWidget
            deadlines={stats?.upcoming_deadlines || []}
            isLoading={isLoading}
          />
        </div>
      </div>
    </div>
  );
}
