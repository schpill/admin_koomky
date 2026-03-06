import { expect, test } from "@playwright/test";
import { seedAuthenticatedSession } from "../helpers/session";

test.describe("Campaign A/B flow", () => {
  test("configure AB campaign in create flow", async ({ page }) => {
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

    await page.fill("#campaign-name", "AB Launch");
    await page.waitForTimeout(500);
    await stepButtons
      .nth(1)
      .evaluate((node: Element) => (node as HTMLButtonElement).click());

    await page.check('input[type="checkbox"]');

    await page.fill("#variant-subject-A", "Sujet A");
    await page.fill("#variant-content-A", "Contenu A");
    await page.fill("#variant-subject-B", "Sujet B");
    await page.fill("#variant-content-B", "Contenu B");

    await expect(page.getByText("Total split: 100%")).toBeVisible();

    await stepButtons
      .nth(2)
      .evaluate((node: Element) => (node as HTMLButtonElement).click());
    await expect(page.getByText("Campaign preview")).toBeVisible();
  });
});
