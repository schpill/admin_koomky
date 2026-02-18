import { beforeEach, describe, expect, it, vi } from "vitest";
import { fireEvent, render, screen, waitFor } from "@testing-library/react";

const toastSuccess = vi.fn();
const toastError = vi.fn();
const push = vi.fn();

const useClientStoreMock = vi.fn();
const useRecurringInvoiceStoreMock = vi.fn();

const formPayload = {
  client_id: "cli_1",
  name: "Retainer",
  frequency: "monthly",
  start_date: "2026-03-01",
  line_items: [
    {
      description: "Service",
      quantity: 1,
      unit_price: 500,
      vat_rate: 20,
    },
  ],
};

vi.mock("next/navigation", () => ({
  useRouter: () => ({ push }),
}));

vi.mock("sonner", () => ({
  toast: {
    success: (...args: unknown[]) => toastSuccess(...args),
    error: (...args: unknown[]) => toastError(...args),
  },
}));

vi.mock("@/lib/stores/clients", () => ({
  useClientStore: () => useClientStoreMock(),
}));

vi.mock("@/lib/stores/recurring-invoices", () => ({
  useRecurringInvoiceStore: () => useRecurringInvoiceStoreMock(),
}));

vi.mock("@/components/invoices/recurring-invoice-form", () => ({
  RecurringInvoiceForm: ({
    onSubmit,
    onCancel,
    submitLabel,
  }: {
    onSubmit: (payload: any) => void;
    onCancel: () => void;
    submitLabel: string;
  }) => (
    <div>
      <button type="button" onClick={() => onSubmit(formPayload)}>
        {submitLabel}
      </button>
      <button type="button" onClick={onCancel}>
        Cancel form
      </button>
    </div>
  ),
}));

import CreateRecurringInvoicePage from "@/app/(dashboard)/invoices/recurring/create/page";

describe("CreateRecurringInvoicePage", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it("creates a profile and redirects to details", async () => {
    const fetchClients = vi.fn().mockResolvedValue(undefined);
    const createProfile = vi.fn().mockResolvedValue({ id: "rip_1" });

    useClientStoreMock.mockReturnValue({
      clients: [{ id: "cli_1", name: "Acme" }],
      fetchClients,
    });

    useRecurringInvoiceStoreMock.mockReturnValue({
      createProfile,
      isLoading: false,
    });

    render(<CreateRecurringInvoicePage />);

    await waitFor(() => {
      expect(fetchClients).toHaveBeenCalledTimes(1);
    });

    fireEvent.click(screen.getByRole("button", { name: "Create profile" }));

    await waitFor(() => {
      expect(createProfile).toHaveBeenCalledWith(formPayload);
      expect(toastSuccess).toHaveBeenCalledWith("Recurring profile created");
      expect(push).toHaveBeenCalledWith("/invoices/recurring/rip_1");
    });
  });

  it("redirects to list when API returns no id", async () => {
    useClientStoreMock.mockReturnValue({
      clients: [],
      fetchClients: vi.fn().mockResolvedValue(undefined),
    });

    const createProfile = vi.fn().mockResolvedValue(null);

    useRecurringInvoiceStoreMock.mockReturnValue({
      createProfile,
      isLoading: false,
    });

    render(<CreateRecurringInvoicePage />);

    fireEvent.click(screen.getByRole("button", { name: "Create profile" }));

    await waitFor(() => {
      expect(push).toHaveBeenCalledWith("/invoices/recurring");
    });
  });

  it("shows error toast when create fails", async () => {
    useClientStoreMock.mockReturnValue({
      clients: [],
      fetchClients: vi.fn().mockResolvedValue(undefined),
    });

    useRecurringInvoiceStoreMock.mockReturnValue({
      createProfile: vi.fn().mockRejectedValue(new Error("create failed")),
      isLoading: false,
    });

    render(<CreateRecurringInvoicePage />);

    fireEvent.click(screen.getByRole("button", { name: "Create profile" }));

    await waitFor(() => {
      expect(toastError).toHaveBeenCalledWith("create failed");
    });
  });
});
