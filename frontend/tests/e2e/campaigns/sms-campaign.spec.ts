import { test, expect } from "@playwright/test";
import { mockProtectedApi, seedAuthenticatedSession } from "../helpers/session";

test.describe("SMS Campaign", () => {
  test("create sms campaign via wizard", async ({ page }) => {
    await seedAuthenticatedSession(page);
    await mockProtectedApi(page);

    await page.goto("/campaigns/create");

    await page.fill("#campaign-name", "SMS promo");
    await page.selectOption("#campaign-type", "sms");
    await page.click('button:has-text("Next")');

    await page.fill("#sms-content", "Hi {{first_name}}, this is your promo.");
    await page.click('button:has-text("Next")');

    await expect(page.getByText("SMS preview")).toBeVisible();

    await page.click('button:has-text("Next")');
    await page.click('button:has-text("Save draft")');

    await expect(page).toHaveURL(/\/campaigns\/camp_1/);
  });
});
