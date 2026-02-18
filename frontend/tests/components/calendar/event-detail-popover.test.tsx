import { describe, expect, it, vi } from "vitest";
import { fireEvent, render, screen } from "@testing-library/react";

vi.mock("@/components/ui/popover", () => ({
  Popover: ({ children }: { children: React.ReactNode }) => (
    <div>{children}</div>
  ),
  PopoverTrigger: ({ children }: { children: React.ReactNode }) => (
    <div>{children}</div>
  ),
  PopoverContent: ({
    children,
    className,
  }: {
    children: React.ReactNode;
    className?: string;
  }) => <div className={className}>{children}</div>,
}));

import { EventDetailPopover } from "@/components/calendar/event-detail-popover";

describe("EventDetailPopover", () => {
  it("renders event details and handles actions", () => {
    const onEdit = vi.fn();
    const onDelete = vi.fn();

    render(
      <EventDetailPopover
        event={{
          id: "evt_1",
          title: "Sprint planning",
          type: "meeting",
          description: "Roadmap sync",
          start_at: "2026-03-10 09:00:00",
          end_at: "2026-03-10 10:00:00",
          location: "Room A",
          all_day: false,
          sync_status: "local",
        }}
        trigger={<button type="button">Open</button>}
        onEdit={onEdit}
        onDelete={onDelete}
      />
    );

    expect(screen.getByText("Sprint planning")).toBeInTheDocument();
    expect(screen.getByText("meeting")).toBeInTheDocument();
    expect(screen.getByText("Roadmap sync")).toBeInTheDocument();
    expect(screen.getByText("Start: 2026-03-10 09:00:00")).toBeInTheDocument();
    expect(screen.getByText("End: 2026-03-10 10:00:00")).toBeInTheDocument();
    expect(screen.getByText("Location: Room A")).toBeInTheDocument();

    fireEvent.click(screen.getByRole("button", { name: "Edit" }));
    fireEvent.click(screen.getByRole("button", { name: "Delete" }));

    expect(onEdit).toHaveBeenCalledTimes(1);
    expect(onDelete).toHaveBeenCalledTimes(1);
  });

  it("omits optional description and location", () => {
    render(
      <EventDetailPopover
        event={{
          id: "evt_2",
          title: "Reminder",
          type: "reminder",
          start_at: "2026-03-12 08:00:00",
          end_at: "2026-03-12 08:30:00",
          all_day: false,
          sync_status: "synced",
        }}
        trigger={<button type="button">Open</button>}
        onEdit={vi.fn()}
        onDelete={vi.fn()}
      />
    );

    expect(screen.queryByText("Location:")).not.toBeInTheDocument();
    expect(screen.queryByText("Roadmap sync")).not.toBeInTheDocument();
  });
});
