import { beforeAll, describe, expect, it, vi } from "vitest";
import { fireEvent, render, screen } from "@testing-library/react";
import { WorkflowBuilder } from "@/components/workflows/workflow-builder";
import type { Workflow } from "@/lib/stores/workflows";

beforeAll(() => {
  global.ResizeObserver = class {
    observe() {}
    unobserve() {}
    disconnect() {}
  } as typeof ResizeObserver;
});

function makeWorkflow(): Workflow {
  return {
    id: "wf_1",
    name: "Lifecycle workflow",
    description: "Workflow details",
    trigger_type: "manual",
    trigger_config: {},
    status: "draft",
    entry_step_id: null,
    steps: [],
    enrollments: [],
    active_enrollments_count: 0,
    completion_rate: 0,
  };
}

describe("WorkflowBuilder", () => {
  it("renders a visual workflow canvas with enhanced controls", () => {
    render(
      <WorkflowBuilder
        value={makeWorkflow()}
        onChange={vi.fn()}
        onSave={vi.fn(async () => undefined)}
      />
    );

    expect(screen.getByTestId("workflow-builder-canvas")).toBeInTheDocument();
    expect(
      screen.getByRole("button", { name: "Fit workflow" })
    ).toBeInTheDocument();
    expect(screen.getByText("Navigator")).toBeInTheDocument();
    expect(
      screen.queryByText(/Visual list fallback active/i)
    ).not.toBeInTheDocument();
  });

  it("adds a step to the visual flow and opens its configuration panel", () => {
    const handleChange = vi.fn();

    render(
      <WorkflowBuilder
        value={makeWorkflow()}
        onChange={handleChange}
        onSave={vi.fn(async () => undefined)}
      />
    );

    fireEvent.click(screen.getByRole("button", { name: "Add send email" }));

    expect(handleChange).toHaveBeenCalledTimes(1);
    expect(handleChange.mock.calls[0]?.[0].steps).toHaveLength(1);
    expect(handleChange.mock.calls[0]?.[0].steps[0]?.type).toBe("send_email");
  });
});
