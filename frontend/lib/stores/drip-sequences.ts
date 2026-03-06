import { create } from "zustand";
import { apiClient } from "@/lib/api";

export interface DripStepInput {
  id?: string;
  position: number;
  delay_hours: number;
  condition: "none" | "if_opened" | "if_clicked" | "if_not_opened";
  subject: string;
  content: string;
  template_id?: string | null;
}

export interface DripEnrollmentContact {
  first_name?: string;
  last_name?: string;
  email?: string;
}

export interface DripEnrollment {
  id: string;
  status: "active" | "completed" | "paused" | "cancelled" | "failed";
  current_step_position: number;
  contact?: DripEnrollmentContact;
}

export interface DripSequence {
  id: string;
  name: string;
  trigger_event: "campaign_sent" | "contact_created" | "manual";
  status: "active" | "paused" | "archived";
  steps: DripStepInput[];
  enrollments: DripEnrollment[];
}

interface DripSequencesState {
  sequences: DripSequence[];
  currentSequence: DripSequence | null;
  enrollments: DripEnrollment[];
  isLoading: boolean;
  error: string | null;
  fetchSequences: () => Promise<void>;
  fetchSequence: (id: string) => Promise<DripSequence | null>;
  createSequence: (
    data: Record<string, unknown>
  ) => Promise<DripSequence | null>;
  updateSequence: (
    id: string,
    data: Record<string, unknown>
  ) => Promise<DripSequence | null>;
  deleteSequence: (id: string) => Promise<void>;
  enrollContact: (sequenceId: string, contactId: string) => Promise<void>;
  enrollSegment: (sequenceId: string, segmentId: string) => Promise<number>;
  pauseEnrollment: (enrollmentId: string) => Promise<void>;
  resumeEnrollment: (enrollmentId: string) => Promise<void>;
  cancelEnrollment: (enrollmentId: string) => Promise<void>;
}

function upsertSequence(
  sequences: DripSequence[],
  sequence: DripSequence
): DripSequence[] {
  const index = sequences.findIndex((item) => item.id === sequence.id);
  if (index === -1) {
    return [sequence, ...sequences];
  }

  const next = [...sequences];
  next[index] = sequence;
  return next;
}

function updateEnrollmentState(
  enrollments: DripEnrollment[],
  enrollment: DripEnrollment
): DripEnrollment[] {
  const index = enrollments.findIndex((item) => item.id === enrollment.id);
  if (index === -1) {
    return [enrollment, ...enrollments];
  }

  const next = [...enrollments];
  next[index] = enrollment;
  return next;
}

export const useDripSequencesStore = create<DripSequencesState>((set, get) => ({
  sequences: [],
  currentSequence: null,
  enrollments: [],
  isLoading: false,
  error: null,

  fetchSequences: async () => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<DripSequence[]>("/drip-sequences");
      set({
        sequences: response.data || [],
        isLoading: false,
      });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
    }
  },

  fetchSequence: async (id) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<DripSequence>(
        `/drip-sequences/${id}`
      );
      const sequence = response.data as DripSequence;
      set({
        currentSequence: sequence,
        sequences: upsertSequence(get().sequences, sequence),
        enrollments: sequence.enrollments || [],
        isLoading: false,
      });
      return sequence;
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  createSequence: async (data) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<DripSequence>(
        "/drip-sequences",
        data
      );
      const sequence = response.data as DripSequence;
      set({
        currentSequence: sequence,
        sequences: upsertSequence(get().sequences, sequence),
        enrollments: sequence.enrollments || [],
        isLoading: false,
      });
      return sequence;
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  updateSequence: async (id, data) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.put<DripSequence>(
        `/drip-sequences/${id}`,
        data
      );
      const sequence = response.data as DripSequence;
      set({
        currentSequence: sequence,
        sequences: upsertSequence(get().sequences, sequence),
        enrollments: sequence.enrollments || get().enrollments,
        isLoading: false,
      });
      return sequence;
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  deleteSequence: async (id) => {
    set({ isLoading: true, error: null });
    try {
      await apiClient.delete(`/drip-sequences/${id}`);
      set({
        sequences: get().sequences.filter((sequence) => sequence.id !== id),
        currentSequence:
          get().currentSequence?.id === id ? null : get().currentSequence,
        isLoading: false,
      });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  enrollContact: async (sequenceId, contactId) => {
    await apiClient.post(`/drip-sequences/${sequenceId}/enroll`, {
      contact_id: contactId,
    });
  },

  enrollSegment: async (sequenceId, segmentId) => {
    const response = await apiClient.post<{ enrolled: number }>(
      `/drip-sequences/${sequenceId}/enroll-segment`,
      {
        segment_id: segmentId,
      }
    );

    return Number(response.data?.enrolled || 0);
  },

  pauseEnrollment: async (enrollmentId) => {
    const response = await apiClient.patch<DripEnrollment>(
      `/drip-enrollments/${enrollmentId}/pause`
    );
    set({
      enrollments: updateEnrollmentState(
        get().enrollments,
        response.data as DripEnrollment
      ),
    });
  },

  resumeEnrollment: async (enrollmentId) => {
    const response = await apiClient.patch<DripEnrollment>(
      `/drip-enrollments/${enrollmentId}/resume`
    );
    set({
      enrollments: updateEnrollmentState(
        get().enrollments,
        response.data as DripEnrollment
      ),
    });
  },

  cancelEnrollment: async (enrollmentId) => {
    const response = await apiClient.patch<DripEnrollment>(
      `/drip-enrollments/${enrollmentId}/cancel`
    );
    set({
      enrollments: updateEnrollmentState(
        get().enrollments,
        response.data as DripEnrollment
      ),
    });
  },
}));
