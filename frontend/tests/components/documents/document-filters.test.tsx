import { describe, expect, it, vi, beforeEach } from "vitest";
import { render, screen, fireEvent } from "@testing-library/react";
import { DocumentFilters } from "@/components/documents/document-filters";
import { useDocumentStore } from "@/lib/stores/documents";

vi.mock("@/lib/stores/documents", () => ({
  useDocumentStore: vi.fn(),
}));

vi.mock("@/lib/stores/clients", () => ({
  useClientStore: () => ({
    clients: [],
    fetchClients: vi.fn(),
  }),
}));

describe("DocumentFilters", () => {
  const fetchDocuments = vi.fn();

  beforeEach(() => {
    (useDocumentStore as any).mockReturnValue({
      fetchDocuments,
    });
    vi.clearAllMocks();
  });

  it("renders search input", () => {
    render(<DocumentFilters />);
    expect(
      screen.getByPlaceholderText(/rechercher par titre/i)
    ).toBeInTheDocument();
  });

  it("calls fetchDocuments when filters are cleared", () => {
    render(<DocumentFilters />);

    const clearButton = screen.getByText(/réinitialiser les filtres/i);
    fireEvent.click(clearButton);

    expect(fetchDocuments).toHaveBeenCalled();
  });
});
