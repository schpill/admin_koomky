import { render, screen } from "@testing-library/react";
import { describe, it, expect, vi, beforeEach } from "vitest";
import { RecentDocumentsWidget } from "@/components/dashboard/recent-documents-widget";
import * as documentStore from "@/lib/stores/documents";

vi.mock("@/lib/stores/documents", () => ({
  useDocumentStore: vi.fn(),
}));

const mockFetchDocuments = vi.fn();

function setup(overrides: Partial<ReturnType<typeof documentStore.useDocumentStore>>) {
  vi.mocked(documentStore.useDocumentStore).mockReturnValue({
    documents: [],
    fetchDocuments: mockFetchDocuments,
    isLoading: false,
    ...overrides,
  } as any);
}

describe("RecentDocumentsWidget", () => {
  beforeEach(() => {
    mockFetchDocuments.mockResolvedValue(undefined);
  });

  it("ne crashe pas si documents est undefined (régression #crash-slice)", () => {
    setup({ documents: undefined as any });
    expect(() => render(<RecentDocumentsWidget />)).not.toThrow();
    expect(screen.getByText(/Aucun document importé/i)).toBeInTheDocument();
  });

  it("affiche le spinner pendant le chargement initial", () => {
    setup({ isLoading: true, documents: [] });
    render(<RecentDocumentsWidget />);
    expect(screen.getByText(/Chargement des documents/i)).toBeInTheDocument();
  });

  it("affiche l'état vide quand il n'y a pas de documents", () => {
    setup({ documents: [] });
    render(<RecentDocumentsWidget />);
    expect(screen.getByText(/Aucun document importé/i)).toBeInTheDocument();
  });

  it("affiche les documents récents", () => {
    const doc = {
      id: "doc-1",
      title: "Contrat Dupont",
      document_type: "pdf" as const,
      created_at: "2026-02-01T00:00:00Z",
    };
    setup({ documents: [doc as any] });
    render(<RecentDocumentsWidget />);
    expect(screen.getByText("Contrat Dupont")).toBeInTheDocument();
  });

  it("appelle fetchDocuments au montage", () => {
    setup({ documents: [] });
    render(<RecentDocumentsWidget />);
    expect(mockFetchDocuments).toHaveBeenCalledWith({
      per_page: 5,
      sort_by: "created_at",
      sort_order: "desc",
    });
  });
});
