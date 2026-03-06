import { expect, test } from "@playwright/test";
import { seedAuthenticatedSession } from "../helpers/session";

test.describe("Campaign test send multi email", () => {
  test("adds multiple emails in test send modal", async ({ page }) => {
    await seedAuthenticatedSession(page);
    await page.route("**/api/v1/**", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({ status: "Success", message: "OK", data: {} }),
      });
    });
    await page.route("**/api/v1/campaign-templates**", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          status: "Success",
          message: "OK",
          data: [],
        }),
      });
    });
    await page.route("**/api/v1/segments**", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          status: "Success",
          message: "OK",
          data: [],
        }),
      });
    });
    await page.route("**/api/v1/campaigns", async (route) => {
      if (route.request().method() !== "POST") {
        await route.fulfill({
          status: 200,
          contentType: "application/json",
          body: JSON.stringify({ status: "Success", message: "OK", data: {} }),
        });
        return;
      }

      await route.fulfill({
        status: 201,
        contentType: "application/json",
        body: JSON.stringify({
          status: "Success",
          message: "OK",
          data: { id: "camp_1" },
        }),
      });
    });
    await page.goto("/campaigns/create");
    const stepButtons = page.locator("div.grid.gap-2.sm\\:grid-cols-4 button");

    await page.fill("#campaign-name", "Test send");
    await page.waitForTimeout(500);
    await stepButtons
      .nth(1)
      .evaluate((node: Element) => (node as HTMLButtonElement).click());

    await page.fill("#campaign-subject", "Hello {{first_name}}");
    await page.fill("#email-content", "Hi {{first_name}}");
    await stepButtons
      .nth(2)
      .evaluate((node: Element) => (node as HTMLButtonElement).click());

    await page.click('button:has-text("Send test")');
    await page.fill("#test-destination", "a@example.test");
    await page.click('button:has-text("Ajouter")');
    await page.fill("#test-destination", "b@example.test");
    await page.click('button:has-text("Ajouter")');

    await expect(page.getByText("a@example.test ×")).toBeVisible();
    await expect(page.getByText("b@example.test ×")).toBeVisible();
  });
});
