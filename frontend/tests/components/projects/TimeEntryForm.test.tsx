import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { TimeEntryForm } from "@/components/projects/time-entry-form";

describe("TimeEntryForm", () => {
  it("validates duration and date", async () => {
    render(<TimeEntryForm onSubmit={vi.fn()} />);

    fireEvent.change(screen.getByLabelText("Duration (minutes)"), {
      target: { value: "0" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Save time entry" }));

    await waitFor(() => {
      expect(
        screen.getByText("Duration must be greater than 0")
      ).toBeInTheDocument();
    });
  });

  it("submits normalized payload", async () => {
    const onSubmit = vi.fn();

    render(<TimeEntryForm onSubmit={onSubmit} />);

    fireEvent.change(screen.getByLabelText("Duration (minutes)"), {
      target: { value: "90" },
    });
    fireEvent.change(screen.getByLabelText("Date"), {
      target: { value: "2026-02-15" },
    });
    fireEvent.change(screen.getByLabelText("Description"), {
      target: { value: "Implementation" },
    });

    fireEvent.click(screen.getByRole("button", { name: "Save time entry" }));

    await waitFor(() => {
      expect(onSubmit).toHaveBeenCalledWith({
        duration_minutes: 90,
        date: "2026-02-15",
        description: "Implementation",
      });
    });
  });
});
