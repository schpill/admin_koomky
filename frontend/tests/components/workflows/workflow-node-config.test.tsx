import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { WorkflowNodeConfig } from "@/components/workflows/workflow-node-config";

describe("WorkflowNodeConfig", () => {
  it("renders selected send email node config and propagates changes", () => {
    const onChange = vi.fn();

    render(
      <WorkflowNodeConfig
        step={{
          id: "step_1",
          type: "send_email",
          config: {
            subject: "Hello",
            content: "World",
          },
          next_step_id: null,
          else_step_id: null,
          position_x: 0,
          position_y: 0,
        }}
        onChange={onChange}
      />
    );

    fireEvent.change(screen.getByLabelText("Subject"), {
      target: { value: "Updated subject" },
    });

    expect(onChange).toHaveBeenCalled();
  });
});
