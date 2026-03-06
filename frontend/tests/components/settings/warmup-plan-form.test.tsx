import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { WarmupPlanForm } from "@/components/settings/warmup-plan-form";

describe("WarmupPlanForm", () => {
  it("submits warmup plan values", async () => {
    const onSubmit = vi.fn().mockResolvedValue(undefined);

    render(<WarmupPlanForm onSubmit={onSubmit} />);

    fireEvent.change(screen.getByLabelText(/name/i), {
      target: { value: "IP warm-up" },
    });
    fireEvent.change(screen.getByLabelText(/start volume/i), {
      target: { value: "50" },
    });
    fireEvent.change(screen.getByLabelText(/max volume/i), {
      target: { value: "500" },
    });
    fireEvent.change(screen.getByLabelText(/increment/i), {
      target: { value: "30" },
    });
    fireEvent.click(screen.getByRole("button", { name: /save/i }));

    await waitFor(() =>
      expect(onSubmit).toHaveBeenCalledWith({
        name: "IP warm-up",
        daily_volume_start: 50,
        daily_volume_max: 500,
        increment_percent: 30,
      })
    );
  });
});
