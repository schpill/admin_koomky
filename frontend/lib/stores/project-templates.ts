import { create } from "zustand";
import { apiClient } from "@/lib/api";

interface TemplateTask {
  id: string;
  title: string;
  description: string | null;
  estimated_hours: number | null;
  priority: string;
  sort_order: number;
}

interface TemplateTaskPayload {
  title: string;
  description?: string | null;
  estimated_hours?: number | null;
  priority?: string;
  sort_order?: number;
}

interface ProjectTemplate {
  id: string;
  name: string;
  description: string | null;
  billing_type: string | null;
  default_hourly_rate: number | null;
  default_currency: string | null;
  estimated_hours: number | null;
  tasks_count: number;
  created_at: string;
  tasks: TemplateTask[];
}

interface InstantiateData {
  name: string;
  client_id: string;
  start_date?: string;
  deadline?: string;
}

interface ProjectTemplatePayload {
  name?: string;
  description?: string | null;
  billing_type?: string | null;
  default_hourly_rate?: number | null;
  default_currency?: string | null;
  estimated_hours?: number | null;
  tasks?: TemplateTaskPayload[];
}

interface ProjectTemplatesState {
  templates: ProjectTemplate[];
  selectedTemplate: ProjectTemplate | null;
  isLoading: boolean;
  error: string | null;

  fetchTemplates: () => Promise<void>;
  createTemplate: (data: ProjectTemplatePayload) => Promise<ProjectTemplate>;
  updateTemplate: (id: string, data: ProjectTemplatePayload) => Promise<void>;
  deleteTemplate: (id: string) => Promise<void>;
  duplicateTemplate: (id: string) => Promise<ProjectTemplate>;
  saveProjectAsTemplate: (projectId: string, name: string, description?: string) => Promise<ProjectTemplate>;
  instantiateTemplate: (id: string, data: InstantiateData) => Promise<{ id: string; name: string }>;
  setSelectedTemplate: (template: ProjectTemplate | null) => void;
}

function extractTemplatesPayload(payload: unknown): ProjectTemplate[] {
  if (Array.isArray(payload)) {
    return payload;
  }

  if (
    payload &&
    typeof payload === "object" &&
    Array.isArray((payload as { data?: unknown }).data)
  ) {
    return (payload as { data: ProjectTemplate[] }).data;
  }

  return [];
}

export const useProjectTemplatesStore = create<ProjectTemplatesState>((set, get) => ({
  templates: [],
  selectedTemplate: null,
  isLoading: false,
  error: null,

  fetchTemplates: async () => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<
        ProjectTemplate[] | { data: ProjectTemplate[] }
      >(
        "/project-templates"
      );
      set({
        templates: extractTemplatesPayload(response.data),
        isLoading: false,
      });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
    }
  },

  createTemplate: async (data: ProjectTemplatePayload) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<ProjectTemplate>(
        "/project-templates",
        data
      );
      const newTemplate = response.data;
      set((state) => ({
        templates: [newTemplate, ...state.templates],
        isLoading: false,
      }));
      return newTemplate;
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  updateTemplate: async (id: string, data: ProjectTemplatePayload) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.patch<ProjectTemplate>(
        `/project-templates/${id}`,
        data
      );
      const updatedTemplate = response.data;
      set((state) => ({
        templates: state.templates.map((t) => (t.id === id ? updatedTemplate : t)),
        isLoading: false,
      }));
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  deleteTemplate: async (id: string) => {
    set({ isLoading: true, error: null });
    try {
      await apiClient.delete(`/project-templates/${id}`);
      set((state) => ({
        templates: state.templates.filter((t) => t.id !== id),
        isLoading: false,
      }));
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  duplicateTemplate: async (id: string) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<ProjectTemplate>(
        `/project-templates/${id}/duplicate`
      );
      const newTemplate = response.data;
      set((state) => ({
        templates: [newTemplate, ...state.templates],
        isLoading: false,
      }));
      return newTemplate;
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  saveProjectAsTemplate: async (projectId: string, name: string, description?: string) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<ProjectTemplate>(
        `/projects/${projectId}/save-as-template`,
        {
          name,
          description,
        }
      );
      const newTemplate = response.data;
      set((state) => ({
        templates: [newTemplate, ...state.templates],
        isLoading: false,
      }));
      return newTemplate;
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  instantiateTemplate: async (id: string, data: InstantiateData) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<{ id: string; name: string }>(
        `/project-templates/${id}/instantiate`,
        data
      );
      set({ isLoading: false });
      return response.data;
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  setSelectedTemplate: (template: ProjectTemplate | null) => {
    set({ selectedTemplate: template });
  },
}));
