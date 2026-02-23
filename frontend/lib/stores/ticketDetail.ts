import { create } from "zustand";
import { apiClient } from "@/lib/api";
import type { Ticket, TicketMessage } from "./tickets";

interface TicketDetailState {
  ticket: Ticket | null;
  messages: TicketMessage[];
  documents: any[];
  isLoading: boolean;
  error: string | null;

  fetchTicket: (id: string) => Promise<void>;
  addMessage: (
    ticketId: string,
    data: { content: string; is_internal: boolean }
  ) => Promise<TicketMessage>;
  editMessage: (
    ticketId: string,
    msgId: string,
    content: string
  ) => Promise<void>;
  deleteMessage: (ticketId: string, msgId: string) => Promise<void>;
  uploadDocument: (ticketId: string, formData: FormData) => Promise<void>;
  attachDocument: (ticketId: string, documentId: string) => Promise<void>;
  detachDocument: (ticketId: string, docId: string) => Promise<void>;
  reset: () => void;
}

export const useTicketDetailStore = create<TicketDetailState>((set, get) => ({
  ticket: null,
  messages: [],
  documents: [],
  isLoading: false,
  error: null,

  fetchTicket: async (id) => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<Ticket>(`/tickets/${id}`);
      const ticket = response.data;
      set({
        ticket,
        messages: (ticket as any).messages ?? [],
        documents: (ticket as any).documents ?? [],
        isLoading: false,
      });
    } catch (error) {
      set({ error: (error as Error).message, isLoading: false });
    }
  },

  addMessage: async (ticketId, data) => {
    try {
      const response = await apiClient.post<TicketMessage>(
        `/tickets/${ticketId}/messages`,
        data
      );
      const message = response.data;
      set((state) => ({ messages: [...state.messages, message] }));
      return message;
    } catch (error) {
      set({ error: (error as Error).message });
      throw error;
    }
  },

  editMessage: async (ticketId, msgId, content) => {
    try {
      const response = await apiClient.put<TicketMessage>(
        `/tickets/${ticketId}/messages/${msgId}`,
        { content }
      );
      const updated = response.data;
      set((state) => ({
        messages: state.messages.map((m) => (m.id === msgId ? updated : m)),
      }));
    } catch (error) {
      set({ error: (error as Error).message });
      throw error;
    }
  },

  deleteMessage: async (ticketId, msgId) => {
    try {
      await apiClient.delete(`/tickets/${ticketId}/messages/${msgId}`);
      set((state) => ({
        messages: state.messages.filter((m) => m.id !== msgId),
      }));
    } catch (error) {
      set({ error: (error as Error).message });
      throw error;
    }
  },

  uploadDocument: async (ticketId, formData) => {
    try {
      const response = await apiClient.post<any>(
        `/tickets/${ticketId}/documents`,
        formData
      );
      set((state) => ({ documents: [...state.documents, response.data] }));
    } catch (error) {
      set({ error: (error as Error).message });
      throw error;
    }
  },

  attachDocument: async (ticketId, documentId) => {
    try {
      const response = await apiClient.post<any>(
        `/tickets/${ticketId}/documents/attach`,
        { document_id: documentId }
      );
      set((state) => ({ documents: [...state.documents, response.data] }));
    } catch (error) {
      set({ error: (error as Error).message });
      throw error;
    }
  },

  detachDocument: async (ticketId, docId) => {
    try {
      await apiClient.delete(`/tickets/${ticketId}/documents/${docId}`);
      set((state) => ({
        documents: state.documents.filter((d) => d.id !== docId),
      }));
    } catch (error) {
      set({ error: (error as Error).message });
      throw error;
    }
  },

  reset: () =>
    set({
      ticket: null,
      messages: [],
      documents: [],
      isLoading: false,
      error: null,
    }),
}));
