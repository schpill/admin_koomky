import { create } from "zustand";
import { apiClient } from "@/lib/api";

interface RevenueTrendItem {
  month: string;
  total: number;
}

interface UpcomingDeadline {
  id: string;
  reference?: string;
  name: string;
  status: string;
  deadline?: string | null;
  client_id?: string;
  client_name?: string | null;
}

interface DashboardStats {
  total_clients: number;
  active_projects: number;
  pending_invoices_amount: number;
  recent_activities: any[];

  revenue_month: number;
  revenue_quarter: number;
  revenue_year: number;
  pending_invoices_count: number;
  overdue_invoices_count: number;
  revenue_trend: RevenueTrendItem[];
  upcoming_deadlines: UpcomingDeadline[];
  active_campaigns_count: number;
  average_campaign_open_rate: number;
  average_campaign_click_rate: number;
}

interface DashboardState {
  stats: DashboardStats | null;
  isLoading: boolean;
  error: string | null;

  fetchStats: () => Promise<void>;
}

export const useDashboardStore = create<DashboardState>((set) => ({
  stats: null,
  isLoading: false,
  error: null,

  fetchStats: async () => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<DashboardStats>("/dashboard");
      set({ stats: response.data, isLoading: false });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
    }
  },
}));
