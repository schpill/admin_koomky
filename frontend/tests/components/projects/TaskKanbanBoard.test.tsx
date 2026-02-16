import { render, screen, fireEvent } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { TaskKanbanBoard } from "@/components/projects/task-kanban-board";
import type { ProjectTask, TaskStatus } from "@/lib/stores/projects";

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
  {
    id: "t2",
    project_id: "p1",
    title: "Blocked task",
    description: "",
    status: "todo",
    priority: "medium",
    due_date: null,
    sort_order: 1,
    blocked_by_dependencies: true,
  },
];

describe("TaskKanbanBoard", () => {
  it("renders columns and tasks", () => {
    render(
      <TaskKanbanBoard
        tasks={tasks}
        onMoveTask={vi.fn()}
        onOpenTask={vi.fn()}
      />
    );

    expect(screen.getByText(/To do/)).toBeInTheDocument();
    expect(screen.getByText(/In progress/)).toBeInTheDocument();
    expect(screen.getByText("Write specs")).toBeInTheDocument();
  });

  it("moves task on drag and drop", () => {
    const onMoveTask = vi.fn();

    render(
      <TaskKanbanBoard
        tasks={tasks}
        onMoveTask={onMoveTask}
        onOpenTask={vi.fn()}
      />
    );

    const taskCard = screen.getByTestId("task-card-t1");
    const targetColumn = screen.getByTestId("kanban-column-in_progress");

    fireEvent.dragStart(taskCard, {
      dataTransfer: {
        setData: vi.fn(),
        getData: () => "t1",
      },
    });

    fireEvent.dragOver(targetColumn);
    fireEvent.drop(targetColumn, {
      dataTransfer: {
        getData: () => "t1",
      },
    });

    expect(onMoveTask).toHaveBeenCalledWith("t1", "in_progress");
  });

  it("prevents moving blocked task to in_progress", () => {
    const onMoveTask = vi.fn();
    const onBlockedMove = vi.fn();

    render(
      <TaskKanbanBoard
        tasks={tasks}
        onMoveTask={onMoveTask}
        onBlockedMove={onBlockedMove}
        onOpenTask={vi.fn()}
      />
    );

    const taskCard = screen.getByTestId("task-card-t2");
    const targetColumn = screen.getByTestId("kanban-column-in_progress");

    fireEvent.dragStart(taskCard, {
      dataTransfer: {
        setData: vi.fn(),
        getData: () => "t2",
      },
    });

    fireEvent.drop(targetColumn, {
      dataTransfer: {
        getData: () => "t2",
      },
    });

    expect(onMoveTask).not.toHaveBeenCalled();
    expect(onBlockedMove).toHaveBeenCalledWith(
      "t2",
      "in_progress" as TaskStatus
    );
  });
});
