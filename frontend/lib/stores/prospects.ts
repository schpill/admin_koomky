import { create } from "zustand";
import { apiClient } from "@/lib/api";

export interface ProspectFilters {
  search?: string;
  industry?: string;
  department?: string;
  tags?: string[];
  city?: string;
  page?: number;
}

export interface ProspectClient {
  id: string;
  name: string;
  email?: string | null;
  phone?: string | null;
  city?: string | null;
  industry?: string | null;
  department?: string | null;
  status: string;
  tags?: Array<{ id: string; name: string }>;
}

interface ProspectState {
  clients: ProspectClient[];
  total: number;
  page: number;
  filters: ProspectFilters;
  isLoading: boolean;
  error: string | null;

  fetchProspects: (filters?: ProspectFilters) => Promise<void>;
  convertToClient: (id: string) => Promise<void>;
  bulkUpdateStatus: (ids: string[], status: string) => Promise<void>;
  bulkAddTags: (ids: string[], tagIds: string[]) => Promise<void>;
  exportCsv: (filters?: ProspectFilters) => Promise<Blob | null>;
}

export const useProspectStore = create<ProspectState>((set, get) => ({
  clients: [],
  total: 0,
  page: 1,
  filters: {},
  isLoading: false,
  error: null,

  fetchProspects: async (filters = {}) => {
    const merged = { ...get().filters, ...filters, status: "prospect" };
    set({ isLoading: true, error: null, filters: merged, page: merged.page || 1 });

    try {
      const response = await apiClient.get<any>("/clients", {
        params: merged,
      });
      const payload = response.data || {};

      set({
        clients: payload.data || [],
        total: payload.meta?.total || 0,
        page: payload.meta?.current_page || 1,
        isLoading: false,
      });
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  convertToClient: async (id) => {
    await apiClient.put(`/clients/${id}`, { status: "active" });
    await get().fetchProspects();
  },

  bulkUpdateStatus: async (ids, status) => {
    await Promise.all(ids.map((id) => apiClient.put(`/clients/${id}`, { status })));
    await get().fetchProspects();
  },

  bulkAddTags: async (ids, tagIds) => {
    await Promise.all(ids.map((id) => apiClient.post(`/clients/${id}/tags`, { tag_ids: tagIds })));
    await get().fetchProspects();
  },

  exportCsv: async () => {
    const response = await apiClient.get<Blob>("/clients/export/csv", { responseType: "blob" });
    return response.data;
  },
}));
