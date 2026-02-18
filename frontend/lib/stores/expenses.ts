import { create } from "zustand";
import { apiClient } from "@/lib/api";
import { useAuthStore } from "@/lib/stores/auth";

export interface Expense {
  id: string;
  user_id?: string;
  expense_category_id: string;
  project_id?: string | null;
  client_id?: string | null;
  description: string;
  amount: number;
  currency: string;
  base_currency_amount?: number | null;
  tax_amount?: number;
  tax_rate?: number | null;
  date: string;
  payment_method: "cash" | "card" | "bank_transfer" | "other";
  is_billable: boolean;
  is_reimbursable: boolean;
  reimbursed_at?: string | null;
  vendor?: string | null;
  reference?: string | null;
  notes?: string | null;
  receipt_path?: string | null;
  receipt_filename?: string | null;
  receipt_mime_type?: string | null;
  status: "pending" | "approved" | "rejected";
  category?: {
    id: string;
    name: string;
    color?: string | null;
    icon?: string | null;
  } | null;
  project?: {
    id: string;
    name: string;
    reference?: string;
  } | null;
  client?: {
    id: string;
    name: string;
  } | null;
}

export interface ExpenseReport {
  filters: Record<string, unknown>;
  base_currency: string;
  total_expenses: number;
  tax_total: number;
  count: number;
  billable_split: {
    billable: number;
    non_billable: number;
  };
  by_category: Array<{ category: string; total: number; count: number }>;
  by_project: Array<{
    project_reference: string;
    project_name?: string | null;
    total: number;
    count: number;
  }>;
  by_month: Array<{ month: string; total: number }>;
  items: Array<Record<string, unknown>>;
}

interface Pagination {
  current_page: number;
  last_page: number;
  total: number;
  per_page: number;
}

interface ExpenseState {
  expenses: Expense[];
  currentExpense: Expense | null;
  report: ExpenseReport | null;
  pagination: Pagination | null;
  isLoading: boolean;
  error: string | null;

  fetchExpenses: (params?: Record<string, unknown>) => Promise<void>;
  fetchExpense: (id: string) => Promise<Expense | null>;
  createExpense: (payload: Record<string, unknown>) => Promise<Expense | null>;
  updateExpense: (
    id: string,
    payload: Record<string, unknown>
  ) => Promise<Expense | null>;
  deleteExpense: (id: string) => Promise<void>;
  uploadReceipt: (id: string, file: File) => Promise<Expense | null>;
  fetchReport: (params?: Record<string, unknown>) => Promise<void>;
  exportReport: (params?: Record<string, unknown>) => Promise<Blob>;
}

function baseApiUrl(): string {
  return process.env.NEXT_PUBLIC_API_URL || "http://localhost/api/v1";
}

export const useExpenseStore = create<ExpenseState>((set, get) => ({
  expenses: [],
  currentExpense: null,
  report: null,
  pagination: null,
  isLoading: false,
  error: null,

  fetchExpenses: async (params = {}) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<any>("/expenses", { params });
      const payload = response.data || {};
      set({
        expenses: payload.data || [],
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

  fetchExpense: async (id) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<Expense>(`/expenses/${id}`);
      const expense = response.data;
      set({
        currentExpense: expense,
        expenses: get().expenses.map((item) => (item.id === id ? expense : item)),
        isLoading: false,
      });
      return expense;
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  createExpense: async (payload) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<Expense>("/expenses", payload);
      const created = response.data;
      set({ expenses: [created, ...get().expenses], isLoading: false });
      return created;
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  updateExpense: async (id, payload) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.put<Expense>(`/expenses/${id}`, payload);
      const updated = response.data;
      set({
        expenses: get().expenses.map((item) => (item.id === id ? updated : item)),
        currentExpense: get().currentExpense?.id === id ? updated : get().currentExpense,
        isLoading: false,
      });
      return updated;
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  deleteExpense: async (id) => {
    set({ isLoading: true, error: null });
    try {
      await apiClient.delete(`/expenses/${id}`);
      set({
        expenses: get().expenses.filter((item) => item.id !== id),
        currentExpense: get().currentExpense?.id === id ? null : get().currentExpense,
        isLoading: false,
      });
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  uploadReceipt: async (id, file) => {
    set({ isLoading: true, error: null });

    const accessToken = useAuthStore.getState().accessToken;
    if (!accessToken) {
      const err = new Error("Authentication required");
      set({ isLoading: false, error: err.message });
      throw err;
    }

    const formData = new FormData();
    formData.append("receipt", file);

    try {
      const response = await fetch(`${baseApiUrl()}/expenses/${id}/receipt`, {
        method: "POST",
        headers: {
          Accept: "application/json",
          Authorization: `Bearer ${accessToken}`,
        },
        body: formData,
      });

      const payload = (await response.json().catch(() => ({}))) as {
        message?: string;
        data?: Expense;
      };

      if (!response.ok || !payload.data) {
        throw new Error(payload.message || "Receipt upload failed");
      }

      const expense = payload.data;
      set({
        expenses: get().expenses.map((item) => (item.id === id ? expense : item)),
        currentExpense: get().currentExpense?.id === id ? expense : get().currentExpense,
        isLoading: false,
      });

      return expense;
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  fetchReport: async (params = {}) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<ExpenseReport>("/expenses/report", {
        params,
      });
      set({ report: response.data, isLoading: false });
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  exportReport: async (params = {}) => {
    const query = new URLSearchParams(
      Object.entries(params)
        .filter(([, value]) => value !== undefined && value !== null && value !== "")
        .map(([key, value]) => [key, String(value)])
    );

    const accessToken = useAuthStore.getState().accessToken;
    if (!accessToken) {
      throw new Error("Authentication required");
    }

    const response = await fetch(
      `${baseApiUrl()}/expenses/report/export${query.toString() ? `?${query.toString()}` : ""}`,
      {
        headers: {
          Accept: "text/csv",
          Authorization: `Bearer ${accessToken}`,
        },
      }
    );

    if (!response.ok) {
      throw new Error("Unable to export expenses report");
    }

    return response.blob();
  },
}));
