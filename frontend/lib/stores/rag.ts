import { create } from "zustand";
import { apiClient } from "@/lib/api";
import { portalApiClient } from "@/lib/portal";

export interface RagSource {
  document_id: string;
  title?: string;
  chunk_index: number;
  score: number;
}

export interface RagMessage {
  id: string;
  role: "user" | "assistant";
  content: string;
  sources?: RagSource[];
  created_at: string;
}

interface RagState {
  messages: RagMessage[];
  loading: boolean;
  error: string | null;
  sources: RagSource[];
  askQuestion: (question: string, clientId?: string, portalMode?: boolean) => Promise<void>;
  searchDocuments: (query: string, clientId?: string, portalMode?: boolean) => Promise<any[]>;
  clearHistory: () => void;
}

function uid() {
  return `${Date.now()}-${Math.random().toString(16).slice(2)}`;
}

export const useRagStore = create<RagState>((set, get) => ({
  messages: [],
  loading: false,
  error: null,
  sources: [],

  askQuestion: async (question, clientId, portalMode = false) => {
    const userMessage: RagMessage = {
      id: uid(),
      role: "user",
      content: question,
      created_at: new Date().toISOString()
    };

    set({ loading: true, error: null, messages: [...get().messages, userMessage] });

    try {
      const response = portalMode
        ? await portalApiClient.post<any>("/portal/rag/ask", { question })
        : await apiClient.post<any>("/rag/ask", { question, client_id: clientId });

      const data = response.data;
      const assistantMessage: RagMessage = {
        id: uid(),
        role: "assistant",
        content: data.answer,
        sources: data.sources || [],
        created_at: new Date().toISOString()
      };

      set({
        loading: false,
        messages: [...get().messages, assistantMessage],
        sources: data.sources || []
      });
    } catch (error) {
      set({
        loading: false,
        error: (error as Error).message,
      });
      throw error;
    }
  },

  searchDocuments: async (query, clientId, portalMode = false) => {
    set({ loading: true, error: null });
    try {
      const response = portalMode
        ? await portalApiClient.get<any>(`/portal/rag/search?q=${encodeURIComponent(query)}`)
        : await apiClient.get<any>("/rag/search", { params: { q: query, client_id: clientId } });

      const data = response.data;
      set({ loading: false });
      return Array.isArray(data) ? data : [];
    } catch (error) {
      set({ loading: false, error: (error as Error).message });
      throw error;
    }
  },

  clearHistory: () => set({ messages: [], sources: [], error: null })
}));
