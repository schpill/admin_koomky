import { create } from "zustand";
import { apiClient } from "@/lib/api";

export type InvoiceStatus =
  | "draft"
  | "sent"
  | "viewed"
  | "paid"
  | "partially_paid"
  | "overdue"
  | "cancelled";

export interface InvoiceLineItemInput {
  description: string;
  quantity: number;
  unit_price: number;
  vat_rate: number;
}

export interface InvoicePayment {
  id: string;
  amount: number;
  payment_date: string;
  payment_method?: string | null;
  reference?: string | null;
  notes?: string | null;
}

export interface Invoice {
  id: string;
  user_id?: string;
  client_id: string;
  project_id?: string | null;
  recurring_invoice_profile_id?: string | null;
  number: string;
  status: InvoiceStatus;
  issue_date: string;
  due_date: string;
  subtotal: number;
  tax_amount: number;
  discount_type?: "percentage" | "fixed" | null;
  discount_value?: number | null;
  discount_amount?: number;
  total: number;
  amount_paid: number;
  balance_due?: number;
  currency?: string;
  base_currency?: string;
  exchange_rate?: number | null;
  base_currency_total?: number | null;
  notes?: string | null;
  payment_terms?: string | null;
  pdf_path?: string | null;
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
  payments?: InvoicePayment[];
  credit_notes?: Array<{
    id: string;
    number: string;
    status: string;
    total: number;
    issue_date: string;
  }>;
}

export interface InvoicingSettings {
  payment_terms_days: number;
  bank_details: string | null;
  invoice_footer: string | null;
  invoice_numbering_pattern: string;
}

interface Pagination {
  current_page: number;
  last_page: number;
  total: number;
  per_page: number;
}

export interface InvoicePaymentPayload {
  amount: number;
  payment_date: string;
  payment_method?: string;
  reference?: string;
  notes?: string;
}

interface InvoiceState {
  invoices: Invoice[];
  currentInvoice: Invoice | null;
  pagination: Pagination | null;
  invoicingSettings: InvoicingSettings | null;
  isLoading: boolean;
  error: string | null;

  fetchInvoices: (params?: Record<string, unknown>) => Promise<void>;
  fetchInvoice: (id: string) => Promise<Invoice | null>;
  createInvoice: (data: Record<string, unknown>) => Promise<Invoice | null>;
  updateInvoice: (
    id: string,
    data: Record<string, unknown>
  ) => Promise<Invoice | null>;
  deleteInvoice: (id: string) => Promise<void>;
  sendInvoice: (id: string) => Promise<Invoice | null>;
  duplicateInvoice: (id: string) => Promise<Invoice | null>;
  recordPayment: (
    id: string,
    data: InvoicePaymentPayload
  ) => Promise<Invoice | null>;

  fetchInvoicingSettings: () => Promise<void>;
  updateInvoicingSettings: (data: InvoicingSettings) => Promise<void>;
}

function upsertInvoice(list: Invoice[], invoice: Invoice): Invoice[] {
  const index = list.findIndex((item) => item.id === invoice.id);
  if (index === -1) {
    return [invoice, ...list];
  }

  const next = [...list];
  next[index] = invoice;

  return next;
}

export const useInvoiceStore = create<InvoiceState>((set, get) => ({
  invoices: [],
  currentInvoice: null,
  pagination: null,
  invoicingSettings: null,
  isLoading: false,
  error: null,

  fetchInvoices: async (params = {}) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<any>("/invoices", { params });
      const payload = response.data || {};

      set({
        invoices: payload.data || [],
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

  fetchInvoice: async (id) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<any>(`/invoices/${id}`);
      const invoice = response.data as Invoice;

      set({
        currentInvoice: invoice,
        invoices: upsertInvoice(get().invoices, invoice),
        isLoading: false,
      });

      return invoice;
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  createInvoice: async (data) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<any>("/invoices", data);
      const invoice = response.data as Invoice;

      set({
        invoices: upsertInvoice(get().invoices, invoice),
        currentInvoice: invoice,
        isLoading: false,
      });

      return invoice;
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  updateInvoice: async (id, data) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.put<any>(`/invoices/${id}`, data);
      const invoice = response.data as Invoice;

      set({
        invoices: upsertInvoice(get().invoices, invoice),
        currentInvoice:
          get().currentInvoice?.id === id ? invoice : get().currentInvoice,
        isLoading: false,
      });

      return invoice;
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  deleteInvoice: async (id) => {
    set({ isLoading: true, error: null });
    try {
      await apiClient.delete(`/invoices/${id}`);

      set({
        invoices: get().invoices.filter((invoice) => invoice.id !== id),
        currentInvoice:
          get().currentInvoice?.id === id ? null : get().currentInvoice,
        isLoading: false,
      });
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  sendInvoice: async (id) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<any>(`/invoices/${id}/send`);
      const invoice = response.data as Invoice;

      set({
        invoices: upsertInvoice(get().invoices, invoice),
        currentInvoice:
          get().currentInvoice?.id === id ? invoice : get().currentInvoice,
        isLoading: false,
      });

      return invoice;
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  duplicateInvoice: async (id) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<any>(`/invoices/${id}/duplicate`);
      const invoice = response.data as Invoice;

      set({
        invoices: upsertInvoice(get().invoices, invoice),
        isLoading: false,
      });

      return invoice;
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  recordPayment: async (id, data) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<any>(
        `/invoices/${id}/payments`,
        data
      );
      const invoice = response.data as Invoice;

      set({
        invoices: upsertInvoice(get().invoices, invoice),
        currentInvoice:
          get().currentInvoice?.id === id ? invoice : get().currentInvoice,
        isLoading: false,
      });

      return invoice;
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  fetchInvoicingSettings: async () => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<any>("/settings/invoicing");
      set({
        invoicingSettings: response.data as InvoicingSettings,
        isLoading: false,
      });
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  updateInvoicingSettings: async (data) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.put<any>("/settings/invoicing", data);
      set({
        invoicingSettings: response.data as InvoicingSettings,
        isLoading: false,
      });
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },
}));
