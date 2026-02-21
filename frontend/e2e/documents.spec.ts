import { test, expect } from "@playwright/test";

test.describe("Documents Library", () => {
  test.beforeEach(async ({ page, context }) => {
    // Set mock cookies for authentication
    await context.addCookies([
      {
        name: "koomky-access-token",
        value: "mock-token",
        domain: "localhost",
        path: "/",
      },
    ]);

    // Mock initial stats and documents list
    await page.route("**/api/v1/documents/stats", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          data: {
            total_count: 0,
            total_size_bytes: 0,
            quota_bytes: 536870912,
            usage_percentage: 0,
            by_type: []
          }
        }),
      });
    });

    await page.route("**/api/v1/documents*", async (route) => {
      if (route.request().method() === 'GET') {
        await route.fulfill({
          status: 200,
          contentType: "application/json",
          body: JSON.stringify({
            data: [],
            current_page: 1,
            last_page: 1,
            total: 0,
            per_page: 24
          }),
        });
      }
    });

    await page.route("**/api/v1/clients", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({ data: [] }),
      });
    });
  });

  test("should display the documents library with empty state", async ({ page }) => {
    await page.goto("/documents");

    // Check header
    await expect(page.locator("h1")).toContainText(/Documents/i);
    
    // Check empty state
    await expect(page.getByText(/Aucun document trouvé/i)).toBeVisible();
    
    // Check if upload button is present
    await expect(page.getByRole("button", { name: /Importer un document/i })).toBeVisible();
  });
});
