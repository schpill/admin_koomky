import { create } from "zustand";
import { apiClient } from "@/lib/api";

export interface ScoringRule {
  id: string;
  event: string;
  points: number;
  expiry_days?: number | null;
  is_active: boolean;
}

interface ScoringRuleState {
  rules: ScoringRule[];
  isLoading: boolean;
  error: string | null;
  fetchRules: () => Promise<void>;
  createRule: (data: Omit<ScoringRule, "id">) => Promise<ScoringRule>;
  updateRule: (
    id: string,
    data: Partial<Omit<ScoringRule, "id" | "event">>
  ) => Promise<ScoringRule>;
  deleteRule: (id: string) => Promise<void>;
}

function upsertRule(list: ScoringRule[], rule: ScoringRule): ScoringRule[] {
  const index = list.findIndex((item) => item.id === rule.id);
  if (index === -1) {
    return [...list, rule].sort((a, b) => a.event.localeCompare(b.event));
  }

  const next = [...list];
  next[index] = rule;
  return next.sort((a, b) => a.event.localeCompare(b.event));
}

export const useScoringRuleStore = create<ScoringRuleState>((set, get) => ({
  rules: [],
  isLoading: false,
  error: null,

  fetchRules: async () => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<ScoringRule[]>("/scoring-rules");
      set({
        rules: response.data || [],
        isLoading: false,
      });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  createRule: async (data) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<ScoringRule>("/scoring-rules", data);
      const rule = response.data;
      set({
        rules: upsertRule(get().rules, rule),
        isLoading: false,
      });
      return rule;
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  updateRule: async (id, data) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.put<ScoringRule>(
        `/scoring-rules/${id}`,
        data
      );
      const rule = response.data;
      set({
        rules: upsertRule(get().rules, rule),
        isLoading: false,
      });
      return rule;
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  deleteRule: async (id) => {
    set({ isLoading: true, error: null });
    try {
      await apiClient.delete(`/scoring-rules/${id}`);
      set({
        rules: get().rules.filter((rule) => rule.id !== id),
        isLoading: false,
      });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },
}));
