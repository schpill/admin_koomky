import { expect, test } from "@playwright/test";
import { seedAuthenticatedSession } from "../helpers/session";

test.describe("Product sale tracking", () => {
  test("catalog renders tracked product card", async ({ page }) => {
    await seedAuthenticatedSession(page);

    await page.route("**/api/v1/products/analytics", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          status: "Success",
          data: {
            top_products: [
              { id: "prod_1", name: "Audit API", revenue: 900, sales_count: 3 },
            ],
            total_revenue: 900,
            total_sales: 3,
          },
        }),
      });
    });

    await page.route("**/api/v1/products?**", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          status: "Success",
          data: [
            {
              id: "prod_1",
              name: "Audit API",
              slug: "audit-api",
              type: "service",
              price: 300,
              price_type: "fixed",
              currency_code: "EUR",
              vat_rate: 20,
              is_active: true,
              created_at: "2026-02-01T00:00:00Z",
              updated_at: "2026-02-01T00:00:00Z",
            },
          ],
          meta: {
            current_page: 1,
            last_page: 1,
            per_page: 15,
            total: 1,
          },
        }),
      });
    });

    await page.goto("/products");

    await expect(page.getByText("Audit API")).toBeVisible();
    await expect(page.getByText("300,00 €")).toBeVisible();
    await expect(page.getByRole("link", { name: "Campagne IA" })).toBeVisible();
  });
});
