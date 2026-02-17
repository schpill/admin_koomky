import { create } from "zustand";
import { apiClient } from "@/lib/api";

export type CalendarEventType =
  | "meeting"
  | "deadline"
  | "reminder"
  | "task"
  | "custom";

export interface CalendarEvent {
  id: string;
  user_id?: string;
  calendar_connection_id?: string | null;
  external_id?: string | null;
  title: string;
  description?: string | null;
  start_at: string;
  end_at: string;
  all_day: boolean;
  location?: string | null;
  type: CalendarEventType;
  eventable_type?: string | null;
  eventable_id?: string | null;
  recurrence_rule?: string | null;
  sync_status: "local" | "synced" | "conflict";
  external_updated_at?: string | null;
}

export interface CalendarConnection {
  id: string;
  user_id?: string;
  provider: "google" | "caldav";
  name: string;
  calendar_id?: string | null;
  sync_enabled: boolean;
  last_synced_at?: string | null;
}

export interface CalendarEventPayload {
  calendar_connection_id?: string | null;
  title: string;
  description?: string | null;
  start_at: string;
  end_at: string;
  all_day?: boolean;
  location?: string | null;
  type: CalendarEventType;
}

export interface CalendarConnectionPayload {
  provider: "google" | "caldav";
  name: string;
  credentials: Record<string, unknown>;
  calendar_id?: string;
  sync_enabled?: boolean;
}

interface CalendarState {
  events: CalendarEvent[];
  connections: CalendarConnection[];
  selectedRange: { from: string; to: string };
  isLoading: boolean;
  error: string | null;

  fetchEvents: (params?: Record<string, string>) => Promise<void>;
  createEvent: (payload: CalendarEventPayload) => Promise<CalendarEvent | null>;
  updateEvent: (
    id: string,
    payload: Partial<CalendarEventPayload>
  ) => Promise<CalendarEvent | null>;
  deleteEvent: (id: string) => Promise<void>;

  fetchConnections: () => Promise<void>;
  createConnection: (
    payload: CalendarConnectionPayload
  ) => Promise<CalendarConnection | null>;
  updateConnection: (
    id: string,
    payload: Partial<CalendarConnectionPayload>
  ) => Promise<CalendarConnection | null>;
  deleteConnection: (id: string) => Promise<void>;
}

function upsertEvent(
  list: CalendarEvent[],
  nextEvent: CalendarEvent
): CalendarEvent[] {
  const index = list.findIndex((event) => event.id === nextEvent.id);
  if (index === -1) {
    return [nextEvent, ...list];
  }

  const clone = [...list];
  clone[index] = nextEvent;
  return clone;
}

function upsertConnection(
  list: CalendarConnection[],
  nextConnection: CalendarConnection
): CalendarConnection[] {
  const index = list.findIndex(
    (connection) => connection.id === nextConnection.id
  );
  if (index === -1) {
    return [nextConnection, ...list];
  }

  const clone = [...list];
  clone[index] = nextConnection;
  return clone;
}

export const useCalendarStore = create<CalendarState>((set, get) => ({
  events: [],
  connections: [],
  selectedRange: {
    from: new Date().toISOString().slice(0, 10),
    to: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000)
      .toISOString()
      .slice(0, 10),
  },
  isLoading: false,
  error: null,

  fetchEvents: async (params = {}) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<CalendarEvent[]>(
        "/calendar-events",
        {
          params,
        }
      );
      set({
        events: response.data || [],
        isLoading: false,
      });
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
    }
  },

  createEvent: async (payload) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<CalendarEvent>(
        "/calendar-events",
        payload
      );
      const event = response.data as CalendarEvent;
      set({
        events: upsertEvent(get().events, event),
        isLoading: false,
      });
      return event;
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  updateEvent: async (id, payload) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.put<CalendarEvent>(
        `/calendar-events/${id}`,
        payload
      );
      const event = response.data as CalendarEvent;
      set({
        events: upsertEvent(get().events, event),
        isLoading: false,
      });
      return event;
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  deleteEvent: async (id) => {
    set({ isLoading: true, error: null });
    try {
      await apiClient.delete(`/calendar-events/${id}`);
      set({
        events: get().events.filter((event) => event.id !== id),
        isLoading: false,
      });
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  fetchConnections: async () => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<CalendarConnection[]>(
        "/calendar-connections"
      );
      set({
        connections: response.data || [],
        isLoading: false,
      });
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
    }
  },

  createConnection: async (payload) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.post<CalendarConnection>(
        "/calendar-connections",
        payload
      );
      const connection = response.data as CalendarConnection;
      set({
        connections: upsertConnection(get().connections, connection),
        isLoading: false,
      });
      return connection;
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  updateConnection: async (id, payload) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.put<CalendarConnection>(
        `/calendar-connections/${id}`,
        payload
      );
      const connection = response.data as CalendarConnection;
      set({
        connections: upsertConnection(get().connections, connection),
        isLoading: false,
      });
      return connection;
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },

  deleteConnection: async (id) => {
    set({ isLoading: true, error: null });
    try {
      await apiClient.delete(`/calendar-connections/${id}`);
      set({
        connections: get().connections.filter(
          (connection) => connection.id !== id
        ),
        isLoading: false,
      });
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },
}));
