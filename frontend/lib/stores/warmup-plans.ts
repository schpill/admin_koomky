import { create } from "zustand";
import { apiClient } from "@/lib/api";

export interface WarmupPlan {
  id: string;
  name: string;
  status: "active" | "paused" | "completed";
  daily_volume_start: number;
  daily_volume_max: number;
  increment_percent: number;
  current_day: number;
  current_daily_limit: number;
  started_at?: string | null;
}

export interface WarmupPlanPayload {
  name: string;
  daily_volume_start: number;
  daily_volume_max: number;
  increment_percent: number;
}

interface WarmupPlansState {
  plans: WarmupPlan[];
  currentPlan: WarmupPlan | null;
  isLoading: boolean;
  error: string | null;
  fetchPlans: () => Promise<void>;
  createPlan: (payload: WarmupPlanPayload) => Promise<WarmupPlan>;
  updatePlan: (
    id: string,
    payload: Partial<WarmupPlanPayload>
  ) => Promise<WarmupPlan>;
  deletePlan: (id: string) => Promise<void>;
  pausePlan: (id: string) => Promise<WarmupPlan>;
  resumePlan: (id: string) => Promise<WarmupPlan>;
}

function upsertPlan(list: WarmupPlan[], plan: WarmupPlan): WarmupPlan[] {
  const index = list.findIndex((item) => item.id === plan.id);
  if (index === -1) {
    return [plan, ...list];
  }

  const next = [...list];
  next[index] = plan;
  return next;
}

export const useWarmupPlansStore = create<WarmupPlansState>((set, get) => ({
  plans: [],
  currentPlan: null,
  isLoading: false,
  error: null,

  fetchPlans: async () => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<WarmupPlan[]>("/warmup-plans");
      set({
        plans: response.data || [],
        currentPlan:
          (response.data || []).find((plan) => plan.status === "active") ??
          null,
        isLoading: false,
      });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  createPlan: async (payload) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<WarmupPlan>(
        "/warmup-plans",
        payload
      );
      const plan = response.data;
      set({
        plans: upsertPlan(get().plans, plan),
        currentPlan: plan,
        isLoading: false,
      });
      return plan;
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  updatePlan: async (id, payload) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.put<WarmupPlan>(
        `/warmup-plans/${id}`,
        payload
      );
      const plan = response.data;
      set({
        plans: upsertPlan(get().plans, plan),
        currentPlan: get().currentPlan?.id === id ? plan : get().currentPlan,
        isLoading: false,
      });
      return plan;
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  deletePlan: async (id) => {
    set({ isLoading: true, error: null });
    try {
      await apiClient.delete(`/warmup-plans/${id}`);
      const plans = get().plans.filter((plan) => plan.id !== id);
      set({
        plans,
        currentPlan: get().currentPlan?.id === id ? null : get().currentPlan,
        isLoading: false,
      });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  pausePlan: async (id) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.patch<WarmupPlan>(
        `/warmup-plans/${id}/pause`
      );
      const plan = response.data;
      set({
        plans: upsertPlan(get().plans, plan),
        currentPlan: plan.status === "active" ? plan : null,
        isLoading: false,
      });
      return plan;
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  resumePlan: async (id) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.patch<WarmupPlan>(
        `/warmup-plans/${id}/resume`
      );
      const plan = response.data;
      set({
        plans: upsertPlan(get().plans, plan),
        currentPlan: plan,
        isLoading: false,
      });
      return plan;
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },
}));
