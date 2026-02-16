import { test, expect } from "@playwright/test";

test.describe("Invoice PDF", () => {
  test("invoice page exposes pdf preview section", async ({ page }) => {
    await page.goto("/invoices");

    await expect(page.getByText("PDF preview")).toBeVisible();
  });
});
