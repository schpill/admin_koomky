import { render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { SegmentBuilder } from "@/components/segments/segment-builder";

describe("SegmentBuilder industry/department", () => {
  it("shows industry and department criteria in type selector", () => {
    render(
      <SegmentBuilder
        value={{
          group_boolean: "and",
          criteria_boolean: "or",
          groups: [{ criteria: [{ type: "tag", operator: "equals", value: "vip" }] }],
        }}
        onChange={vi.fn()}
      />
    );

    expect(screen.getByRole("option", { name: "industry" })).toBeInTheDocument();
    expect(screen.getByRole("option", { name: "department" })).toBeInTheDocument();
  });
});
