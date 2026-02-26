import { create } from "zustand";
import { persist } from "zustand/middleware";

export interface Product {
  id: string;
  name: string;
  slug: string;
  type: "service" | "training" | "product" | "subscription";
  description?: string;
  short_description?: string;
  price: number;
  price_type: "fixed" | "hourly" | "daily" | "per_unit";
  currency_code: string;
  vat_rate: number;
  duration?: number;
  duration_unit?: "hours" | "days" | "weeks" | "months";
  sku?: string;
  tags?: string[];
  is_active: boolean;
  created_at: string;
  updated_at: string;
  deleted_at?: string;

  // Relations
  sales_count?: number;
  campaigns_count?: number;
}

export interface ProductSale {
  id: string;
  quantity: number;
  unit_price: number;
  total_price: number;
  currency_code: string;
  status: "pending" | "confirmed" | "delivered" | "cancelled" | "refunded";
  sold_at?: string;

  // Relations
  client?: {
    id: string;
    name: string;
  };
  invoice?: {
    id: string;
    number: string;
  };
  quote?: {
    id: string;
    reference: string;
  };
}

export interface ProductAnalytics {
  total_revenue: number;
  total_sales: number;
  avg_order_value: number;
  conversion_rate: number;
  monthly_breakdown: { month: string; revenue: number }[];
}

interface ProductsState {
  // Products
  products: Product[];
  selectedProduct: Product | null;
  isLoading: boolean;
  error: string | null;

  // Filters & Pagination
  filters: {
    type?: Product["type"];
    is_active?: boolean;
    search?: string;
  };
  pagination: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };

  // Sales
  productSales: ProductSale[];
  salesLoading: boolean;

  // Analytics
  analytics: ProductAnalytics | null;
  globalAnalytics: {
    top_products: Array<{
      id: string;
      name: string;
      revenue: number;
      sales_count: number;
    }>;
    total_revenue: number;
    total_sales: number;
  } | null;
  analyticsLoading: boolean;
}

interface ProductsActions {
  // Products
  fetchProducts: (page?: number) => Promise<void>;
  createProduct: (data: Partial<Product>) => Promise<Product>;
  updateProduct: (id: string, data: Partial<Product>) => Promise<Product>;
  deleteProduct: (id: string) => Promise<void>;
  restoreProduct: (id: string) => Promise<Product>;
  setSelectedProduct: (product: Product | null) => void;

  // Filters
  setFilters: (filters: Partial<ProductsState["filters"]>) => void;
  clearFilters: () => void;

  // Sales
  fetchProductSales: (productId: string, page?: number) => Promise<void>;

  // Analytics
  fetchProductAnalytics: (productId: string) => Promise<void>;
  fetchGlobalAnalytics: () => Promise<void>;

  // Campaigns
  generateCampaign: (
    productId: string,
    segmentId: string
  ) => Promise<{
    id: string;
    name: string;
    status: string;
  }>;

  // State management
  setLoading: (loading: boolean) => void;
  setError: (error: string | null) => void;
  reset: () => void;
}

type ProductsStore = ProductsState & ProductsActions;

const initialState: ProductsState = {
  products: [],
  selectedProduct: null,
  isLoading: false,
  error: null,

  filters: {},
  pagination: {
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
  },

  productSales: [],
  salesLoading: false,

  analytics: null,
  globalAnalytics: null,
  analyticsLoading: false,
};

