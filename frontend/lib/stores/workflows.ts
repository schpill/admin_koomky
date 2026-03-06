import { create } from "zustand";
import { apiClient } from "@/lib/api";

export type WorkflowTriggerType =
  | "email_opened"
  | "email_clicked"
  | "score_threshold"
  | "contact_created"
  | "contact_updated"
  | "segment_entered"
  | "manual";

export type WorkflowStatus = "draft" | "active" | "paused" | "archived";

export type WorkflowStepType =
  | "send_email"
  | "wait"
  | "condition"
  | "update_score"
  | "add_tag"
  | "remove_tag"
  | "enroll_drip"
  | "update_field"
  | "end";

export interface WorkflowStep {
  id?: string;
  workflow_id?: string;
  type: WorkflowStepType;
  config: Record<string, unknown>;
  next_step_id?: string | null;
  else_step_id?: string | null;
  position_x: number;
  position_y: number;
}

export interface WorkflowEnrollment {
  id: string;
  workflow_id: string;
  status: "active" | "completed" | "paused" | "cancelled" | "failed";
  enrolled_at?: string | null;
  current_step_id?: string | null;
  current_step?: {
    id: string;
    type: WorkflowStepType;
  } | null;
  contact?: {
    first_name?: string;
    last_name?: string;
    email?: string;
  } | null;
}

export interface WorkflowAnalytics {
  active_enrollments: number;
  completion_rate: number;
  dropoff_by_step: Array<{
    step_id: string;
    type: WorkflowStepType;
    count: number;
  }>;
}

export interface Workflow {
  id: string;
  name: string;
  description?: string | null;
  trigger_type: WorkflowTriggerType;
  trigger_config?: Record<string, unknown> | null;
  status: WorkflowStatus;
  entry_step_id?: string | null;
  steps: WorkflowStep[];
  enrollments: WorkflowEnrollment[];
  analytics?: WorkflowAnalytics;
}

type WorkflowInput = Omit<
  Workflow,
  "id" | "steps" | "enrollments" | "analytics"
> & {
  steps?: WorkflowStep[];
};

interface WorkflowState {
  workflows: Workflow[];
  currentWorkflow: Workflow | null;
  enrollments: WorkflowEnrollment[];
  isLoading: boolean;
  error: string | null;
  fetchWorkflows: () => Promise<void>;
  fetchWorkflow: (id: string) => Promise<Workflow | null>;
  createWorkflow: (data: Partial<WorkflowInput>) => Promise<Workflow | null>;
  updateWorkflow: (
    id: string,
    data: Partial<WorkflowInput>
  ) => Promise<Workflow | null>;
  deleteWorkflow: (id: string) => Promise<void>;
  activateWorkflow: (id: string) => Promise<Workflow | null>;
  pauseWorkflow: (id: string) => Promise<Workflow | null>;
  createStep: (workflowId: string, step: WorkflowStep) => Promise<WorkflowStep>;
  updateStep: (stepId: string, step: WorkflowStep) => Promise<WorkflowStep>;
  deleteStep: (stepId: string) => Promise<void>;
  saveWorkflowGraph: (
    workflowId: string,
    steps: WorkflowStep[],
    entryStepId?: string | null
  ) => Promise<void>;
  fetchEnrollments: (workflowId: string) => Promise<void>;
  pauseEnrollment: (enrollmentId: string) => Promise<void>;
  resumeEnrollment: (enrollmentId: string) => Promise<void>;
  cancelEnrollment: (enrollmentId: string) => Promise<void>;
}

function upsertWorkflow(workflows: Workflow[], workflow: Workflow): Workflow[] {
  const index = workflows.findIndex((item) => item.id === workflow.id);
  if (index === -1) {
    return [workflow, ...workflows];
  }

  const next = [...workflows];
  next[index] = workflow;
  return next;
}

function updateEnrollmentState(
  enrollments: WorkflowEnrollment[],
  enrollment: WorkflowEnrollment
): WorkflowEnrollment[] {
  const index = enrollments.findIndex((item) => item.id === enrollment.id);
  if (index === -1) {
    return [enrollment, ...enrollments];
  }

  const next = [...enrollments];
  next[index] = enrollment;
  return next;
}

