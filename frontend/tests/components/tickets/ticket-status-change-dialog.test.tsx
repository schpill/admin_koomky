import { render, screen, fireEvent } from "@testing-library/react";
import { describe, it, expect, vi } from "vitest";
import { TicketStatusChangeDialog } from "@/components/tickets/ticket-status-change-dialog";
import { I18nProvider } from "@/components/providers/i18n-provider";

const mockTicket = {
  id: "t1",
  status: "open" as const,
  title: "Test",
} as any;

function renderWithProviders(ui: React.ReactElement) {
  return render(<I18nProvider initialLocale="en">{ui}</I18nProvider>);
}

describe("TicketStatusChangeDialog", () => {
  it("renders dialog when open=true", () => {
    renderWithProviders(
      <TicketStatusChangeDialog
        ticket={mockTicket}
        open={true}
        onOpenChange={vi.fn()}
        onStatusChange={vi.fn()}
      />
    );
    expect(screen.getByRole("dialog")).toBeInTheDocument();
    expect(
      screen.getByRole("heading", { name: "Change Status" })
    ).toBeInTheDocument();
  });

  it("shows comment textarea", () => {
    renderWithProviders(
      <TicketStatusChangeDialog
        ticket={mockTicket}
        open={true}
        onOpenChange={vi.fn()}
        onStatusChange={vi.fn()}
      />
    );
    expect(screen.getByPlaceholderText(/comment/i)).toBeInTheDocument();
  });

  it("calls onStatusChange on submit", () => {
    const onStatusChange = vi.fn();
    renderWithProviders(
      <TicketStatusChangeDialog
        ticket={mockTicket}
        open={true}
        onOpenChange={vi.fn()}
        onStatusChange={onStatusChange}
      />
    );
    fireEvent.click(screen.getByRole("button", { name: "Change Status" }));
    expect(onStatusChange).toHaveBeenCalled();
  });

  it("renders for resolved ticket with limited transitions", () => {
    const resolvedTicket = { ...mockTicket, status: "resolved" as const };
    renderWithProviders(
      <TicketStatusChangeDialog
        ticket={resolvedTicket}
        open={true}
        onOpenChange={vi.fn()}
        onStatusChange={vi.fn()}
      />
    );
    expect(screen.getByRole("dialog")).toBeInTheDocument();
  });
});
