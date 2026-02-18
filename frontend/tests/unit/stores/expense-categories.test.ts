import { beforeEach, describe, expect, it, vi } from "vitest";
import { useExpenseCategoryStore } from "@/lib/stores/expense-categories";

vi.mock("@/lib/api", () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
  },
}));

import { apiClient } from "@/lib/api";

describe("useExpenseCategoryStore", () => {
  beforeEach(() => {
    useExpenseCategoryStore.setState({
      categories: [],
      isLoading: false,
      error: null,
    });
    vi.clearAllMocks();
  });

  it("fetches categories", async () => {
    (apiClient.get as any).mockResolvedValue({
      data: [{ id: "cat_1", name: "Travel", is_default: false }],
    });

    await useExpenseCategoryStore.getState().fetchCategories();

    expect(useExpenseCategoryStore.getState().categories).toHaveLength(1);
    expect(useExpenseCategoryStore.getState().categories[0].name).toBe(
      "Travel"
    );
  });

  it("creates updates and deletes category", async () => {
    (apiClient.post as any).mockResolvedValue({
      data: { id: "cat_1", name: "Software", is_default: false },
    });

    await useExpenseCategoryStore.getState().createCategory({
      name: "Software",
      color: "#ffffff",
      icon: "code",
    });

    expect(useExpenseCategoryStore.getState().categories).toHaveLength(1);

    (apiClient.put as any).mockResolvedValue({
      data: { id: "cat_1", name: "Software + Tools", is_default: false },
    });

    await useExpenseCategoryStore.getState().updateCategory("cat_1", {
      name: "Software + Tools",
    });

    expect(useExpenseCategoryStore.getState().categories[0].name).toBe(
      "Software + Tools"
    );

    (apiClient.delete as any).mockResolvedValue({});

    await useExpenseCategoryStore.getState().deleteCategory("cat_1");

    expect(useExpenseCategoryStore.getState().categories).toEqual([]);
  });
});
