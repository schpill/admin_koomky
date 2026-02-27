import { beforeEach, describe, expect, it, vi } from "vitest";
import { useProductsStore } from "@/lib/stores/products";

describe("useProductsStore", () => {
  beforeEach(() => {
    vi.restoreAllMocks();
    useProductsStore.getState().reset();
  });

  it("fetches products and updates pagination", async () => {
    const fetchMock = vi.spyOn(global, "fetch" as any).mockResolvedValue({
      ok: true,
      json: async () => ({
        data: [
          {
            id: "prod_1",
            name: "Formation Laravel",
            slug: "formation-laravel",
            type: "training",
            price: 1200,
            price_type: "fixed",
            currency_code: "EUR",
            vat_rate: 20,
            is_active: true,
            created_at: "2026-01-01T00:00:00Z",
            updated_at: "2026-01-01T00:00:00Z",
          },
        ],
        meta: {
          current_page: 1,
          last_page: 1,
          per_page: 15,
          total: 1,
        },
      }),
    } as Response);

    await useProductsStore.getState().fetchProducts();

    const state = useProductsStore.getState();
    expect(fetchMock).toHaveBeenCalledWith("/api/v1/products?page=1");
    expect(state.products).toHaveLength(1);
    expect(state.products[0].id).toBe("prod_1");
    expect(state.pagination.total).toBe(1);
    expect(state.error).toBeNull();
  });

  it("supports fetchProducts with picker-style options", async () => {
    const fetchMock = vi.spyOn(global, "fetch" as any).mockResolvedValue({
      ok: true,
      json: async () => ({
        data: [],
        meta: {
          current_page: 1,
          last_page: 1,
          per_page: 15,
          total: 0,
        },
      }),
    } as Response);

    await useProductsStore.getState().fetchProducts({ isActive: true, page: 1 });

    expect(fetchMock).toHaveBeenCalledWith("/api/v1/products?page=1&is_active=true");
  });

  it("creates a product and refreshes the list", async () => {
    const fetchMock = vi.spyOn(global, "fetch" as any);

    fetchMock
      .mockResolvedValueOnce({
        ok: true,
        json: async () => ({
          data: {
            id: "prod_new",
            name: "Audit API",
            slug: "audit-api",
            type: "service",
            price: 500,
            price_type: "fixed",
            currency_code: "EUR",
            vat_rate: 20,
            is_active: true,
            created_at: "2026-01-01T00:00:00Z",
            updated_at: "2026-01-01T00:00:00Z",
          },
        }),
      } as Response)
      .mockResolvedValueOnce({
        ok: true,
        json: async () => ({
          data: [],
          meta: {
            current_page: 1,
            last_page: 1,
            per_page: 15,
            total: 0,
          },
        }),
      } as Response);

    const product = await useProductsStore.getState().createProduct({
      name: "Audit API",
      type: "service",
      price: 500,
      price_type: "fixed",
      currency_code: "EUR",
      vat_rate: 20,
      is_active: true,
    });

    expect(product.id).toBe("prod_new");
    expect(fetchMock).toHaveBeenNthCalledWith(
      1,
      "/api/v1/products",
      expect.objectContaining({ method: "POST" })
    );
    expect(fetchMock).toHaveBeenNthCalledWith(2, "/api/v1/products?page=1");
  });

  it("sets error when campaign generation fails", async () => {
    vi.spyOn(global, "fetch" as any).mockResolvedValue({
      ok: false,
      json: async () => ({}),
    } as Response);

    await expect(
      useProductsStore.getState().generateCampaign("prod_1", "seg_1")
    ).rejects.toThrow("Failed to generate campaign");

    expect(useProductsStore.getState().error).toBe("Failed to generate campaign");
  });
});
