import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { describe, expect, it, vi, beforeEach } from "vitest";
import { I18nProvider } from "@/components/providers/i18n-provider";
import LoginPage from "@/app/auth/login/page";

class ResizeObserverMock {
  observe() {}
  unobserve() {}
  disconnect() {}
}

globalThis.ResizeObserver = ResizeObserverMock as typeof ResizeObserver;

const { routerPush, setAuth, setTokens, post } = vi.hoisted(() => ({
  routerPush: vi.fn(),
  setAuth: vi.fn(),
  setTokens: vi.fn(),
  post: vi.fn(),
}));

vi.mock("next/navigation", () => ({
  useRouter: () => ({
    push: routerPush,
  }),
}));

vi.mock("@/lib/stores/auth", () => ({
  useAuthStore: (selector: (state: any) => unknown) =>
    selector({
      setAuth,
      setTokens,
    }),
}));

vi.mock("@/lib/api", () => ({
  apiClient: {
    post,
  },
}));

vi.mock("sonner", () => ({
  toast: {
    success: vi.fn(),
    error: vi.fn(),
    info: vi.fn(),
  },
}));

describe("Login remember me", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    post.mockResolvedValue({
      data: {
        user: { id: "user_1", name: "Ada", email: "ada@example.test" },
        access_token: "access-token",
        refresh_token: "refresh-token",
      },
    });
  });

  it("shows a checked remember me checkbox by default", () => {
    render(
      <I18nProvider initialLocale="fr">
        <LoginPage />
      </I18nProvider>
    );

    expect(
      screen.getByRole("checkbox", { name: "Rester connecté.e" })
    ).toHaveAttribute("data-state", "checked");
  });

  it("sends remember_me=false when the checkbox is unchecked", async () => {
    render(
      <I18nProvider initialLocale="en">
        <LoginPage />
      </I18nProvider>
    );

    fireEvent.click(screen.getByRole("checkbox", { name: "Stay signed in" }));
    fireEvent.change(screen.getByLabelText("Email"), {
      target: { value: "ada@example.test" },
    });
    fireEvent.change(screen.getByLabelText("Password"), {
      target: { value: "Password123!" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Sign in" }));

    await waitFor(() => {
      expect(post).toHaveBeenCalledWith(
        "/auth/login",
        {
          email: "ada@example.test",
          password: "Password123!",
          remember_me: false,
        },
        {
          skipAuth: true,
        }
      );
    });
  });
});
