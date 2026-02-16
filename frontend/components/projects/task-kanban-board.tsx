"use client";

import { useMemo, useState, type DragEvent } from "react";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { cn } from "@/lib/utils";
import type { ProjectTask, TaskStatus } from "@/lib/stores/projects";

interface TaskKanbanBoardProps {
  tasks: ProjectTask[];
  onMoveTask: (
    taskId: string,
    targetStatus: TaskStatus
  ) => void | Promise<void>;
  onOpenTask: (task: ProjectTask) => void;
  onBlockedMove?: (taskId: string, targetStatus: TaskStatus) => void;
}

const columns: Array<{ status: TaskStatus; title: string }> = [
  { status: "todo", title: "To do" },
  { status: "in_progress", title: "In progress" },
  { status: "in_review", title: "In review" },
  { status: "done", title: "Done" },
  { status: "blocked", title: "Blocked" },
];

const priorityVariant: Record<string, "default" | "secondary" | "destructive"> =
  {
    urgent: "destructive",
    high: "default",
    medium: "secondary",
    low: "secondary",
  };

export function TaskKanbanBoard({
  tasks,
  onMoveTask,
  onOpenTask,
  onBlockedMove,
}: TaskKanbanBoardProps) {
  const [draggedTaskId, setDraggedTaskId] = useState<string | null>(null);

  const grouped = useMemo(() => {
    return columns.reduce<Record<TaskStatus, ProjectTask[]>>(
      (acc, column) => {
        acc[column.status] = tasks
          .filter((task) => task.status === column.status)
          .sort((a, b) => a.sort_order - b.sort_order);
        return acc;
      },
      {
        todo: [],
        in_progress: [],
        in_review: [],
        done: [],
        blocked: [],
      }
    );
  }, [tasks]);

  const resolveTaskId = (event: DragEvent<HTMLDivElement>): string | null => {
    const dataTransferId = event.dataTransfer.getData("text/plain");

    return dataTransferId || draggedTaskId;
  };

  const handleDrop = (
    event: DragEvent<HTMLDivElement>,
    targetStatus: TaskStatus
  ) => {
    event.preventDefault();

    const taskId = resolveTaskId(event);
    if (!taskId) {
      return;
    }

    const task = tasks.find((item) => item.id === taskId);
    if (!task || task.status === targetStatus) {
      setDraggedTaskId(null);
      return;
    }

    if (task.blocked_by_dependencies && targetStatus === "in_progress") {
      onBlockedMove?.(task.id, targetStatus);
      setDraggedTaskId(null);
      return;
    }

    onMoveTask(task.id, targetStatus);
    setDraggedTaskId(null);
  };

  return (
    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
      {columns.map((column) => (
        <Card
          key={column.status}
          data-testid={`kanban-column-${column.status}`}
          onDragOver={(event) => event.preventDefault()}
          onDrop={(event) => handleDrop(event, column.status)}
          className="bg-muted/30"
        >
          <CardHeader className="pb-3">
            <CardTitle className="text-sm font-semibold">
              {column.title} ({grouped[column.status].length})
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            {grouped[column.status].map((task) => (
              <article
                key={task.id}
                data-testid={`task-card-${task.id}`}
                draggable
                onClick={() => onOpenTask(task)}
                onDragStart={(event) => {
                  setDraggedTaskId(task.id);
                  event.dataTransfer.setData("text/plain", task.id);
                }}
                className={cn(
                  "cursor-pointer rounded-lg border border-border bg-card p-3 shadow-sm transition hover:border-primary/40",
                  task.blocked_by_dependencies &&
                    "border-amber-500/40 bg-amber-50/40 dark:bg-amber-900/10"
                )}
              >
                <div className="flex items-center justify-between gap-2">
                  <h4 className="line-clamp-2 text-sm font-medium">
                    {task.title}
                  </h4>
                  <Badge
                    variant={priorityVariant[task.priority] ?? "secondary"}
                  >
                    {task.priority}
                  </Badge>
                </div>
                {task.due_date && (
                  <p className="mt-2 text-xs text-muted-foreground">
                    Due {task.due_date}
                  </p>
                )}
                {task.blocked_by_dependencies && (
                  <p className="mt-2 text-xs text-amber-700 dark:text-amber-400">
                    Blocked by dependencies
                  </p>
                )}
              </article>
            ))}
            {grouped[column.status].length === 0 && (
              <div className="rounded-md border border-dashed border-border p-3 text-xs text-muted-foreground">
                No tasks
              </div>
            )}
          </CardContent>
        </Card>
      ))}
    </div>
  );
}
