import { create } from "zustand";
import { apiClient } from "@/lib/api";

interface DashboardStats {
  total_clients: number;
  active_projects: number;
  pending_invoices_amount: number;
  recent_activities: any[];
}

interface DashboardState {
  stats: DashboardStats | null;
  isLoading: boolean;
  error: string | null;

  // Actions
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
