import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { StepOptions } from "@/components/prospects/import-wizard/step-options";

describe("StepOptions", () => {
  it("changes duplicate strategy", () => {
    const onChange = vi.fn();

    render(
      <StepOptions
        tags={[]}
        defaultStatus="prospect"
        duplicateStrategy="skip"
        onChange={onChange}
      />
    );

    fireEvent.click(screen.getByLabelText(/Mettre à jour/i));
    expect(onChange).toHaveBeenCalled();
  });
});
