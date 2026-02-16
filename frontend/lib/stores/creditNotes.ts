import { create } from "zustand";
import { apiClient } from "@/lib/api";

export type CreditNoteStatus = "draft" | "sent" | "applied";

export interface CreditNoteLineItemInput {
  description: string;
  quantity: number;
  unit_price: number;
  vat_rate: number;
}

export interface CreditNote {
  id: string;
  user_id?: string;
  client_id: string;
  invoice_id: string;
  number: string;
  status: CreditNoteStatus;
  issue_date: string;
  subtotal: number;
  tax_amount: number;
  total: number;
  currency?: string;
  reason?: string | null;
  pdf_path?: string | null;
  sent_at?: string | null;
  applied_at?: string | null;
  client?: {
    id: string;
    name: string;
    email?: string | null;
  } | null;
  invoice?: {
    id: string;
    number: string;
    total?: number;
    balance_due?: number;
  } | null;
  line_items?: Array<{
    id?: string;
    description: string;
    quantity: number;
    unit_price: number;
    vat_rate: number;
    total?: number;
  }>;
}

interface Pagination {
  current_page: number;
  last_page: number;
  total: number;
  per_page: number;
}

interface CreditNoteState {
  creditNotes: CreditNote[];
  currentCreditNote: CreditNote | null;
  pagination: Pagination | null;
  isLoading: boolean;
  error: string | null;

  fetchCreditNotes: (params?: Record<string, unknown>) => Promise<void>;
  fetchCreditNote: (id: string) => Promise<CreditNote | null>;
  createCreditNote: (
    data: Record<string, unknown>
  ) => Promise<CreditNote | null>;
  updateCreditNote: (
    id: string,
    data: Record<string, unknown>
  ) => Promise<CreditNote | null>;
  deleteCreditNote: (id: string) => Promise<void>;
  sendCreditNote: (id: string) => Promise<CreditNote | null>;
  applyCreditNote: (id: string) => Promise<CreditNote | null>;
}

function upsertCreditNote(
  list: CreditNote[],
  creditNote: CreditNote
): CreditNote[] {
  const index = list.findIndex((item) => item.id === creditNote.id);
  if (index === -1) {
    return [creditNote, ...list];
  }

  const next = [...list];
  next[index] = creditNote;

  return next;
}

export const useCreditNoteStore = create<CreditNoteState>((set, get) => ({
  creditNotes: [],
  currentCreditNote: null,
  pagination: null,
  isLoading: false,
  error: null,

  fetchCreditNotes: async (params = {}) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<any>("/credit-notes", { params });
      const payload = response.data || {};

      set({
        creditNotes: payload.data || [],
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

  fetchCreditNote: async (id) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<any>(`/credit-notes/${id}`);
      const creditNote = response.data as CreditNote;

      set({
        currentCreditNote: creditNote,
        creditNotes: upsertCreditNote(get().creditNotes, creditNote),
        isLoading: false,
      });

      return creditNote;
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  createCreditNote: async (data) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<any>("/credit-notes", data);
      const creditNote = response.data as CreditNote;

      set({
        creditNotes: upsertCreditNote(get().creditNotes, creditNote),
        currentCreditNote: creditNote,
        isLoading: false,
      });

      return creditNote;
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  updateCreditNote: async (id, data) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.put<any>(`/credit-notes/${id}`, data);
      const creditNote = response.data as CreditNote;

      set({
        creditNotes: upsertCreditNote(get().creditNotes, creditNote),
        currentCreditNote:
          get().currentCreditNote?.id === id
            ? creditNote
            : get().currentCreditNote,
        isLoading: false,
      });

      return creditNote;
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  deleteCreditNote: async (id) => {
    set({ isLoading: true, error: null });
    try {
      await apiClient.delete(`/credit-notes/${id}`);

      set({
        creditNotes: get().creditNotes.filter((note) => note.id !== id),
        currentCreditNote:
          get().currentCreditNote?.id === id ? null : get().currentCreditNote,
        isLoading: false,
      });
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  sendCreditNote: async (id) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<any>(`/credit-notes/${id}/send`);
      const creditNote = response.data as CreditNote;

      set({
        creditNotes: upsertCreditNote(get().creditNotes, creditNote),
        currentCreditNote:
          get().currentCreditNote?.id === id
            ? creditNote
            : get().currentCreditNote,
        isLoading: false,
      });

      return creditNote;
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  applyCreditNote: async (id) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<any>(`/credit-notes/${id}/apply`);
      const creditNote = response.data as CreditNote;

      set({
        creditNotes: upsertCreditNote(get().creditNotes, creditNote),
        currentCreditNote:
          get().currentCreditNote?.id === id
            ? creditNote
            : get().currentCreditNote,
        isLoading: false,
      });

      return creditNote;
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },
}));
