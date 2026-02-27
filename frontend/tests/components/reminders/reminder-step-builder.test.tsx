import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { ReminderStepBuilder } from "@/components/reminders/reminder-step-builder";

describe("ReminderStepBuilder", () => {
  it("adds and removes steps", () => {
    const onChange = vi.fn();
    render(
      <ReminderStepBuilder
        value={[{ step_number: 1, delay_days: 3, subject: "A", body: "B" }]}
        onChange={onChange}
      />
    );

    fireEvent.click(screen.getByText("+ Ajouter étape"));
    fireEvent.click(screen.getByText("Supprimer"));

    expect(onChange).toHaveBeenCalled();
  });
});
