import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { ReminderSequenceForm } from "@/components/reminders/reminder-sequence-form";

describe("ReminderSequenceForm", () => {
  it("submits valid form", async () => {
    const onSubmit = vi.fn();

    render(<ReminderSequenceForm onSubmit={onSubmit} />);

    fireEvent.change(screen.getByLabelText("Nom"), {
      target: { value: "Relance" },
    });
    fireEvent.click(screen.getByText("Enregistrer"));

    expect(onSubmit).toHaveBeenCalled();
  });
});
