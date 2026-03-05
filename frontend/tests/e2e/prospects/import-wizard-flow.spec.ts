import { test, expect } from "@playwright/test";

test.describe("prospect import wizard", () => {
  test("renders import page", async ({ page }) => {
    await page.goto("/prospects/import");
    await expect(page.getByText("Import de prospects")).toBeVisible();
  });
});
