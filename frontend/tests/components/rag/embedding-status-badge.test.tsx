import { describe, expect, it, vi } from "vitest";
import { render, screen, fireEvent } from "@testing-library/react";
import { EmbeddingStatusBadge } from "@/components/rag/embedding-status-badge";

describe("EmbeddingStatusBadge", () => {
  it("renders indexed state", () => {
    render(<EmbeddingStatusBadge status="indexed" />);
    expect(screen.getByText("Indexed")).toBeInTheDocument();
  });

  it("shows retry button for failed state", () => {
    const onRetry = vi.fn();
    render(<EmbeddingStatusBadge status="failed" onRetry={onRetry} />);
    fireEvent.click(screen.getByText("Relancer l'indexation"));
    expect(onRetry).toHaveBeenCalledTimes(1);
  });

  it("renders non indexable for null", () => {
    render(<EmbeddingStatusBadge status={null} />);
    expect(screen.getByText("Non indexable")).toBeInTheDocument();
  });
});
