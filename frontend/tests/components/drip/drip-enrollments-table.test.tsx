import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { DripEnrollmentsTable } from "@/components/drip/drip-enrollments-table";

describe("DripEnrollmentsTable", () => {
  it("renders enrollments and action buttons", () => {
    const onPause = vi.fn();
    const onCancel = vi.fn();

    render(
      <DripEnrollmentsTable
        enrollments={[
          {
            id: "enr_1",
            status: "active",
            current_step_position: 1,
            contact: { first_name: "Ada", last_name: "Lovelace" },
          },
        ]}
        onPause={onPause}
        onCancel={onCancel}
      />
    );

    fireEvent.click(screen.getByRole("button", { name: /pause/i }));
    fireEvent.click(screen.getByRole("button", { name: /cancel/i }));

    expect(screen.getByText("Ada Lovelace")).toBeInTheDocument();
    expect(onPause).toHaveBeenCalledWith("enr_1");
    expect(onCancel).toHaveBeenCalledWith("enr_1");
  });
});
