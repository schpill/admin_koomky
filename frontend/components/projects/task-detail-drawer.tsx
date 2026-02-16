"use client";

import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import type { ProjectTask, TaskStatus } from "@/lib/stores/projects";
import { TimeEntryForm, type TimeEntryFormValues } from "@/components/projects/time-entry-form";

interface TaskDetailDrawerProps {
  open: boolean;
  task: ProjectTask | null;
  onOpenChange: (open: boolean) => void;
  onStatusChange: (taskId: string, status: TaskStatus) => Promise<void> | void;
  onCreateTimeEntry: (taskId: string, data: TimeEntryFormValues) => Promise<void> | void;
}

export function TaskDetailDrawer({
  open,
  task,
  onOpenChange,
  onStatusChange,
  onCreateTimeEntry,
}: TaskDetailDrawerProps) {
  if (!task) {
    return null;
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
        <DialogHeader>
          <DialogTitle>{task.title}</DialogTitle>
          <DialogDescription>
            Task details, dependencies and time tracking
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-4 text-sm">
          <div className="flex items-center gap-2">
            <Badge variant="secondary">{task.status.replace("_", " ")}</Badge>
            <Badge variant={task.priority === "urgent" ? "destructive" : "outline"}>
              {task.priority}
            </Badge>
          </div>

          {task.description && (
            <p className="rounded-md border bg-muted/40 p-3 leading-relaxed">
              {task.description}
            </p>
          )}

          <div className="grid gap-2 sm:grid-cols-2">
            <button
              type="button"
              className="rounded-md border px-3 py-2 text-left hover:bg-muted"
              onClick={() => onStatusChange(task.id, "in_progress")}
            >
              Move to in progress
            </button>
            <button
              type="button"
              className="rounded-md border px-3 py-2 text-left hover:bg-muted"
              onClick={() => onStatusChange(task.id, "done")}
            >
              Mark as done
            </button>
          </div>

          <Separator />

          <section className="space-y-3">
            <h3 className="font-semibold">Log time</h3>
            <TimeEntryForm onSubmit={(values) => onCreateTimeEntry(task.id, values)} />
          </section>
        </div>
      </DialogContent>
    </Dialog>
  );
}
