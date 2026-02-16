import { DashboardLayout as AppDashboardLayout } from "@/components/layout/dashboard-layout";

export default function DashboardRouteLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return <AppDashboardLayout>{children}</AppDashboardLayout>;
}
