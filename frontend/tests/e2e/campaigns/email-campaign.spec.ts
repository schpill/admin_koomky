import { test, expect } from "@playwright/test";
import { mockProtectedApi, seedAuthenticatedSession } from "../helpers/session";

test.describe("Email Campaign", () => {
  test("create email campaign via wizard", async ({ page }) => {
    await seedAuthenticatedSession(page);
    await mockProtectedApi(page);

    await page.goto("/campaigns/create");

    await page.fill("#campaign-name", "Spring launch");
    await page.getByTestId("campaign-wizard-next").click();

    await page.fill("#campaign-subject", "Hello {{first_name}}");
    await page.fill(
      "#email-content",
      "Welcome {{first_name}} from {{company}}"
    );
    await page.getByTestId("campaign-wizard-next").click();

    await expect(page.getByText("Campaign preview")).toBeVisible();

    await page.getByTestId("campaign-wizard-next").click();
    await page.click('button:has-text("Save draft")');

    await expect(page).toHaveURL(/\/campaigns\/camp_1/);
  });
});
