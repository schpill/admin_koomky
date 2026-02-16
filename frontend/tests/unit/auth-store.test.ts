import { describe, it, expect, beforeEach, vi } from "vitest";
import { useAuthStore } from "../../lib/stores/auth";

// Mock document.cookie
const mockCookies: Record<string, string> = {};
Object.defineProperty(document, "cookie", {
  get: () =>
    Object.entries(mockCookies)
      .map(([k, v]) => `${k}=${v}`)
      .join("; "),
  set: (v) => {
    const [pair] = v.split(";");
    const [key, value] = pair.split("=");
    mockCookies[key.trim()] = value;
  },
  configurable: true,
});

describe("useAuthStore", () => {
  beforeEach(() => {
    // Reset store
    useAuthStore.setState({
      user: null,
      accessToken: null,
      refreshToken: null,
      isAuthenticated: false,
      isLoading: false,
    });
    // Reset cookies
    Object.keys(mockCookies).forEach((key) => delete mockCookies[key]);
  });

  it("should have initial state", () => {
    const state = useAuthStore.getState();
    expect(state.user).toBeNull();
    expect(state.isAuthenticated).toBe(false);
  });

  it("should set auth and cookies", () => {
    const user = { id: "1", name: "John", email: "john@example.com" };
    const accessToken = "access-token";
    const refreshToken = "refresh-token";

    useAuthStore.getState().setAuth(user, accessToken, refreshToken);

    const state = useAuthStore.getState();
    expect(state.user).toEqual(user);
    expect(state.accessToken).toBe(accessToken);
    expect(state.isAuthenticated).toBe(true);

    // Check cookies
    expect(document.cookie).toContain("koomky-access-token=access-token");
    expect(document.cookie).toContain("koomky-refresh-token=refresh-token");
  });

  it("should clear auth and cookies on logout", () => {
    const user = { id: "1", name: "John", email: "john@example.com" };
    useAuthStore.getState().setAuth(user, "at", "rt");

    useAuthStore.getState().logout();

    const state = useAuthStore.getState();
    expect(state.user).toBeNull();
    expect(state.isAuthenticated).toBe(false);

    // In our mock, document.cookie won't automatically clear based on expires,
    // but the logout function sets them to empty.
    expect(mockCookies["koomky-access-token"]).toBe("");
  });
});
