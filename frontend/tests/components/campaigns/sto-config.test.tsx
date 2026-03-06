import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { StoConfig } from "@/components/campaigns/sto-config";

describe("StoConfig", () => {
  it("updates the toggle and window hours", () => {
    const onEnabledChange = vi.fn();
    const onWindowHoursChange = vi.fn();

    render(
      <StoConfig
        enabled
        windowHours={24}
        knownContactsCount={12}
        onEnabledChange={onEnabledChange}
        onWindowHoursChange={onWindowHoursChange}
      />
    );

    fireEvent.click(screen.getByLabelText(/Enable send time optimization/i));
    fireEvent.change(screen.getByLabelText(/Optimization window/i), {
      target: { value: "12" },
    });

    expect(screen.getByText(/12 contacts currently have a known optimal hour/i))
      .toBeInTheDocument();
    expect(onEnabledChange).toHaveBeenCalled();
    expect(onWindowHoursChange).toHaveBeenCalledWith(12);
  });
});
