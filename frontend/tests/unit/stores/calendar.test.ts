import { beforeEach, describe, expect, it, vi } from "vitest";
import { useCalendarStore } from "@/lib/stores/calendar";

vi.mock("@/lib/api", () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
  },
}));

import { apiClient } from "@/lib/api";

describe("useCalendarStore", () => {
  beforeEach(() => {
    useCalendarStore.setState({
      events: [],
      connections: [],
      autoEventRules: {
        project_deadlines: true,
        task_due_dates: true,
        invoice_reminders: true,
      },
      selectedRange: {
        from: "2026-03-01",
        to: "2026-03-31",
      },
      isLoading: false,
      error: null,
    });
    vi.clearAllMocks();
  });

  it("fetches events and connections", async () => {
    (apiClient.get as any).mockResolvedValueOnce({
      data: [
        {
          id: "evt_1",
          title: "Kickoff",
          start_at: "2026-03-10 09:00:00",
          end_at: "2026-03-10 10:00:00",
          type: "meeting",
          all_day: false,
          sync_status: "synced",
        },
      ],
    });

    (apiClient.get as any).mockResolvedValueOnce({
      data: [
        {
          id: "conn_1",
          provider: "google",
          name: "Google Work",
          sync_enabled: true,
        },
      ],
    });

    await useCalendarStore.getState().fetchEvents({
      date_from: "2026-03-01",
      date_to: "2026-03-31",
    });
    await useCalendarStore.getState().fetchConnections();

    const state = useCalendarStore.getState();
    expect(state.events).toHaveLength(1);
    expect(state.connections).toHaveLength(1);
    expect(apiClient.get).toHaveBeenNthCalledWith(1, "/calendar-events", {
      params: { date_from: "2026-03-01", date_to: "2026-03-31" },
    });
    expect(apiClient.get).toHaveBeenNthCalledWith(2, "/calendar-connections");
  });

  it("creates updates and deletes calendar events", async () => {
    (apiClient.post as any).mockResolvedValueOnce({
      data: {
        id: "evt_2",
        title: "Planning",
        start_at: "2026-03-11 09:00:00",
        end_at: "2026-03-11 10:00:00",
        type: "meeting",
        all_day: false,
        sync_status: "local",
      },
    });

    const created = await useCalendarStore.getState().createEvent({
      title: "Planning",
      start_at: "2026-03-11 09:00:00",
      end_at: "2026-03-11 10:00:00",
      type: "meeting",
    });

    expect(created?.id).toBe("evt_2");

    (apiClient.put as any).mockResolvedValueOnce({
      data: {
        id: "evt_2",
        title: "Planning updated",
        start_at: "2026-03-11 09:00:00",
        end_at: "2026-03-11 10:00:00",
        type: "meeting",
        all_day: false,
        sync_status: "local",
      },
    });

    await useCalendarStore.getState().updateEvent("evt_2", {
      title: "Planning updated",
    });
    expect(useCalendarStore.getState().events[0]?.title).toBe(
      "Planning updated"
    );

    (apiClient.delete as any).mockResolvedValueOnce({});
    await useCalendarStore.getState().deleteEvent("evt_2");
    expect(useCalendarStore.getState().events).toEqual([]);
  });

  it("fetches and updates auto-event rules", async () => {
    (apiClient.get as any).mockResolvedValueOnce({
      data: {
        auto_events: {
          project_deadlines: false,
          task_due_dates: true,
          invoice_reminders: false,
        },
      },
    });

    await useCalendarStore.getState().fetchAutoEventRules();

    expect(useCalendarStore.getState().autoEventRules).toEqual({
      project_deadlines: false,
      task_due_dates: true,
      invoice_reminders: false,
    });
    expect(apiClient.get).toHaveBeenCalledWith("/settings/calendar");

    (apiClient.put as any).mockResolvedValueOnce({
      data: {
        auto_events: {
          project_deadlines: true,
          task_due_dates: false,
          invoice_reminders: true,
        },
      },
    });

    const updated = await useCalendarStore.getState().updateAutoEventRules({
      project_deadlines: true,
      task_due_dates: false,
      invoice_reminders: true,
    });

    expect(updated).toEqual({
      project_deadlines: true,
      task_due_dates: false,
      invoice_reminders: true,
    });
    expect(useCalendarStore.getState().autoEventRules).toEqual(updated);
    expect(apiClient.put).toHaveBeenCalledWith("/settings/calendar", {
      auto_events: {
        project_deadlines: true,
        task_due_dates: false,
        invoice_reminders: true,
      },
    });
  });

  it("creates updates and deletes calendar connections", async () => {
    (apiClient.post as any).mockResolvedValueOnce({
      data: {
        id: "conn_1",
        provider: "google",
        name: "Google Work",
        sync_enabled: true,
      },
    });

    const created = await useCalendarStore.getState().createConnection({
      provider: "google",
      name: "Google Work",
      credentials: { access_token: "token" },
      calendar_id: "primary",
      sync_enabled: true,
    });

    expect(created?.id).toBe("conn_1");

    (apiClient.put as any).mockResolvedValueOnce({
      data: {
        id: "conn_1",
        provider: "google",
        name: "Google Work",
        sync_enabled: false,
      },
    });

    const updated = await useCalendarStore
      .getState()
      .updateConnection("conn_1", { sync_enabled: false });

    expect(updated?.sync_enabled).toBe(false);
    expect(useCalendarStore.getState().connections[0]?.sync_enabled).toBe(
      false
    );

    (apiClient.delete as any).mockResolvedValueOnce({});
    await useCalendarStore.getState().deleteConnection("conn_1");
    expect(useCalendarStore.getState().connections).toEqual([]);
  });

  it("captures fetch errors and throws mutation errors", async () => {
    (apiClient.get as any).mockRejectedValueOnce(new Error("events failed"));
    await useCalendarStore.getState().fetchEvents();
    expect(useCalendarStore.getState().error).toBe("events failed");

    (apiClient.get as any).mockRejectedValueOnce(
      new Error("connections failed")
    );
    await useCalendarStore.getState().fetchConnections();
    expect(useCalendarStore.getState().error).toBe("connections failed");

    (apiClient.post as any).mockRejectedValueOnce(
      new Error("create event failed")
    );
    await expect(
      useCalendarStore.getState().createEvent({
        title: "x",
        start_at: "2026-03-10 09:00:00",
        end_at: "2026-03-10 10:00:00",
        type: "meeting",
      })
    ).rejects.toThrow("create event failed");

    (apiClient.put as any).mockRejectedValueOnce(
      new Error("update event failed")
    );
    await expect(
      useCalendarStore.getState().updateEvent("evt_1", { title: "x" })
    ).rejects.toThrow("update event failed");

    (apiClient.delete as any).mockRejectedValueOnce(
      new Error("delete event failed")
    );
    await expect(
      useCalendarStore.getState().deleteEvent("evt_1")
    ).rejects.toThrow("delete event failed");

    (apiClient.post as any).mockRejectedValueOnce(
      new Error("create connection failed")
    );
    await expect(
      useCalendarStore.getState().createConnection({
        provider: "google",
        name: "x",
        credentials: {},
      })
    ).rejects.toThrow("create connection failed");

    (apiClient.put as any).mockRejectedValueOnce(
      new Error("update connection failed")
    );
    await expect(
      useCalendarStore.getState().updateConnection("conn_1", {
        sync_enabled: false,
      })
    ).rejects.toThrow("update connection failed");

    (apiClient.delete as any).mockRejectedValueOnce(
      new Error("delete connection failed")
    );
    await expect(
      useCalendarStore.getState().deleteConnection("conn_1")
    ).rejects.toThrow("delete connection failed");

    (apiClient.get as any).mockRejectedValueOnce(new Error("rules failed"));
    await expect(
      useCalendarStore.getState().fetchAutoEventRules()
    ).rejects.toThrow("rules failed");
    expect(useCalendarStore.getState().error).toBe("rules failed");

    (apiClient.put as any).mockRejectedValueOnce(
      new Error("update rules failed")
    );
    await expect(
      useCalendarStore.getState().updateAutoEventRules({
        project_deadlines: false,
        task_due_dates: false,
        invoice_reminders: false,
      })
    ).rejects.toThrow("update rules failed");
    expect(useCalendarStore.getState().error).toBe("update rules failed");
  });
});
