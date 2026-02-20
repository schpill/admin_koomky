"use client";

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";

interface ProjectOverviewProps {
  project: {
    id: string;
    name: string;
    status: string;
    progress_percentage?: number;
    total_time_spent?: number;
    budget_consumed?: number;
    total_tasks?: number;
    completed_tasks?: number;
    billing_type?: "hourly" | "fixed";
  };
}

function formatDuration(minutes: number): string {
  const safeMinutes = Number.isFinite(minutes) ? minutes : 0;
  const hours = Math.floor(safeMinutes / 60);
  const remaining = safeMinutes % 60;

  return `${hours}h ${remaining}m tracked`;
}

function formatCurrency(value: number): string {
  return `${value.toLocaleString("en-US", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })} EUR used`;
}

function statusVariant(
  status: string
): "default" | "secondary" | "destructive" {
  if (status === "completed") {
    return "default";
  }

  if (status === "cancelled") {
    return "destructive";
  }

  return "secondary";
}

export function ProjectOverview({ project }: ProjectOverviewProps) {
  const progress = Math.max(
    0,
    Math.min(100, Math.round(project.progress_percentage ?? 0))
  );
  const totalTime = Math.round(project.total_time_spent ?? 0);
  const budget = Number(project.budget_consumed ?? 0);
  const completedTasks = project.completed_tasks ?? 0;
  const totalTasks = project.total_tasks ?? 0;

  return (
    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      <Card>
        <CardHeader className="pb-2">
          <CardTitle className="text-sm font-medium">Progress</CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          <div className="flex items-center justify-between">
            <span className="text-2xl font-semibold">{progress}%</span>
            <Badge
              variant={statusVariant(project.status)}
              className="capitalize"
            >
              {project.status.replace("_", " ")}
            </Badge>
          </div>
          <p className="text-xs text-muted-foreground">{progress}% complete</p>
          <div className="h-2 rounded-full bg-muted">
            <div
              className="h-2 rounded-full bg-primary transition-all"
              style={{ width: `${progress}%` }}
              aria-label={`${progress}% complete`}
            />
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="pb-2">
          <CardTitle className="text-sm font-medium">Time tracked</CardTitle>
        </CardHeader>
        <CardContent className="space-y-2">
          <span className="text-2xl font-semibold">
            {Math.floor(totalTime / 60)}h
          </span>
          <p className="text-xs text-muted-foreground">
            {formatDuration(totalTime)}
          </p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="pb-2">
          <CardTitle className="text-sm font-medium">
            Budget consumption
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-2">
          <span className="text-2xl font-semibold">
            {budget.toLocaleString("en-US")}
          </span>
          <p className="text-xs text-muted-foreground">
            {formatCurrency(budget)}
          </p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className="pb-2">
          <CardTitle className="text-sm font-medium">Task completion</CardTitle>
        </CardHeader>
        <CardContent className="space-y-2">
          <span className="text-2xl font-semibold">
            {completedTasks} / {totalTasks}
          </span>
          <p className="text-xs text-muted-foreground">
            {completedTasks} / {totalTasks} tasks completed
          </p>
        </CardContent>
      </Card>
    </div>
  );
}
