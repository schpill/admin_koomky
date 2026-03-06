import type { WorkflowStep } from "@/lib/stores/workflows";

export function normalizeWorkflowStep(
  step: WorkflowStep
): Record<string, unknown> {
  return {
    type: step.type,
    config: step.config || {},
    next_step_id: step.next_step_id || null,
    else_step_id: step.else_step_id || null,
    position_x: step.position_x || 0,
    position_y: step.position_y || 0,
  };
}

export function normalizeWorkflowStepWithMap(
  step: WorkflowStep,
  idMap: Map<string, string>
): Record<string, unknown> {
  return {
    type: step.type,
    config: step.config || {},
    next_step_id: step.next_step_id
      ? idMap.get(step.next_step_id) || step.next_step_id
      : null,
    else_step_id: step.else_step_id
      ? idMap.get(step.else_step_id) || step.else_step_id
      : null,
    position_x: step.position_x || 0,
    position_y: step.position_y || 0,
  };
}
