"use client";

import { useEffect, useMemo, useState } from "react";
import Link from "next/link";
import { useParams } from "next/navigation";
import { toast } from "sonner";
import { ChevronLeft, Plus } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
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
import { TaskKanbanBoard } from "@/components/projects/task-kanban-board";
import { TaskListView } from "@/components/projects/task-list-view";
import { TaskDetailDrawer } from "@/components/projects/task-detail-drawer";
import { ProjectTimeline } from "@/components/projects/project-timeline";
import { TimeEntryForm, type TimeEntryFormValues } from "@/components/projects/time-entry-form";
import { ProjectInvoices } from "@/components/projects/project-invoices";
import {
  useProjectStore,
  type ProjectTask,
  type TaskPriority,
  type TaskStatus,
} from "@/lib/stores/projects";

export default function ProjectDetailPage() {
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
      toast.error("Task title is required");
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

      toast.success("Task created");
      setTaskDialogOpen(false);
      setTaskTitle("");
      setTaskDescription("");
      setTaskPriority("medium");
      setTaskDueDate("");
    } catch (error) {
      toast.error((error as Error).message || "Unable to create task");
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
      toast.error((error as Error).message || "Unable to move task");
    }
  };

  const handleCreateTimeEntry = async (taskId: string, values: TimeEntryFormValues) => {
    try {
      await createTimeEntry(projectId, taskId, values);
      await fetchProject(projectId);
      toast.success("Time entry saved");
    } catch (error) {
      toast.error((error as Error).message || "Unable to log time");
    }
  };

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
        title="Project not found"
        description="This project may have been deleted or you no longer have access to it."
        action={
          <Button asChild>
            <Link href="/projects">Back to projects</Link>
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
              Back to projects
            </Link>
          </Button>
          <h1 className="text-3xl font-bold">{currentProject.name}</h1>
          <p className="font-mono text-xs text-muted-foreground">{currentProject.reference}</p>
        </div>

        <Dialog open={isTaskDialogOpen} onOpenChange={setTaskDialogOpen}>
          <DialogTrigger asChild>
            <Button>
              <Plus className="mr-2 h-4 w-4" />
              Add task
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Create task</DialogTitle>
            </DialogHeader>
            <div className="space-y-3">
              <div className="space-y-2">
                <Label htmlFor="task-title">Title</Label>
                <Input
                  id="task-title"
                  value={taskTitle}
                  onChange={(event) => setTaskTitle(event.target.value)}
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="task-description">Description</Label>
                <Textarea
                  id="task-description"
                  rows={3}
                  value={taskDescription}
                  onChange={(event) => setTaskDescription(event.target.value)}
                />
              </div>
              <div className="grid gap-3 sm:grid-cols-2">
                <div className="space-y-2">
                  <Label>Priority</Label>
                  <Select
                    value={taskPriority}
                    onValueChange={(value) => setTaskPriority(value as TaskPriority)}
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="low">Low</SelectItem>
                      <SelectItem value="medium">Medium</SelectItem>
                      <SelectItem value="high">High</SelectItem>
                      <SelectItem value="urgent">Urgent</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="task-due">Due date</Label>
                  <Input
                    id="task-due"
                    type="date"
                    value={taskDueDate}
                    onChange={(event) => setTaskDueDate(event.target.value)}
                  />
                </div>
              </div>
              <div className="flex justify-end">
                <Button onClick={handleCreateTask}>Create task</Button>
              </div>
            </div>
          </DialogContent>
        </Dialog>
      </div>

      <ProjectOverview project={currentProject} />

      <Tabs defaultValue="tasks" className="space-y-4">
        <TabsList className="grid w-full grid-cols-5">
          <TabsTrigger value="overview">Overview</TabsTrigger>
          <TabsTrigger value="tasks">Tasks</TabsTrigger>
          <TabsTrigger value="time">Time</TabsTrigger>
          <TabsTrigger value="files">Files</TabsTrigger>
          <TabsTrigger value="invoices">Invoices</TabsTrigger>
        </TabsList>

        <TabsContent value="overview">
          <ProjectTimeline tasks={tasks} />
        </TabsContent>

        <TabsContent value="tasks" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle className="text-base">Task board</CardTitle>
              <p className="text-xs text-muted-foreground">
                {tasksByStatus.todo} todo, {tasksByStatus.in_progress} in progress, {tasksByStatus.done} done
              </p>
              <div className="flex gap-2 pt-2">
                <Button
                  variant={viewMode === "kanban" ? "default" : "outline"}
                  size="sm"
                  onClick={() => setViewMode("kanban")}
                >
                  Kanban
                </Button>
                <Button
                  variant={viewMode === "list" ? "default" : "outline"}
                  size="sm"
                  onClick={() => setViewMode("list")}
                >
                  List
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
                    toast.warning("Dependencies must be completed before starting this task")
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
              <CardTitle>Time tracking</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              {tasks.length === 0 ? (
                <p className="text-sm text-muted-foreground">Create a task before logging time.</p>
              ) : (
                <div className="grid gap-4 md:grid-cols-2">
                  {tasks.slice(0, 4).map((task) => (
                    <Card key={task.id} className="border-dashed">
                      <CardHeader>
                        <CardTitle className="text-sm">{task.title}</CardTitle>
                      </CardHeader>
                      <CardContent>
                        <TimeEntryForm onSubmit={(values) => handleCreateTimeEntry(task.id, values)} />
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
            title="Files"
            description="Task attachments are managed from each task detail panel."
          />
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
