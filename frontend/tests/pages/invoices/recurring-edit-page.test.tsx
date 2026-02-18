import { beforeEach, describe, expect, it, vi } from "vitest";
import { fireEvent, render, screen, waitFor } from "@testing-library/react";

const toastSuccess = vi.fn();
const toastError = vi.fn();
const push = vi.fn();

const useClientStoreMock = vi.fn();
const useRecurringInvoiceStoreMock = vi.fn();

const formPayload = {
  client_id: "cli_1",
  name: "Updated",
  frequency: "monthly",
  start_date: "2026-03-01",
  line_items: [
    {
      description: "Service",
      quantity: 1,
      unit_price: 600,
      vat_rate: 20,
    },
  ],
};

const currentProfile = {
  id: "rip_1",
  client_id: "cli_1",
  name: "Current profile",
  frequency: "monthly",
  start_date: "2026-03-01",
  end_date: null,
  next_due_date: "2026-03-20",
  day_of_month: null,
  line_items: formPayload.line_items,
  notes: null,
  payment_terms_days: 30,
  tax_rate: null,
  discount_percent: null,
  max_occurrences: null,
  auto_send: false,
  currency: "EUR",
};

vi.mock("next/navigation", () => ({
  useParams: () => ({ id: "rip_1" }),
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
    initialPayload,
  }: {
    onSubmit: (payload: any) => void;
    onCancel: () => void;
    submitLabel: string;
    initialPayload: { name: string };
  }) => (
    <div>
      <p>Initial profile: {initialPayload.name}</p>
      <button type="button" onClick={() => onSubmit(formPayload)}>
        {submitLabel}
      </button>
      <button type="button" onClick={onCancel}>
        Cancel form
      </button>
    </div>
  ),
}));

import EditRecurringInvoicePage from "@/app/(dashboard)/invoices/recurring/[id]/edit/page";

describe("EditRecurringInvoicePage", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it("renders skeleton when profile is missing", () => {
    useClientStoreMock.mockReturnValue({
      clients: [],
      fetchClients: vi.fn().mockResolvedValue(undefined),
    });

    useRecurringInvoiceStoreMock.mockReturnValue({
      currentProfile: null,
      fetchProfile: vi.fn().mockResolvedValue(null),
      updateProfile: vi.fn(),
      isLoading: false,
    });

    render(<EditRecurringInvoicePage />);

    expect(
      screen.queryByText("Edit recurring profile")
    ).not.toBeInTheDocument();
  });

  it("updates profile and redirects", async () => {
    const fetchClients = vi.fn().mockResolvedValue(undefined);
    const fetchProfile = vi.fn().mockResolvedValue(currentProfile);
    const updateProfile = vi.fn().mockResolvedValue(currentProfile);

    useClientStoreMock.mockReturnValue({
      clients: [{ id: "cli_1", name: "Acme" }],
      fetchClients,
    });

    useRecurringInvoiceStoreMock.mockReturnValue({
      currentProfile,
      fetchProfile,
      updateProfile,
      isLoading: false,
    });

    render(<EditRecurringInvoicePage />);

    await waitFor(() => {
      expect(fetchClients).toHaveBeenCalledTimes(1);
      expect(fetchProfile).toHaveBeenCalledWith("rip_1");
    });

    expect(
      screen.getByText("Initial profile: Current profile")
    ).toBeInTheDocument();

    fireEvent.click(screen.getByRole("button", { name: "Save changes" }));

    await waitFor(() => {
      expect(updateProfile).toHaveBeenCalledWith("rip_1", formPayload);
      expect(toastSuccess).toHaveBeenCalledWith("Recurring profile updated");
      expect(push).toHaveBeenCalledWith("/invoices/recurring/rip_1");
    });
  });

  it("shows errors when loading or saving fails", async () => {
    useClientStoreMock.mockReturnValue({
      clients: [],
      fetchClients: vi.fn().mockResolvedValue(undefined),
    });

    useRecurringInvoiceStoreMock.mockReturnValue({
      currentProfile,
      fetchProfile: vi.fn().mockRejectedValue(new Error("load failed")),
      updateProfile: vi.fn().mockRejectedValue(new Error("update failed")),
      isLoading: false,
    });

    render(<EditRecurringInvoicePage />);

    await waitFor(() => {
      expect(toastError).toHaveBeenCalledWith("load failed");
      expect(push).toHaveBeenCalledWith("/invoices/recurring");
    });

    fireEvent.click(screen.getByRole("button", { name: "Save changes" }));

    await waitFor(() => {
      expect(toastError).toHaveBeenCalledWith("update failed");
    });
  });
});
