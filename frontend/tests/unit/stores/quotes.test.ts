import { describe, it, expect, beforeEach, vi } from "vitest";
import { useQuoteStore } from "@/lib/stores/quotes";

vi.mock("@/lib/api", () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
  },
}));

import { apiClient } from "@/lib/api";

describe("useQuoteStore", () => {
  const baseQuote = {
    id: "q1",
    client_id: "cli_1",
    number: "DEV-2026-0001",
    status: "draft",
    issue_date: "2026-02-16",
    valid_until: "2026-03-18",
    subtotal: 100,
    tax_amount: 20,
    total: 120,
  };

  beforeEach(() => {
    useQuoteStore.setState({
      quotes: [],
      currentQuote: null,
      pagination: null,
      isLoading: false,
      error: null,
    });
    vi.clearAllMocks();
  });

  it("fetches quotes with pagination", async () => {
    (apiClient.get as any).mockResolvedValue({
      data: {
        data: [{ ...baseQuote }],
        current_page: 1,
        last_page: 2,
        per_page: 15,
        total: 16,
      },
    });

    await useQuoteStore.getState().fetchQuotes({ status: "draft" });

    const state = useQuoteStore.getState();
    expect(state.quotes).toHaveLength(1);
    expect(state.pagination?.total).toBe(16);
  });

  it("uses fallback values when quote list payload is empty", async () => {
    (apiClient.get as any).mockResolvedValue({ data: null });

    await useQuoteStore.getState().fetchQuotes();

    expect(useQuoteStore.getState().quotes).toEqual([]);
    expect(useQuoteStore.getState().pagination).toEqual({
      current_page: 1,
      last_page: 1,
      total: 0,
      per_page: 15,
    });
  });

  it("creates updates sends accepts rejects converts and deletes quote", async () => {
    (apiClient.post as any).mockResolvedValueOnce({
      data: { ...baseQuote, id: "q1", status: "draft" },
    });

    const created = await useQuoteStore.getState().createQuote({
      client_id: "cli_1",
      issue_date: "2026-02-16",
      line_items: [
        {
          description: "Design",
          quantity: 1,
          unit_price: 100,
          vat_rate: 20,
        },
      ],
    });

    expect(created?.id).toBe("q1");

    (apiClient.put as any).mockResolvedValueOnce({
      data: { ...baseQuote, id: "q1", notes: "Updated" },
    });

    const updated = await useQuoteStore.getState().updateQuote("q1", {
      notes: "Updated",
    });

    expect(updated?.notes).toBe("Updated");

    (apiClient.post as any).mockResolvedValueOnce({
      data: { ...baseQuote, id: "q1", status: "sent" },
    });

    const sent = await useQuoteStore.getState().sendQuote("q1");
    expect(sent?.status).toBe("sent");

    (apiClient.post as any).mockResolvedValueOnce({
      data: { ...baseQuote, id: "q1", status: "accepted" },
    });

    const accepted = await useQuoteStore.getState().acceptQuote("q1");
    expect(accepted?.status).toBe("accepted");

    (apiClient.post as any).mockResolvedValueOnce({
      data: { ...baseQuote, id: "q1", status: "rejected" },
    });

    const rejected = await useQuoteStore.getState().rejectQuote("q1");
    expect(rejected?.status).toBe("rejected");

    useQuoteStore.setState({
      currentQuote: { ...baseQuote, id: "q1", status: "sent" } as any,
    });

    (apiClient.post as any).mockResolvedValueOnce({
      data: { id: "inv_1" },
    });

    const invoice = await useQuoteStore.getState().convertQuote("q1");
    expect(invoice?.id).toBe("inv_1");
    expect(useQuoteStore.getState().currentQuote?.converted_invoice_id).toBe(
      "inv_1"
    );

    (apiClient.delete as any).mockResolvedValue({});

    await useQuoteStore.getState().deleteQuote("q1");
    expect(useQuoteStore.getState().quotes).toEqual([]);
  });

  it("handles action failures and list errors", async () => {
    (apiClient.get as any).mockRejectedValueOnce(new Error("list failed"));
    await useQuoteStore.getState().fetchQuotes();
    expect(useQuoteStore.getState().error).toBe("list failed");

    (apiClient.get as any).mockRejectedValueOnce(new Error("fetch failed"));
    await expect(useQuoteStore.getState().fetchQuote("q404")).rejects.toThrow(
      "fetch failed"
    );

    (apiClient.post as any).mockRejectedValueOnce(new Error("create failed"));
    await expect(
      useQuoteStore.getState().createQuote({ client_id: "cli_1" })
    ).rejects.toThrow("create failed");

    (apiClient.put as any).mockRejectedValueOnce(new Error("update failed"));
    await expect(
      useQuoteStore.getState().updateQuote("q1", { notes: "x" })
    ).rejects.toThrow("update failed");

    (apiClient.delete as any).mockRejectedValueOnce(new Error("delete failed"));
    await expect(useQuoteStore.getState().deleteQuote("q1")).rejects.toThrow(
      "delete failed"
    );

    (apiClient.post as any).mockRejectedValueOnce(new Error("send failed"));
    await expect(useQuoteStore.getState().sendQuote("q1")).rejects.toThrow(
      "send failed"
    );

    (apiClient.post as any).mockRejectedValueOnce(new Error("accept failed"));
    await expect(useQuoteStore.getState().acceptQuote("q1")).rejects.toThrow(
      "accept failed"
    );

    (apiClient.post as any).mockRejectedValueOnce(new Error("reject failed"));
    await expect(useQuoteStore.getState().rejectQuote("q1")).rejects.toThrow(
      "reject failed"
    );

    (apiClient.post as any).mockRejectedValueOnce(new Error("convert failed"));
    await expect(useQuoteStore.getState().convertQuote("q1")).rejects.toThrow(
      "convert failed"
    );
  });
});
