export interface PortalClient {
  id: string;
  name: string;
  email?: string | null;
}

export interface PortalSession {
  portal_token: string;
  expires_at: number;
  client: PortalClient;
}

interface PortalApiResponse<T> {
  status: string;
  message: string;
  data: T;
}

const portalSessionStorageKey = "koomky-portal-session";

function isBrowser(): boolean {
  return typeof window !== "undefined";
}

export function getPortalSession(): PortalSession | null {
  if (!isBrowser()) {
    return null;
  }

  const raw = window.localStorage.getItem(portalSessionStorageKey);
  if (!raw) {
    return null;
  }

  try {
    const parsed = JSON.parse(raw) as PortalSession;
    if (!parsed?.portal_token || !parsed?.expires_at || !parsed?.client?.id) {
      window.localStorage.removeItem(portalSessionStorageKey);
      return null;
    }

    if (Date.now() >= parsed.expires_at) {
      window.localStorage.removeItem(portalSessionStorageKey);
      return null;
    }

    return parsed;
  } catch {
    window.localStorage.removeItem(portalSessionStorageKey);
    return null;
  }
}

export function savePortalSession(input: {
  portal_token: string;
  expires_in?: number;
  client: PortalClient;
}): void {
  if (!isBrowser()) {
    return;
  }

  const expiresIn = Number(input.expires_in || 0);
  const expiresAt = Date.now() + Math.max(1, expiresIn) * 1000;

  const payload: PortalSession = {
    portal_token: input.portal_token,
    expires_at: expiresAt,
    client: input.client,
  };

  window.localStorage.setItem(portalSessionStorageKey, JSON.stringify(payload));
}

export function clearPortalSession(): void {
  if (!isBrowser()) {
    return;
  }

  window.localStorage.removeItem(portalSessionStorageKey);
}

function portalApiBaseUrl(): string {
  return process.env.NEXT_PUBLIC_API_URL || "http://localhost/api/v1";
}

export async function portalApi<T>(
  endpoint: string,
  init: RequestInit = {},
  options: { skipAuth?: boolean } = {}
): Promise<PortalApiResponse<T>> {
  const { skipAuth = false } = options;
  const headers: HeadersInit = {
    "Content-Type": "application/json",
    Accept: "application/json",
    ...(init.headers || {}),
  };

  if (!skipAuth) {
    const session = getPortalSession();
    if (!session?.portal_token) {
      throw new Error("Portal session required");
    }

    (headers as Record<string, string>).Authorization =
      `Bearer ${session.portal_token}`;
  }

  const response = await fetch(`${portalApiBaseUrl()}${endpoint}`, {
    ...init,
    headers,
  });

  const data = (await response.json().catch(() => ({}))) as PortalApiResponse<T>;

  if (!response.ok) {
    throw new Error(data?.message || `Portal API error (${response.status})`);
  }

  return data;
}

export const portalApiClient = {
  get: <T>(endpoint: string, options?: { skipAuth?: boolean }) =>
    portalApi<T>(endpoint, { method: "GET" }, options),

  post: <T>(
    endpoint: string,
    body?: Record<string, unknown>,
    options?: { skipAuth?: boolean }
  ) =>
    portalApi<T>(
      endpoint,
      {
        method: "POST",
        body: body ? JSON.stringify(body) : undefined,
      },
      options
    ),
};
