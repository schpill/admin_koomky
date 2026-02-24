import { describe, expect, it, vi } from "vitest";
import { render, screen, fireEvent } from "@testing-library/react";

vi.mock("@/lib/stores/rag", () => ({
  useRagStore: () => ({
    messages: [],
    loading: false,
    askQuestion: vi.fn(),
    clearHistory: vi.fn(),
  }),
}));

import { ChatWidget } from "@/components/rag/chat-widget";

describe("ChatWidget", () => {
  it("renders toggle button", () => {
    render(<ChatWidget />);
    expect(screen.getByRole("button")).toBeInTheDocument();
  });

  it("opens panel and allows typing", () => {
    render(<ChatWidget />);
    fireEvent.click(screen.getByRole("button"));
    expect(
      screen.getByPlaceholderText("Posez votre question...")
    ).toBeInTheDocument();
  });
});
