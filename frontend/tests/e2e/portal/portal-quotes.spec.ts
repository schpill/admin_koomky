import { expect, test } from "@playwright/test";

function portalResponse(data: unknown, message = "OK") {
  return JSON.stringify({ status: "Success", message, data });
}

test.describe("Portal quotes", () => {
  test("view and respond to quote", async ({ page }) => {
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
      const method = route.request().method();

      if (path.endsWith("/portal/dashboard")) {
        return route.fulfill({
          status: 200,
          contentType: "application/json",
          body: portalResponse({
            client: { id: "cli_1", name: "Acme Client" },
            branding: { custom_logo: null, custom_color: "#2459ff" },
            outstanding_invoices: { count: 0, total: 0, currency: "EUR" },
            recent_invoices: [],
            recent_quotes: [],
          }),
        });
      }

      if (path.endsWith("/portal/quotes")) {
        return route.fulfill({
          status: 200,
          contentType: "application/json",
          body: portalResponse({
            data: [
              {
                id: "quo_1",
                number: "QUO-001",
                issue_date: "2026-02-01",
                valid_until: "2026-02-20",
                status: "sent",
                total: 300,
                currency: "EUR",
              },
            ],
          }),
        });
      }

      if (path.endsWith("/portal/quotes/quo_1") && method === "GET") {
        return route.fulfill({
          status: 200,
          contentType: "application/json",
          body: portalResponse({
            id: "quo_1",
            number: "QUO-001",
            status: "sent",
            issue_date: "2026-02-01",
            valid_until: "2026-02-20",
            subtotal: 250,
            tax_amount: 50,
            total: 300,
            currency: "EUR",
            line_items: [
              {
                description: "Discovery",
                quantity: 1,
                unit_price: 250,
                vat_rate: 20,
                total: 300,
              },
            ],
          }),
        });
      }

      if (path.endsWith("/portal/quotes/quo_1/accept")) {
        return route.fulfill({
          status: 200,
          contentType: "application/json",
          body: portalResponse({ id: "quo_1", status: "accepted" }),
        });
      }

      if (path.endsWith("/portal/quotes/quo_1/reject")) {
        return route.fulfill({
          status: 200,
          contentType: "application/json",
          body: portalResponse({ id: "quo_1", status: "rejected" }),
        });
      }

      return route.fulfill({
        status: 200,
        contentType: "application/json",
        body: portalResponse({}),
      });
    });

    await page.goto("/portal/quotes");

    await expect(
      page.locator("main").getByText("Quotes", { exact: true })
    ).toBeVisible();

    await page.getByRole("link", { name: "QUO-001" }).click();

    await expect(
      page.locator("main").getByText("QUO-001", { exact: true })
    ).toBeVisible();
    await expect(
      page.getByRole("button", { name: "Accept quote" })
    ).toBeVisible();
  });
});
