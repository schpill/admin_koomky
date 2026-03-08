import { describe, it, expect, beforeEach, vi } from "vitest";
import { useAuthStore } from "../../lib/stores/auth";

// Mock document.cookie
const mockCookies: Record<string, string> = {};
const cookieAssignments: string[] = [];
Object.defineProperty(document, "cookie", {
  get: () =>
    Object.entries(mockCookies)
      .map(([k, v]) => `${k}=${v}`)
      .join("; "),
  set: (v) => {
    cookieAssignments.push(v);
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
    cookieAssignments.length = 0;
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

  it("stores a persistent refresh cookie when remember me is enabled", () => {
    const user = { id: "1", name: "John", email: "john@example.com" };

    useAuthStore.getState().setAuth(user, "access-token", "refresh-token", {
      rememberMe: true,
    });

    expect(mockCookies["koomky-refresh-token"]).toBe("refresh-token");
    expect(useAuthStore.getState().rememberMe).toBe(true);
    expect(
      cookieAssignments.some(
        (value) =>
          value.includes("koomky-refresh-token=refresh-token") &&
          value.includes("max-age=2592000")
      )
    ).toBe(true);
  });

  it("stores a session refresh cookie when remember me is disabled", () => {
    const user = { id: "1", name: "John", email: "john@example.com" };

    useAuthStore.getState().setAuth(user, "access-token", "refresh-token", {
      rememberMe: false,
    });

    expect(mockCookies["koomky-refresh-token"]).toBe("refresh-token");
    expect(useAuthStore.getState().rememberMe).toBe(false);
    expect(
      cookieAssignments.some(
        (value) =>
          value.includes("koomky-refresh-token=refresh-token") &&
          !value.includes("max-age=")
      )
    ).toBe(true);
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
