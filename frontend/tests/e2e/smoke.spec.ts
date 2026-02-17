import { test, expect } from "@playwright/test";

test("smoke test - homepage loads", async ({ page }) => {
  await page.goto("/auth/login");
  await expect(page).toHaveTitle(/Koomky/);
});
