import { create } from "zustand";
import { apiClient } from "@/lib/api";

export type TicketStatus =
  | "open"
  | "in_progress"
  | "pending"
  | "resolved"
  | "closed";
export type TicketPriority = "low" | "normal" | "high" | "urgent";

export interface Ticket {
  id: string;
  user_id: string;
  assigned_to: string | null;
  client_id: string | null;
  project_id: string | null;
  title: string;
  description: string;
  status: TicketStatus;
  priority: TicketPriority;
  category: string | null;
  tags: string[];
  deadline: string | null;
  resolved_at: string | null;
  closed_at: string | null;
  first_response_at: string | null;
  created_at: string;
  updated_at: string;
  owner?: { id: string; name: string; email: string };
  assignee?: { id: string; name: string; email: string } | null;
  client?: { id: string; name: string } | null;
  project?: { id: string; name: string } | null;
}

export interface TicketMessage {
  id: string;
  ticket_id: string;
  user_id: string;
  content: string;
  is_internal: boolean;
  created_at: string;
  updated_at: string;
  user?: { id: string; name: string; email: string };
}

export interface TicketStats {
  total_tickets: number;
  total_open: number;
  total_in_progress: number;
  total_pending: number;
  total_resolved: number;
  total_closed: number;
  total_low_priority: number;
  total_normal_priority: number;
  total_high_priority: number;
  total_urgent_priority: number;
  total_overdue: number;
  average_resolution_time_in_hours: number | null;
}

export interface TicketFilters {
  status?: TicketStatus | "";
  priority?: TicketPriority | "";
  client_id?: string;
  assigned_to?: string;
  category?: string;
  tags?: string[];
  date_from?: string;
  date_to?: string;
  deadline_from?: string;
  deadline_to?: string;
  overdue?: boolean;
}

export interface TicketPagination {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

interface TicketState {
  tickets: Ticket[];
  overdueTickets: Ticket[];
  stats: TicketStats | null;
  pagination: TicketPagination | null;
  isLoading: boolean;
  error: string | null;
  searchQuery: string;
  filters: TicketFilters;
  sort: string;
  sortDir: "asc" | "desc";

  fetchTickets: (
    params?: TicketFilters & {
      q?: string;
      sort?: string;
      sort_dir?: string;
      page?: number;
    }
  ) => Promise<void>;
  createTicket: (data: Partial<Ticket>) => Promise<Ticket>;
  updateTicket: (id: string, data: Partial<Ticket>) => Promise<Ticket>;
  deleteTicket: (id: string) => Promise<void>;
  changeStatus: (
    id: string,
    status: TicketStatus,
    comment?: string
  ) => Promise<Ticket>;
  reassign: (id: string, assigned_to: string) => Promise<Ticket>;
  fetchStats: () => Promise<void>;
  fetchOverdue: (params?: { page?: number }) => Promise<void>;
  setSearchQuery: (q: string) => void;
  setFilters: (filters: TicketFilters) => void;
  setSort: (sort: string, dir: "asc" | "desc") => void;
}

export const useTicketStore = create<TicketState>((set, get) => ({
  tickets: [],
  overdueTickets: [],
  stats: null,
  pagination: null,
  isLoading: false,
  error: null,
  searchQuery: "",
  filters: {},
  sort: "created_at",
  sortDir: "desc",

  fetchTickets: async (params = {}) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<any>("/tickets", { params });
      set({
        tickets: Array.isArray(response.data?.data) ? response.data.data : [],
        pagination: {
          current_page: response.data?.current_page ?? 1,
          last_page: response.data?.last_page ?? 1,
          per_page: response.data?.per_page ?? 0,
          total: response.data?.total ?? 0,
        },
        isLoading: false,
      });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
    }
  },

  createTicket: async (data) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<Ticket>("/tickets", data);
      const ticket = response.data;
      set((state) => ({
        tickets: [ticket, ...state.tickets],
        isLoading: false,
      }));
      return ticket;
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  updateTicket: async (id, data) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.put<Ticket>(`/tickets/${id}`, data);
      const ticket = response.data;
      set((state) => ({
        tickets: state.tickets.map((t) => (t.id === id ? ticket : t)),
        isLoading: false,
      }));
      return ticket;
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  deleteTicket: async (id) => {
    set({ isLoading: true, error: null });
    try {
      await apiClient.delete(`/tickets/${id}`);
      set((state) => ({
        tickets: state.tickets.filter((t) => t.id !== id),
        isLoading: false,
      }));
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  changeStatus: async (id, status, comment) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.patch<Ticket>(`/tickets/${id}/status`, {
        status,
        comment,
      });
      const ticket = response.data;
      set((state) => ({
        tickets: state.tickets.map((t) => (t.id === id ? ticket : t)),
        isLoading: false,
      }));
      return ticket;
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  reassign: async (id, assigned_to) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.patch<Ticket>(`/tickets/${id}/assign`, {
        assigned_to,
      });
      const ticket = response.data;
      set((state) => ({
        tickets: state.tickets.map((t) => (t.id === id ? ticket : t)),
        isLoading: false,
      }));
      return ticket;
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  fetchStats: async () => {
    try {
      const response = await apiClient.get<TicketStats>("/tickets/stats");
      set({ stats: response.data });
    } catch (error) {
      set({ error: (error as Error).message });
    }
  },

  fetchOverdue: async (params = {}) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<any>("/tickets/overdue", {
        params,
      });
      set({
        overdueTickets: response.data.data ?? response.data,
        isLoading: false,
      });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
    }
  },

  setSearchQuery: (q) => set({ searchQuery: q }),
  setFilters: (filters) => set({ filters }),
  setSort: (sort, sortDir) => set({ sort, sortDir }),
}));
