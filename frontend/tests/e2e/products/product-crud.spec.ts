import { expect, test } from "@playwright/test";
import { seedAuthenticatedSession } from "../helpers/session";

test.describe("Products CRUD", () => {
  test("catalog and new product pages are reachable", async ({ page }) => {
    await seedAuthenticatedSession(page);

    await page.route("**/api/v1/products/analytics", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          status: "Success",
          data: {
            top_products: [],
            total_revenue: 0,
            total_sales: 0,
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
          data: [],
          meta: {
            current_page: 1,
            last_page: 1,
            per_page: 15,
            total: 0,
          },
        }),
      });
    });

    await page.goto("/products");
    await expect(page.getByRole("heading", { name: "Catalogue" })).toBeVisible();

    await page.goto("/products/new");
    await expect(
      page.getByRole("heading", { name: "Nouveau Produit" })
    ).toBeVisible();
  });
});
