import { beforeEach, describe, expect, it, vi } from "vitest";
import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { I18nProvider } from "@/components/providers/i18n-provider";
import { Header } from "@/components/layout/header";
import { useAuthStore } from "@/lib/stores/auth";

const routerPush = vi.fn();

vi.mock("next/navigation", () => ({
  useRouter: () => ({
    push: routerPush,
  }),
}));

vi.mock("next-themes", () => ({
  useTheme: () => ({
    theme: "light",
    resolvedTheme: "light",
    setTheme: vi.fn(),
  }),
}));

vi.mock("@/components/search/command-palette", () => ({
  CommandPalette: () => <div>Command Palette</div>,
}));

vi.mock("@/components/layout/locale-switcher", () => ({
  LocaleSwitcher: () => <div>Locale Switcher</div>,
}));

vi.mock("@/components/layout/notification-bell", () => ({
  NotificationBell: () => <div>Notifications</div>,
}));

vi.mock("@/components/timer/timer-badge", () => ({
  TimerBadge: () => <div>Timer</div>,
}));

vi.mock("@/lib/api", () => ({
  apiClient: {
    post: vi.fn(),
  },
}));

import { apiClient } from "@/lib/api";

describe("Header user menu", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    useAuthStore.setState({
      user: {
        id: "user_1",
        name: "Ada Lovelace",
        email: "ada@example.test",
        avatar_url: null,
      } as any,
      accessToken: "access-token",
      refreshToken: "refresh-token",
      isAuthenticated: true,
      isLoading: false,
    });
  });

  it("opens the dropdown and shows the profile link", async () => {
    render(
      <I18nProvider initialLocale="en">
        <Header />
      </I18nProvider>
    );

    fireEvent.click(screen.getByLabelText(/user menu/i));

    await waitFor(() => {
      expect(screen.getByRole("menuitem", { name: /my profile/i })).toBeVisible();
    });
  });

  it("logs out the user and redirects to login", async () => {
    (apiClient.post as any).mockResolvedValue({ data: null });

    render(
      <I18nProvider initialLocale="en">
        <Header />
      </I18nProvider>
    );

    fireEvent.click(screen.getByLabelText(/user menu/i));
    fireEvent.click(await screen.findByRole("menuitem", { name: /logout/i }));

    await waitFor(() => {
      expect(apiClient.post).toHaveBeenCalledWith("/auth/logout");
      expect(useAuthStore.getState().isAuthenticated).toBe(false);
      expect(routerPush).toHaveBeenCalledWith("/auth/login");
    });
  });
});
