import { beforeEach, describe, expect, it, vi } from "vitest";
import { fireEvent, render, screen, waitFor } from "@testing-library/react";

const toastSuccess = vi.fn();
const toastError = vi.fn();
const push = vi.fn();

const useRecurringInvoiceStoreMock = vi.fn();

const baseProfile = {
  id: "rip_1",
  client_id: "cli_1",
  name: "Monthly retainer",
  frequency: "monthly",
  start_date: "2026-03-01",
  next_due_date: "2026-03-20",
  line_items: [],
  payment_terms_days: 30,
  status: "active",
  occurrences_generated: 2,
  auto_send: true,
  currency: "EUR",
  client: { id: "cli_1", name: "Acme" },
  generated_invoices: [],
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

vi.mock("@/lib/stores/recurring-invoices", () => ({
  useRecurringInvoiceStore: () => useRecurringInvoiceStoreMock(),
}));

import RecurringInvoiceDetailPage from "@/app/(dashboard)/invoices/recurring/[id]/page";

describe("RecurringInvoiceDetailPage", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it("renders loading skeleton when profile is loading", () => {
    useRecurringInvoiceStoreMock.mockReturnValue({
      currentProfile: null,
      isLoading: true,
      fetchProfile: vi.fn().mockResolvedValue(baseProfile),
      pauseProfile: vi.fn(),
      resumeProfile: vi.fn(),
      cancelProfile: vi.fn(),
    });

    render(<RecurringInvoiceDetailPage />);

    expect(screen.queryByText("Profile not found")).not.toBeInTheDocument();
    expect(screen.queryByText("Profile information")).not.toBeInTheDocument();
  });

  it("renders empty state when profile is missing", () => {
    useRecurringInvoiceStoreMock.mockReturnValue({
      currentProfile: null,
      isLoading: false,
      fetchProfile: vi.fn().mockResolvedValue(null),
      pauseProfile: vi.fn(),
      resumeProfile: vi.fn(),
      cancelProfile: vi.fn(),
    });

    render(<RecurringInvoiceDetailPage />);

    expect(screen.getByText("Profile not found")).toBeInTheDocument();
  });

  it("handles pause action and refresh", async () => {
    const fetchProfile = vi.fn().mockResolvedValue(baseProfile);
    const pauseProfile = vi.fn().mockResolvedValue({
      ...baseProfile,
      status: "paused",
    });

    useRecurringInvoiceStoreMock.mockReturnValue({
      currentProfile: baseProfile,
      isLoading: false,
      fetchProfile,
      pauseProfile,
      resumeProfile: vi.fn(),
      cancelProfile: vi.fn().mockResolvedValue({}),
    });

    render(<RecurringInvoiceDetailPage />);

    await waitFor(() => {
      expect(fetchProfile).toHaveBeenCalledWith("rip_1");
    });

    fireEvent.click(screen.getByRole("button", { name: "Pause" }));

    await waitFor(() => {
      expect(pauseProfile).toHaveBeenCalledWith("rip_1");
      expect(toastSuccess).toHaveBeenCalledWith("Profile paused");
      expect(fetchProfile).toHaveBeenCalledTimes(2);
    });
  });

  it("handles resume action and mount failure", async () => {
    const fetchProfile = vi.fn().mockRejectedValue(new Error("load failed"));

    useRecurringInvoiceStoreMock.mockReturnValue({
      currentProfile: {
        ...baseProfile,
        status: "paused",
      },
      isLoading: false,
      fetchProfile,
      pauseProfile: vi.fn(),
      resumeProfile: vi.fn().mockRejectedValue(new Error("resume failed")),
      cancelProfile: vi.fn(),
    });

    render(<RecurringInvoiceDetailPage />);

    await waitFor(() => {
      expect(toastError).toHaveBeenCalledWith("load failed");
      expect(push).toHaveBeenCalledWith("/invoices/recurring");
    });

    fireEvent.click(screen.getByRole("button", { name: "Resume" }));

    await waitFor(() => {
      expect(toastError).toHaveBeenCalledWith("resume failed");
    });
  });
});
