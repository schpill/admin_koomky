import { test, expect } from "@playwright/test";

test.describe("Invoices CRUD", () => {
  test("invoice list page is reachable", async ({ page }) => {
    await page.goto("/invoices");

    await expect(page.getByRole("heading", { name: "Invoices" })).toBeVisible();
  });
});
