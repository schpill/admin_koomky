import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { SuppressionListTable } from "@/components/campaigns/suppression-list-table";

describe("SuppressionListTable", () => {
  it("renders suppression rows and remove button", () => {
    const onRemove = vi.fn();

    render(
      <SuppressionListTable
        entries={[
          {
            id: "sup_1",
            email: "blocked@test.dev",
            reason: "hard_bounce",
          },
        ]}
        onRemove={onRemove}
      />
    );

    fireEvent.click(screen.getByRole("button", { name: /remove/i }));

    expect(screen.getByText("blocked@test.dev")).toBeInTheDocument();
    expect(screen.getByText("hard_bounce")).toBeInTheDocument();
    expect(onRemove).toHaveBeenCalledWith("sup_1");
  });
});
