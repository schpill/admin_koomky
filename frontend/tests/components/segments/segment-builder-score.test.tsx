import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { SegmentBuilder } from "@/components/segments/segment-builder";

describe("SegmentBuilder email score", () => {
  it("supports email_score type with numeric operators", () => {
    const onChange = vi.fn();

    render(
      <SegmentBuilder
        value={{
          group_boolean: "and",
          criteria_boolean: "or",
          groups: [
            {
              criteria: [
                {
                  type: "tag",
                  operator: "equals",
                  value: "vip",
                },
              ],
            },
          ],
        }}
        onChange={onChange}
      />
    );

    fireEvent.change(screen.getByLabelText("Criterion type 1"), {
      target: { value: "email_score" },
    });

    const operatorSelect = screen.getByLabelText("Operator");
    fireEvent.change(operatorSelect, {
      target: { value: "gte" },
    });

    fireEvent.change(screen.getByLabelText("Criterion value 1"), {
      target: { value: "50" },
    });

    expect(onChange).toHaveBeenCalled();
  });
});
