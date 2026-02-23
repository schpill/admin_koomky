import { render, screen, fireEvent } from "@testing-library/react";
import { describe, it, expect, vi, beforeAll } from "vitest";
import { TicketMessageComposer } from "@/components/tickets/ticket-message-composer";

beforeAll(() => {
  global.ResizeObserver = class {
    observe() {}
    unobserve() {}
    disconnect() {}
  } as any;
});

describe("TicketMessageComposer", () => {
  it("shows internal note toggle for owner/assignee", () => {
    render(
      <TicketMessageComposer
        currentUserId="u1"
        isOwnerOrAssignee={true}
        onSubmit={vi.fn()}
      />
    );
    expect(screen.getByLabelText("Internal note")).toBeInTheDocument();
  });

  it("hides internal note toggle for regular users", () => {
    render(
      <TicketMessageComposer
        currentUserId="u1"
        isOwnerOrAssignee={false}
        onSubmit={vi.fn()}
      />
    );
    expect(screen.queryByLabelText("Internal note")).not.toBeInTheDocument();
  });

  it("submit button disabled when content is empty", () => {
    render(
      <TicketMessageComposer
        currentUserId="u1"
        isOwnerOrAssignee={false}
        onSubmit={vi.fn()}
      />
    );
    expect(screen.getByRole("button", { name: /send/i })).toBeDisabled();
  });

  it("calls onSubmit with content and is_internal false by default", () => {
    const onSubmit = vi.fn();
    render(
      <TicketMessageComposer
        currentUserId="u1"
        isOwnerOrAssignee={true}
        onSubmit={onSubmit}
      />
    );
    fireEvent.change(screen.getByPlaceholderText(/write a message/i), {
      target: { value: "Hello world" },
    });
    fireEvent.submit(
      screen.getByPlaceholderText(/write a message/i).closest("form")!
    );
    expect(onSubmit).toHaveBeenCalledWith({
      content: "Hello world",
      is_internal: false,
    });
  });
});
