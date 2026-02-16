import { create } from "zustand";
import { apiClient } from "@/lib/api";

export type ProjectStatus =
  | "draft"
  | "proposal_sent"
  | "in_progress"
  | "on_hold"
  | "completed"
  | "cancelled";

export type TaskStatus =
  | "todo"
  | "in_progress"
  | "in_review"
  | "done"
  | "blocked";

export type TaskPriority = "low" | "medium" | "high" | "urgent";

export interface Project {
  id: string;
  reference?: string;
  name: string;
  description?: string | null;
  status: ProjectStatus;
  billing_type?: "hourly" | "fixed";
  hourly_rate?: number | null;
  fixed_price?: number | null;
  estimated_hours?: number | null;
  start_date?: string | null;
  deadline?: string | null;
  total_tasks?: number;
  completed_tasks?: number;
  total_time_spent?: number;
  progress_percentage?: number;
  budget_consumed?: number;
  client?: {
    id: string;
    name: string;
  } | null;
}

export interface ProjectTask {
  id: string;
  project_id: string;
  title: string;
  description?: string | null;
  status: TaskStatus;
  priority: TaskPriority;
  due_date?: string | null;
  estimated_hours?: number | null;
  sort_order: number;
  blocked_by_dependencies?: boolean;
}

export interface TimeEntryPayload {
  duration_minutes: number;
  date: string;
  description?: string;
}

interface Pagination {
  current_page: number;
  last_page: number;
  total: number;
  per_page: number;
}

interface ProjectState {
  projects: Project[];
  currentProject: Project | null;
  tasks: ProjectTask[];
  pagination: Pagination | null;
  isLoading: boolean;
  error: string | null;

  fetchProjects: (params?: Record<string, unknown>) => Promise<void>;
  fetchProject: (id: string) => Promise<void>;
  createProject: (data: Record<string, unknown>) => Promise<Project | null>;
  updateProject: (
    id: string,
    data: Record<string, unknown>
  ) => Promise<Project | null>;
  deleteProject: (id: string) => Promise<void>;

  fetchTasks: (
    projectId: string,
    params?: Record<string, unknown>
  ) => Promise<void>;
  createTask: (
    projectId: string,
    data: Record<string, unknown>
  ) => Promise<ProjectTask | null>;
  updateTask: (
    projectId: string,
    taskId: string,
    data: Record<string, unknown>
  ) => Promise<ProjectTask | null>;
  deleteTask: (projectId: string, taskId: string) => Promise<void>;
  reorderTasks: (projectId: string, taskIds: string[]) => Promise<void>;
  addTaskDependency: (
    projectId: string,
    taskId: string,
    dependsOnTaskId: string
  ) => Promise<void>;

  createTimeEntry: (
    projectId: string,
    taskId: string,
    data: TimeEntryPayload
  ) => Promise<void>;
}

export const useProjectStore = create<ProjectState>((set, get) => ({
  projects: [],
  currentProject: null,
  tasks: [],
  pagination: null,
  isLoading: false,
  error: null,

  fetchProjects: async (params = {}) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<any>("/projects", { params });
      set({
        projects: response.data.data || [],
        pagination: {
          current_page: response.data.current_page,
          last_page: response.data.last_page,
          total: response.data.total,
          per_page: response.data.per_page,
        },
        isLoading: false,
      });
    } catch (error) {
      set({
        error: (error as Error).message,
        isLoading: false,
      });
    }
  },

  fetchProject: async (id) => {
    set({ isLoading: true, error: null });
    try {
      const projectResponse = await apiClient.get<any>(`/projects/${id}`);
      const tasksResponse = await apiClient.get<any>(`/projects/${id}/tasks`);
      const tasks = (tasksResponse.data || []) as ProjectTask[];
      set({
        currentProject: projectResponse.data,
        tasks: tasks.map((task) => ({
          ...task,
          blocked_by_dependencies:
            Array.isArray((task as any).dependencies) &&
            (task as any).dependencies.some((dep: any) => dep.status !== "done"),
        })),
        isLoading: false,
      });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  createProject: async (data) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<any>("/projects", data);
      const created = response.data as Project;
      set({
        projects: [created, ...get().projects],
        isLoading: false,
      });

      return created;
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  updateProject: async (id, data) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.put<any>(`/projects/${id}`, data);
      const updated = response.data as Project;
      set({
        projects: get().projects.map((project) =>
          project.id === id ? updated : project
        ),
        currentProject:
          get().currentProject?.id === id ? updated : get().currentProject,
        isLoading: false,
      });

      return updated;
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  deleteProject: async (id) => {
    set({ isLoading: true, error: null });
    try {
      await apiClient.delete(`/projects/${id}`);
      set({
        projects: get().projects.filter((project) => project.id !== id),
        currentProject: get().currentProject?.id === id ? null : get().currentProject,
        tasks: get().currentProject?.id === id ? [] : get().tasks,
        isLoading: false,
      });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  fetchTasks: async (projectId, params = {}) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<any>(`/projects/${projectId}/tasks`, {
        params,
      });
      const tasks = (response.data || []) as ProjectTask[];
      set({
        tasks: tasks.map((task) => ({
          ...task,
          blocked_by_dependencies:
            Array.isArray((task as any).dependencies) &&
            (task as any).dependencies.some((dep: any) => dep.status !== "done"),
        })),
        isLoading: false,
      });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  createTask: async (projectId, data) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<any>(`/projects/${projectId}/tasks`, data);
      const createdTask = response.data as ProjectTask;
      set({
        tasks: [...get().tasks, createdTask],
        isLoading: false,
      });

      return createdTask;
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  updateTask: async (projectId, taskId, data) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.put<any>(
        `/projects/${projectId}/tasks/${taskId}`,
        data
      );
      const updatedTask = response.data as ProjectTask;
      set({
        tasks: get().tasks.map((task) =>
          task.id === taskId ? updatedTask : task
        ),
        isLoading: false,
      });

      return updatedTask;
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  deleteTask: async (projectId, taskId) => {
    set({ isLoading: true, error: null });
    try {
      await apiClient.delete(`/projects/${projectId}/tasks/${taskId}`);
      set({
        tasks: get().tasks.filter((task) => task.id !== taskId),
        isLoading: false,
      });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  reorderTasks: async (projectId, taskIds) => {
    set({ isLoading: true, error: null });
    try {
      await apiClient.post(`/projects/${projectId}/tasks/reorder`, {
        task_ids: taskIds,
      });

      const sortedTasks = [...get().tasks].sort(
        (a, b) => taskIds.indexOf(a.id) - taskIds.indexOf(b.id)
      );

      set({ tasks: sortedTasks, isLoading: false });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  addTaskDependency: async (projectId, taskId, dependsOnTaskId) => {
    set({ isLoading: true, error: null });
    try {
      await apiClient.post(`/projects/${projectId}/tasks/${taskId}/dependencies`, {
        depends_on_task_id: dependsOnTaskId,
      });
      await get().fetchTasks(projectId);
      set({ isLoading: false });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  createTimeEntry: async (projectId, taskId, data) => {
    set({ isLoading: true, error: null });
    try {
      await apiClient.post(`/projects/${projectId}/tasks/${taskId}/time-entries`, data);
      set({ isLoading: false });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },
}));
