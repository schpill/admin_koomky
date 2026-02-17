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
});
