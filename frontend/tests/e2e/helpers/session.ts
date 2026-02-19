import { Page } from "@playwright/test";

export function portalResponse(data: unknown, message = "OK") {
  return JSON.stringify({ status: "Success", message, data });
}

const MOCK_USER = {
  id: "user_1",
  name: "Test User",
  email: "test@example.com",
};

const MOCK_CLIENT = {
  id: "cli_1",
  name: "Acme Client Corp",
};

async function setCookie(page: Page, name: string, value: string) {
  await page.context().addCookies([
    {
      name,
      value,
      domain: "localhost",
      path: "/",
    },
  ]);
}

export async function seedPortalSession(page: Page) {
  const session = {
    portal_token: "portal-token-for-e2e",
    expires_at: Date.now() + 60 * 60 * 1000,
    client: MOCK_CLIENT,
  };

  // We assume portal uses a different cookie or primarily localStorage
  await setCookie(page, "koomky-portal-token", session.portal_token);

  await page.addInitScript((session) => {
    window.localStorage.setItem(
      "koomky-portal-session",
      JSON.stringify(session)
    );
  }, session);

  await page.route("**/api/v1/portal/me", (route) =>
    route.fulfill({
      status: 200,
      contentType: "application/json",
      body: portalResponse({ client: MOCK_CLIENT }),
    })
  );
}

export async function seedAuthenticatedSession(page: Page) {
  const session = {
    access_token: "valid-token-for-e2e",
    refresh_token: "valid-refresh-token",
    expires_at: Date.now() + 60 * 60 * 1000,
    user: MOCK_USER,
  };

  // This is the critical part: set the cookie for the server-side middleware
  await setCookie(page, "koomky-access-token", session.access_token);

  // Set localStorage for client-side state hydration
  await page.addInitScript((session) => {
    window.localStorage.setItem("koomky-session", JSON.stringify(session));
  }, session);

  // Mock the initial session validation call
  await page.route("**/api/v1/user", (route) =>
    route.fulfill({
      status: 200,
      contentType: "application/json",
      body: JSON.stringify({ status: "Success", data: MOCK_USER }),
    })
  );
}

// Generic API mock to prevent unexpected network calls
export async function mockProtectedApi(page: Page) {
  await page.route("**/api/v1/**", async (route) => {
    if (route.request().url().includes("/api/v1/user")) {
      return route.continue(); // Don't override the specific user mock
    }
    await route.fulfill({
      status: 200,
      contentType: "application/json",
      body: JSON.stringify({ status: "Success", message: "OK", data: {} }),
    });
  });
}
