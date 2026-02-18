import { expect, test } from "@playwright/test";

function portalResponse(data: unknown, message = "OK") {
  return JSON.stringify({
    status: "Success",
    message,
    data,
  });
}

test.describe("Portal invoices", () => {
  test("view invoices list and detail", async ({ page }) => {
    await page.addInitScript(() => {
      window.localStorage.setItem(
        "koomky-portal-session",
        JSON.stringify({
          portal_token: "portal-token",
          expires_at: Date.now() + 60 * 60 * 1000,
          client: {
            id: "cli_1",
            name: "Acme Client",
            email: "client@acme.test",
          },
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
            recent_payments: [],
          }),
        });
      }

      if (path.endsWith("/portal/invoices")) {
        return route.fulfill({
          status: 200,
          contentType: "application/json",
          body: portalResponse({
            data: [
              {
                id: "inv_1",
                number: "INV-001",
                issue_date: "2026-02-01",
                due_date: "2026-02-15",
                status: "sent",
                total: 120,
                currency: "EUR",
              },
            ],
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
            status: "sent",
            issue_date: "2026-02-01",
            due_date: "2026-02-15",
            subtotal: 100,
            tax_amount: 20,
            total: 120,
            amount_paid: 0,
            balance_due: 120,
            currency: "EUR",
            line_items: [
              {
                description: "Design",
                quantity: 1,
                unit_price: 100,
                vat_rate: 20,
                total: 120,
              },
            ],
          }),
        });
      }

      if (path.endsWith("/portal/auth/logout")) {
        return route.fulfill({
          status: 200,
          contentType: "application/json",
          body: portalResponse(null),
        });
      }

      return route.fulfill({
        status: 200,
        contentType: "application/json",
        body: portalResponse({}),
      });
    });

    await page.goto("/portal/invoices");

    await expect(
      page.locator("main").getByText("Invoices", { exact: true })
    ).toBeVisible();
    await page.getByRole("link", { name: "INV-001" }).click();

    await expect(
      page.locator("main").getByText("INV-001", { exact: true })
    ).toBeVisible();
    await expect(page.getByRole("link", { name: "Pay now" })).toBeVisible();
  });
});
