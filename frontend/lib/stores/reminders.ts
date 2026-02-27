import { create } from "zustand";
import { apiClient } from "@/lib/api";

export interface ReminderStepInput {
  step_number: number;
  delay_days: number;
  subject: string;
  body: string;
}

export interface ReminderSequence {
  id: string;
  user_id: string;
  name: string;
  description?: string | null;
  is_active: boolean;
  is_default: boolean;
  steps: ReminderStepInput[];
}

export interface ReminderDelivery {
  id: string;
  reminder_step_id: string;
  status: "pending" | "sent" | "failed" | "skipped";
  sent_at?: string | null;
  error_message?: string | null;
  step?: ReminderStepInput;
}

export interface InvoiceReminder {
  id: string;
  invoice_id: string;
  sequence_id?: string | null;
  is_paused: boolean;
  completed_at?: string | null;
  next_reminder_step_id?: string | null;
  sequence?: ReminderSequence;
  deliveries?: ReminderDelivery[];
}

interface ReminderState {
  sequences: ReminderSequence[];
  selectedSequence: ReminderSequence | null;
  invoiceReminder: InvoiceReminder | null;
  isLoading: boolean;
  error: string | null;

  fetchSequences: () => Promise<void>;
  createSequence: (
    data: Partial<ReminderSequence>
  ) => Promise<ReminderSequence>;
  updateSequence: (
    id: string,
    data: Partial<ReminderSequence>
  ) => Promise<ReminderSequence>;
  deleteSequence: (id: string) => Promise<void>;
  setDefaultSequence: (id: string) => Promise<ReminderSequence>;

  fetchInvoiceReminder: (invoiceId: string) => Promise<void>;
  attachSequence: (invoiceId: string, sequenceId: string) => Promise<void>;
  pauseReminder: (invoiceId: string) => Promise<void>;
  resumeReminder: (invoiceId: string) => Promise<void>;
  skipStep: (invoiceId: string) => Promise<void>;
  cancelReminder: (invoiceId: string) => Promise<void>;
}

export const useReminderStore = create<ReminderState>((set, get) => ({
  sequences: [],
  selectedSequence: null,
  invoiceReminder: null,
  isLoading: false,
  error: null,

  fetchSequences: async () => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<ReminderSequence[]>(
        "/reminder-sequences"
      );
      set({ sequences: response.data || [], isLoading: false });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
    }
  },

  createSequence: async (data) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<ReminderSequence>(
        "/reminder-sequences",
        data
      );
      const created = response.data;
      set({
        sequences: [created, ...get().sequences],
        selectedSequence: created,
        isLoading: false,
      });
      return created;
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  updateSequence: async (id, data) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.patch<ReminderSequence>(
        `/reminder-sequences/${id}`,
        data
      );
      const updated = response.data;
      set({
        sequences: get().sequences.map((item) =>
          item.id === id ? updated : item
        ),
        selectedSequence:
          get().selectedSequence?.id === id ? updated : get().selectedSequence,
        isLoading: false,
      });
      return updated;
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  deleteSequence: async (id) => {
    set({ isLoading: true, error: null });
    try {
      await apiClient.delete(`/reminder-sequences/${id}`);
      set({
        sequences: get().sequences.filter((item) => item.id !== id),
        selectedSequence:
          get().selectedSequence?.id === id ? null : get().selectedSequence,
        isLoading: false,
      });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  setDefaultSequence: async (id) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<ReminderSequence>(
        `/reminder-sequences/${id}/default`
      );
      const updated = response.data;
      set({
        sequences: get().sequences.map((item) => ({
          ...item,
          is_default: item.id === id,
        })),
        selectedSequence:
          get().selectedSequence?.id === id
            ? { ...get().selectedSequence!, is_default: true }
            : get().selectedSequence,
        isLoading: false,
      });
      return updated;
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  fetchInvoiceReminder: async (invoiceId) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<InvoiceReminder | null>(
        `/invoices/${invoiceId}/reminder`
      );
      set({ invoiceReminder: response.data || null, isLoading: false });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
    }
  },

  attachSequence: async (invoiceId, sequenceId) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<InvoiceReminder>(
        `/invoices/${invoiceId}/reminder/attach`,
        { sequence_id: sequenceId }
      );
      set({ invoiceReminder: response.data, isLoading: false });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  pauseReminder: async (invoiceId) => {
    const response = await apiClient.post<InvoiceReminder>(
      `/invoices/${invoiceId}/reminder/pause`
    );
    set({ invoiceReminder: response.data });
  },

  resumeReminder: async (invoiceId) => {
    const response = await apiClient.post<InvoiceReminder>(
      `/invoices/${invoiceId}/reminder/resume`
    );
    set({ invoiceReminder: response.data });
  },

  skipStep: async (invoiceId) => {
    const response = await apiClient.post<InvoiceReminder>(
      `/invoices/${invoiceId}/reminder/skip`
    );
    set({ invoiceReminder: response.data });
  },

  cancelReminder: async (invoiceId) => {
    await apiClient.delete(`/invoices/${invoiceId}/reminder`);
    set({ invoiceReminder: null });
  },
}));
