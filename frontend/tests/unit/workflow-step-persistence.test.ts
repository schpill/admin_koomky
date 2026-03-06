import { describe, expect, it } from "vitest";
import {
  normalizeWorkflowStep,
  normalizeWorkflowStepWithMap,
} from "@/components/workflows/workflow-step-persistence";
import type { WorkflowStep } from "@/lib/stores/workflows";

describe("workflow-step-persistence", () => {
  it("normalizes nullable workflow step fields", () => {
    const step: WorkflowStep = {
      id: "step-1",
      type: "wait",
      config: {},
    };

    expect(normalizeWorkflowStep(step)).toEqual({
      type: "wait",
      config: {},
      next_step_id: null,
      else_step_id: null,
      position_x: 0,
      position_y: 0,
    });
  });

  it("resolves temporary ids when an id map is provided", () => {
    const step: WorkflowStep = {
      id: "tmp-condition",
      type: "condition",
      config: { attribute: "email_score", operator: "gte", value: 50 },
      next_step_id: "tmp-next",
      else_step_id: "tmp-else",
      position_x: 32,
      position_y: 64,
    };

    const idMap = new Map<string, string>([
      ["tmp-next", "step-2"],
      ["tmp-else", "step-3"],
    ]);

    expect(normalizeWorkflowStepWithMap(step, idMap)).toEqual({
      type: "condition",
      config: { attribute: "email_score", operator: "gte", value: 50 },
      next_step_id: "step-2",
      else_step_id: "step-3",
      position_x: 32,
      position_y: 64,
    });
  });
});
