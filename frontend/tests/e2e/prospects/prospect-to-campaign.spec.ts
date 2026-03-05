import { test, expect } from "@playwright/test";

test.describe("prospect to campaign", () => {
  test("renders prospects page", async ({ page }) => {
    await page.goto("/prospects");
    await expect(page.getByText("Prospects")).toBeVisible();
  });
});
