import { useAuthStore } from "@/lib/stores/auth";

interface ApiOptions extends RequestInit {
  skipAuth?: boolean;
  params?: Record<string, any>;
  responseType?: "json" | "blob" | "text" | "arraybuffer";
}

interface ApiResponse<T> {
  status: string;
  message: string;
  data: T;
  meta?: Record<string, any>;
  links?: Record<string, string>;
  headers?: Headers;
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
      `${process.env.NEXT_PUBLIC_API_URL || "/api/v1"}/auth/refresh`,
      {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
          Authorization: `Bearer ${refreshToken}`,
        },
      }
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
  options: ApiOptions = {}
): Promise<ApiResponse<T>> {
  const { skipAuth = false, params, ...fetchOptions } = options;
  const accessToken = useAuthStore.getState().accessToken;

  const headers: Record<string, string> = {
    ...((options.headers as Record<string, string>) || {}),
  };

  // Only set default Content-Type if not already set and not FormData
  if (!headers["Content-Type"] && !(options.body instanceof FormData)) {
    headers["Content-Type"] = "application/json";
  }
  
  if (!headers["Accept"]) {
    headers["Accept"] = "application/json";
  }

  if (!skipAuth && accessToken) {
    headers["Authorization"] = `Bearer ${accessToken}`;
  }

  const baseUrl = process.env.NEXT_PUBLIC_API_URL || "/api/v1";

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
      errorData
    );
  }

  const responseType = options.responseType || "json";
  let data: any;

  if (responseType === "blob") {
    data = await response.blob();
  } else if (responseType === "text") {
    data = await response.text();
  } else if (responseType === "arraybuffer") {
    data = await response.arrayBuffer();
  } else {
    data = await response.json();
  }

  // If it's not JSON, we wrap it in the expected ApiResponse structure if needed,
  // but usually for blobs we just want the data.
  // Looking at existing stores, they expect response.data to be the actual content.
  if (responseType !== "json") {
    return {
      status: "success",
      message: "",
      data: data as T,
      headers: response.headers,
    };
  }

  return data;
}

// Convenience methods
export const apiClient = {
  get: <T>(endpoint: string, options?: ApiOptions) =>
    api<T>(endpoint, { ...options, method: "GET" }),

  post: <T>(endpoint: string, body?: any, options?: ApiOptions) =>
    api<T>(endpoint, {
      ...options,
      method: "POST",
      body: body instanceof FormData ? body : body ? JSON.stringify(body) : undefined,
    }),

  put: <T>(endpoint: string, body?: any, options?: ApiOptions) =>
    api<T>(endpoint, {
      ...options,
      method: "PUT",
      body: body instanceof FormData ? body : body ? JSON.stringify(body) : undefined,
    }),

  patch: <T>(endpoint: string, body?: any, options?: ApiOptions) =>
    api<T>(endpoint, {
      ...options,
      method: "PATCH",
      body: body instanceof FormData ? body : body ? JSON.stringify(body) : undefined,
    }),

  delete: <T>(endpoint: string, options?: ApiOptions) =>
    api<T>(endpoint, { ...options, method: "DELETE" }),
};

export { ApiError };
