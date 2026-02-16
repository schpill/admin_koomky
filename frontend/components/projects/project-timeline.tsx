"use client";

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import type { ProjectTask } from "@/lib/stores/projects";

interface ProjectTimelineProps {
  tasks: ProjectTask[];
}

function estimateWidth(task: ProjectTask): number {
  if (!task.estimated_hours || task.estimated_hours <= 0) {
    return 20;
  }

  return Math.min(100, Math.max(20, task.estimated_hours * 8));
}

export function ProjectTimeline({ tasks }: ProjectTimelineProps) {
  const sortedTasks = [...tasks].sort((a, b) => {
    if (!a.due_date) {
      return 1;
    }
    if (!b.due_date) {
      return -1;
    }

    return a.due_date.localeCompare(b.due_date);
  });

  return (
    <Card>
      <CardHeader>
        <CardTitle>Project timeline</CardTitle>
      </CardHeader>
      <CardContent>
        {sortedTasks.length === 0 ? (
          <p className="text-sm text-muted-foreground">No dated tasks to display.</p>
        ) : (
          <div className="space-y-3">
            {sortedTasks.map((task) => (
              <div key={task.id} className="space-y-1">
                <div className="flex items-center justify-between text-sm">
                  <span className="font-medium">{task.title}</span>
                  <span className="text-muted-foreground">{task.due_date || "No due date"}</span>
                </div>
                <div className="h-2 rounded-full bg-muted">
                  <div
                    className="h-2 rounded-full bg-primary"
                    style={{ width: `${estimateWidth(task)}%` }}
                  />
                </div>
              </div>
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  );
}
