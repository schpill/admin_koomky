"use client";

import { useEffect, useMemo, useState } from "react";
import dynamic from "next/dynamic";
import Link from "next/link";
import { useParams } from "next/navigation";
import { toast } from "sonner";
import { ChevronLeft, Plus } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { EmptyState } from "@/components/ui/empty-state";
import { Skeleton } from "@/components/ui/skeleton";
import { ProjectOverview } from "@/components/projects/project-overview";
import { TaskListView } from "@/components/projects/task-list-view";
import { ProjectTimeline } from "@/components/projects/project-timeline";
import { CurrencyAmount } from "@/components/shared/currency-amount";
import {
  TimeEntryForm,
  type TimeEntryFormValues,
} from "@/components/projects/time-entry-form";
import { ProjectInvoices } from "@/components/projects/project-invoices";
import {
  useProjectStore,
  type ProjectTask,
  type TaskPriority,
  type TaskStatus,
} from "@/lib/stores/projects";
import { apiClient } from "@/lib/api";
import { useI18n } from "@/components/providers/i18n-provider";

interface ProjectExpense {
  id: string;
  description: string;
  date: string;
  amount: number;
  currency: string;
  status: string;
  is_billable: boolean;
}

const TaskKanbanBoard = dynamic(
  () =>
    import("@/components/projects/task-kanban-board").then(
      (mod) => mod.TaskKanbanBoard
    ),
  {
    loading: () => (
      <div className="h-72 animate-pulse rounded-lg border border-border bg-muted/40" />
    ),
  }
);
const TaskDetailDrawer = dynamic(() =>
  import("@/components/projects/task-detail-drawer").then(
    (mod) => mod.TaskDetailDrawer
  )
);

