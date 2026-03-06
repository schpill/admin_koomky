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
    const url = route.request().url();
    const method = route.request().method();

    if (url.includes("/api/v1/user")) {
      return route.continue(); // Don't override the specific user mock
    }

    if (url.includes("/api/v1/campaign-templates")) {
      return route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({ status: "Success", message: "OK", data: [] }),
      });
    }

    if (url.includes("/api/v1/segments")) {
      return route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          status: "Success",
          message: "OK",
          data: [],
          current_page: 1,
          last_page: 1,
          total: 0,
          per_page: 15,
        }),
      });
    }

    if (url.includes("/api/v1/campaigns") && method === "POST") {
      return route.fulfill({
        status: 201,
        contentType: "application/json",
        body: JSON.stringify({
          status: "Success",
          message: "OK",
          data: {
            id: "camp_1",
            name: "Mock campaign",
            type: "email",
            status: "draft",
            subject: "Subject",
            content: "Body",
            recipients: [],
            variants: [],
          },
        }),
      });
    }

    if (url.match(/\/api\/v1\/campaigns\/[^/]+$/) && method === "GET") {
      return route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          status: "Success",
          message: "OK",
          data: {
            id: "camp_1",
            name: "Mock campaign",
            type: "email",
            status: "draft",
            subject: "Subject",
            content: "Body",
            recipients: [],
            variants: [],
            use_sto: true,
            sto_window_hours: 24,
          },
        }),
      });
    }

    if (url.includes("/api/v1/campaigns/") && url.includes("/analytics")) {
      return route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          status: "Success",
          message: "OK",
          data: {
            campaign_id: "camp_1",
            campaign_name: "Mock campaign",
            total_recipients: 0,
            open_rate: 0,
            click_rate: 0,
            time_series: [],
            ab_variants: [],
          },
        }),
      });
    }

    if (url.includes("/api/v1/campaigns/") && url.includes("/links/export")) {
      return route.fulfill({
        status: 200,
        contentType: "text/csv",
        body: "url,total_clicks,unique_clicks,click_rate\nhttps://example.com/a,2,2,50\n",
      });
    }

    if (url.includes("/api/v1/campaigns/") && url.includes("/links")) {
      return route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          status: "Success",
          message: "OK",
          data: [
            {
              url: "https://example.com/a",
              total_clicks: 2,
              unique_clicks: 2,
              click_rate: 50,
            },
          ],
        }),
      });
    }

    if (url.includes("/api/v1/warmup-plans")) {
      const method = route.request().method();

      if (method === "POST" || method === "PUT" || method === "PATCH") {
        return route.fulfill({
          status: 200,
          contentType: "application/json",
          body: JSON.stringify({
            status: "Success",
            message: "OK",
            data: {
              id: "warm_1",
              name: "IP warm-up",
              status:
                method === "PATCH" && url.endsWith("/pause")
                  ? "paused"
                  : "active",
              daily_volume_start: 25,
              daily_volume_max: 500,
              increment_percent: 30,
              current_day: 2,
              current_daily_limit: 42,
            },
          }),
        });
      }

      if (method === "DELETE") {
        return route.fulfill({
          status: 200,
          contentType: "application/json",
          body: JSON.stringify({
            status: "Success",
            message: "OK",
            data: null,
          }),
        });
      }

      return route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          status: "Success",
          message: "OK",
          data: [
            {
              id: "warm_1",
              name: "IP warm-up",
              status: "active",
              daily_volume_start: 25,
              daily_volume_max: 500,
              increment_percent: 30,
              current_day: 2,
              current_daily_limit: 42,
            },
          ],
        }),
      });
    }

    if (url.includes("/api/v1/dashboard")) {
      return route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          status: "Success",
          message: "OK",
          data: {
            total_clients: 0,
            active_projects: 0,
            pending_invoices_amount: 0,
            recent_activities: [],
            revenue_month: 0,
            revenue_quarter: 0,
            revenue_year: 0,
            pending_invoices_count: 0,
            overdue_invoices_count: 0,
            revenue_trend: [],
            upcoming_deadlines: [],
            hot_contacts_count: 0,
            warmup_widget: {
              plan_id: "warm_1",
              name: "IP warm-up",
              current_day: 2,
              current_daily_limit: 42,
              sent_today: 12,
              status: "active",
            },
          },
        }),
      });
    }

    await route.fulfill({
      status: 200,
      contentType: "application/json",
      body: JSON.stringify({ status: "Success", message: "OK", data: {} }),
    });
  });
}
