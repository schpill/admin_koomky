import { create } from "zustand";
import { apiClient } from "@/lib/api";

interface ActiveTimerEntry {
  id: string;
  task_id: string;
  task_name: string;
  project_id: string;
  project_name: string;
  started_at: string;
  description: string | null;
}

interface TimerState {
  activeEntry: ActiveTimerEntry | null;
  elapsedSeconds: number;
  isRunning: boolean;
  taskId: string | null;
  projectId: string | null;
  isLoading: boolean;
  error: string | null;

  fetchActive: () => Promise<void>;
  startTimer: (
    taskId: string,
    projectId: string,
    description?: string
  ) => Promise<void>;
  stopTimer: () => Promise<void>;
  cancelTimer: () => Promise<void>;
  tick: () => void;
}

function isActiveTimerEntry(value: unknown): value is ActiveTimerEntry {
  if (!value || typeof value !== "object") {
    return false;
  }

  const candidate = value as Record<string, unknown>;

  return (
    typeof candidate.id === "string" &&
    typeof candidate.task_id === "string" &&
    typeof candidate.task_name === "string" &&
    typeof candidate.project_id === "string" &&
    typeof candidate.project_name === "string" &&
    typeof candidate.started_at === "string"
  );
}

export const useTimerStore = create<TimerState>((set, get) => ({
  activeEntry: null,
  elapsedSeconds: 0,
  isRunning: false,
  taskId: null,
  projectId: null,
  isLoading: false,
  error: null,

  fetchActive: async () => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<ActiveTimerEntry | null>(
        "/timer/active"
      );
      const entry = response.data;

      if (isActiveTimerEntry(entry)) {
        const startedAt = new Date(entry.started_at);
        const elapsed = Math.floor((Date.now() - startedAt.getTime()) / 1000);

        set({
          activeEntry: entry,
          elapsedSeconds: elapsed,
          isRunning: true,
          taskId: entry.task_id,
          projectId: entry.project_id,
          isLoading: false,
        });
      } else {
        set({
          activeEntry: null,
          elapsedSeconds: 0,
          isRunning: false,
          taskId: null,
          projectId: null,
          isLoading: false,
        });
      }
    } catch (error) {
      if ((error as any)?.response?.status === 204) {
        set({
          activeEntry: null,
          elapsedSeconds: 0,
          isRunning: false,
          taskId: null,
          projectId: null,
          isLoading: false,
          error: null,
        });

        return;
      }

      set({ isLoading: false, error: (error as Error).message });
    }
  },

  startTimer: async (
    taskId: string,
    projectId: string,
    description?: string
  ) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<ActiveTimerEntry>("/timer/start", {
        task_id: taskId,
        description,
      });

      const entry = response.data;

      if (!isActiveTimerEntry(entry)) {
        throw new Error("Invalid timer response");
      }

      set({
        activeEntry: entry,
        elapsedSeconds: 0,
        isRunning: true,
        taskId: entry.task_id,
        projectId: entry.project_id,
        isLoading: false,
      });
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  stopTimer: async () => {
    set({ isLoading: true, error: null });
    try {
      await apiClient.post("/timer/stop");

      set({
        activeEntry: null,
        elapsedSeconds: 0,
        isRunning: false,
        taskId: null,
        projectId: null,
        isLoading: false,
      });
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  cancelTimer: async () => {
    set({ isLoading: true, error: null });
    try {
      await apiClient.delete("/timer/cancel");

      set({
        activeEntry: null,
        elapsedSeconds: 0,
        isRunning: false,
        taskId: null,
        projectId: null,
        isLoading: false,
      });
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  tick: () => {
    const { isRunning, activeEntry } = get();
    if (isRunning && activeEntry) {
      const startedAt = new Date(activeEntry.started_at);
      const elapsed = Math.floor((Date.now() - startedAt.getTime()) / 1000);
      set({ elapsedSeconds: elapsed });
    }
  },
}));
