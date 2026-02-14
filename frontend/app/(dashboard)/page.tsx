import { DashboardLayout } from "@/components/layout/dashboard-layout";

export default function DashboardPage() {
  return (
    <DashboardLayout>
      <div className="space-y-6">
        <h1 className="text-3xl font-bold">Dashboard</h1>

        {/* Metrics Grid - Placeholder */}
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          <div className="rounded-lg border bg-card p-6">
            <h3 className="text-sm font-medium text-muted-foreground">
              Total Clients
            </h3>
            <p className="text-2xl font-bold">0</p>
          </div>
          <div className="rounded-lg border bg-card p-6">
            <h3 className="text-sm font-medium text-muted-foreground">
              Active Projects
            </h3>
            <p className="text-2xl font-bold">0</p>
          </div>
          <div className="rounded-lg border bg-card p-6">
            <h3 className="text-sm font-medium text-muted-foreground">
              Pending Invoices
            </h3>
            <p className="text-2xl font-bold">0</p>
          </div>
        </div>

        {/* Recent Activity - Placeholder */}
        <div className="rounded-lg border bg-card p-6">
          <h3 className="text-lg font-semibold">Recent Activity</h3>
          <p className="text-sm text-muted-foreground mt-2">
            No recent activity to display.
          </p>
        </div>
      </div>
    </DashboardLayout>
  );
}
