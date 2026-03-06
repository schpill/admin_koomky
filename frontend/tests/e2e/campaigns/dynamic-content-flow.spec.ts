import { test, expect } from "@playwright/test";
import { mockProtectedApi, seedAuthenticatedSession } from "../helpers/session";

test.describe("Campaign dynamic content", () => {
  test("insert a conditional content block from the editor", async ({
    page,
  }) => {
    await seedAuthenticatedSession(page);
    await mockProtectedApi(page);

    await page.goto("/campaigns/create");
    await page.fill("#campaign-name", "Dynamic launch");
    await nextWizardStep(page);
    await page.waitForSelector("#dynamic-attribute");

    await page.selectOption("#dynamic-attribute", "client.industry");
    await page.selectOption("#dynamic-operator", "==");
    await page.fill("#dynamic-value", "Wedding Planner");
    await page.fill("#dynamic-truthy", "VIP content");
    await page.fill("#dynamic-falsy", "Standard content");
    await page.click('button:has-text("Insert block")');

    await expect(page.getByText(/Variables disponibles/i)).toBeVisible();
  });
});

async function nextWizardStep(page: import("@playwright/test").Page) {
  await page.getByTestId("campaign-wizard-next").click();
}
