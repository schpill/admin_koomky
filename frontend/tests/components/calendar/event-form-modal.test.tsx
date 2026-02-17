import { describe, expect, it, vi } from "vitest";
import { fireEvent, render, screen } from "@testing-library/react";

vi.mock("@/components/providers/i18n-provider", () => ({
  useI18n: () => ({
    t: (key: string) => key,
    locale: "en",
    setLocale: vi.fn(),
  }),
}));

import { EventFormModal } from "@/components/calendar/event-form-modal";

describe("EventFormModal", () => {
  it("creates event payload", async () => {
    const onSubmit = vi.fn();

    render(
      <EventFormModal
        open
        onOpenChange={vi.fn()}
        onSubmit={onSubmit}
        submitLabel="Save event"
      />
    );

    fireEvent.change(screen.getByLabelText("Title"), {
      target: { value: "Sprint planning" },
    });
    fireEvent.change(screen.getByLabelText("Start"), {
      target: { value: "2026-03-12T09:00" },
    });
    fireEvent.change(screen.getByLabelText("End"), {
      target: { value: "2026-03-12T10:00" },
    });

    fireEvent.click(screen.getByRole("button", { name: "Save event" }));

    expect(onSubmit).toHaveBeenCalledTimes(1);
    expect(onSubmit.mock.calls[0][0].title).toBe("Sprint planning");
  });

  it("supports edit mode, all-day toggle and type selection", async () => {
    const onSubmit = vi.fn();

    render(
      <EventFormModal
        open
        onOpenChange={vi.fn()}
        onSubmit={onSubmit}
        submitLabel="Update event"
        initialEvent={{
          id: "evt_1",
          title: "Kickoff",
          start_at: "2026-03-13 09:00:00",
          end_at: "2026-03-13 10:00:00",
          type: "meeting",
          all_day: false,
        }}
      />
    );

    fireEvent.click(screen.getByLabelText("All day"));
    fireEvent.change(screen.getByLabelText("Type"), {
      target: { value: "deadline" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Update event" }));

    expect(onSubmit).toHaveBeenCalledTimes(1);
    expect(onSubmit.mock.calls[0][0].all_day).toBe(true);
    expect(onSubmit.mock.calls[0][0].type).toBe("deadline");
  });
});
