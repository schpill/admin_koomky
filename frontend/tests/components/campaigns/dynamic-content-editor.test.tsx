import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { DynamicContentEditor } from "@/components/campaigns/dynamic-content-editor";

describe("DynamicContentEditor", () => {
  it("builds and inserts a conditional snippet", () => {
    const onInsert = vi.fn();

    render(<DynamicContentEditor onInsert={onInsert} />);

    fireEvent.change(screen.getByLabelText(/Attribute/i), {
      target: { value: "client.industry" },
    });
    fireEvent.change(screen.getByLabelText(/Operator/i), {
      target: { value: "==" },
    });
    fireEvent.change(screen.getByLabelText(/^Value$/i), {
      target: { value: "Wedding Planner" },
    });
    fireEvent.change(screen.getByLabelText(/If true/i), {
      target: { value: "VIP content" },
    });
    fireEvent.change(screen.getByLabelText(/If false/i), {
      target: { value: "Standard content" },
    });

    fireEvent.click(screen.getByRole("button", { name: /Insert block/i }));

    expect(onInsert).toHaveBeenCalledWith(
      '{{#if client.industry == "Wedding Planner"}}VIP content{{else}}Standard content{{/if}}'
    );
  });
});
