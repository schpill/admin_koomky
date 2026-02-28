import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { TaskListView } from "@/components/projects/task-list-view";
import type { ProjectTask } from "@/lib/stores/projects";

vi.mock("@/components/timer/task-timer-button", () => ({
  TaskTimerButton: ({ taskId }: { taskId: string }) => (
    <div data-testid={`task-timer-button-${taskId}`} />
  ),
}));

const tasks: ProjectTask[] = [
  {
    id: "t1",
    project_id: "p1",
    title: "Write specs",
    description: "",
    status: "todo",
    priority: "high",
    due_date: null,
    sort_order: 0,
  },
];

describe("TaskListView", () => {
  it("renders a timer action for each task row", () => {
    render(
      <TaskListView
        tasks={tasks}
        onStatusChange={vi.fn()}
        onOpenTask={vi.fn()}
      />
    );

    expect(screen.getByText("Timer")).toBeInTheDocument();
    expect(screen.getByTestId("task-timer-button-t1")).toBeInTheDocument();
  });

  it("opens a task when clicking a row", () => {
    const onOpenTask = vi.fn();

    render(
      <TaskListView
        tasks={tasks}
        onStatusChange={vi.fn()}
        onOpenTask={onOpenTask}
      />
    );

    fireEvent.click(screen.getByText("Write specs"));

    expect(onOpenTask).toHaveBeenCalledWith(tasks[0]);
  });
});
