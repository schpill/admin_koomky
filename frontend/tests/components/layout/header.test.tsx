import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { Header } from "@/components/layout/header";

vi.mock("next-themes", () => ({
  useTheme: () => ({
    theme: "light",
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
  NotificationBell: () => <div data-testid="notification-bell" />,
}));

describe("Header", () => {
  it("calls mobile navigation and shortcuts callbacks", () => {
    const onOpenNavigation = vi.fn();
    const onOpenShortcuts = vi.fn();

    render(
      <Header
        onOpenNavigation={onOpenNavigation}
        onOpenShortcuts={onOpenShortcuts}
      />
    );

    fireEvent.click(screen.getByRole("button", { name: "Open navigation" }));
    fireEvent.click(screen.getByRole("button", { name: "Open shortcuts" }));

    expect(onOpenNavigation).toHaveBeenCalledOnce();
    expect(onOpenShortcuts).toHaveBeenCalledOnce();
  });
});
