import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { StepMapping } from "@/components/prospects/import-wizard/step-mapping";

describe("StepMapping", () => {
  it("updates mapping on select change", () => {
    const onChange = vi.fn();

    render(
      <StepMapping
        columnList={["Nom"]}
        previewRows={[]}
        mapping={{ Nom: "name" }}
        onChange={onChange}
      />
    );

    fireEvent.change(screen.getByDisplayValue("name"), {
      target: { value: "email" },
    });

    expect(onChange).toHaveBeenCalled();
  });
});
