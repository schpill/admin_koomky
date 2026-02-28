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
        from: new Date().toISOString().slice(0, 10),
        to: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000)
          .toISOString()
          .slice(0, 10),
      },
      isLoading: false,
      error: null,
    });
    vi.clearAllMocks();
  });

  it("falls back to an empty event list for malformed payloads", async () => {
    (apiClient.get as any).mockResolvedValue({
      data: {},
    });

    await useCalendarStore.getState().fetchEvents();

    expect(useCalendarStore.getState().events).toEqual([]);
    expect(useCalendarStore.getState().error).toBeNull();
  });
});
