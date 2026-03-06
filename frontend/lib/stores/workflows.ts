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
  type: WorkflowStepType;
  config: Record<string, unknown>;
  next_step_id?: string | null;
  else_step_id?: string | null;
  position_x?: number;
  position_y?: number;
}

export interface WorkflowEnrollment {
  id: string;
  status: "active" | "completed" | "paused" | "cancelled" | "failed";
  enrolled_at?: string;
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

export interface Workflow {
  id: string;
  name: string;
  description?: string | null;
  trigger_type: WorkflowTriggerType;
  trigger_config: Record<string, unknown>;
  status: "draft" | "active" | "paused" | "archived";
  entry_step_id?: string | null;
  steps: WorkflowStep[];
  enrollments: WorkflowEnrollment[];
  active_enrollments_count: number;
  completion_rate: number;
}

interface WorkflowsState {
  workflows: Workflow[];
  currentWorkflow: Workflow | null;
  enrollments: WorkflowEnrollment[];
  isLoading: boolean;
  error: string | null;
  fetchWorkflows: () => Promise<void>;
  fetchWorkflow: (id: string) => Promise<Workflow | null>;
  createWorkflow: (data: Record<string, unknown>) => Promise<Workflow | null>;
  updateWorkflow: (
    id: string,
    data: Record<string, unknown>
  ) => Promise<Workflow | null>;
  deleteWorkflow: (id: string) => Promise<void>;
  activateWorkflow: (id: string) => Promise<Workflow | null>;
  pauseWorkflow: (id: string) => Promise<Workflow | null>;
  createStep: (
    workflowId: string,
    data: Record<string, unknown>
  ) => Promise<WorkflowStep | null>;
  updateStep: (
    stepId: string,
    data: Record<string, unknown>
  ) => Promise<WorkflowStep | null>;
  deleteStep: (stepId: string) => Promise<void>;
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
  next[index] = { ...next[index], ...enrollment };
  return next;
}

function updateStepState(
  steps: WorkflowStep[],
  step: WorkflowStep
): WorkflowStep[] {
  const index = steps.findIndex((item) => item.id === step.id);
  if (index === -1) {
    return [...steps, step];
  }

  const next = [...steps];
  next[index] = step;
  return next;
}

export const useWorkflowsStore = create<WorkflowsState>((set, get) => ({
  workflows: [],
  currentWorkflow: null,
  enrollments: [],
  isLoading: false,
  error: null,

  fetchWorkflows: async () => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<Workflow[]>("/workflows");
      set({ workflows: response.data || [], isLoading: false });
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
      currentWorkflow:
        get().currentWorkflow?.id === id ? workflow : get().currentWorkflow,
      workflows: upsertWorkflow(get().workflows, workflow),
    });
    return workflow;
  },

  pauseWorkflow: async (id) => {
    const response = await apiClient.patch<Workflow>(`/workflows/${id}/pause`);
    const workflow = response.data as Workflow;
    set({
      currentWorkflow:
        get().currentWorkflow?.id === id ? workflow : get().currentWorkflow,
      workflows: upsertWorkflow(get().workflows, workflow),
    });
    return workflow;
  },

  createStep: async (workflowId, data) => {
    const response = await apiClient.post<WorkflowStep>(
      `/workflows/${workflowId}/steps`,
      data
    );
    const step = response.data as WorkflowStep;
    const currentWorkflow = get().currentWorkflow;
    if (currentWorkflow?.id === workflowId) {
      set({
        currentWorkflow: {
          ...currentWorkflow,
          steps: updateStepState(currentWorkflow.steps, step),
        },
      });
    }
    return step;
  },

  updateStep: async (stepId, data) => {
    const response = await apiClient.put<WorkflowStep>(
      `/workflow-steps/${stepId}`,
      data
    );
    const step = response.data as WorkflowStep;
    const currentWorkflow = get().currentWorkflow;
    if (currentWorkflow) {
      set({
        currentWorkflow: {
          ...currentWorkflow,
          steps: updateStepState(currentWorkflow.steps, step),
        },
      });
    }
    return step;
  },

  deleteStep: async (stepId) => {
    await apiClient.delete(`/workflow-steps/${stepId}`);
    const currentWorkflow = get().currentWorkflow;
    if (currentWorkflow) {
      set({
        currentWorkflow: {
          ...currentWorkflow,
          steps: currentWorkflow.steps.filter((step) => step.id !== stepId),
        },
      });
    }
  },

  fetchEnrollments: async (workflowId) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<{ data: WorkflowEnrollment[] }>(
        `/workflows/${workflowId}/enrollments`
      );
      set({
        enrollments: response.data?.data || [],
        isLoading: false,
      });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
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

export const useWorkflowStore = useWorkflowsStore;
