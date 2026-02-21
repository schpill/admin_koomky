import { describe, it, expect, beforeEach, vi } from "vitest";
import { useDocumentStore } from "@/lib/stores/documents";

vi.mock("@/lib/api", () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
  },
}));

import { apiClient } from "@/lib/api";

describe("useDocumentStore", () => {
  beforeEach(() => {
    useDocumentStore.setState({
      documents: [],
      currentDocument: null,
      stats: null,
      pagination: null,
      isLoading: false,
      error: null,
    });
    vi.clearAllMocks();
  });

  it("fetches documents and updates state", async () => {
    (apiClient.get as any).mockResolvedValue({
      data: {
        data: [{ id: "d1", title: "Doc 1", document_type: "pdf" }],
        current_page: 1,
        last_page: 1,
        per_page: 24,
        total: 1,
      },
    });

    await useDocumentStore.getState().fetchDocuments();

    const state = useDocumentStore.getState();
    expect(state.documents).toHaveLength(1);
    expect(state.documents[0].title).toBe("Doc 1");
    expect(state.pagination?.total).toBe(1);
  });

  it("uploads document and adds to list", async () => {
    const newDoc = { id: "d2", title: "New Doc", document_type: "image" };
    (apiClient.post as any).mockResolvedValue({
      data: newDoc,
    });

    const formData = new FormData();
    formData.append("file", new File([""], "test.png"));
    
    await useDocumentStore.getState().uploadDocument(formData);

    const state = useDocumentStore.getState();
    expect(state.documents[0].id).toBe("d2");
  });

  it("updates document metadata", async () => {
    useDocumentStore.setState({
      documents: [{ id: "d1", title: "Old Title" } as any],
    });

    (apiClient.put as any).mockResolvedValue({
      data: { id: "d1", title: "New Title" },
    });

    await useDocumentStore.getState().updateDocument("d1", { title: "New Title" });

    expect(useDocumentStore.getState().documents[0].title).toBe("New Title");
  });

  it("deletes document", async () => {
    useDocumentStore.setState({
      documents: [{ id: "d1" } as any, { id: "d2" } as any],
    });

    (apiClient.delete as any).mockResolvedValue({});

    await useDocumentStore.getState().deleteDocument("d1");

    expect(useDocumentStore.getState().documents).toHaveLength(1);
    expect(useDocumentStore.getState().documents[0].id).toBe("d2");
  });

  it("bulk deletes documents", async () => {
    useDocumentStore.setState({
      documents: [{ id: "d1" } as any, { id: "d2" } as any, { id: "d3" } as any],
    });

    (apiClient.delete as any).mockResolvedValue({});

    await useDocumentStore.getState().bulkDelete(["d1", "d2"]);

    expect(useDocumentStore.getState().documents).toHaveLength(1);
    expect(useDocumentStore.getState().documents[0].id).toBe("d3");
  });

  it("fetches stats", async () => {
    const stats = { total_count: 10, total_size_bytes: 1024 };
    (apiClient.get as any).mockResolvedValue({
      data: stats,
    });

    await useDocumentStore.getState().fetchStats();

    expect(useDocumentStore.getState().stats).toEqual(stats);
  });

  it("handles errors", async () => {
    (apiClient.get as any).mockRejectedValue(new Error("API Error"));

    await useDocumentStore.getState().fetchDocuments();

    expect(useDocumentStore.getState().error).toBe("API Error");
  });
});
