import { test, expect } from "@playwright/test";
import { mockProtectedApi, seedAuthenticatedSession } from "../helpers/session";

test.describe("Campaign STO", () => {
  test("configure send time optimization during campaign creation", async ({
    page,
  }) => {
    await seedAuthenticatedSession(page);
    await mockProtectedApi(page);

    await page.goto("/campaigns/create");

    await page.fill("#campaign-name", "STO launch");
    await nextWizardStep(page);
    await page.waitForSelector("#campaign-subject");

    await page.fill("#campaign-subject", "Hello {{first_name}}");
    await page.fill("#email-content", "Welcome {{first_name}}");
    await nextWizardStep(page);
    await nextWizardStep(page);
    await page.waitForSelector("#sto-window-hours");

    await page.getByLabel("Enable send time optimization").check();
    await page.fill("#sto-window-hours", "12");

    await expect(page.getByText(/contacts currently have a known optimal hour/i))
      .toBeVisible();
  });
});

async function nextWizardStep(page: import("@playwright/test").Page) {
  await page.getByTestId("campaign-wizard-next").click();
}
