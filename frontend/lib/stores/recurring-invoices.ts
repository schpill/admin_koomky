import { create } from "zustand";
import { apiClient } from "@/lib/api";
import type { InvoiceLineItemInput } from "@/components/invoices/line-items-editor";

export type RecurringInvoiceFrequency =
  | "weekly"
  | "biweekly"
  | "monthly"
  | "quarterly"
  | "semiannual"
  | "annual";

export type RecurringInvoiceStatus =
  | "active"
  | "paused"
  | "completed"
  | "cancelled";

export interface RecurringInvoiceProfile {
  id: string;
  user_id?: string;
  client_id: string;
  name: string;
  frequency: RecurringInvoiceFrequency;
  start_date: string;
  end_date?: string | null;
  next_due_date: string;
  day_of_month?: number | null;
  line_items: InvoiceLineItemInput[];
  notes?: string | null;
  payment_terms_days: number;
  tax_rate?: number | null;
  discount_percent?: number | null;
  status: RecurringInvoiceStatus;
  last_generated_at?: string | null;
  occurrences_generated: number;
  max_occurrences?: number | null;
  auto_send: boolean;
  currency: string;
  client?: {
    id: string;
    name: string;
    email?: string | null;
  } | null;
  generated_invoices?: Array<{
    id: string;
    number: string;
    status: string;
    total: number;
    issue_date: string;
    due_date: string;
    recurring_invoice_profile_id?: string | null;
  }>;
}

export interface RecurringInvoiceProfilePayload {
  client_id: string;
  name: string;
  frequency: RecurringInvoiceFrequency;
  start_date: string;
  end_date?: string | null;
  next_due_date?: string;
  day_of_month?: number | null;
  line_items: InvoiceLineItemInput[];
  notes?: string | null;
  payment_terms_days?: number;
  tax_rate?: number | null;
  discount_percent?: number | null;
  max_occurrences?: number | null;
  auto_send?: boolean;
  currency?: string;
  status?: RecurringInvoiceStatus;
}

interface Pagination {
  current_page: number;
  last_page: number;
  total: number;
  per_page: number;
}

interface RecurringInvoiceState {
  profiles: RecurringInvoiceProfile[];
  currentProfile: RecurringInvoiceProfile | null;
  pagination: Pagination | null;
  isLoading: boolean;
  error: string | null;

  fetchProfiles: (params?: Record<string, unknown>) => Promise<void>;
  fetchProfile: (id: string) => Promise<RecurringInvoiceProfile | null>;
  createProfile: (
    payload: RecurringInvoiceProfilePayload
  ) => Promise<RecurringInvoiceProfile | null>;
  updateProfile: (
    id: string,
    payload: RecurringInvoiceProfilePayload
  ) => Promise<RecurringInvoiceProfile | null>;
  deleteProfile: (id: string) => Promise<void>;
  pauseProfile: (id: string) => Promise<RecurringInvoiceProfile | null>;
  resumeProfile: (id: string) => Promise<RecurringInvoiceProfile | null>;
  cancelProfile: (id: string) => Promise<RecurringInvoiceProfile | null>;
}

function upsertProfile(
  list: RecurringInvoiceProfile[],
  profile: RecurringInvoiceProfile
): RecurringInvoiceProfile[] {
  const index = list.findIndex((item) => item.id === profile.id);
  if (index === -1) {
    return [profile, ...list];
  }

  const next = [...list];
  next[index] = profile;

  return next;
}

function profileAction(endpoint: string) {
  return apiClient.post<any>(endpoint).then((response) => {
    return response.data as RecurringInvoiceProfile;
  });
}

