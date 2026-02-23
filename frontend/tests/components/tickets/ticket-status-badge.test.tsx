import { render, screen } from "@testing-library/react";
import { describe, it, expect } from "vitest";
import { TicketStatusBadge } from "@/components/tickets/ticket-status-badge";

describe("TicketStatusBadge", () => {
  it('renders "Open" for open status', () => {
    render(<TicketStatusBadge status="open" />);
    expect(screen.getByText("Open")).toBeInTheDocument();
  });

  it('renders "In Progress" for in_progress status', () => {
    render(<TicketStatusBadge status="in_progress" />);
    expect(screen.getByText("In Progress")).toBeInTheDocument();
  });

  it('renders "Pending" for pending status', () => {
    render(<TicketStatusBadge status="pending" />);
    expect(screen.getByText("Pending")).toBeInTheDocument();
  });

  it('renders "Resolved" for resolved status', () => {
    render(<TicketStatusBadge status="resolved" />);
    expect(screen.getByText("Resolved")).toBeInTheDocument();
  });

  it('renders "Closed" for closed status', () => {
    render(<TicketStatusBadge status="closed" />);
    expect(screen.getByText("Closed")).toBeInTheDocument();
  });

  it("applies correct color class for open status", () => {
    const { container } = render(<TicketStatusBadge status="open" />);
    expect(container.firstChild).toHaveClass("bg-gray-100");
  });

  it("applies correct color class for resolved status", () => {
    const { container } = render(<TicketStatusBadge status="resolved" />);
    expect(container.firstChild).toHaveClass("bg-green-100");
  });
});
