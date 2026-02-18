import { beforeEach, describe, expect, it, vi } from "vitest";
import { useExpenseStore } from "@/lib/stores/expenses";
import { useAuthStore } from "@/lib/stores/auth";

vi.mock("@/lib/api", () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
  },
}));

import { apiClient } from "@/lib/api";

describe("useExpenseStore", () => {
  beforeEach(() => {
    useExpenseStore.setState({
      expenses: [],
      currentExpense: null,
      report: null,
      pagination: null,
      isLoading: false,
      error: null,
    });
    useAuthStore.setState({ accessToken: "token", refreshToken: null } as any);
    vi.clearAllMocks();
  });

  it("fetches expenses and pagination", async () => {
    (apiClient.get as any).mockResolvedValue({
      data: {
        data: [{ id: "exp_1", description: "Travel", amount: 50 }],
        current_page: 1,
        last_page: 1,
        total: 1,
        per_page: 15,
      },
    });

    await useExpenseStore.getState().fetchExpenses();

    const state = useExpenseStore.getState();
    expect(state.expenses).toHaveLength(1);
    expect(state.pagination?.total).toBe(1);
  });

  it("creates updates and deletes expense", async () => {
    (apiClient.post as any).mockResolvedValue({
      data: {
        id: "exp_1",
        description: "Travel",
        amount: 100,
        currency: "EUR",
      },
    });

    const created = await useExpenseStore.getState().createExpense({
      description: "Travel",
      amount: 100,
    });
    expect(created?.id).toBe("exp_1");

    (apiClient.put as any).mockResolvedValue({
      data: {
        id: "exp_1",
        description: "Travel + meal",
        amount: 120,
        currency: "EUR",
      },
    });

    const updated = await useExpenseStore.getState().updateExpense("exp_1", {
      description: "Travel + meal",
      amount: 120,
    });

    expect(updated?.description).toBe("Travel + meal");

    (apiClient.delete as any).mockResolvedValue({});
    await useExpenseStore.getState().deleteExpense("exp_1");

    expect(useExpenseStore.getState().expenses).toEqual([]);
  });

  it("fetches expense report", async () => {
    (apiClient.get as any).mockResolvedValue({
      data: {
        base_currency: "EUR",
        total_expenses: 240,
        tax_total: 40,
        count: 2,
        billable_split: { billable: 150, non_billable: 90 },
        by_category: [],
        by_project: [],
        by_month: [],
        items: [],
      },
    });

    await useExpenseStore.getState().fetchReport({
      date_from: "2026-01-01",
      date_to: "2026-01-31",
    });

    expect(useExpenseStore.getState().report?.total_expenses).toBe(240);
  });

  it("uploads receipt", async () => {
    const fetchMock = vi.fn().mockResolvedValue({
      ok: true,
      json: async () => ({
        data: {
          id: "exp_1",
          description: "Travel",
          amount: 100,
          currency: "EUR",
          receipt_path: "receipts/file.jpg",
        },
      }),
    });

    global.fetch = fetchMock as any;

    useExpenseStore.setState({
      expenses: [
        {
          id: "exp_1",
          description: "Travel",
          amount: 100,
          currency: "EUR",
        } as any,
      ],
      currentExpense: null,
    } as any);

    const file = new File(["abc"], "receipt.jpg", { type: "image/jpeg" });

    const expense = await useExpenseStore.getState().uploadReceipt("exp_1", file);

    expect(fetchMock).toHaveBeenCalledTimes(1);
    expect(expense?.receipt_path).toBe("receipts/file.jpg");
  });
});
