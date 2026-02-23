import { render, screen, fireEvent } from "@testing-library/react";
import { describe, it, expect, vi } from "vitest";
import { TicketMessageThread } from "@/components/tickets/ticket-message-thread";

const publicMessage = {
  id: "m1",
  ticket_id: "t1",
  user_id: "u1",
  content: "Public message",
  is_internal: false,
  created_at: "2026-01-01T10:00:00Z",
  updated_at: "2026-01-01T10:00:00Z",
  user: { id: "u1", name: "Alice", email: "alice@test.com" },
};

const internalMessage = {
  id: "m2",
  ticket_id: "t1",
  user_id: "u2",
  content: "Secret internal content",
  is_internal: true,
  created_at: "2026-01-01T11:00:00Z",
  updated_at: "2026-01-01T11:00:00Z",
  user: { id: "u2", name: "Bob", email: "bob@test.com" },
};

describe("TicketMessageThread", () => {
  it("shows public messages for all users", () => {
    render(
      <TicketMessageThread
        messages={[publicMessage]}
        currentUserId="u3"
        isOwnerOrAssignee={false}
        onEdit={vi.fn()}
        onDelete={vi.fn()}
      />
    );
    expect(screen.getByText("Public message")).toBeInTheDocument();
  });

  it("hides internal notes for non-owner/non-assignee", () => {
    render(
      <TicketMessageThread
        messages={[internalMessage]}
        currentUserId="u3"
        isOwnerOrAssignee={false}
        onEdit={vi.fn()}
        onDelete={vi.fn()}
      />
    );
    expect(
      screen.queryByText("Secret internal content")
    ).not.toBeInTheDocument();
  });

  it('shows internal notes for owner/assignee with "Internal note" badge', () => {
    render(
      <TicketMessageThread
        messages={[internalMessage]}
        currentUserId="u1"
        isOwnerOrAssignee={true}
        onEdit={vi.fn()}
        onDelete={vi.fn()}
      />
    );
    expect(screen.getByText("Secret internal content")).toBeInTheDocument();
    expect(
      screen.getByText("Internal note", { selector: "span" })
    ).toBeInTheDocument();
  });

  it("shows edit/delete buttons only for own messages", () => {
    render(
      <TicketMessageThread
        messages={[publicMessage, internalMessage]}
        currentUserId="u1"
        isOwnerOrAssignee={true}
        onEdit={vi.fn()}
        onDelete={vi.fn()}
      />
    );
    expect(screen.getAllByLabelText("Edit message")).toHaveLength(1);
    expect(screen.getAllByLabelText("Delete message")).toHaveLength(1);
  });

  it("calls onDelete when delete button clicked", () => {
    const onDelete = vi.fn();
    render(
      <TicketMessageThread
        messages={[publicMessage]}
        currentUserId="u1"
        isOwnerOrAssignee={false}
        onEdit={vi.fn()}
        onDelete={onDelete}
      />
    );
    fireEvent.click(screen.getByLabelText("Delete message"));
    expect(onDelete).toHaveBeenCalledWith("m1");
  });
});
