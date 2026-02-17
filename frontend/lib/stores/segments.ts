import { create } from "zustand";
import { apiClient } from "@/lib/api";

export type SegmentBoolean = "and" | "or";

export interface SegmentCriterion {
  type: string;
  operator: string;
  field?: string;
  value?: unknown;
}

export interface SegmentGroup {
  criteria: SegmentCriterion[];
}

export interface SegmentFilters {
  group_boolean?: SegmentBoolean;
  criteria_boolean?: SegmentBoolean;
  groups: SegmentGroup[];
}

export interface Segment {
  id: string;
  name: string;
  description?: string | null;
  filters: SegmentFilters;
  is_dynamic?: boolean;
  contact_count?: number;
  cached_contact_count?: number;
  created_at?: string;
  updated_at?: string;
}

export interface SegmentPreviewContact {
  id: string;
  first_name: string;
  last_name?: string | null;
  email?: string | null;
  phone?: string | null;
  client?: {
    id: string;
    name: string;
  } | null;
}

interface Pagination {
  current_page: number;
  last_page: number;
  total: number;
  per_page: number;
}

export interface SegmentPreview {
  segment_id: string;
  total_matching: number;
  cached_contact_count: number;
  contacts: {
    data: SegmentPreviewContact[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
  };
}

interface SegmentState {
  segments: Segment[];
  currentSegment: Segment | null;
  pagination: Pagination | null;
  preview: SegmentPreview | null;
  isLoading: boolean;
  error: string | null;

  fetchSegments: (params?: Record<string, unknown>) => Promise<void>;
  fetchSegment: (id: string) => Promise<Segment | null>;
  createSegment: (data: Record<string, unknown>) => Promise<Segment | null>;
  updateSegment: (
    id: string,
    data: Record<string, unknown>
  ) => Promise<Segment | null>;
  deleteSegment: (id: string) => Promise<void>;
  previewSegment: (
    id: string,
    params?: Record<string, unknown>
  ) => Promise<SegmentPreview | null>;
}

function upsertSegment(list: Segment[], segment: Segment): Segment[] {
  const index = list.findIndex((item) => item.id === segment.id);
  if (index === -1) {
    return [segment, ...list];
  }

  const next = [...list];
  next[index] = segment;

  return next;
}

function normalizePagination(payload: any): Pagination {
  return {
    current_page: payload.current_page || 1,
    last_page: payload.last_page || 1,
    total: payload.total || 0,
    per_page: payload.per_page || 15,
  };
}

export const useSegmentStore = create<SegmentState>((set, get) => ({
  segments: [],
  currentSegment: null,
  pagination: null,
  preview: null,
  isLoading: false,
  error: null,

  fetchSegments: async (params = {}) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<any>("/segments", { params });
      const payload = response.data || {};

      set({
        segments: payload.data || [],
        pagination: normalizePagination(payload),
        isLoading: false,
      });
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
    }
  },

  fetchSegment: async (id) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<any>(`/segments/${id}`);
      const segment = response.data as Segment;

      set({
        currentSegment: segment,
        segments: upsertSegment(get().segments, segment),
        isLoading: false,
      });

      return segment;
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  createSegment: async (data) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<any>("/segments", data);
      const segment = response.data as Segment;

      set({
        currentSegment: segment,
        segments: upsertSegment(get().segments, segment),
        isLoading: false,
      });

      return segment;
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  updateSegment: async (id, data) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.put<any>(`/segments/${id}`, data);
      const segment = response.data as Segment;

      set({
        currentSegment:
          get().currentSegment?.id === id ? segment : get().currentSegment,
        segments: upsertSegment(get().segments, segment),
        isLoading: false,
      });

      return segment;
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  deleteSegment: async (id) => {
    set({ isLoading: true, error: null });
    try {
      await apiClient.delete(`/segments/${id}`);

      set({
        segments: get().segments.filter((segment) => segment.id !== id),
        currentSegment:
          get().currentSegment?.id === id ? null : get().currentSegment,
        isLoading: false,
      });
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  previewSegment: async (id, params = {}) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<any>(`/segments/${id}/preview`, {
        params,
      });
      const preview = response.data as SegmentPreview;

      set({
        preview,
        isLoading: false,
      });

      return preview;
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },
}));
