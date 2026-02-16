import { describe, it, expect, beforeEach, vi } from "vitest";
import { useCreditNoteStore } from "@/lib/stores/creditNotes";

vi.mock("@/lib/api", () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
  },
}));

import { apiClient } from "@/lib/api";

describe("useCreditNoteStore", () => {
  const baseCreditNote = {
    id: "cn1",
    client_id: "cli_1",
    invoice_id: "inv_1",
    number: "AVO-2026-0001",
    status: "draft",
    issue_date: "2026-02-16",
    subtotal: 100,
    tax_amount: 20,
    total: 120,
  };

  beforeEach(() => {
    useCreditNoteStore.setState({
      creditNotes: [],
      currentCreditNote: null,
      pagination: null,
      isLoading: false,
      error: null,
    });
    vi.clearAllMocks();
  });

  it("fetches credit notes and uses fallback pagination", async () => {
    (apiClient.get as any).mockResolvedValueOnce({
      data: {
        data: [{ ...baseCreditNote }],
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 1,
      },
    });

    await useCreditNoteStore.getState().fetchCreditNotes();
    expect(useCreditNoteStore.getState().creditNotes).toHaveLength(1);

    (apiClient.get as any).mockResolvedValueOnce({ data: null });
    await useCreditNoteStore.getState().fetchCreditNotes();

    expect(useCreditNoteStore.getState().creditNotes).toEqual([]);
    expect(useCreditNoteStore.getState().pagination).toEqual({
      current_page: 1,
      last_page: 1,
      total: 0,
      per_page: 15,
    });
  });

  it("creates updates sends applies and deletes credit notes", async () => {
    (apiClient.post as any).mockResolvedValueOnce({
      data: { ...baseCreditNote, id: "cn1" },
    });

    const created = await useCreditNoteStore.getState().createCreditNote({
      invoice_id: "inv_1",
      issue_date: "2026-02-16",
      line_items: [
        {
          description: "Refund",
          quantity: 1,
          unit_price: 100,
          vat_rate: 20,
        },
      ],
    });

    expect(created?.id).toBe("cn1");

    (apiClient.put as any).mockResolvedValueOnce({
      data: { ...baseCreditNote, id: "cn1", reason: "Updated" },
    });

    const updated = await useCreditNoteStore
      .getState()
      .updateCreditNote("cn1", {
        reason: "Updated",
      });

    expect(updated?.reason).toBe("Updated");

    (apiClient.post as any).mockResolvedValueOnce({
      data: { ...baseCreditNote, id: "cn1", status: "sent" },
    });

    const sent = await useCreditNoteStore.getState().sendCreditNote("cn1");
    expect(sent?.status).toBe("sent");

    (apiClient.post as any).mockResolvedValueOnce({
      data: { ...baseCreditNote, id: "cn1", status: "applied" },
    });

    const applied = await useCreditNoteStore.getState().applyCreditNote("cn1");
    expect(applied?.status).toBe("applied");

    (apiClient.delete as any).mockResolvedValue({});

    await useCreditNoteStore.getState().deleteCreditNote("cn1");
    expect(useCreditNoteStore.getState().creditNotes).toEqual([]);
  });

  it("handles action failures", async () => {
    (apiClient.get as any).mockRejectedValueOnce(new Error("list failed"));
    await useCreditNoteStore.getState().fetchCreditNotes();
    expect(useCreditNoteStore.getState().error).toBe("list failed");

    (apiClient.get as any).mockRejectedValueOnce(new Error("fetch failed"));
    await expect(
      useCreditNoteStore.getState().fetchCreditNote("cn404")
    ).rejects.toThrow("fetch failed");

    (apiClient.post as any).mockRejectedValueOnce(new Error("create failed"));
    await expect(
      useCreditNoteStore.getState().createCreditNote({ invoice_id: "inv_1" })
    ).rejects.toThrow("create failed");

    (apiClient.put as any).mockRejectedValueOnce(new Error("update failed"));
    await expect(
      useCreditNoteStore.getState().updateCreditNote("cn1", { reason: "x" })
    ).rejects.toThrow("update failed");

    (apiClient.delete as any).mockRejectedValueOnce(new Error("delete failed"));
    await expect(
      useCreditNoteStore.getState().deleteCreditNote("cn1")
    ).rejects.toThrow("delete failed");

    (apiClient.post as any).mockRejectedValueOnce(new Error("send failed"));
    await expect(
      useCreditNoteStore.getState().sendCreditNote("cn1")
    ).rejects.toThrow("send failed");

    (apiClient.post as any).mockRejectedValueOnce(new Error("apply failed"));
    await expect(
      useCreditNoteStore.getState().applyCreditNote("cn1")
    ).rejects.toThrow("apply failed");
  });
});
