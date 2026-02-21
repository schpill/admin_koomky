import { describe, expect, it, vi } from "vitest";
import { render, screen, fireEvent } from "@testing-library/react";
import { DocumentCard } from "@/components/documents/document-card";

describe("DocumentCard", () => {
  const mockDoc = {
    id: "d1",
    title: "Test Document",
    original_filename: "test.pdf",
    document_type: "pdf",
    file_size: 1024,
    created_at: new Date().toISOString(),
    version: 1,
    tags: [],
    client_id: null,
  } as any;

  it("renders document information", () => {
    render(<DocumentCard document={mockDoc} />);
    expect(screen.getByText("Test Document")).toBeInTheDocument();
    expect(screen.getByText("test.pdf")).toBeInTheDocument();
    expect(screen.getByText("1 KB")).toBeInTheDocument();
  });

  it("shows version badge when version > 1", () => {
    render(<DocumentCard document={{ ...mockDoc, version: 2 }} />);
    expect(screen.getByText("v2")).toBeInTheDocument();
  });

  it("calls onSelect when checkbox is clicked", () => {
    const onSelect = vi.fn();
    render(<DocumentCard document={mockDoc} onSelect={onSelect} />);
    
    const checkbox = screen.getByRole("checkbox");
    fireEvent.click(checkbox);
    
    expect(onSelect).toHaveBeenCalled();
  });

  it("shows client badge when associated", () => {
    render(<DocumentCard document={{ ...mockDoc, client: { name: "Client A" } }} />);
    expect(screen.getByText("Client A")).toBeInTheDocument();
  });
});
