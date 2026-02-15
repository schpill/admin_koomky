import { useAuthStore } from "@/lib/stores/auth";

interface ApiOptions extends RequestInit {
  skipAuth?: boolean;
  params?: Record<string, any>;
}

interface ApiResponse<T> {
  status: string;
  message: string;
  data: T;
  meta?: Record<string, any>;
  links?: Record<string, string>;
}

class ApiError extends Error {
  status: number;
  data: any;

  constructor(message: string, status: number, data?: any) {
    super(message);
    this.name = "ApiError";
    this.status = status;
    this.data = data;
  }
}

async function refreshAccessToken(): Promise<boolean> {
  const refreshToken = useAuthStore.getState().refreshToken;

  if (!refreshToken) {
    return false;
  }

  try {
    const response = await fetch(
      `${process.env.NEXT_PUBLIC_API_URL || "http://localhost/api/v1"}/auth/refresh`,
      {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
          Authorization: `Bearer ${refreshToken}`,
        },
      },
    );

    if (!response.ok) {
      return false;
    }

    const json = await response.json();
    const { access_token, refresh_token } = json.data;
    useAuthStore.getState().setTokens(access_token, refresh_token);
    return true;
  } catch {
    return false;
  }
}

export async function api<T>(
  endpoint: string,
  options: ApiOptions = {},
): Promise<ApiResponse<T>> {
  const { skipAuth = false, params, ...fetchOptions } = options;
  const accessToken = useAuthStore.getState().accessToken;

  const headers: HeadersInit = {
    "Content-Type": "application/json",
    Accept: "application/json",
    ...options.headers,
  };

  if (!skipAuth && accessToken) {
    (headers as Record<string, string>)["Authorization"] =
      `Bearer ${accessToken}`;
  }

  const baseUrl = process.env.NEXT_PUBLIC_API_URL || "http://localhost/api/v1";

  let url = `${baseUrl}${endpoint}`;

  if (params && Object.keys(params).length > 0) {
    const searchParams = new URLSearchParams();
    Object.entries(params).forEach(([key, value]) => {
      if (value !== undefined && value !== null) {
        searchParams.append(key, String(value));
      }
    });
    const queryString = searchParams.toString();
    if (queryString) {
      url += (url.includes("?") ? "&" : "?") + queryString;
    }
  }

  let response = await fetch(url, {
    ...fetchOptions,
    headers,
  });

  // Handle 401 - try to refresh token
  if (response.status === 401 && !skipAuth) {
    const refreshed = await refreshAccessToken();

    if (refreshed) {
      const newAccessToken = useAuthStore.getState().accessToken;
      (headers as Record<string, string>)["Authorization"] =
        `Bearer ${newAccessToken}`;

      response = await fetch(url, {
        ...fetchOptions,
        headers,
      });
    } else {
      // Refresh failed - logout user
      useAuthStore.getState().logout();

      // Redirect to login if in browser
      if (
        typeof window !== "undefined" &&
        !window.location.pathname.startsWith("/auth")
      ) {
        window.location.href = "/auth/login";
      }

      throw new ApiError("Session expired", 401);
    }
  }

  if (!response.ok) {
    const errorData = await response.json().catch(() => ({}));
    throw new ApiError(
      errorData.message || `HTTP error! status: ${response.status}`,
      response.status,
      errorData,
    );
  }

  return response.json();
}

// Convenience methods
export const apiClient = {
  get: <T>(endpoint: string, options?: ApiOptions) =>
    api<T>(endpoint, { ...options, method: "GET" }),

  post: <T>(endpoint: string, body?: any, options?: ApiOptions) =>
    api<T>(endpoint, {
      ...options,
      method: "POST",
      body: body ? JSON.stringify(body) : undefined,
    }),

  put: <T>(endpoint: string, body?: any, options?: ApiOptions) =>
    api<T>(endpoint, {
      ...options,
      method: "PUT",
      body: body ? JSON.stringify(body) : undefined,
    }),

  patch: <T>(endpoint: string, body?: any, options?: ApiOptions) =>
    api<T>(endpoint, {
      ...options,
      method: "PATCH",
      body: body ? JSON.stringify(body) : undefined,
    }),

  delete: <T>(endpoint: string, options?: ApiOptions) =>
    api<T>(endpoint, { ...options, method: "DELETE" }),
};

export { ApiError };
