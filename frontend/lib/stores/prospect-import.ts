import { create } from "zustand";
import { apiClient } from "@/lib/api";

export interface ImportSession {
  id: string;
  status: string;
  total_rows: number;
  processed_rows: number;
  success_rows: number;
  error_rows: number;
  progress_percent?: number;
}

export interface ImportErrorRow {
  id: string;
  row_number: number;
  raw_data: Record<string, unknown>;
  error_message: string;
}

export interface ImportOptions {
  duplicate_strategy: "skip" | "update";
  default_status: "prospect" | "lead" | "active";
}

interface ProspectImportState {
  session: ImportSession | null;
  columnList: string[];
  previewRows: Array<Record<string, string | null>>;
  detectedMapping: Record<string, string | null>;
  columnMapping: Record<string, string | null>;
  defaultTags: string[];
  options: ImportOptions;
  isUploading: boolean;
  isProcessing: boolean;
  progress: number;
  errors: ImportErrorRow[];
  error: string | null;

  uploadFile: (file: File) => Promise<void>;
  updateMapping: (mapping: Record<string, string | null>) => Promise<void>;
  updateOptions: (
    options: Partial<ImportOptions> & { default_tags?: string[] }
  ) => Promise<void>;
  processImport: () => Promise<void>;
  pollStatus: () => Promise<void>;
  fetchErrors: (page?: number) => Promise<void>;
  exportErrors: () => Promise<Blob | null>;
  reset: () => void;
}

const defaultOptions: ImportOptions = {
  duplicate_strategy: "skip",
  default_status: "prospect",
};

export const useProspectImportStore = create<ProspectImportState>(
  (set, get) => ({
    session: null,
    columnList: [],
    previewRows: [],
    detectedMapping: {},
    columnMapping: {},
    defaultTags: [],
    options: defaultOptions,
    isUploading: false,
    isProcessing: false,
    progress: 0,
    errors: [],
    error: null,

    uploadFile: async (file) => {
      set({ isUploading: true, error: null });
      try {
        const formData = new FormData();
        formData.append("file", file);

        const response = await apiClient.post<any>(
          "/import-sessions",
          formData
        );
        const payload = response.data || {};
        set({
          session: payload.session || null,
          columnList: payload.column_list || [],
          previewRows: payload.preview_rows || [],
          detectedMapping: payload.detected_mapping || {},
          columnMapping: payload.detected_mapping || {},
          progress: 0,
          errors: [],
          isUploading: false,
        });
      } catch (error) {
        set({ isUploading: false, error: (error as Error).message });
        throw error;
      }
    },

    updateMapping: async (mapping) => {
      const session = get().session;
      if (!session) return;

      await apiClient.patch(`/import-sessions/${session.id}`, {
        column_mapping: mapping,
        default_tags: get().defaultTags,
        options: get().options,
      });

      set({ columnMapping: mapping });
    },

    updateOptions: async (options) => {
      const session = get().session;
      if (!session) return;

      const nextOptions = {
        ...get().options,
        ...options,
      };

      const nextTags = options.default_tags ?? get().defaultTags;

      await apiClient.patch(`/import-sessions/${session.id}`, {
        column_mapping: get().columnMapping,
        default_tags: nextTags,
        options: {
          duplicate_strategy: nextOptions.duplicate_strategy,
          default_status: nextOptions.default_status,
        },
      });

      set({
        defaultTags: nextTags,
        options: {
          duplicate_strategy: nextOptions.duplicate_strategy,
          default_status: nextOptions.default_status,
        },
      });
    },

    processImport: async () => {
      const session = get().session;
      if (!session) return;

      set({ isProcessing: true });
      await apiClient.post(`/import-sessions/${session.id}/process`);
      await get().pollStatus();
    },

    pollStatus: async () => {
      const session = get().session;
      if (!session) return;

      let keepPolling = true;
      while (keepPolling) {
        const response = await apiClient.get<any>(
          `/import-sessions/${session.id}`
        );
        const current = response.data as ImportSession;
        const progress = current.progress_percent ?? 0;

        set({ session: current, progress });

        if (current.status === "completed" || current.status === "failed") {
          keepPolling = false;
          set({ isProcessing: false });
          await get().fetchErrors(1);
          break;
        }

        await new Promise((resolve) => setTimeout(resolve, 3000));
      }
    },

    fetchErrors: async (page = 1) => {
      const session = get().session;
      if (!session) return;

      const response = await apiClient.get<any>(
        `/import-sessions/${session.id}/errors`,
        {
          params: { page },
        }
      );

      set({ errors: response.data?.data || [] });
    },

    exportErrors: async () => {
      const session = get().session;
      if (!session) return null;

      const response = await apiClient.get<Blob>(
        `/import-sessions/${session.id}/errors/export`,
        {
          responseType: "blob",
        }
      );

      return response.data;
    },

    reset: () =>
      set({
        session: null,
        columnList: [],
        previewRows: [],
        detectedMapping: {},
        columnMapping: {},
        defaultTags: [],
        options: defaultOptions,
        isUploading: false,
        isProcessing: false,
        progress: 0,
        errors: [],
        error: null,
      }),
  })
);
