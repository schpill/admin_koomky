import { render, screen } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { InvoiceReminderPanel } from "@/components/reminders/invoice-reminder-panel";

const fetchSequences = vi.fn();
const fetchInvoiceReminder = vi.fn();

vi.mock("@/lib/stores/reminders", () => ({
  useReminderStore: () => ({
    sequences: [],
    invoiceReminder: null,
    fetchSequences,
    fetchInvoiceReminder,
    attachSequence: vi.fn(),
    pauseReminder: vi.fn(),
    resumeReminder: vi.fn(),
    skipStep: vi.fn(),
    cancelReminder: vi.fn(),
  }),
}));

describe("InvoiceReminderPanel", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it("renders empty state", () => {
    render(<InvoiceReminderPanel invoiceId="inv_1" />);
    expect(screen.getByText("Aucune séquence attachée.")).toBeInTheDocument();
    expect(fetchSequences).toHaveBeenCalled();
    expect(fetchInvoiceReminder).toHaveBeenCalledWith("inv_1");
  });
});
