import { beforeEach, describe, expect, it, vi } from "vitest";
import { api, apiClient, ApiError } from "../../lib/api";
import { useAuthStore } from "../../lib/stores/auth";

interface MockResponse {
  ok: boolean;
  status: number;
  json: () => Promise<any>;
}

function makeResponse(status: number, body: any): MockResponse {
  return {
    ok: status >= 200 && status < 300,
    status,
    json: async () => body,
  };
}

describe("api", () => {
  const fetchMock = vi.fn();

  beforeEach(() => {
    vi.clearAllMocks();
    (global as unknown as { fetch: typeof fetch }).fetch = fetchMock;

    useAuthStore.setState({
      user: null,
      accessToken: null,
      refreshToken: null,
      isAuthenticated: false,
      isLoading: false,
    });

    window.history.pushState({}, "", "/auth/login");
  });

  it("serializes params and attaches authorization header", async () => {
    useAuthStore.setState({ accessToken: "access-token" });
    fetchMock.mockResolvedValue(
      makeResponse(200, { status: "Success", message: "ok", data: [] })
    );

    await api("/clients?existing=1", {
      method: "GET",
      params: {
        q: "john",
        page: 2,
        ignoredNull: null,
        ignoredUndefined: undefined,
      },
    });

    expect(fetchMock).toHaveBeenCalledTimes(1);
    const [url, options] = fetchMock.mock.calls[0];
    expect(url).toContain("/clients?existing=1&q=john&page=2");
    expect(options.headers.Authorization).toBe("Bearer access-token");
  });

  it("does not attach auth header when skipAuth is true", async () => {
    useAuthStore.setState({ accessToken: "access-token" });
    fetchMock.mockResolvedValue(
      makeResponse(200, { status: "Success", message: "ok", data: {} })
    );

    await api("/health", { method: "GET", skipAuth: true });

    const [, options] = fetchMock.mock.calls[0];
    expect(options.headers.Authorization).toBeUndefined();
  });

  it("refreshes token and retries on 401", async () => {
    useAuthStore.setState({
      accessToken: "old-access",
      refreshToken: "refresh-token",
      isAuthenticated: true,
    });

    fetchMock
      .mockResolvedValueOnce(makeResponse(401, { message: "expired" }))
      .mockResolvedValueOnce(
        makeResponse(200, {
          data: {
            access_token: "new-access",
            refresh_token: "new-refresh",
          },
        })
      )
      .mockResolvedValueOnce(
        makeResponse(200, {
          status: "Success",
          message: "ok",
          data: { id: "1" },
        })
      );

    const response = await api<{ id: string }>("/clients", { method: "GET" });

    expect(response.data.id).toBe("1");
    expect(fetchMock).toHaveBeenCalledTimes(3);

    const [refreshUrl, refreshOptions] = fetchMock.mock.calls[1];
    expect(refreshUrl).toContain("/auth/refresh");
    expect(refreshOptions.headers.Authorization).toBe("Bearer refresh-token");

    const [, retryOptions] = fetchMock.mock.calls[2];
    expect(retryOptions.headers.Authorization).toBe("Bearer new-access");
  });

  it("logs out and throws ApiError when refresh is not possible", async () => {
    useAuthStore.setState({
      accessToken: "old-access",
      refreshToken: null,
      isAuthenticated: true,
      user: { id: "1", name: "John", email: "john@example.com" },
    });

    fetchMock.mockResolvedValue(makeResponse(401, { message: "expired" }));

    await expect(api("/clients", { method: "GET" })).rejects.toMatchObject({
      name: "ApiError",
      message: "Session expired",
      status: 401,
    });

    const state = useAuthStore.getState();
    expect(state.user).toBeNull();
    expect(state.accessToken).toBeNull();
    expect(state.refreshToken).toBeNull();
    expect(state.isAuthenticated).toBe(false);
  });

  it("throws ApiError with API message on non-OK response", async () => {
    fetchMock.mockResolvedValue(
      makeResponse(422, { message: "Invalid payload" })
    );

    await expect(api("/clients", { method: "POST" })).rejects.toMatchObject({
      name: "ApiError",
      message: "Invalid payload",
      status: 422,
    });
  });

  it("throws fallback ApiError message when response body is not JSON", async () => {
    fetchMock.mockResolvedValue({
      ok: false,
      status: 500,
      json: async () => {
        throw new Error("invalid json");
      },
    });

    await expect(api("/clients", { method: "GET" })).rejects.toMatchObject({
      name: "ApiError",
      message: "HTTP error! status: 500",
      status: 500,
    });
  });

  it("supports convenience methods", async () => {
    fetchMock.mockResolvedValue(
      makeResponse(200, { status: "Success", message: "ok", data: {} })
    );

    await apiClient.post("/clients", { name: "A" });
    await apiClient.put("/clients/1", { name: "B" });
    await apiClient.patch("/clients/1", { status: "active" });
    await apiClient.delete("/clients/1");

    expect(fetchMock.mock.calls[0][1].method).toBe("POST");
    expect(fetchMock.mock.calls[0][1].body).toBe(JSON.stringify({ name: "A" }));
    expect(fetchMock.mock.calls[1][1].method).toBe("PUT");
    expect(fetchMock.mock.calls[2][1].method).toBe("PATCH");
    expect(fetchMock.mock.calls[3][1].method).toBe("DELETE");
  });

  it("creates ApiError instances with exported class", () => {
    const error = new ApiError("boom", 418, { test: true });
    expect(error.name).toBe("ApiError");
    expect(error.status).toBe(418);
    expect(error.data).toEqual({ test: true });
  });
});
