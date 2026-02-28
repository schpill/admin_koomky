import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { TaskTimerButton } from "@/components/timer/task-timer-button";

const timerStoreMock = vi.fn();
const toastSuccess = vi.fn();
const toastError = vi.fn();

vi.mock("@/lib/stores/timer", () => ({
  useTimerStore: () => timerStoreMock(),
}));

vi.mock("sonner", () => ({
  toast: {
    success: (...args: unknown[]) => toastSuccess(...args),
    error: (...args: unknown[]) => toastError(...args),
  },
}));

describe("TaskTimerButton", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it("starts a timer for the current task", async () => {
    const startTimer = vi.fn().mockResolvedValue(undefined);

    timerStoreMock.mockReturnValue({
      isRunning: false,
      taskId: null,
      startTimer,
      stopTimer: vi.fn(),
      isLoading: false,
    });

    render(
      <TaskTimerButton
        taskId="task-1"
        projectId="project-1"
        taskName="Write tests"
      />
    );

    fireEvent.click(screen.getByRole("button"));

    await waitFor(() => {
      expect(startTimer).toHaveBeenCalledWith("task-1", "project-1");
    });
  });

  it("stops the timer when the current task is active", async () => {
    const stopTimer = vi.fn().mockResolvedValue(undefined);

    timerStoreMock.mockReturnValue({
      isRunning: true,
      taskId: "task-1",
      startTimer: vi.fn(),
      stopTimer,
      isLoading: false,
    });

    render(
      <TaskTimerButton
        taskId="task-1"
        projectId="project-1"
        taskName="Write tests"
      />
    );

    fireEvent.click(screen.getByRole("button"));

    await waitFor(() => {
      expect(stopTimer).toHaveBeenCalledOnce();
    });
  });

  it("disables the button when another task already has an active timer", () => {
    timerStoreMock.mockReturnValue({
      isRunning: true,
      taskId: "task-2",
      startTimer: vi.fn(),
      stopTimer: vi.fn(),
      isLoading: false,
    });

    render(
      <TaskTimerButton
        taskId="task-1"
        projectId="project-1"
        taskName="Write tests"
      />
    );

    expect(screen.getByRole("button")).toBeDisabled();
    expect(screen.getByRole("button")).toHaveAttribute(
      "title",
      "Un timer est déjà actif sur une autre tâche"
    );
  });
});
