import { expect, test } from "@playwright/test";

function portalResponse(data: unknown, message = "OK") {
  return JSON.stringify({ status: "Success", message, data });
}

test.describe("Portal payments", () => {
  test("payment page is reachable", async ({ page }) => {
    await page.addInitScript(() => {
      window.localStorage.setItem(
        "koomky-portal-session",
        JSON.stringify({
          portal_token: "portal-token",
          expires_at: Date.now() + 60 * 60 * 1000,
          client: { id: "cli_1", name: "Acme Client" },
        })
      );
    });

    await page.route("**/api/v1/portal/**", async (route) => {
      const url = new URL(route.request().url());
      const path = url.pathname;

      if (path.endsWith("/portal/dashboard")) {
        return route.fulfill({
          status: 200,
          contentType: "application/json",
          body: portalResponse({
            client: { id: "cli_1", name: "Acme Client" },
            branding: { custom_logo: null, custom_color: "#2459ff" },
            outstanding_invoices: { count: 1, total: 120, currency: "EUR" },
            recent_invoices: [],
            recent_quotes: [],
          }),
        });
      }

      if (path.endsWith("/portal/invoices/inv_1")) {
        return route.fulfill({
          status: 200,
          contentType: "application/json",
          body: portalResponse({
            id: "inv_1",
            number: "INV-001",
            due_date: "2026-02-15",
            total: 120,
            balance_due: 120,
            currency: "EUR",
          }),
        });
      }

      if (path.endsWith("/portal/invoices/inv_1/pay")) {
        return route.fulfill({
          status: 201,
          contentType: "application/json",
          body: portalResponse({
            id: "pi_1",
            amount: 120,
            currency: "EUR",
            client_secret: "pi_secret_123",
          }),
        });
      }

      if (path.endsWith("/portal/invoices/inv_1/payment-status")) {
        return route.fulfill({
          status: 200,
          contentType: "application/json",
          body: portalResponse({ status: "succeeded" }),
        });
      }

      return route.fulfill({
        status: 200,
        contentType: "application/json",
        body: portalResponse({}),
      });
    });

    await page.goto("/portal/invoices/inv_1/pay");

    await expect(
      page.locator("main").getByText("Pay invoice INV-001", { exact: true })
    ).toBeVisible();

    await expect(
      page.getByText("Stripe publishable key is missing")
    ).toBeVisible();
  });
});
