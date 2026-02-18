import { create } from "zustand";
import { apiClient } from "@/lib/api";

export interface ExpenseCategory {
  id: string;
  user_id?: string;
  name: string;
  color?: string | null;
  icon?: string | null;
  is_default: boolean;
  created_at?: string;
  updated_at?: string;
}

interface ExpenseCategoryState {
  categories: ExpenseCategory[];
  isLoading: boolean;
  error: string | null;

  fetchCategories: () => Promise<void>;
  createCategory: (payload: {
    name: string;
    color?: string;
    icon?: string;
  }) => Promise<ExpenseCategory | null>;
  updateCategory: (
    id: string,
    payload: {
      name: string;
      color?: string;
      icon?: string;
    }
  ) => Promise<ExpenseCategory | null>;
  deleteCategory: (id: string) => Promise<void>;
}

export const useExpenseCategoryStore = create<ExpenseCategoryState>((set, get) => ({
  categories: [],
  isLoading: false,
  error: null,

  fetchCategories: async () => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<ExpenseCategory[]>(
        "/expense-categories"
      );
      set({ categories: response.data || [], isLoading: false });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
    }
  },

  createCategory: async (payload) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<ExpenseCategory>(
        "/expense-categories",
        payload
      );
      const created = response.data;
      set({ categories: [created, ...get().categories], isLoading: false });
      return created;
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  updateCategory: async (id, payload) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.put<ExpenseCategory>(
        `/expense-categories/${id}`,
        payload
      );
      const updated = response.data;
      set({
        categories: get().categories.map((category) =>
          category.id === id ? updated : category
        ),
        isLoading: false,
      });
      return updated;
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  deleteCategory: async (id) => {
    set({ isLoading: true, error: null });
    try {
      await apiClient.delete(`/expense-categories/${id}`);
      set({
        categories: get().categories.filter((category) => category.id !== id),
        isLoading: false,
      });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },
}));
