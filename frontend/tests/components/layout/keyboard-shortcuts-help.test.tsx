import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { KeyboardShortcutsHelp } from "@/components/layout/keyboard-shortcuts-help";

vi.mock("@/components/providers/i18n-provider", () => ({
  useI18n: () => ({
    t: (key: string) => {
      if (key === "ui.dialog.close") {
        return "Close";
      }
      return key;
    },
  }),
}));

describe("KeyboardShortcutsHelp", () => {
  it("opens with ? key and shows expected shortcuts", () => {
    render(<KeyboardShortcutsHelp />);

    fireEvent.keyDown(window, { key: "?" });

    expect(screen.getByText("Keyboard shortcuts")).toBeInTheDocument();
    expect(screen.getByText("Ctrl/Cmd + K")).toBeInTheDocument();
    expect(screen.getByText("Ctrl/Cmd + N")).toBeInTheDocument();
    expect(screen.getByText("Escape")).toBeInTheDocument();
  });
});
