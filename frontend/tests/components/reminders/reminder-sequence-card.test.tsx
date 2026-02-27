import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { ReminderSequenceCard } from "@/components/reminders/reminder-sequence-card";

describe("ReminderSequenceCard", () => {
  it("renders and triggers actions", () => {
    const onEdit = vi.fn();
    const onSetDefault = vi.fn();
    const onDelete = vi.fn();
    const onToggleActive = vi.fn();

    render(
      <ReminderSequenceCard
        sequence={
          {
            id: "s1",
            user_id: "u1",
            name: "Relance",
            is_active: true,
            is_default: false,
            steps: [{ step_number: 1, delay_days: 3, subject: "A", body: "B" }],
          } as any
        }
        onEdit={onEdit}
        onSetDefault={onSetDefault}
        onDelete={onDelete}
        onToggleActive={onToggleActive}
      />
    );

    fireEvent.click(screen.getByText("Modifier"));
    fireEvent.click(screen.getByText("Définir par défaut"));
    fireEvent.click(screen.getByText("Supprimer"));
    fireEvent.click(screen.getByRole("checkbox"));

    expect(onEdit).toHaveBeenCalled();
    expect(onSetDefault).toHaveBeenCalled();
    expect(onDelete).toHaveBeenCalled();
    expect(onToggleActive).toHaveBeenCalled();
  });
});
