import { create } from "zustand";
import { apiClient } from "@/lib/api";

export type QuoteStatus =
  | "draft"
  | "sent"
  | "accepted"
  | "rejected"
  | "expired";

export interface QuoteLineItemInput {
  description: string;
  quantity: number;
  unit_price: number;
  vat_rate: number;
}

export interface Quote {
  id: string;
  user_id?: string;
  client_id: string;
  project_id?: string | null;
  converted_invoice_id?: string | null;
  number: string;
  status: QuoteStatus;
  issue_date: string;
  valid_until: string;
  subtotal: number;
  tax_amount: number;
  discount_type?: "percentage" | "fixed" | null;
  discount_value?: number | null;
  discount_amount?: number;
  total: number;
  currency?: string;
  base_currency?: string;
  exchange_rate?: number | null;
  base_currency_total?: number | null;
  notes?: string | null;
  pdf_path?: string | null;
  sent_at?: string | null;
  accepted_at?: string | null;
  client?: {
    id: string;
    name: string;
    email?: string | null;
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

interface QuoteState {
  quotes: Quote[];
  currentQuote: Quote | null;
  pagination: Pagination | null;
  isLoading: boolean;
  error: string | null;

  fetchQuotes: (params?: Record<string, unknown>) => Promise<void>;
  fetchQuote: (id: string) => Promise<Quote | null>;
  createQuote: (data: Record<string, unknown>) => Promise<Quote | null>;
  updateQuote: (
    id: string,
    data: Record<string, unknown>
  ) => Promise<Quote | null>;
  deleteQuote: (id: string) => Promise<void>;
  sendQuote: (id: string) => Promise<Quote | null>;
  acceptQuote: (id: string) => Promise<Quote | null>;
  rejectQuote: (id: string) => Promise<Quote | null>;
  convertQuote: (id: string) => Promise<{ id: string } | null>;
}

function upsertQuote(list: Quote[], quote: Quote): Quote[] {
  const index = list.findIndex((item) => item.id === quote.id);
  if (index === -1) {
    return [quote, ...list];
  }

  const next = [...list];
  next[index] = quote;

  return next;
}

export const useQuoteStore = create<QuoteState>((set, get) => ({
  quotes: [],
  currentQuote: null,
  pagination: null,
  isLoading: false,
  error: null,

  fetchQuotes: async (params = {}) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<any>("/quotes", { params });
      const payload = response.data || {};

      set({
        quotes: payload.data || [],
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

  fetchQuote: async (id) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<any>(`/quotes/${id}`);
      const quote = response.data as Quote;

      set({
        currentQuote: quote,
        quotes: upsertQuote(get().quotes, quote),
        isLoading: false,
      });

      return quote;
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  createQuote: async (data) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<any>("/quotes", data);
      const quote = response.data as Quote;

      set({
        quotes: upsertQuote(get().quotes, quote),
        currentQuote: quote,
        isLoading: false,
      });

      return quote;
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  updateQuote: async (id, data) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.put<any>(`/quotes/${id}`, data);
      const quote = response.data as Quote;

      set({
        quotes: upsertQuote(get().quotes, quote),
        currentQuote:
          get().currentQuote?.id === id ? quote : get().currentQuote,
        isLoading: false,
      });

      return quote;
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  deleteQuote: async (id) => {
    set({ isLoading: true, error: null });
    try {
      await apiClient.delete(`/quotes/${id}`);

      set({
        quotes: get().quotes.filter((quote) => quote.id !== id),
        currentQuote: get().currentQuote?.id === id ? null : get().currentQuote,
        isLoading: false,
      });
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  sendQuote: async (id) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<any>(`/quotes/${id}/send`);
      const quote = response.data as Quote;

      set({
        quotes: upsertQuote(get().quotes, quote),
        currentQuote:
          get().currentQuote?.id === id ? quote : get().currentQuote,
        isLoading: false,
      });

      return quote;
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  acceptQuote: async (id) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<any>(`/quotes/${id}/accept`);
      const quote = response.data as Quote;

      set({
        quotes: upsertQuote(get().quotes, quote),
        currentQuote:
          get().currentQuote?.id === id ? quote : get().currentQuote,
        isLoading: false,
      });

      return quote;
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  rejectQuote: async (id) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<any>(`/quotes/${id}/reject`);
      const quote = response.data as Quote;

      set({
        quotes: upsertQuote(get().quotes, quote),
        currentQuote:
          get().currentQuote?.id === id ? quote : get().currentQuote,
        isLoading: false,
      });

      return quote;
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  convertQuote: async (id) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<any>(`/quotes/${id}/convert`);
      const invoice = response.data as { id: string };

      const current = get().currentQuote;
      const updatedCurrent =
        current && current.id === id
          ? {
              ...current,
              status: "accepted" as QuoteStatus,
              converted_invoice_id: invoice.id,
            }
          : current;

      set({
        currentQuote: updatedCurrent,
        quotes: get().quotes.map((quote) =>
          quote.id === id
            ? {
                ...quote,
                status: "accepted",
                converted_invoice_id: invoice.id,
              }
            : quote
        ),
        isLoading: false,
      });

      return invoice;
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },
}));
