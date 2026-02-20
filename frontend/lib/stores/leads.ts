import { create } from "zustand";
import { apiClient } from "@/lib/api";

export interface Lead {
  id: string;
  user_id: string;
  company_name: string | null;
  first_name: string;
  last_name: string;
  full_name: string;
  email: string | null;
  phone: string | null;
  source: "manual" | "referral" | "website" | "campaign" | "other";
  status:
    | "new"
    | "contacted"
    | "qualified"
    | "proposal_sent"
    | "negotiating"
    | "won"
    | "lost";
  estimated_value: number | null;
  currency: string;
  probability: number | null;
  expected_close_date: string | null;
  notes: string | null;
  lost_reason: string | null;
  converted_at: string | null;
  can_convert: boolean;
  is_terminal: boolean;
  created_at: string;
  updated_at: string;
  activities?: LeadActivity[];
  won_client?: { id: string; name: string } | null;
}

export interface LeadActivity {
  id: string;
  type: "note" | "email_sent" | "call" | "meeting" | "follow_up";
  content: string | null;
  scheduled_at: string | null;
  completed_at: string | null;
  created_at: string;
}

export interface PipelineColumn {
  id: string;
  status: string;
  title: string;
  leads: Lead[];
}

export interface LeadAnalytics {
  total_pipeline_value: number;
  leads_by_status: Record<string, number>;
  win_rate: number;
  average_deal_value: number;
  average_time_to_close: number;
  pipeline_by_source: Array<{
    source: string;
    count: number;
    total_value: number;
  }>;
}

interface Pagination {
  current_page: number;
  last_page: number;
  total: number;
}

interface LeadState {
  leads: Lead[];
  currentLead: Lead | null;
  pipeline: {
    columns: Record<string, Lead[]>;
    column_stats: Record<string, { count: number; total_value: number }>;
    total_pipeline_value: number;
  } | null;
  analytics: LeadAnalytics | null;
  pagination: Pagination | null;
  isLoading: boolean;
  error: string | null;

  fetchLeads: (params?: Record<string, unknown>) => Promise<void>;
  fetchLead: (id: string) => Promise<Lead | null>;
  createLead: (payload: Record<string, unknown>) => Promise<Lead | null>;
  updateLead: (
    id: string,
    payload: Record<string, unknown>
  ) => Promise<Lead | null>;
  deleteLead: (id: string) => Promise<void>;
  updateStatus: (
    id: string,
    status: string,
    lostReason?: string
  ) => Promise<Lead | null>;
  updatePosition: (id: string, position: number) => Promise<void>;
  convertToClient: (
    id: string,
    overrides?: Record<string, unknown>
  ) => Promise<{ client: { id: string; name: string }; lead: Lead } | null>;
  fetchPipeline: () => Promise<void>;
  fetchAnalytics: (params?: Record<string, unknown>) => Promise<void>;
  fetchActivities: (leadId: string) => Promise<LeadActivity[]>;
  createActivity: (
    leadId: string,
    payload: Record<string, unknown>
  ) => Promise<LeadActivity | null>;
  deleteActivity: (leadId: string, activityId: string) => Promise<void>;
}

export const useLeadStore = create<LeadState>((set, get) => ({
  leads: [],
  currentLead: null,
  pipeline: null,
  analytics: null,
  pagination: null,
  isLoading: false,
  error: null,

  fetchLeads: async (params = {}) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<{
        data: {
          data: Lead[];
          current_page: number;
          last_page: number;
          total: number;
        };
      }>("/leads", { params });
      const payload = response.data || {
        data: [],
        current_page: 1,
        last_page: 1,
        total: 0,
      };
      set({
        leads: payload.data || [],
        pagination: {
          current_page: payload.current_page || 1,
          last_page: payload.last_page || 1,
          total: payload.total || 0,
        },
        isLoading: false,
      });
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
    }
  },

  fetchLead: async (id) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<Lead>(`/leads/${id}`);
      const lead = response.data;
      set({
        currentLead: lead,
        leads: get().leads.map((item) => (item.id === id ? lead : item)),
        isLoading: false,
      });
      return lead;
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  createLead: async (payload) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<Lead>("/leads", payload);
      const created = response.data;
      set({ leads: [created, ...get().leads], isLoading: false });
      return created;
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  updateLead: async (id, payload) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.put<Lead>(`/leads/${id}`, payload);
      const updated = response.data;
      set({
        leads: get().leads.map((item) => (item.id === id ? updated : item)),
        currentLead: get().currentLead?.id === id ? updated : get().currentLead,
        isLoading: false,
      });
      return updated;
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  deleteLead: async (id) => {
    set({ isLoading: true, error: null });
    try {
      await apiClient.delete(`/leads/${id}`);
      set({
        leads: get().leads.filter((item) => item.id !== id),
        currentLead: get().currentLead?.id === id ? null : get().currentLead,
        isLoading: false,
      });
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  updateStatus: async (id, status, lostReason) => {
    set({ isLoading: true, error: null });
    try {
      const payload: Record<string, unknown> = { status };
      if (lostReason) {
        payload.lost_reason = lostReason;
      }
      const response = await apiClient.patch<Lead>(
        `/leads/${id}/status`,
        payload
      );
      const updated = response.data;
      set({
        leads: get().leads.map((item) => (item.id === id ? updated : item)),
        currentLead: get().currentLead?.id === id ? updated : get().currentLead,
        isLoading: false,
      });
      return updated;
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  updatePosition: async (id, position) => {
    try {
      await apiClient.patch(`/leads/${id}/position`, { position });
    } catch (error) {
      set({ error: (error as Error).message });
      throw error;
    }
  },

  convertToClient: async (id, overrides = {}) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<{
        client: { id: string; name: string };
        lead: Lead;
      }>(`/leads/${id}/convert`, overrides);
      const result = response.data;
      set({
        leads: get().leads.map((item) => (item.id === id ? result.lead : item)),
        currentLead:
          get().currentLead?.id === id ? result.lead : get().currentLead,
        isLoading: false,
      });
      return result;
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  fetchPipeline: async () => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<{
        data: {
          columns: Record<string, Lead[]>;
          column_stats: Record<string, { count: number; total_value: number }>;
          total_pipeline_value: number;
        };
      }>("/leads/pipeline");
      set({ pipeline: response.data, isLoading: false });
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
    }
  },

  fetchAnalytics: async (params = {}) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<LeadAnalytics>("/leads/analytics", {
        params,
      });
      set({ analytics: response.data, isLoading: false });
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
    }
  },

  fetchActivities: async (leadId) => {
    try {
      const response = await apiClient.get<{ data: { data: LeadActivity[] } }>(
        `/leads/${leadId}/activities`
      );
      const activities = response.data?.data || [];
      return activities;
    } catch (error) {
      throw error;
    }
  },

  createActivity: async (leadId, payload) => {
    try {
      const response = await apiClient.post<LeadActivity>(
        `/leads/${leadId}/activities`,
        payload
      );
      return response.data;
    } catch (error) {
      throw error;
    }
  },

  deleteActivity: async (leadId, activityId) => {
    try {
      await apiClient.delete(`/leads/${leadId}/activities/${activityId}`);
    } catch (error) {
      throw error;
    }
  },
}));
