import { create } from "zustand";
import { apiClient } from "@/lib/api";

export type DocumentType =
  | "pdf"
  | "spreadsheet"
  | "document"
  | "text"
  | "script"
  | "image"
  | "archive"
  | "presentation"
  | "other";

export interface Document {
  id: string;
  user_id: string;
  client_id: string | null;
  title: string;
  original_filename: string;
  storage_path: string;
  storage_disk: string;
  mime_type: string;
  document_type: DocumentType;
  script_language: string | null;
  file_size: number;
  version: number;
  tags: string[];
  last_sent_at: string | null;
  last_sent_to: string | null;
  created_at: string;
  updated_at: string;
  client?: any;
}

export interface DocumentStats {
  total_count: number;
  total_size_bytes: number;
  quota_bytes: number;
  usage_percentage: number;
  by_type: {
    document_type: DocumentType;
    count: number;
    size: number;
  }[];
}

interface Pagination {
  current_page: number;
  last_page: number;
  total: number;
  per_page: number;
}

interface DocumentFilters {
  client_id?: string;
  document_type?: DocumentType;
  tag?: string;
  date_from?: string;
  date_to?: string;
  q?: string;
  sort_by?: string;
  sort_order?: "asc" | "desc";
}

interface DocumentState {
  documents: Document[];
  currentDocument: Document | null;
  stats: DocumentStats | null;
  pagination: Pagination | null;
  isLoading: boolean;
  error: string | null;

  // Actions
  fetchDocuments: (
    params?: DocumentFilters & { page?: number; per_page?: number }
  ) => Promise<void>;
  fetchDocument: (id: string) => Promise<void>;
  uploadDocument: (data: FormData) => Promise<Document>;
  updateDocument: (id: string, data: Partial<Document>) => Promise<Document>;
  deleteDocument: (id: string) => Promise<void>;
  bulkDelete: (ids: string[]) => Promise<void>;
  reuploadDocument: (id: string, file: File) => Promise<Document>;
  downloadDocument: (
    id: string,
    options?: { inline?: boolean }
  ) => Promise<void>;
  sendEmail: (
    id: string,
    data: { email: string; message?: string }
  ) => Promise<void>;
  fetchStats: () => Promise<void>;
}

export const useDocumentStore = create<DocumentState>((set, get) => ({
  documents: [],
  currentDocument: null,
  stats: null,
  pagination: null,
  isLoading: false,
  error: null,

  fetchDocuments: async (params = {}) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<any>("/documents", { params });
      set({
        documents: response.data.data,
        pagination: {
          current_page: response.data.current_page,
          last_page: response.data.last_page,
          total: response.data.total,
          per_page: response.data.per_page,
        },
        isLoading: false,
      });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
    }
  },

  fetchDocument: async (id) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<Document>(`/documents/${id}`);
      set({
        currentDocument: response.data,
        isLoading: false,
      });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  uploadDocument: async (data) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<Document>("/documents", data, {
        headers: { "Content-Type": "multipart/form-data" },
      });
      set({
        documents: [response.data, ...get().documents],
        isLoading: false,
      });
      return response.data;
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  updateDocument: async (id, data) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.put<Document>(`/documents/${id}`, data);
      set({
        documents: get().documents.map((doc) =>
          doc.id === id ? response.data : doc
        ),
        currentDocument: response.data,
        isLoading: false,
      });
      return response.data;
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  deleteDocument: async (id) => {
    set({ isLoading: true, error: null });
    try {
      await apiClient.delete(`/documents/${id}`);
      set({
        documents: get().documents.filter((doc) => doc.id !== id),
        isLoading: false,
      });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  bulkDelete: async (ids) => {
    set({ isLoading: true, error: null });
    try {
      await apiClient.delete("/documents/bulk", {
        body: JSON.stringify({ ids }),
      } as any);
      set({
        documents: get().documents.filter((doc) => !ids.includes(doc.id)),
        isLoading: false,
      });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  reuploadDocument: async (id, file) => {
    set({ isLoading: true, error: null });
    try {
      const formData = new FormData();
      formData.append("file", file);
      const response = await apiClient.post<Document>(
        `/documents/${id}/reupload`,
        formData,
        {
          headers: { "Content-Type": "multipart/form-data" },
        }
      );
      set({
        documents: get().documents.map((doc) =>
          doc.id === id ? response.data : doc
        ),
        currentDocument: response.data,
        isLoading: false,
      });
      return response.data;
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  downloadDocument: async (id, options = {}) => {
    try {
      const response = await apiClient.get<any>(`/documents/${id}/download`, {
        params: options,
        responseType: "blob",
      });

      const contentDisposition = response.headers?.get("content-disposition");
      let filename = "document";
      if (contentDisposition) {
        const filenameMatch = contentDisposition.match(/filename="?(.+)"?/);
        if (filenameMatch && filenameMatch.length > 1) {
          filename = filenameMatch[1];
        }
      }

      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement("a");
      link.href = url;
      link.setAttribute("download", filename);
      document.body.appendChild(link);
      link.click();
      link.remove();
    } catch (error) {
      set({ error: (error as Error).message });
      throw error;
    }
  },

  sendEmail: async (id, data) => {
    set({ isLoading: true, error: null });
    try {
      await apiClient.post(`/documents/${id}/email`, data);
      set({ isLoading: false });
      // Refresh document to get updated last_sent_at
      const updatedDocResponse = await apiClient.get<Document>(
        `/documents/${id}`
      );
      set({
        documents: get().documents.map((doc) =>
          doc.id === id ? updatedDocResponse.data : doc
        ),
        currentDocument: updatedDocResponse.data,
      });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
      throw error;
    }
  },

  fetchStats: async () => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<DocumentStats>("/documents/stats");
      set({
        stats: response.data,
        isLoading: false,
      });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
    }
  },
}));
