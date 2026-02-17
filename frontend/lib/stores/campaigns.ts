import { create } from "zustand";
import { apiClient } from "@/lib/api";

export type CampaignType = "email" | "sms";
export type CampaignStatus =
  | "draft"
  | "scheduled"
  | "sending"
  | "sent"
  | "paused"
  | "cancelled";

export interface CampaignTemplate {
  id: string;
  name: string;
  type: CampaignType;
  subject?: string | null;
  content: string;
}

export interface CampaignAttachment {
  id?: string;
  filename: string;
  path: string;
  mime_type: string;
  size_bytes: number;
}

export interface Campaign {
  id: string;
  name: string;
  type: CampaignType;
  status: CampaignStatus;
  subject?: string | null;
  content: string;
  segment_id?: string | null;
  template_id?: string | null;
  scheduled_at?: string | null;
  started_at?: string | null;
  completed_at?: string | null;
  settings?: Record<string, unknown> | null;
  recipients_count?: number;
  recipients?: Array<Record<string, unknown>>;
  attachments?: CampaignAttachment[];
}

export interface CampaignAnalytics {
  campaign_id: string;
  campaign_name?: string;
  total_recipients: number;
  sent_count?: number;
  delivered_count?: number;
  opened_count?: number;
  clicked_count?: number;
  bounced_count?: number;
  failed_count?: number;
  open_rate: number;
  click_rate: number;
  bounce_rate?: number;
  failure_reasons?: Array<{ reason: string; count: number }>;
  time_series: Array<{ hour: string; opens: number; clicks: number }>;
}

interface Pagination {
  current_page: number;
  last_page: number;
  total: number;
  per_page: number;
}

interface CampaignState {
  campaigns: Campaign[];
  currentCampaign: Campaign | null;
  templates: CampaignTemplate[];
  analytics: CampaignAnalytics | null;
  comparison: CampaignAnalytics[];
  pagination: Pagination | null;
  isLoading: boolean;
  error: string | null;

  fetchCampaigns: (params?: Record<string, unknown>) => Promise<void>;
  fetchCampaign: (id: string) => Promise<Campaign | null>;
  createCampaign: (data: Record<string, unknown>) => Promise<Campaign | null>;
  updateCampaign: (
    id: string,
    data: Record<string, unknown>
  ) => Promise<Campaign | null>;
  deleteCampaign: (id: string) => Promise<void>;

  sendCampaign: (id: string) => Promise<Campaign | null>;
  pauseCampaign: (id: string) => Promise<Campaign | null>;
  duplicateCampaign: (id: string) => Promise<Campaign | null>;
  testSendCampaign: (
    id: string,
    payload: { email?: string; phone?: string }
  ) => Promise<void>;

  fetchTemplates: () => Promise<void>;
  createTemplate: (
    data: Record<string, unknown>
  ) => Promise<CampaignTemplate | null>;
  updateTemplate: (
    id: string,
    data: Record<string, unknown>
  ) => Promise<CampaignTemplate | null>;
  deleteTemplate: (id: string) => Promise<void>;

  fetchCampaignAnalytics: (id: string) => Promise<CampaignAnalytics | null>;
  compareCampaigns: (ids: string[]) => Promise<CampaignAnalytics[]>;

  updateEmailSettings: (data: Record<string, unknown>) => Promise<void>;
  updateSmsSettings: (data: Record<string, unknown>) => Promise<void>;
  updateNotificationPreferences: (
    data: Record<string, unknown>
  ) => Promise<void>;
}

function upsertCampaign(list: Campaign[], campaign: Campaign): Campaign[] {
  const index = list.findIndex((item) => item.id === campaign.id);
  if (index === -1) {
    return [campaign, ...list];
  }

  const next = [...list];
  next[index] = campaign;

  return next;
}

function upsertTemplate(
  list: CampaignTemplate[],
  template: CampaignTemplate
): CampaignTemplate[] {
  const index = list.findIndex((item) => item.id === template.id);
  if (index === -1) {
    return [template, ...list];
  }

  const next = [...list];
  next[index] = template;

  return next;
}

function normalizePagination(payload: any): Pagination {
  return {
    current_page: payload.current_page || 1,
    last_page: payload.last_page || 1,
    total: payload.total || 0,
    per_page: payload.per_page || 15,
  };
}

