import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { ReminderTimeline } from "@/components/reminders/reminder-timeline";

describe("ReminderTimeline", () => {
  it("renders steps and statuses", () => {
    render(
      <ReminderTimeline
        steps={[
          { step_number: 1, delay_days: 3, subject: "Sujet", body: "Body" },
        ]}
        deliveries={[
          {
            id: "d1",
            reminder_step_id: "x",
            status: "sent",
            step: { step_number: 1 },
          } as any,
        ]}
      />
    );

    expect(screen.getByText(/J\+3/)).toBeInTheDocument();
    expect(screen.getByText(/Statut: sent/)).toBeInTheDocument();
  });
});
