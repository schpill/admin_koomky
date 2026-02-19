import { beforeEach, describe, expect, it, vi } from "vitest";
import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { I18nProvider } from "@/components/providers/i18n-provider";

const toastSuccess = vi.fn();
const toastError = vi.fn();
const useCalendarStoreMock = vi.fn();

const modalPayload = {
  title: "From modal",
  description: null,
  start_at: "2026-03-15 09:00:00",
  end_at: "2026-03-15 10:00:00",
  all_day: false,
  location: null,
  type: "meeting",
} as const;

vi.mock("sonner", () => ({
  toast: {
    success: (...args: unknown[]) => toastSuccess(...args),
    error: (...args: unknown[]) => toastError(...args),
  },
}));

vi.mock("@/lib/stores/calendar", () => ({
  useCalendarStore: () => useCalendarStoreMock(),
}));

vi.mock("@/components/calendar/event-detail-popover", () => ({
  EventDetailPopover: ({
    event,
    onEdit,
    onDelete,
    trigger,
  }: {
    event: { id: string };
    onEdit: () => void;
    onDelete: () => void;
    trigger: React.ReactNode;
  }) => (
    <div>
      {trigger}
      <button type="button" onClick={onEdit}>{`Edit ${event.id}`}</button>
      <button type="button" onClick={onDelete}>{`Delete ${event.id}`}</button>
    </div>
  ),
}));

vi.mock("@/components/calendar/event-form-modal", () => ({
  EventFormModal: ({
    open,
    submitLabel,
    onSubmit,
    initialEvent,
  }: {
    open: boolean;
    submitLabel: string;
    onSubmit: (payload: typeof modalPayload) => void;
    initialEvent?: { id?: string } | null;
  }) =>
    open ? (
      <div>
        <p>{submitLabel}</p>
        <p>{initialEvent?.id || "new-event"}</p>
        <button type="button" onClick={() => onSubmit(modalPayload)}>
          Submit modal
        </button>
      </div>
    ) : null,
}));

import CalendarPage from "@/app/(dashboard)/calendar/page";

describe("CalendarPage", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it("fetches events, supports view switches and empty state", async () => {
    const fetchEvents = vi.fn().mockResolvedValue(undefined);

    useCalendarStoreMock.mockReturnValue({
      events: [],
      isLoading: false,
      fetchEvents,
      createEvent: vi.fn(),
      updateEvent: vi.fn(),
      deleteEvent: vi.fn(),
    });

    render(
      <I18nProvider initialLocale="en">
        <CalendarPage />
      </I18nProvider>
    );

    await waitFor(() => {
      expect(fetchEvents).toHaveBeenCalledTimes(1);
    });

    expect(screen.getByText("No events")).toBeInTheDocument();

    fireEvent.click(screen.getByRole("button", { name: "Week" }));
    fireEvent.click(screen.getByRole("button", { name: "Day" }));
    fireEvent.click(screen.getByRole("button", { name: "Month" }));

    await waitFor(() => {
      expect(fetchEvents.mock.calls.length).toBeGreaterThanOrEqual(4);
    });
  });

  it("creates an event from modal", async () => {
    const fetchEvents = vi.fn().mockResolvedValue(undefined);
    const createEvent = vi.fn().mockResolvedValue({ id: "evt_created" });

    useCalendarStoreMock.mockReturnValue({
      events: [],
      isLoading: false,
      fetchEvents,
      createEvent,
      updateEvent: vi.fn(),
      deleteEvent: vi.fn(),
    });

    render(
      <I18nProvider initialLocale="en">
        <CalendarPage />
      </I18nProvider>
    );

    fireEvent.click(screen.getByRole("button", { name: "Create event" }));
    expect(screen.getByText("Save event")).toBeInTheDocument();

    fireEvent.click(screen.getByRole("button", { name: "Submit modal" }));

    await waitFor(() => {
      expect(createEvent).toHaveBeenCalledWith(modalPayload);
      expect(toastSuccess).toHaveBeenCalledWith("Event created");
      expect(fetchEvents.mock.calls.length).toBeGreaterThanOrEqual(2);
    });
  });

  it("updates and handles delete failures", async () => {
    const fetchEvents = vi.fn().mockResolvedValue(undefined);
    const updateEvent = vi.fn().mockResolvedValue({ id: "evt_1" });
    const deleteEvent = vi.fn().mockRejectedValue(new Error("delete failed"));

    useCalendarStoreMock.mockReturnValue({
      events: [
        {
          id: "evt_1",
          title: "Kickoff",
          start_at: "2026-03-15 09:00:00",
          end_at: "2026-03-15 10:00:00",
          type: "meeting",
          all_day: false,
          sync_status: "local",
        },
      ],
      isLoading: false,
      fetchEvents,
      createEvent: vi.fn(),
      updateEvent,
      deleteEvent,
    });

    render(
      <I18nProvider initialLocale="en">
        <CalendarPage />
      </I18nProvider>
    );

    fireEvent.click(screen.getByRole("button", { name: "Edit evt_1" }));
    expect(screen.getByText("Update event")).toBeInTheDocument();
    expect(screen.getByText("evt_1")).toBeInTheDocument();

    fireEvent.click(screen.getByRole("button", { name: "Submit modal" }));

    await waitFor(() => {
      expect(updateEvent).toHaveBeenCalledWith("evt_1", modalPayload);
      expect(toastSuccess).toHaveBeenCalledWith("Event updated");
    });

    fireEvent.click(screen.getByRole("button", { name: "Delete evt_1" }));

    await waitFor(() => {
      expect(deleteEvent).toHaveBeenCalledWith("evt_1");
      expect(toastError).toHaveBeenCalledWith("delete failed");
    });
  });
});
