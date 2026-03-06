import { create } from "zustand";
import { apiClient } from "@/lib/api";

export interface SuppressionEntry {
  id: string;
  email: string;
  reason: "manual" | "unsubscribed" | "hard_bounce";
}

interface SuppressionListState {
  entries: SuppressionEntry[];
  total: number;
  page: number;
  search: string;
  isLoading: boolean;
  error: string | null;
  fetchEntries: (params?: { page?: number; search?: string }) => Promise<void>;
  addEntry: (email: string) => Promise<SuppressionEntry | null>;
  removeEntry: (id: string) => Promise<void>;
  importCsv: (file: File) => Promise<{ imported: number; skipped: number }>;
  exportCsv: () => Promise<Blob>;
}

export const useSuppressionListStore = create<SuppressionListState>((set, get) => ({
  entries: [],
  total: 0,
  page: 1,
  search: "",
  isLoading: false,
  error: null,

  fetchEntries: async (params = {}) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<any>("/suppression-list", {
        params,
      });
      const payload = response.data || {};
      set({
        entries: payload.data || [],
        total: payload.total || 0,
        page: payload.current_page || 1,
        search: String(params.search || get().search || ""),
        isLoading: false,
      });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
    }
  },

  addEntry: async (email) => {
    const response = await apiClient.post<SuppressionEntry>("/suppression-list", {
      email,
      reason: "manual",
    });
    const entry = response.data as SuppressionEntry;
    set({
      entries: [entry, ...get().entries],
      total: get().total + 1,
    });
    return entry;
  },

  removeEntry: async (id) => {
    await apiClient.delete(`/suppression-list/${id}`);
    set({
      entries: get().entries.filter((entry) => entry.id !== id),
      total: Math.max(0, get().total - 1),
    });
  },

  importCsv: async (file) => {
    const body = new FormData();
    body.append("file", file);
    const response = await apiClient.post<{ imported: number; skipped: number }>(
      "/suppression-list/import",
      body
    );
    return response.data;
  },

  exportCsv: async () => {
    const response = await apiClient.get<Blob>("/suppression-list/export", {
      responseType: "blob",
    });
    return response.data;
  },
}));