export const useCampaignStore = create<CampaignState>((set, get) => ({
  campaigns: [],
  currentCampaign: null,
  templates: [],
  analytics: null,
  comparison: [],
  pagination: null,
  isLoading: false,
  error: null,

  fetchCampaigns: async (params = {}) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<any>("/campaigns", { params });
      const payload = response.data || {};

      set({
        campaigns: payload.data || [],
        pagination: normalizePagination(payload),
        isLoading: false,
      });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
    }
  },

  fetchCampaign: async (id) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<any>(`/campaigns/${id}`);
      const campaign = response.data as Campaign;

      set({
        currentCampaign: campaign,
        campaigns: upsertCampaign(get().campaigns, campaign),
        isLoading: false,
      });

      return campaign;
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  createCampaign: async (data) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<any>("/campaigns", data);
      const campaign = response.data as Campaign;

      set({
        currentCampaign: campaign,
        campaigns: upsertCampaign(get().campaigns, campaign),
        isLoading: false,
      });

      return campaign;
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  updateCampaign: async (id, data) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.put<any>(`/campaigns/${id}`, data);
      const campaign = response.data as Campaign;

      set({
        campaigns: upsertCampaign(get().campaigns, campaign),
        currentCampaign:
          get().currentCampaign?.id === id ? campaign : get().currentCampaign,
        isLoading: false,
      });

      return campaign;
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  deleteCampaign: async (id) => {
    set({ isLoading: true, error: null });
    try {
      await apiClient.delete(`/campaigns/${id}`);

      set({
        campaigns: get().campaigns.filter((item) => item.id !== id),
        currentCampaign:
          get().currentCampaign?.id === id ? null : get().currentCampaign,
        isLoading: false,
      });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  sendCampaign: async (id) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<any>(`/campaigns/${id}/send`);
      const campaign = response.data as Campaign;

      set({
        campaigns: upsertCampaign(get().campaigns, campaign),
        currentCampaign:
          get().currentCampaign?.id === id ? campaign : get().currentCampaign,
        isLoading: false,
      });

      return campaign;
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  pauseCampaign: async (id) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<any>(`/campaigns/${id}/pause`);
      const campaign = response.data as Campaign;

      set({
        campaigns: upsertCampaign(get().campaigns, campaign),
        currentCampaign:
          get().currentCampaign?.id === id ? campaign : get().currentCampaign,
        isLoading: false,
      });

      return campaign;
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  duplicateCampaign: async (id) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<any>(`/campaigns/${id}/duplicate`);
      const campaign = response.data as Campaign;

      set({
        campaigns: upsertCampaign(get().campaigns, campaign),
        isLoading: false,
      });

      return campaign;
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  testSendCampaign: async (id, payload) => {
    set({ isLoading: true, error: null });
    try {
      await apiClient.post(`/campaigns/${id}/test`, payload);
      set({ isLoading: false });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  fetchTemplates: async () => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<any>("/campaign-templates");
      set({ templates: response.data || [], isLoading: false });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  createTemplate: async (data) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<any>("/campaign-templates", data);
      const template = response.data as CampaignTemplate;
      set({
        templates: upsertTemplate(get().templates, template),
        isLoading: false,
      });

      return template;
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  updateTemplate: async (id, data) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.put<any>(`/campaign-templates/${id}`, data);
      const template = response.data as CampaignTemplate;
      set({
        templates: upsertTemplate(get().templates, template),
        isLoading: false,
      });

      return template;
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  deleteTemplate: async (id) => {
    set({ isLoading: true, error: null });
    try {
      await apiClient.delete(`/campaign-templates/${id}`);
      set({
        templates: get().templates.filter((template) => template.id !== id),
        isLoading: false,
      });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  fetchCampaignAnalytics: async (id) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<any>(`/campaigns/${id}/analytics`);
      const analytics = response.data as CampaignAnalytics;
      set({ analytics, isLoading: false });

      return analytics;
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  compareCampaigns: async (ids) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<any>("/campaigns/compare", {
        params: { ids: ids.join(",") },
      });
      const comparison = (response.data || []) as CampaignAnalytics[];
      set({ comparison, isLoading: false });

      return comparison;
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  updateEmailSettings: async (data) => {
    set({ isLoading: true, error: null });
    try {
      await apiClient.put("/settings/email", data);
      set({ isLoading: false });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  updateSmsSettings: async (data) => {
    set({ isLoading: true, error: null });
    try {
      await apiClient.put("/settings/sms", data);
      set({ isLoading: false });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  updateNotificationPreferences: async (data) => {
    set({ isLoading: true, error: null });
    try {
      await apiClient.put("/settings/notifications", data);
      set({ isLoading: false });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },
}));
