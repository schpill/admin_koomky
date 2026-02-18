import { beforeEach, describe, expect, it, vi } from "vitest";
import { fireEvent, render, screen, waitFor } from "@testing-library/react";

const toastSuccess = vi.fn();
const toastError = vi.fn();

const useRecurringInvoiceStoreMock = vi.fn();

vi.mock("sonner", () => ({
  toast: {
    success: (...args: unknown[]) => toastSuccess(...args),
    error: (...args: unknown[]) => toastError(...args),
  },
}));

vi.mock("@/lib/stores/recurring-invoices", () => ({
  useRecurringInvoiceStore: () => useRecurringInvoiceStoreMock(),
}));

import RecurringInvoicesPage from "@/app/(dashboard)/invoices/recurring/page";

describe("RecurringInvoicesPage", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it("loads profiles and renders empty state", async () => {
    const fetchProfiles = vi.fn().mockResolvedValue(undefined);

    useRecurringInvoiceStoreMock.mockReturnValue({
      profiles: [],
      isLoading: false,
      pagination: { total: 0 },
      fetchProfiles,
      pauseProfile: vi.fn(),
      resumeProfile: vi.fn(),
      cancelProfile: vi.fn(),
    });

    render(<RecurringInvoicesPage />);

    await waitFor(() => {
      expect(fetchProfiles).toHaveBeenCalledTimes(1);
    });

    expect(screen.getByText("Recurring invoices")).toBeInTheDocument();
    expect(screen.getByText("No recurring profile")).toBeInTheDocument();
  });

  it("handles pause and cancel actions", async () => {
    const fetchProfiles = vi.fn().mockResolvedValue(undefined);
    const pauseProfile = vi.fn().mockResolvedValue({});
    const cancelProfile = vi.fn().mockResolvedValue({});

    useRecurringInvoiceStoreMock.mockReturnValue({
      profiles: [
        {
          id: "rip_1",
          client_id: "cli_1",
          name: "Monthly retainer",
          frequency: "monthly",
          next_due_date: "2026-03-20",
          status: "active",
          occurrences_generated: 2,
          client: { id: "cli_1", name: "Acme" },
        },
      ],
      isLoading: false,
      pagination: { total: 1 },
      fetchProfiles,
      pauseProfile,
      resumeProfile: vi.fn(),
      cancelProfile,
    });

    render(<RecurringInvoicesPage />);

    fireEvent.click(screen.getByRole("button", { name: "Pause" }));
    await waitFor(() => {
      expect(pauseProfile).toHaveBeenCalledWith("rip_1");
      expect(toastSuccess).toHaveBeenCalledWith("Profile paused");
    });

    fireEvent.click(screen.getByRole("button", { name: "Cancel" }));
    await waitFor(() => {
      expect(cancelProfile).toHaveBeenCalledWith("rip_1");
      expect(toastSuccess).toHaveBeenCalledWith("Profile cancelled");
    });
  });

  it("handles resume and action errors", async () => {
    const fetchProfiles = vi.fn().mockResolvedValue(undefined);
    const resumeProfile = vi.fn().mockRejectedValue(new Error("resume failed"));

    useRecurringInvoiceStoreMock.mockReturnValue({
      profiles: [
        {
          id: "rip_2",
          client_id: "cli_2",
          name: "Paused profile",
          frequency: "monthly",
          next_due_date: "2026-03-20",
          status: "paused",
          occurrences_generated: 4,
          client: { id: "cli_2", name: "Beta" },
        },
      ],
      isLoading: false,
      pagination: { total: 1 },
      fetchProfiles,
      pauseProfile: vi.fn(),
      resumeProfile,
      cancelProfile: vi.fn(),
    });

    render(<RecurringInvoicesPage />);

    fireEvent.click(screen.getByRole("button", { name: "Resume" }));

    await waitFor(() => {
      expect(resumeProfile).toHaveBeenCalledWith("rip_2");
      expect(toastError).toHaveBeenCalledWith("resume failed");
    });
  });
});
