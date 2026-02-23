import { render, screen } from "@testing-library/react";
import { describe, it, expect } from "vitest";
import { TicketPriorityBadge } from "@/components/tickets/ticket-priority-badge";

describe("TicketPriorityBadge", () => {
  it('renders "Low" for low priority', () => {
    render(<TicketPriorityBadge priority="low" />);
    expect(screen.getByText("Low")).toBeInTheDocument();
  });

  it('renders "Normal" for normal priority', () => {
    render(<TicketPriorityBadge priority="normal" />);
    expect(screen.getByText("Normal")).toBeInTheDocument();
  });

  it('renders "High" for high priority', () => {
    render(<TicketPriorityBadge priority="high" />);
    expect(screen.getByText("High")).toBeInTheDocument();
  });

  it('renders "Urgent" with alert icon for urgent priority', () => {
    render(<TicketPriorityBadge priority="urgent" />);
    expect(screen.getByText("Urgent")).toBeInTheDocument();
  });

  it("does NOT render alert icon for non-urgent priorities", () => {
    const { container } = render(<TicketPriorityBadge priority="normal" />);
    expect(container.querySelector("svg")).toBeNull();
  });

  it("renders alert icon for urgent priority", () => {
    const { container } = render(<TicketPriorityBadge priority="urgent" />);
    expect(container.querySelector("svg")).toBeInTheDocument();
  });
});
