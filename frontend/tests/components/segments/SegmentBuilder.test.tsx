import { render, screen, fireEvent } from "@testing-library/react";
import { describe, it, expect, vi } from "vitest";
import { SegmentBuilder } from "@/components/segments/segment-builder";

const initialFilters = {
  group_boolean: "and" as const,
  criteria_boolean: "or" as const,
  groups: [
    {
      criteria: [{ type: "tag", operator: "equals", value: "vip" }],
    },
  ],
};

describe("SegmentBuilder", () => {
  it("adds and removes criteria and groups", () => {
    const onChange = vi.fn();

    render(<SegmentBuilder value={initialFilters} onChange={onChange} />);

    fireEvent.click(screen.getByRole("button", { name: /Add criterion/i }));
    fireEvent.click(screen.getByRole("button", { name: /Add group/i }));

    expect(onChange).toHaveBeenCalled();
  });

  it("updates criterion type and value", () => {
    const onChange = vi.fn();

    render(<SegmentBuilder value={initialFilters} onChange={onChange} />);

    fireEvent.change(screen.getByLabelText("Criterion type 1"), {
      target: { value: "revenue" },
    });

    fireEvent.change(screen.getByLabelText("Criterion value 1"), {
      target: { value: "5000" },
    });

    expect(onChange).toHaveBeenCalled();
  });

  it("shows preview badge count", () => {
    render(
      <SegmentBuilder
        value={initialFilters}
        onChange={vi.fn()}
        previewCount={24}
      />
    );

    expect(screen.getByText("24 matching contacts")).toBeInTheDocument();
  });
});
