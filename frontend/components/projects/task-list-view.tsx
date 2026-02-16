"use client";

import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Badge } from "@/components/ui/badge";
import type { ProjectTask, TaskStatus } from "@/lib/stores/projects";

interface TaskListViewProps {
  tasks: ProjectTask[];
  onStatusChange: (taskId: string, status: TaskStatus) => void | Promise<void>;
  onOpenTask: (task: ProjectTask) => void;
}

export function TaskListView({ tasks, onStatusChange, onOpenTask }: TaskListViewProps) {
  return (
    <div className="overflow-hidden rounded-lg border">
      <table className="w-full text-sm">
        <thead className="bg-muted/50 text-left">
          <tr>
            <th className="px-4 py-3 font-medium">Title</th>
            <th className="px-4 py-3 font-medium">Priority</th>
            <th className="px-4 py-3 font-medium">Due date</th>
            <th className="px-4 py-3 font-medium">Status</th>
          </tr>
        </thead>
        <tbody>
          {tasks.map((task) => (
            <tr
              key={task.id}
              className="border-t transition hover:bg-muted/30"
              onClick={() => onOpenTask(task)}
            >
              <td className="px-4 py-3">
                <div className="font-medium">{task.title}</div>
                {task.description && (
                  <div className="line-clamp-1 text-xs text-muted-foreground">{task.description}</div>
                )}
              </td>
              <td className="px-4 py-3">
                <Badge variant={task.priority === "urgent" ? "destructive" : "secondary"}>
                  {task.priority}
                </Badge>
              </td>
              <td className="px-4 py-3 text-muted-foreground">{task.due_date || "-"}</td>
              <td
                className="px-4 py-3"
                onClick={(event) => {
                  event.stopPropagation();
                }}
              >
                <Select
                  value={task.status}
                  onValueChange={(value) => onStatusChange(task.id, value as TaskStatus)}
                >
                  <SelectTrigger className="h-8 w-[150px]">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="todo">To do</SelectItem>
                    <SelectItem value="in_progress">In progress</SelectItem>
                    <SelectItem value="in_review">In review</SelectItem>
                    <SelectItem value="done">Done</SelectItem>
                    <SelectItem value="blocked">Blocked</SelectItem>
                  </SelectContent>
                </Select>
              </td>
            </tr>
          ))}
          {tasks.length === 0 && (
            <tr>
              <td colSpan={4} className="px-4 py-6 text-center text-muted-foreground">
                No tasks yet
              </td>
            </tr>
          )}
        </tbody>
      </table>
    </div>
  );
}