export const useRecurringInvoiceStore = create<RecurringInvoiceState>(
  (set, get) => ({
    profiles: [],
    currentProfile: null,
    pagination: null,
    isLoading: false,
    error: null,

    fetchProfiles: async (params = {}) => {
      set({ isLoading: true, error: null });
      try {
        const response = await apiClient.get<any>("/recurring-invoices", {
          params,
        });
        const payload = response.data || {};

        set({
          profiles: payload.data || [],
          pagination: {
            current_page: payload.current_page || 1,
            last_page: payload.last_page || 1,
            total: payload.total || 0,
            per_page: payload.per_page || 15,
          },
          isLoading: false,
        });
      } catch (error) {
        set({ isLoading: false, error: (error as Error).message });
      }
    },

    fetchProfile: async (id) => {
      set({ isLoading: true, error: null });
      try {
        const response = await apiClient.get<any>(`/recurring-invoices/${id}`);
        const profile = response.data as RecurringInvoiceProfile;

        set({
          currentProfile: profile,
          profiles: upsertProfile(get().profiles, profile),
          isLoading: false,
        });

        return profile;
      } catch (error) {
        set({ isLoading: false, error: (error as Error).message });
        throw error;
      }
    },

    createProfile: async (payload) => {
      set({ isLoading: true, error: null });
      try {
        const response = await apiClient.post<any>("/recurring-invoices", payload);
        const profile = response.data as RecurringInvoiceProfile;

        set({
          profiles: upsertProfile(get().profiles, profile),
          currentProfile: profile,
          isLoading: false,
        });

        return profile;
      } catch (error) {
        set({ isLoading: false, error: (error as Error).message });
        throw error;
      }
    },

    updateProfile: async (id, payload) => {
      set({ isLoading: true, error: null });
      try {
        const response = await apiClient.put<any>(
          `/recurring-invoices/${id}`,
          payload
        );
        const profile = response.data as RecurringInvoiceProfile;

        set({
          profiles: upsertProfile(get().profiles, profile),
          currentProfile:
            get().currentProfile?.id === id ? profile : get().currentProfile,
          isLoading: false,
        });

        return profile;
      } catch (error) {
        set({ isLoading: false, error: (error as Error).message });
        throw error;
      }
    },

    deleteProfile: async (id) => {
      set({ isLoading: true, error: null });
      try {
        await apiClient.delete(`/recurring-invoices/${id}`);
        set({
          profiles: get().profiles.filter((profile) => profile.id !== id),
          currentProfile:
            get().currentProfile?.id === id ? null : get().currentProfile,
          isLoading: false,
        });
      } catch (error) {
        set({ isLoading: false, error: (error as Error).message });
        throw error;
      }
    },

    pauseProfile: async (id) => {
      set({ isLoading: true, error: null });
      try {
        const profile = await profileAction(`/recurring-invoices/${id}/pause`);
        set({
          profiles: upsertProfile(get().profiles, profile),
          currentProfile:
            get().currentProfile?.id === id ? profile : get().currentProfile,
          isLoading: false,
        });

        return profile;
      } catch (error) {
        set({ isLoading: false, error: (error as Error).message });
        throw error;
      }
    },

    resumeProfile: async (id) => {
      set({ isLoading: true, error: null });
      try {
        const profile = await profileAction(`/recurring-invoices/${id}/resume`);
        set({
          profiles: upsertProfile(get().profiles, profile),
          currentProfile:
            get().currentProfile?.id === id ? profile : get().currentProfile,
          isLoading: false,
        });

        return profile;
      } catch (error) {
        set({ isLoading: false, error: (error as Error).message });
        throw error;
      }
    },

    cancelProfile: async (id) => {
      set({ isLoading: true, error: null });
      try {
        const profile = await profileAction(`/recurring-invoices/${id}/cancel`);
        set({
          profiles: upsertProfile(get().profiles, profile),
          currentProfile:
            get().currentProfile?.id === id ? profile : get().currentProfile,
          isLoading: false,
        });

        return profile;
      } catch (error) {
        set({ isLoading: false, error: (error as Error).message });
        throw error;
      }
    },
  })
);
