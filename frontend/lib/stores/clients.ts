import { create } from "zustand";
import { apiClient } from "@/lib/api";

export interface Client {
  id: string;
  reference: string;
  name: string;
  email: string | null;
  phone: string | null;
  status: string;
  address?: string | null;
  city?: string | null;
  zip_code?: string | null;
  country?: string | null;
  preferred_currency?: string | null;
  notes?: string | null;
  created_at: string;
  tags?: any[];
  contacts?: any[];
}

interface Pagination {
  current_page: number;
  last_page: number;
  total: number;
  per_page: number;
}

interface ClientState {
  clients: Client[];
  currentClient: Client | null;
  pagination: Pagination | null;
  isLoading: boolean;
  error: string | null;

  // Actions
  fetchClients: (params?: Record<string, any>) => Promise<void>;
  fetchClient: (id: string) => Promise<void>;
  createClient: (data: any) => Promise<void>;
  updateClient: (id: string, data: any) => Promise<void>;
  deleteClient: (id: string) => Promise<void>;
  restoreClient: (id: string) => Promise<void>;
}

export const useClientStore = create<ClientState>((set, get) => ({
  clients: [],
  currentClient: null,
  pagination: null,
  isLoading: false,
  error: null,

  fetchClients: async (params = {}) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<any>("/clients", { params });
      set({
        clients: response.data.data,
        pagination: response.data.meta,
        isLoading: false,
      });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
    }
  },

  fetchClient: async (id) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<any>(`/clients/${id}`);
      set({
        currentClient: response.data,
        isLoading: false,
      });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  createClient: async (data) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<any>("/clients", data);
      set({
        clients: [response.data, ...get().clients],
        isLoading: false,
      });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  updateClient: async (id, data) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.put<any>(`/clients/${id}`, data);
      set({
        clients: get().clients.map((c) => (c.id === id ? response.data : c)),
        currentClient: response.data,
        isLoading: false,
      });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  deleteClient: async (id) => {
    set({ isLoading: true, error: null });
    try {
      await apiClient.delete(`/clients/${id}`);
      set({
        clients: get().clients.filter((c) => c.id !== id),
        isLoading: false,
      });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  restoreClient: async (id) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<any>(`/clients/${id}/restore`);
      set({
        clients: [response.data, ...get().clients],
        isLoading: false,
      });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },
}));
