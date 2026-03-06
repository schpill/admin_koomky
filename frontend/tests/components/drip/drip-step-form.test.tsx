import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { DripStepForm } from "@/components/drip/drip-step-form";

describe("DripStepForm", () => {
  it("renders fields and forwards updates", () => {
    const onChange = vi.fn();

    render(
      <DripStepForm
        value={{
          position: 1,
          delay_hours: 0,
          condition: "none",
          subject: "Welcome",
          content: "<p>Hello</p>",
        }}
        onChange={onChange}
      />
    );

    fireEvent.change(screen.getByLabelText(/subject/i), {
      target: { value: "Updated subject" },
    });

    expect(onChange).toHaveBeenCalled();
    expect(screen.getByDisplayValue("Welcome")).toBeInTheDocument();
  });
});
