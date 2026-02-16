import { describe, it, expect, beforeEach, vi } from "vitest";
import { useInvoiceStore } from "@/lib/stores/invoices";

vi.mock("@/lib/api", () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
  },
}));

import { apiClient } from "@/lib/api";

describe("useInvoiceStore", () => {
  const baseInvoice = {
    id: "inv_base",
    client_id: "cli_1",
    number: "FAC-2026-0001",
    status: "draft",
    issue_date: "2026-02-16",
    due_date: "2026-03-16",
    subtotal: 1000,
    tax_amount: 200,
    total: 1200,
    amount_paid: 0,
  };

  beforeEach(() => {
    useInvoiceStore.setState({
      invoices: [],
      currentInvoice: null,
      pagination: null,
      invoicingSettings: null,
      isLoading: false,
      error: null,
    });
    vi.clearAllMocks();
  });

  it("fetches invoices with pagination", async () => {
    (apiClient.get as any).mockResolvedValue({
      data: {
        data: [
          {
            id: "inv_1",
            number: "FAC-2026-0001",
            status: "draft",
            total: 1200,
          },
        ],
        current_page: 1,
        last_page: 2,
        per_page: 15,
        total: 16,
      },
    });

    await useInvoiceStore.getState().fetchInvoices({ status: "draft" });

    const state = useInvoiceStore.getState();
    expect(state.invoices).toHaveLength(1);
    expect(state.invoices[0].number).toBe("FAC-2026-0001");
    expect(state.pagination?.total).toBe(16);
  });

  it("uses fallback values when invoice list payload is empty", async () => {
    (apiClient.get as any).mockResolvedValue({
      data: null,
    });

    await useInvoiceStore.getState().fetchInvoices();

    const state = useInvoiceStore.getState();
    expect(state.invoices).toEqual([]);
    expect(state.pagination).toEqual({
      current_page: 1,
      last_page: 1,
      total: 0,
      per_page: 15,
    });
  });

  it("creates sends duplicates and deletes invoice", async () => {
    (apiClient.post as any).mockResolvedValueOnce({
      data: {
        id: "inv_1",
        number: "FAC-2026-0001",
        status: "draft",
        total: 1200,
        amount_paid: 0,
      },
    });

    const created = await useInvoiceStore.getState().createInvoice({
      client_id: "cli_1",
      issue_date: "2026-02-16",
      due_date: "2026-03-16",
      line_items: [
        {
          description: "Service",
          quantity: 2,
          unit_price: 100,
          vat_rate: 20,
        },
      ],
    });

    expect(created?.id).toBe("inv_1");
    expect(useInvoiceStore.getState().invoices).toHaveLength(1);

    (apiClient.post as any).mockResolvedValueOnce({
      data: {
        id: "inv_1",
        number: "FAC-2026-0001",
        status: "sent",
        total: 1200,
        amount_paid: 0,
      },
    });

    const sent = await useInvoiceStore.getState().sendInvoice("inv_1");
    expect(sent?.status).toBe("sent");

    (apiClient.post as any).mockResolvedValueOnce({
      data: {
        id: "inv_1",
        number: "FAC-2026-0001",
        status: "paid",
        total: 1200,
        amount_paid: 1200,
      },
    });

    const paid = await useInvoiceStore.getState().recordPayment("inv_1", {
      amount: 1200,
      payment_date: "2026-02-16",
      payment_method: "bank_transfer",
    });

    expect(paid?.status).toBe("paid");
    expect(paid?.amount_paid).toBe(1200);

    (apiClient.post as any).mockResolvedValueOnce({
      data: {
        id: "inv_2",
        number: "FAC-2026-0002",
        status: "draft",
        total: 1200,
        amount_paid: 0,
      },
    });

    const clone = await useInvoiceStore.getState().duplicateInvoice("inv_1");
    expect(clone?.id).toBe("inv_2");
    expect(useInvoiceStore.getState().invoices).toHaveLength(2);

    (apiClient.delete as any).mockResolvedValue({});

    await useInvoiceStore.getState().deleteInvoice("inv_2");

    expect(useInvoiceStore.getState().invoices).toHaveLength(1);
    expect(useInvoiceStore.getState().invoices[0].id).toBe("inv_1");
  });

  it("handles current invoice transitions and action failures", async () => {
    useInvoiceStore.setState({
      invoices: [
        { ...baseInvoice, id: "inv_1", number: "FAC-2026-0001" } as any,
        { ...baseInvoice, id: "inv_2", number: "FAC-2026-0002" } as any,
      ],
      currentInvoice: {
        ...baseInvoice,
        id: "inv_2",
        number: "FAC-2026-0002",
      } as any,
      pagination: null,
      invoicingSettings: null,
      isLoading: false,
      error: null,
    });

    (apiClient.post as any).mockResolvedValueOnce({
      data: { ...baseInvoice, id: "inv_1", status: "sent" },
    });

    await useInvoiceStore.getState().sendInvoice("inv_1");
    expect(useInvoiceStore.getState().currentInvoice?.id).toBe("inv_2");

    (apiClient.post as any).mockResolvedValueOnce({
      data: { ...baseInvoice, id: "inv_1", status: "paid", amount_paid: 1200 },
    });

    await useInvoiceStore.getState().recordPayment("inv_1", {
      amount: 1200,
      payment_date: "2026-02-16",
      payment_method: "bank_transfer",
    });
    expect(useInvoiceStore.getState().currentInvoice?.id).toBe("inv_2");

    useInvoiceStore.setState({
      currentInvoice: {
        ...baseInvoice,
        id: "inv_1",
        number: "FAC-2026-0001",
      } as any,
    });

    (apiClient.delete as any).mockResolvedValueOnce({});
    await useInvoiceStore.getState().deleteInvoice("inv_1");
    expect(useInvoiceStore.getState().currentInvoice).toBeNull();

    (apiClient.post as any).mockRejectedValueOnce(new Error("create failed"));
    await expect(
      useInvoiceStore.getState().createInvoice({ client_id: "cli_1" })
    ).rejects.toThrow("create failed");

    (apiClient.delete as any).mockRejectedValueOnce(new Error("delete failed"));
    await expect(
      useInvoiceStore.getState().deleteInvoice("inv_2")
    ).rejects.toThrow("delete failed");

    (apiClient.post as any).mockRejectedValueOnce(new Error("send failed"));
    await expect(
      useInvoiceStore.getState().sendInvoice("inv_1")
    ).rejects.toThrow("send failed");

    (apiClient.post as any).mockRejectedValueOnce(
      new Error("duplicate failed")
    );
    await expect(
      useInvoiceStore.getState().duplicateInvoice("inv_1")
    ).rejects.toThrow("duplicate failed");

    (apiClient.post as any).mockRejectedValueOnce(new Error("payment failed"));
    await expect(
      useInvoiceStore.getState().recordPayment("inv_1", {
        amount: 100,
        payment_date: "2026-02-17",
      })
    ).rejects.toThrow("payment failed");
  });

  it("records list errors without throwing", async () => {
    (apiClient.get as any).mockRejectedValue(new Error("list failed"));

    await expect(
      useInvoiceStore.getState().fetchInvoices({ status: "draft" })
    ).resolves.toBeUndefined();
    expect(useInvoiceStore.getState().error).toBe("list failed");
  });

  it("throws for invoice fetch and settings failures", async () => {
    (apiClient.get as any).mockRejectedValueOnce(new Error("fetch failed"));
    await expect(
      useInvoiceStore.getState().fetchInvoice("inv_404")
    ).rejects.toThrow("fetch failed");

    (apiClient.get as any).mockRejectedValueOnce(new Error("settings failed"));
    await expect(
      useInvoiceStore.getState().fetchInvoicingSettings()
    ).rejects.toThrow("settings failed");

    (apiClient.put as any).mockRejectedValueOnce(
      new Error("settings update failed")
    );
    await expect(
      useInvoiceStore.getState().updateInvoicingSettings({
        payment_terms_days: 30,
        bank_details: "IBAN TEST",
        invoice_footer: "Footer",
        invoice_numbering_pattern: "FAC-YYYY-NNNN",
      })
    ).rejects.toThrow("settings update failed");
  });

  it("loads and updates invoicing settings", async () => {
    (apiClient.get as any).mockResolvedValue({
      data: {
        payment_terms_days: 30,
        bank_details: "IBAN TEST",
        invoice_footer: "Footer",
        invoice_numbering_pattern: "FAC-YYYY-NNNN",
      },
    });

    await useInvoiceStore.getState().fetchInvoicingSettings();

    expect(
      useInvoiceStore.getState().invoicingSettings?.payment_terms_days
    ).toBe(30);

    (apiClient.put as any).mockResolvedValue({
      data: {
        payment_terms_days: 45,
        bank_details: "IBAN UPDATED",
        invoice_footer: "Updated footer",
        invoice_numbering_pattern: "FAC-YYYY-NNNN",
      },
    });

    await useInvoiceStore.getState().updateInvoicingSettings({
      payment_terms_days: 45,
      bank_details: "IBAN UPDATED",
      invoice_footer: "Updated footer",
      invoice_numbering_pattern: "FAC-YYYY-NNNN",
    });

    expect(
      useInvoiceStore.getState().invoicingSettings?.payment_terms_days
    ).toBe(45);
    expect(useInvoiceStore.getState().invoicingSettings?.invoice_footer).toBe(
      "Updated footer"
    );
  });
});