export const useProductsStore = create<ProductsStore>()(
  persist(
    (set, get) => ({
      ...initialState,

      fetchProducts: async (page = 1) => {
        const { filters } = get();
        set({ isLoading: true, error: null });

        try {
          const params = new URLSearchParams({
            page: page.toString(),
            ...(filters.type && { type: filters.type }),
            ...(filters.is_active !== undefined && {
              is_active: filters.is_active.toString(),
            }),
            ...(filters.search && { search: filters.search }),
          });

          const response = await fetch(`/api/v1/products?${params}`);
          if (!response.ok) throw new Error("Failed to fetch products");

          const { data, meta } = await response.json();

          set({
            products: data,
            pagination: meta,
            isLoading: false,
          });
        } catch (error) {
          set({
            error: error instanceof Error ? error.message : "Unknown error",
            isLoading: false,
          });
        }
      },

      createProduct: async (data) => {
        set({ isLoading: true, error: null });

        try {
          const response = await fetch("/api/v1/products", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data),
          });

          if (!response.ok) throw new Error("Failed to create product");

          const { data: product } = await response.json();

          // Refresh products list
          get().fetchProducts();

          set({ isLoading: false });
          return product;
        } catch (error) {
          const errorMessage =
            error instanceof Error ? error.message : "Unknown error";
          set({ error: errorMessage, isLoading: false });
          throw error;
        }
      },

      updateProduct: async (id, data) => {
        set({ isLoading: true, error: null });

        try {
          const response = await fetch(`/api/v1/products/${id}`, {
            method: "PATCH",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data),
          });

          if (!response.ok) throw new Error("Failed to update product");

          const { data: product } = await response.json();

          // Update in products list
          set((state) => ({
            products: state.products.map((p) => (p.id === id ? product : p)),
            selectedProduct:
              state.selectedProduct?.id === id
                ? product
                : state.selectedProduct,
            isLoading: false,
          }));

          return product;
        } catch (error) {
          const errorMessage =
            error instanceof Error ? error.message : "Unknown error";
          set({ error: errorMessage, isLoading: false });
          throw error;
        }
      },

      deleteProduct: async (id) => {
        set({ isLoading: true, error: null });

        try {
          const response = await fetch(`/api/v1/products/${id}`, {
            method: "DELETE",
          });

          if (!response.ok) throw new Error("Failed to delete product");

          // Remove from products list
          set((state) => ({
            products: state.products.filter((p) => p.id !== id),
            selectedProduct:
              state.selectedProduct?.id === id ? null : state.selectedProduct,
            isLoading: false,
          }));
        } catch (error) {
          const errorMessage =
            error instanceof Error ? error.message : "Unknown error";
          set({ error: errorMessage, isLoading: false });
          throw error;
        }
      },

      restoreProduct: async (id) => {
        set({ isLoading: true, error: null });

        try {
          const response = await fetch(`/api/v1/products/${id}/restore`, {
            method: "POST",
          });

          if (!response.ok) throw new Error("Failed to restore product");

          const { data: product } = await response.json();

          // Add back to products list
          set((state) => ({
            products: [...state.products, product],
            selectedProduct:
              state.selectedProduct?.id === id
                ? product
                : state.selectedProduct,
            isLoading: false,
          }));

          return product;
        } catch (error) {
          const errorMessage =
            error instanceof Error ? error.message : "Unknown error";
          set({ error: errorMessage, isLoading: false });
          throw error;
        }
      },

      setSelectedProduct: (product) => set({ selectedProduct: product }),

      setFilters: (filters) => {
        set((state) => ({
          filters: { ...state.filters, ...filters },
          pagination: { ...state.pagination, current_page: 1 }, // Reset pagination
        }));
        get().fetchProducts(1); // Re-fetch with new filters
      },

      clearFilters: () => {
        set({ filters: {} });
        get().fetchProducts(1);
      },

      fetchProductSales: async (productId, page = 1) => {
        set({ salesLoading: true, error: null });

        try {
          const response = await fetch(
            `/api/v1/products/${productId}/sales?page=${page}`
          );
          if (!response.ok) throw new Error("Failed to fetch product sales");

          const { data } = await response.json();

          set({
            productSales: data,
            salesLoading: false,
          });
        } catch (error) {
          set({
            error: error instanceof Error ? error.message : "Unknown error",
            salesLoading: false,
          });
        }
      },

      fetchProductAnalytics: async (productId) => {
        set({ analyticsLoading: true, error: null });

        try {
          const response = await fetch(
            `/api/v1/products/${productId}/analytics`
          );
          if (!response.ok)
            throw new Error("Failed to fetch product analytics");

          const { data } = await response.json();

          set({
            analytics: data,
            analyticsLoading: false,
          });
        } catch (error) {
          set({
            error: error instanceof Error ? error.message : "Unknown error",
            analyticsLoading: false,
          });
        }
      },

      fetchGlobalAnalytics: async () => {
        set({ analyticsLoading: true, error: null });

        try {
          const response = await fetch("/api/v1/products/analytics");
          if (!response.ok) throw new Error("Failed to fetch global analytics");

          const { data } = await response.json();

          set({
            globalAnalytics: data,
            analyticsLoading: false,
          });
        } catch (error) {
          set({
            error: error instanceof Error ? error.message : "Unknown error",
            analyticsLoading: false,
          });
        }
      },

      generateCampaign: async (productId, segmentId) => {
        set({ isLoading: true, error: null });

        try {
          const response = await fetch(
            `/api/v1/products/${productId}/campaigns/generate`,
            {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({ segment_id: segmentId }),
            }
          );

          if (!response.ok) throw new Error("Failed to generate campaign");

          const { data } = await response.json();

          set({ isLoading: false });
          return data;
        } catch (error) {
          const errorMessage =
            error instanceof Error ? error.message : "Unknown error";
          set({ error: errorMessage, isLoading: false });
          throw error;
        }
      },

      setLoading: (isLoading) => set({ isLoading }),
      setError: (error) => set({ error }),
      reset: () => set(initialState),
    }),
    {
      name: "products-store",
      partialize: (state) => ({
        filters: state.filters,
        pagination: state.pagination,
      }),
    }
  )
);