export const useWorkflowStore = create<WorkflowState>((set, get) => ({
  workflows: [],
  currentWorkflow: null,
  enrollments: [],
  isLoading: false,
  error: null,

  fetchWorkflows: async () => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<Workflow[]>("/workflows");
      set({
        workflows: response.data || [],
        isLoading: false,
      });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
    }
  },

  fetchWorkflow: async (id) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<Workflow>(`/workflows/${id}`);
      const workflow = response.data as Workflow;
      set({
        currentWorkflow: workflow,
        workflows: upsertWorkflow(get().workflows, workflow),
        enrollments: workflow.enrollments || [],
        isLoading: false,
      });
      return workflow;
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  createWorkflow: async (data) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<Workflow>("/workflows", data);
      const workflow = response.data as Workflow;
      set({
        currentWorkflow: workflow,
        workflows: upsertWorkflow(get().workflows, workflow),
        enrollments: workflow.enrollments || [],
        isLoading: false,
      });
      return workflow;
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  updateWorkflow: async (id, data) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.put<Workflow>(`/workflows/${id}`, data);
      const workflow = response.data as Workflow;
      set({
        currentWorkflow: workflow,
        workflows: upsertWorkflow(get().workflows, workflow),
        enrollments: workflow.enrollments || get().enrollments,
        isLoading: false,
      });
      return workflow;
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  deleteWorkflow: async (id) => {
    set({ isLoading: true, error: null });
    try {
      await apiClient.delete(`/workflows/${id}`);
      set({
        workflows: get().workflows.filter((workflow) => workflow.id !== id),
        currentWorkflow:
          get().currentWorkflow?.id === id ? null : get().currentWorkflow,
        isLoading: false,
      });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  activateWorkflow: async (id) => {
    const response = await apiClient.patch<Workflow>(
      `/workflows/${id}/activate`
    );
    const workflow = response.data as Workflow;
    set({
      currentWorkflow: workflow,
      workflows: upsertWorkflow(get().workflows, workflow),
    });
    return workflow;
  },

  pauseWorkflow: async (id) => {
    const response = await apiClient.patch<Workflow>(`/workflows/${id}/pause`);
    const workflow = response.data as Workflow;
    set({
      currentWorkflow: workflow,
      workflows: upsertWorkflow(get().workflows, workflow),
    });
    return workflow;
  },

  createStep: async (workflowId, step) => {
    const response = await apiClient.post<WorkflowStep>(
      `/workflows/${workflowId}/steps`,
      step
    );
    return response.data as WorkflowStep;
  },

  updateStep: async (stepId, step) => {
    const response = await apiClient.put<WorkflowStep>(
      `/workflow-steps/${stepId}`,
      step
    );
    return response.data as WorkflowStep;
  },

  deleteStep: async (stepId) => {
    await apiClient.delete(`/workflow-steps/${stepId}`);
  },

  saveWorkflowGraph: async (workflowId, steps, entryStepId) => {
    const current = get().currentWorkflow;
    const existing = new Map(
      (current?.steps || []).map((step) => [step.id, step])
    );
    const nextIds = new Set(steps.map((step) => step.id).filter(Boolean));

    for (const currentStep of current?.steps || []) {
      if (currentStep.id && !nextIds.has(currentStep.id)) {
        await get().deleteStep(currentStep.id);
      }
    }

    const idMap = new Map<string, string>();
    for (const step of steps) {
      if (step.id) {
        idMap.set(step.id, step.id);
        continue;
      }
      const clientTempId = `temp-${Math.random().toString(36).slice(2)}`;
      const created = await get().createStep(workflowId, {
        ...step,
        next_step_id: null,
        else_step_id: null,
      });
      if (created.id) {
        idMap.set(clientTempId, created.id);
        step.id = created.id;
      }
    }

    for (const step of steps) {
      const persistedId = step.id || "";
      const payload: WorkflowStep = {
        ...step,
        next_step_id:
          steps.find((candidate) => candidate.id === step.next_step_id)?.id ||
          step.next_step_id ||
          null,
        else_step_id:
          steps.find((candidate) => candidate.id === step.else_step_id)?.id ||
          step.else_step_id ||
          null,
      };

      if (!persistedId) {
        continue;
      }

      if (!existing.has(persistedId)) {
        await get().updateStep(persistedId, payload);
        continue;
      }

      await get().updateStep(persistedId, payload);
    }

    await get().updateWorkflow(workflowId, {
      entry_step_id: entryStepId || steps[0]?.id || null,
    });
    await get().fetchWorkflow(workflowId);
  },

  fetchEnrollments: async (workflowId) => {
    const response = await apiClient.get<{
      data: WorkflowEnrollment[];
    }>(`/workflows/${workflowId}/enrollments`);

    const payload = response.data as unknown as { data?: WorkflowEnrollment[] };
    set({ enrollments: payload.data || [] });
  },

  pauseEnrollment: async (enrollmentId) => {
    const response = await apiClient.patch<WorkflowEnrollment>(
      `/workflow-enrollments/${enrollmentId}/pause`
    );
    set({
      enrollments: updateEnrollmentState(
        get().enrollments,
        response.data as WorkflowEnrollment
      ),
    });
  },

  resumeEnrollment: async (enrollmentId) => {
    const response = await apiClient.patch<WorkflowEnrollment>(
      `/workflow-enrollments/${enrollmentId}/resume`
    );
    set({
      enrollments: updateEnrollmentState(
        get().enrollments,
        response.data as WorkflowEnrollment
      ),
    });
  },

  cancelEnrollment: async (enrollmentId) => {
    const response = await apiClient.patch<WorkflowEnrollment>(
      `/workflow-enrollments/${enrollmentId}/cancel`
    );
    set({
      enrollments: updateEnrollmentState(
        get().enrollments,
        response.data as WorkflowEnrollment
      ),
    });
  },
}));
