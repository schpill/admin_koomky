import { describe, it, expect, beforeEach, vi } from "vitest";
import { useTicketStore } from "@/lib/stores/tickets";
import type { Ticket, TicketStats } from "@/lib/stores/tickets";

vi.mock("@/lib/api", () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
  },
}));

import { apiClient } from "@/lib/api";

const mockTicket: Ticket = {
  id: "t1",
  user_id: "u1",
  assigned_to: "u1",
  client_id: null,
  project_id: null,
  title: "Test ticket",
  description: "Description",
  status: "open",
  priority: "normal",
  category: null,
  tags: [],
  deadline: null,
  resolved_at: null,
  closed_at: null,
  first_response_at: null,
  created_at: "2026-01-01T00:00:00Z",
  updated_at: "2026-01-01T00:00:00Z",
};

describe("useTicketStore", () => {
  beforeEach(() => {
    useTicketStore.setState({
      tickets: [],
      overdueTickets: [],
      stats: null,
      pagination: null,
      isLoading: false,
      error: null,
      searchQuery: "",
      filters: {},
      sort: "created_at",
      sortDir: "desc",
    });
    vi.clearAllMocks();
  });

  it("fetchTickets updates list and pagination", async () => {
    (apiClient.get as any).mockResolvedValue({
      data: {
        data: [mockTicket],
        current_page: 1,
        last_page: 1,
        per_page: 20,
        total: 1,
      },
    });
    await useTicketStore.getState().fetchTickets();
    const state = useTicketStore.getState();
    expect(state.tickets).toHaveLength(1);
    expect(state.tickets[0].title).toBe("Test ticket");
    expect(state.pagination?.total).toBe(1);
    expect(state.isLoading).toBe(false);
  });

  it("createTicket adds ticket to list", async () => {
    (apiClient.post as any).mockResolvedValue({ data: mockTicket });
    await useTicketStore.getState().createTicket({
      title: "Test ticket",
      description: "Desc",
      priority: "normal",
    });
    expect(useTicketStore.getState().tickets).toHaveLength(1);
  });

  it("updateTicket modifies ticket in list", async () => {
    useTicketStore.setState({ tickets: [mockTicket] });
    const updated = { ...mockTicket, title: "Updated" };
    (apiClient.put as any).mockResolvedValue({ data: updated });
    await useTicketStore.getState().updateTicket("t1", { title: "Updated" });
    expect(useTicketStore.getState().tickets[0].title).toBe("Updated");
  });

  it("deleteTicket removes ticket from list", async () => {
    useTicketStore.setState({ tickets: [mockTicket] });
    (apiClient.delete as any).mockResolvedValue({});
    await useTicketStore.getState().deleteTicket("t1");
    expect(useTicketStore.getState().tickets).toHaveLength(0);
  });

  it("changeStatus updates ticket status in list", async () => {
    useTicketStore.setState({ tickets: [mockTicket] });
    const updated = { ...mockTicket, status: "in_progress" as const };
    (apiClient.patch as any).mockResolvedValue({ data: updated });
    await useTicketStore.getState().changeStatus("t1", "in_progress");
    expect(useTicketStore.getState().tickets[0].status).toBe("in_progress");
  });

  it("reassign updates assigned_to in list", async () => {
    useTicketStore.setState({ tickets: [mockTicket] });
    const updated = { ...mockTicket, assigned_to: "u2" };
    (apiClient.patch as any).mockResolvedValue({ data: updated });
    await useTicketStore.getState().reassign("t1", "u2");
    expect(useTicketStore.getState().tickets[0].assigned_to).toBe("u2");
  });

  it("fetchStats updates stats state", async () => {
    const mockStats: TicketStats = {
      total_tickets: 5,
      total_open: 2,
      total_in_progress: 1,
      total_pending: 0,
      total_resolved: 1,
      total_closed: 1,
      total_low_priority: 0,
      total_normal_priority: 3,
      total_high_priority: 1,
      total_urgent_priority: 1,
      total_overdue: 1,
      average_resolution_time_in_hours: 12.5,
    };
    (apiClient.get as any).mockResolvedValue({ data: mockStats });
    await useTicketStore.getState().fetchStats();
    expect(useTicketStore.getState().stats?.total_tickets).toBe(5);
  });

  it("fetchOverdue updates overdueTickets", async () => {
    (apiClient.get as any).mockResolvedValue({
      data: { data: [mockTicket] },
    });
    await useTicketStore.getState().fetchOverdue();
    expect(useTicketStore.getState().overdueTickets).toHaveLength(1);
  });

  it("sets error on fetchTickets failure", async () => {
    (apiClient.get as any).mockRejectedValue(new Error("Network error"));
    await useTicketStore.getState().fetchTickets();
    expect(useTicketStore.getState().error).toBe("Network error");
  });

  it("setSearchQuery updates searchQuery", () => {
    useTicketStore.getState().setSearchQuery("bug");
    expect(useTicketStore.getState().searchQuery).toBe("bug");
  });

  it("setFilters updates filters", () => {
    useTicketStore.getState().setFilters({ status: "open" });
    expect(useTicketStore.getState().filters.status).toBe("open");
  });

  it("setSort updates sort and sortDir", () => {
    useTicketStore.getState().setSort("priority", "asc");
    expect(useTicketStore.getState().sort).toBe("priority");
    expect(useTicketStore.getState().sortDir).toBe("asc");
  });
});
