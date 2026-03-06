import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { WorkflowEnrollmentsTable } from "@/components/workflows/workflow-enrollments-table";

describe("WorkflowEnrollmentsTable", () => {
  it("renders enrollments and exposes pause/resume/cancel actions", () => {
    const onPause = vi.fn();
    const onResume = vi.fn();
    const onCancel = vi.fn();

    render(
      <WorkflowEnrollmentsTable
        enrollments={[
          {
            id: "enr_1",
            workflow_id: "wf_1",
            status: "active",
            enrolled_at: "2026-03-06T10:00:00Z",
            current_step: { id: "step_1", type: "send_email" },
            contact: { first_name: "Ada", last_name: "Lovelace" },
          },
          {
            id: "enr_2",
            workflow_id: "wf_1",
            status: "paused",
            enrolled_at: "2026-03-06T11:00:00Z",
            current_step: { id: "step_2", type: "wait" },
            contact: { email: "paused@example.test" },
          },
        ]}
        onPause={onPause}
        onResume={onResume}
        onCancel={onCancel}
      />
    );

    fireEvent.click(screen.getAllByRole("button", { name: "Pause" })[0]);
    fireEvent.click(screen.getByRole("button", { name: "Resume" }));
    fireEvent.click(screen.getAllByRole("button", { name: "Cancel" })[0]);

    expect(onPause).toHaveBeenCalledWith("enr_1");
    expect(onResume).toHaveBeenCalledWith("enr_2");
    expect(onCancel).toHaveBeenCalledWith("enr_1");
  });
});
