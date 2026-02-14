import { create } from "zustand";
import { apiClient } from "@/lib/api";

interface Client {
  id: string;
  reference: string;
  name: string;
  email: string | null;
  phone: string | null;
  status: string;
  created_at: string;
}

interface Pagination {
  current_page: number;
  last_page: number;
  total: number;
  per_page: number;
}

interface ClientState {
  clients: Client[];
  pagination: Pagination | null;
  isLoading: boolean;
  error: string | null;

  // Actions
  fetchClients: (params?: Record<string, any>) => Promise<void>;
  createClient: (data: any) => Promise<void>;
  updateClient: (id: string, data: any) => Promise<void>;
  deleteClient: (id: string) => Promise<void>;
}

export const useClientStore = create<ClientState>((set, get) => ({
  clients: [],
  pagination: null,
  isLoading: false,
  error: null,

  fetchClients: async (params = {}) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<any>("/clients", { params } as any);
      // Laravel Resource returns { data: { data: [...], meta: {...} } }
      set({ 
        clients: response.data.data, 
        pagination: response.data.meta,
        isLoading: false 
      });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
    }
  },

  createClient: async (data) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<Client>("/clients", data);
      set({ 
        clients: [response.data, ...get().clients],
        isLoading: false 
      });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  updateClient: async (id, data) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.put<Client>(`/clients/${id}`, data);
      set({
        clients: get().clients.map(c => c.id === id ? response.data : c),
        isLoading: false
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
        clients: get().clients.filter(c => c.id !== id),
        isLoading: false
      });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },
}));
