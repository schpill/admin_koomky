import { render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { Header } from "@/components/layout/header";

vi.mock("next/navigation", () => ({
  useRouter: () => ({
    push: vi.fn(),
  }),
}));

vi.mock("next-themes", () => ({
  useTheme: () => ({
    theme: "light",
    resolvedTheme: "light",
    setTheme: vi.fn(),
  }),
}));

vi.mock("@/components/providers/i18n-provider", () => ({
  useI18n: () => ({
    t: (key: string) => {
      const map: Record<string, string> = {
        "header.toggleTheme": "Toggle theme",
        "header.userMenu": "User menu",
        "header.openNavigation": "Open navigation",
        "header.openShortcuts": "Open shortcuts",
      };

      return map[key] ?? key;
    },
  }),
}));

vi.mock("@/components/search/command-palette", () => ({
  CommandPalette: () => <div data-testid="command-palette" />,
}));

vi.mock("@/components/layout/locale-switcher", () => ({
  LocaleSwitcher: () => <div data-testid="locale-switcher" />,
}));

vi.mock("@/components/layout/notification-bell", () => ({
  NotificationBell: () => (
    <button aria-label="Notifications" className="brand-control" />
  ),
}));

vi.mock("@/components/timer/timer-badge", () => ({
  TimerBadge: () => <div data-testid="timer-badge" />,
}));

describe("Header cursor affordance", () => {
  it("adds cursor-pointer to interactive icon buttons", () => {
    render(<Header />);

    expect(screen.getByRole("button", { name: "Open shortcuts" })).toHaveClass(
      "cursor-pointer"
    );
    expect(screen.getByRole("button", { name: "Toggle theme" })).toHaveClass(
      "cursor-pointer"
    );
    expect(screen.getByRole("button", { name: "User menu" })).toHaveClass(
      "cursor-pointer"
    );
  });
});
