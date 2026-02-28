import { beforeEach, describe, expect, it, vi } from "vitest";
import { useTimerStore } from "@/lib/stores/timer";

vi.mock("@/lib/api", () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
    delete: vi.fn(),
  },
}));

import { apiClient } from "@/lib/api";

describe("useTimerStore", () => {
  beforeEach(() => {
    useTimerStore.setState({
      activeEntry: null,
      elapsedSeconds: 0,
      isRunning: false,
      taskId: null,
      projectId: null,
      isLoading: false,
      error: null,
    });
    vi.clearAllMocks();
  });

  it("hydrates state from the active timer endpoint", async () => {
    (apiClient.get as any).mockResolvedValue({
      data: {
        id: "entry-1",
        task_id: "task-1",
        task_name: "Write tests",
        project_id: "project-1",
        project_name: "Phase 13",
        started_at: new Date(Date.now() - 65_000).toISOString(),
        description: "Focus session",
      },
    });

    await useTimerStore.getState().fetchActive();

    const state = useTimerStore.getState();
    expect(state.isRunning).toBe(true);
    expect(state.taskId).toBe("task-1");
    expect(state.projectId).toBe("project-1");
    expect(state.elapsedSeconds).toBeGreaterThanOrEqual(65);
  });

  it("clears state when the server reports no active timer", async () => {
    useTimerStore.setState({
      activeEntry: {
        id: "entry-1",
        task_id: "task-1",
        task_name: "Write tests",
        project_id: "project-1",
        project_name: "Phase 13",
        started_at: new Date().toISOString(),
        description: null,
      },
      elapsedSeconds: 42,
      isRunning: true,
      taskId: "task-1",
      projectId: "project-1",
      isLoading: false,
      error: null,
    });

    (apiClient.get as any).mockRejectedValue({
      response: {
        status: 204,
      },
    });

    await useTimerStore.getState().fetchActive();

    const state = useTimerStore.getState();
    expect(state.activeEntry).toBeNull();
    expect(state.isRunning).toBe(false);
    expect(state.elapsedSeconds).toBe(0);
  });

  it("ignores malformed active timer payloads", async () => {
    (apiClient.get as any).mockResolvedValue({
      data: {},
    });

    await useTimerStore.getState().fetchActive();

    const state = useTimerStore.getState();
    expect(state.activeEntry).toBeNull();
    expect(state.isRunning).toBe(false);
    expect(state.elapsedSeconds).toBe(0);
  });

  it("starts and stops a timer", async () => {
    (apiClient.post as any)
      .mockResolvedValueOnce({
        data: {
          id: "entry-1",
          task_id: "task-1",
          task_name: "Write tests",
          project_id: "project-1",
          project_name: "Phase 13",
          started_at: new Date().toISOString(),
          description: null,
        },
      })
      .mockResolvedValueOnce({ data: { id: "entry-1" } });

    await useTimerStore.getState().startTimer("task-1", "project-1");

    expect(useTimerStore.getState().isRunning).toBe(true);
    expect(useTimerStore.getState().taskId).toBe("task-1");

    await useTimerStore.getState().stopTimer();

    expect(useTimerStore.getState().activeEntry).toBeNull();
    expect(useTimerStore.getState().isRunning).toBe(false);
  });

  it("ticks from the active entry started_at timestamp", () => {
    useTimerStore.setState({
      activeEntry: {
        id: "entry-1",
        task_id: "task-1",
        task_name: "Write tests",
        project_id: "project-1",
        project_name: "Phase 13",
        started_at: new Date(Date.now() - 5_000).toISOString(),
        description: null,
      },
      elapsedSeconds: 0,
      isRunning: true,
      taskId: "task-1",
      projectId: "project-1",
      isLoading: false,
      error: null,
    });

    useTimerStore.getState().tick();

    expect(useTimerStore.getState().elapsedSeconds).toBeGreaterThanOrEqual(5);
  });
});
