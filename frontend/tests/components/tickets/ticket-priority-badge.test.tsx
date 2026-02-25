import { render, screen } from "@testing-library/react";
import { describe, it, expect } from "vitest";
import { TicketPriorityBadge } from "@/components/tickets/ticket-priority-badge";
import { I18nProvider } from "@/components/providers/i18n-provider";

function renderWithProviders(ui: React.ReactElement) {
  return render(<I18nProvider initialLocale="en">{ui}</I18nProvider>);
}

describe("TicketPriorityBadge", () => {
  it('renders "Low" for low priority', () => {
    renderWithProviders(<TicketPriorityBadge priority="low" />);
    expect(screen.getByText("Low")).toBeInTheDocument();
  });

  it('renders "Normal" for normal priority', () => {
    renderWithProviders(<TicketPriorityBadge priority="normal" />);
    expect(screen.getByText("Normal")).toBeInTheDocument();
  });

  it('renders "High" for high priority', () => {
    renderWithProviders(<TicketPriorityBadge priority="high" />);
    expect(screen.getByText("High")).toBeInTheDocument();
  });

  it('renders "Urgent" with alert icon for urgent priority', () => {
    renderWithProviders(<TicketPriorityBadge priority="urgent" />);
    expect(screen.getByText("Urgent")).toBeInTheDocument();
  });

  it("does NOT render alert icon for non-urgent priorities", () => {
    const { container } = renderWithProviders(<TicketPriorityBadge priority="normal" />);
    expect(container.querySelector("svg")).toBeNull();
  });

  it("renders alert icon for urgent priority", () => {
    const { container } = renderWithProviders(<TicketPriorityBadge priority="urgent" />);
    expect(container.querySelector("svg")).toBeInTheDocument();
  });
});
