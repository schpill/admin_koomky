import { describe, it, expect, beforeEach, vi } from "vitest";
import { useTicketDetailStore } from "@/lib/stores/ticketDetail";
import type { Ticket, TicketMessage } from "@/lib/stores/tickets";

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

const mockMessage: TicketMessage = {
  id: "m1",
  ticket_id: "t1",
  user_id: "u1",
  content: "Hello",
  is_internal: false,
  created_at: "2026-01-01T00:00:00Z",
  updated_at: "2026-01-01T00:00:00Z",
};

describe("useTicketDetailStore", () => {
  beforeEach(() => {
    useTicketDetailStore.getState().reset();
    vi.clearAllMocks();
  });

  it("fetchTicket loads ticket with messages and documents", async () => {
    const ticketWithRelations = {
      ...mockTicket,
      messages: [mockMessage],
      documents: [],
    };
    (apiClient.get as any).mockResolvedValue({ data: ticketWithRelations });
    await useTicketDetailStore.getState().fetchTicket("t1");
    const state = useTicketDetailStore.getState();
    expect(state.ticket?.title).toBe("Test ticket");
    expect(state.messages).toHaveLength(1);
    expect(state.documents).toHaveLength(0);
  });

  it("addMessage appends to messages", async () => {
    useTicketDetailStore.setState({ messages: [] });
    (apiClient.post as any).mockResolvedValue({ data: mockMessage });
    await useTicketDetailStore
      .getState()
      .addMessage("t1", { content: "Hello", is_internal: false });
    expect(useTicketDetailStore.getState().messages).toHaveLength(1);
  });

  it("editMessage updates message in list", async () => {
    useTicketDetailStore.setState({ messages: [mockMessage] });
    const updated = { ...mockMessage, content: "Updated" };
    (apiClient.put as any).mockResolvedValue({ data: updated });
    await useTicketDetailStore.getState().editMessage("t1", "m1", "Updated");
    expect(useTicketDetailStore.getState().messages[0].content).toBe("Updated");
  });

  it("deleteMessage removes from list", async () => {
    useTicketDetailStore.setState({ messages: [mockMessage] });
    (apiClient.delete as any).mockResolvedValue({});
    await useTicketDetailStore.getState().deleteMessage("t1", "m1");
    expect(useTicketDetailStore.getState().messages).toHaveLength(0);
  });

  it("uploadDocument appends to documents", async () => {
    useTicketDetailStore.setState({ documents: [] });
    const mockDoc = { id: "d1", title: "file.pdf" };
    (apiClient.post as any).mockResolvedValue({ data: mockDoc });
    const fd = new FormData();
    await useTicketDetailStore.getState().uploadDocument("t1", fd);
    expect(useTicketDetailStore.getState().documents).toHaveLength(1);
  });

  it("attachDocument appends to documents", async () => {
    useTicketDetailStore.setState({ documents: [] });
    const mockDoc = { id: "d2", title: "existing.pdf" };
    (apiClient.post as any).mockResolvedValue({ data: mockDoc });
    await useTicketDetailStore.getState().attachDocument("t1", "d2");
    expect(useTicketDetailStore.getState().documents).toHaveLength(1);
  });

  it("detachDocument removes from list without deleting from GED", async () => {
    useTicketDetailStore.setState({
      documents: [{ id: "d1", title: "file.pdf" }],
    });
    (apiClient.delete as any).mockResolvedValue({});
    await useTicketDetailStore.getState().detachDocument("t1", "d1");
    expect(useTicketDetailStore.getState().documents).toHaveLength(0);
  });

  it("reset clears all state", () => {
    useTicketDetailStore.setState({
      ticket: mockTicket,
      messages: [mockMessage],
    });
    useTicketDetailStore.getState().reset();
    const state = useTicketDetailStore.getState();
    expect(state.ticket).toBeNull();
    expect(state.messages).toHaveLength(0);
  });

  it("sets error on fetchTicket failure", async () => {
    (apiClient.get as any).mockRejectedValue(new Error("Not found"));
    await useTicketDetailStore.getState().fetchTicket("t1");
    expect(useTicketDetailStore.getState().error).toBe("Not found");
  });
});