export default function ProjectDetailPage() {
  const { t } = useI18n();
  const params = useParams<{ id: string }>();
  const projectId = params.id;

  const {
    currentProject,
    tasks,
    isLoading,
    fetchProject,
    createTask,
    updateTask,
    createTimeEntry,
  } = useProjectStore();

  const [isTaskDialogOpen, setTaskDialogOpen] = useState(false);
  const [taskTitle, setTaskTitle] = useState("");
  const [taskDescription, setTaskDescription] = useState("");
  const [taskPriority, setTaskPriority] = useState<TaskPriority>("medium");
  const [taskDueDate, setTaskDueDate] = useState("");
  const [viewMode, setViewMode] = useState<"kanban" | "list">("kanban");
  const [selectedTask, setSelectedTask] = useState<ProjectTask | null>(null);
  const [projectExpenses, setProjectExpenses] = useState<ProjectExpense[]>([]);
  const [isExpensesLoading, setExpensesLoading] = useState(false);

  useEffect(() => {
    if (!projectId) {
      return;
    }

    fetchProject(projectId).catch((error) => {
      toast.error((error as Error).message || "Unable to load project");
    });
  }, [fetchProject, projectId]);

  const tasksByStatus = useMemo(() => {
    return {
      todo: tasks.filter((task) => task.status === "todo").length,
      in_progress: tasks.filter((task) => task.status === "in_progress").length,
      in_review: tasks.filter((task) => task.status === "in_review").length,
      done: tasks.filter((task) => task.status === "done").length,
      blocked: tasks.filter((task) => task.status === "blocked").length,
    };
  }, [tasks]);

  const handleCreateTask = async () => {
    if (!taskTitle.trim()) {
      toast.error(t("projects.detail.taskTitleRequired"));
      return;
    }

    try {
      await createTask(projectId, {
        title: taskTitle,
        description: taskDescription || undefined,
        priority: taskPriority,
        due_date: taskDueDate || undefined,
        status: "todo",
      });

      toast.success(t("projects.detail.toasts.taskCreated"));
      setTaskDialogOpen(false);
      setTaskTitle("");
      setTaskDescription("");
      setTaskPriority("medium");
      setTaskDueDate("");
    } catch (error) {
      toast.error(
        (error as Error).message || t("projects.detail.toasts.taskCreateFailed")
      );
    }
  };

  const handleMoveTask = async (taskId: string, targetStatus: TaskStatus) => {
    try {
      const task = tasks.find((item) => item.id === taskId);
      if (!task) {
        return;
      }

      await updateTask(projectId, taskId, {
        title: task.title,
        description: task.description,
        priority: task.priority,
        due_date: task.due_date,
        status: targetStatus,
      });
    } catch (error) {
      toast.error(
        (error as Error).message || t("projects.detail.toasts.taskMoveFailed")
      );
    }
  };

  const handleCreateTimeEntry = async (
    taskId: string,
    values: TimeEntryFormValues
  ) => {
    try {
      await createTimeEntry(projectId, taskId, values);
      await fetchProject(projectId);
      toast.success(t("projects.detail.toasts.timeSaved"));
    } catch (error) {
      toast.error(
        (error as Error).message || t("projects.detail.toasts.timeLogFailed")
      );
    }
  };

  const loadProjectExpenses = async () => {
    setExpensesLoading(true);
    try {
      const response = await apiClient.get<ProjectExpense[]>(
        `/projects/${projectId}/expenses`
      );
      setProjectExpenses(response.data || []);
    } catch (error) {
      toast.error(
        (error as Error).message ||
          t("projects.detail.toasts.expenseLoadFailed")
      );
    } finally {
      setExpensesLoading(false);
    }
  };

  useEffect(() => {
    if (!projectId) {
      return;
    }

    loadProjectExpenses();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [projectId]);

  if (isLoading && !currentProject) {
    return (
      <div className="space-y-4">
        <Skeleton className="h-8 w-44" />
        <Skeleton className="h-40 w-full" />
      </div>
    );
  }

  if (!currentProject) {
    return (
      <EmptyState
        title={t("projects.detail.notFound")}
        description={t("projects.detail.notFoundDescription")}
        action={
          <Button asChild>
            <Link href="/projects">{t("projects.detail.backToProjects")}</Link>
          </Button>
        }
      />
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div className="space-y-1">
          <Button variant="ghost" className="-ml-2" asChild>
            <Link href="/projects">
              <ChevronLeft className="mr-2 h-4 w-4" />
              {t("projects.detail.backToProjects")}
            </Link>
          </Button>
          <h1 className="text-3xl font-bold">{currentProject.name}</h1>
          <p className="font-mono text-xs text-muted-foreground">
            {currentProject.reference}
          </p>
        </div>

        <Dialog open={isTaskDialogOpen} onOpenChange={setTaskDialogOpen}>
          <DialogTrigger asChild>
            <Button>
              <Plus className="mr-2 h-4 w-4" />
              {t("projects.detail.addTask")}
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>{t("projects.detail.createTask")}</DialogTitle>
            </DialogHeader>
            <div className="space-y-3">
              <div className="space-y-2">
                <Label htmlFor="task-title">
                  {t("projects.detail.taskTitle")}
                </Label>
                <Input
                  id="task-title"
                  value={taskTitle}
                  onChange={(event) => setTaskTitle(event.target.value)}
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="task-description">
                  {t("projects.detail.description")}
                </Label>
                <Textarea
                  id="task-description"
                  rows={3}
                  value={taskDescription}
                  onChange={(event) => setTaskDescription(event.target.value)}
                />
              </div>
              <div className="grid gap-3 sm:grid-cols-2">
                <div className="space-y-2">
                  <Label>{t("projects.detail.priority")}</Label>
                  <Select
                    value={taskPriority}
                    onValueChange={(value) =>
                      setTaskPriority(value as TaskPriority)
                    }
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="low">
                        {t("projects.detail.low")}
                      </SelectItem>
                      <SelectItem value="medium">
                        {t("projects.detail.medium")}
                      </SelectItem>
                      <SelectItem value="high">
                        {t("projects.detail.high")}
                      </SelectItem>
                      <SelectItem value="urgent">
                        {t("projects.detail.urgent")}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="task-due">
                    {t("projects.detail.dueDate")}
                  </Label>
                  <Input
                    id="task-due"
                    type="date"
                    value={taskDueDate}
                    onChange={(event) => setTaskDueDate(event.target.value)}
                  />
                </div>
              </div>
              <div className="flex justify-end">
                <Button onClick={handleCreateTask}>
                  {t("projects.detail.createTask")}
                </Button>
              </div>
            </div>
          </DialogContent>
        </Dialog>
      </div>

      <ProjectOverview project={currentProject} />

      <Tabs defaultValue="tasks" className="space-y-4">
        <TabsList className="grid w-full grid-cols-6">
          <TabsTrigger value="overview">
            {t("projects.detail.tabs.overview")}
          </TabsTrigger>
          <TabsTrigger value="tasks">
            {t("projects.detail.tabs.tasks")}
          </TabsTrigger>
          <TabsTrigger value="time">
            {t("projects.detail.tabs.time")}
          </TabsTrigger>
          <TabsTrigger value="files">
            {t("projects.detail.tabs.files")}
          </TabsTrigger>
          <TabsTrigger value="expenses">
            {t("projects.detail.tabs.expenses")}
          </TabsTrigger>
          <TabsTrigger value="invoices">
            {t("projects.detail.tabs.invoices")}
          </TabsTrigger>
        </TabsList>

        <TabsContent value="overview">
          <ProjectTimeline tasks={tasks} />
        </TabsContent>

        <TabsContent value="tasks" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle className="text-base">
                {t("projects.detail.taskBoard")}
              </CardTitle>
              <p className="text-xs text-muted-foreground">
                {tasksByStatus.todo} todo, {tasksByStatus.in_progress} in
                progress, {tasksByStatus.done} done
              </p>
              <div className="flex gap-2 pt-2">
                <Button
                  variant={viewMode === "kanban" ? "default" : "outline"}
                  size="sm"
                  onClick={() => setViewMode("kanban")}
                >
                  {t("projects.detail.kanban")}
                </Button>
                <Button
                  variant={viewMode === "list" ? "default" : "outline"}
                  size="sm"
                  onClick={() => setViewMode("list")}
                >
                  {t("projects.detail.list")}
                </Button>
              </div>
            </CardHeader>
            <CardContent>
              {viewMode === "kanban" ? (
                <TaskKanbanBoard
                  tasks={tasks}
                  onMoveTask={handleMoveTask}
                  onOpenTask={(task) => setSelectedTask(task)}
                  onBlockedMove={() =>
                    toast.warning(
                      "Dependencies must be completed before starting this task"
                    )
                  }
                />
              ) : (
                <TaskListView
                  tasks={tasks}
                  onOpenTask={(task) => setSelectedTask(task)}
                  onStatusChange={handleMoveTask}
                />
              )}
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="time">
          <Card>
            <CardHeader>
              <CardTitle>{t("projects.detail.timeTracking")}</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              {tasks.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                  {t("projects.detail.noTaskBeforeTime")}
                </p>
              ) : (
                <div className="grid gap-4 md:grid-cols-2">
                  {tasks.slice(0, 4).map((task) => (
                    <Card key={task.id} className="border-dashed">
                      <CardHeader>
                        <CardTitle className="text-sm">{task.title}</CardTitle>
                      </CardHeader>
                      <CardContent>
                        <TimeEntryForm
                          onSubmit={(values) =>
                            handleCreateTimeEntry(task.id, values)
                          }
                        />
                      </CardContent>
                    </Card>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="files">
          <EmptyState
            title={t("projects.detail.tabs.files")}
            description={t("projects.detail.filesNote")}
          />
        </TabsContent>

        <TabsContent value="expenses">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between">
              <CardTitle>{t("projects.detail.projectExpenses")}</CardTitle>
              <Button asChild size="sm" variant="outline">
                <Link href={`/expenses/create?project_id=${projectId}`}>
                  <Plus className="mr-2 h-4 w-4" />
                  {t("projects.detail.quickAddExpense")}
                </Link>
              </Button>
            </CardHeader>
            <CardContent>
              {isExpensesLoading ? (
                <p className="text-sm text-muted-foreground">
                  {t("projects.detail.loadingExpenses")}
                </p>
              ) : projectExpenses.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                  {t("projects.detail.noExpenses")}
                </p>
              ) : (
                <div className="overflow-x-auto">
                  <table className="w-full text-sm">
                    <thead>
                      <tr className="border-b text-left">
                        <th className="pb-3">{t("projects.detail.date")}</th>
                        <th className="pb-3">
                          {t("projects.detail.description")}
                        </th>
                        <th className="pb-3">{t("projects.detail.amount")}</th>
                        <th className="pb-3">
                          {t("projects.detail.billable")}
                        </th>
                        <th className="pb-3">{t("projects.table.status")}</th>
                      </tr>
                    </thead>
                    <tbody>
                      {projectExpenses.map((expense) => (
                        <tr key={expense.id} className="border-b">
                          <td className="py-2 text-muted-foreground">
                            {expense.date}
                          </td>
                          <td className="py-2">
                            <Link
                              href={`/expenses/${expense.id}`}
                              className="font-medium text-primary hover:underline"
                            >
                              {expense.description}
                            </Link>
                          </td>
                          <td className="py-2">
                            <CurrencyAmount
                              amount={Number(expense.amount || 0)}
                              currency={expense.currency || "EUR"}
                            />
                          </td>
                          <td className="py-2">
                            {expense.is_billable
                              ? t("projects.detail.yes")
                              : t("projects.detail.no")}
                          </td>
                          <td className="py-2">{expense.status}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="invoices">
          <ProjectInvoices projectId={projectId} />
        </TabsContent>
      </Tabs>

      <TaskDetailDrawer
        open={!!selectedTask}
        task={selectedTask}
        onOpenChange={(open) => {
          if (!open) {
            setSelectedTask(null);
          }
        }}
        onStatusChange={async (taskId, status) => {
          await handleMoveTask(taskId, status);
          const refreshedTask = useProjectStore
            .getState()
            .tasks.find((task) => task.id === taskId);
          if (refreshedTask) {
            setSelectedTask(refreshedTask);
          }
        }}
        onCreateTimeEntry={handleCreateTimeEntry}
      />
    </div>
  );
}
